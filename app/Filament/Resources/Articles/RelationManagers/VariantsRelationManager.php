<?php

namespace App\Filament\Resources\Articles\RelationManagers;

use App\Models\Article;
use App\Models\ArticleVariant;
use App\Models\PriceTier;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class VariantsRelationManager extends RelationManager
{
    protected static string $relationship = 'variants';

    protected static ?string $title = 'Variantes';

    protected static ?string $modelLabel = 'variante';

    protected static ?string $pluralModelLabel = 'variantes';

    public function form(Schema $schema): Schema
    {
        /** @var Article $article */
        $article = $this->getOwnerRecord();
        $axes = $article->variant_axes ?? [];

        $selects = [];
        foreach ($axes as $index => $axis) {
            $select = Select::make($axis['name'])
                ->label($axis['name'])
                ->options(array_combine($axis['values'], $axis['values']))
                // As chaves de array numéricas ("38") viram int: guarda-se sempre string.
                ->dehydrateStateUsing(fn (mixed $state): string => (string) $state)
                ->required()
                ->native(false);

            // A combinação completa tem de ser única no artigo; a mensagem fica no primeiro eixo.
            if ($index === 0) {
                $select->rules([fn (?ArticleVariant $record, Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($article, $record, $get, $axes): void {
                    $chosen = [];
                    foreach ($axes as $axis) {
                        $chosen[$axis['name']] = (string) $get($axis['name']);
                    }
                    $this->failIfDuplicate($article, $record, $chosen, $fail);
                }]);
            }

            $selects[] = $select;
        }

        return $schema->components([
            Group::make($selects)->statePath('options')->columns(2)->columnSpanFull(),
            TextInput::make('reference')
                ->label('Referência')
                ->required()
                ->maxLength(100)
                ->unique(ignoreRecord: true),
            Select::make('article_image_id')
                ->label('Imagem')
                ->helperText('Opcional. Uma das imagens deste artigo.')
                ->options($article->images->values()->mapWithKeys(fn ($image, int $i): array => [
                    $image->id => 'Imagem '.($i + 1).($image->is_primary ? ' (principal)' : ''),
                ])->all())
                ->native(false),
            Toggle::make('is_active')
                ->label('Activa')
                ->default(true),
            Repeater::make('priceTiers')
                ->label('Escalões de preço')
                ->helperText('Quantidade mínima → preço unitário em euros. Sem escalões, a variante fica «sob consulta».')
                ->relationship()
                // Apagar e recriar: actualizar no lugar chocava com o índice único ao trocar quantidades (1↔2).
                ->saveRelationshipsUsing(function (Repeater $component, ?array $state): void {
                    $variant = $component->getRecord();

                    if (! $variant instanceof ArticleVariant) {
                        return;
                    }

                    DB::transaction(function () use ($variant, $state): void {
                        $variant->priceTiers()->delete();
                        $variant->priceTiers()->createMany(collect($state)->map(fn (array $tier): array => [
                            'min_quantity' => $tier['min_quantity'],
                            'unit_price_cents' => PriceTier::centsFromInput($tier['unit_price_cents']),
                        ])->values()->all());
                    });
                })
                ->defaultItems(0)
                ->addActionLabel('Acrescentar escalão')
                ->schema([
                    TextInput::make('min_quantity')
                        ->label('Quantidade mínima')
                        ->required()
                        ->integer()
                        ->minValue(1)
                        ->maxValue(PriceTier::MAX_MIN_QUANTITY)
                        ->distinct()
                        ->validationMessages(['distinct' => 'Já existe um escalão com esta quantidade mínima.']),
                    TextInput::make('unit_price_cents')
                        ->label('Preço unitário')
                        ->suffix('€')
                        ->required()
                        ->formatStateUsing(fn (mixed $state): ?string => $state === null ? null : PriceTier::inputFromCents((int) $state))
                        ->dehydrateStateUsing(fn (mixed $state): ?int => PriceTier::centsFromInput($state))
                        ->rules([fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                            $cents = PriceTier::centsFromInput($value);

                            if ($cents === null) {
                                $fail('Indique um preço em euros, com até 2 casas decimais (ex.: 1,25).');
                            } elseif ($cents === 0) {
                                $fail('O preço tem de ser superior a 0,00 €. Para «sob consulta», não defina escalões.');
                            } elseif ($cents > PriceTier::MAX_UNIT_PRICE_CENTS) {
                                $fail('O preço máximo é '.PriceTier::formatCents(PriceTier::MAX_UNIT_PRICE_CENTS).'.');
                            }
                        }]),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $chosen
     */
    private function failIfDuplicate(Article $article, ?ArticleVariant $record, array $chosen, Closure $fail): void
    {
        ksort($chosen);

        $duplicate = $article->variants()->get()->contains(function (ArticleVariant $variant) use ($record, $chosen): bool {
            $options = array_map('strval', $variant->options);
            ksort($options);

            return $variant->id !== $record?->id && $options === $chosen;
        });

        if ($duplicate) {
            $fail('Já existe uma variante com esta combinação.');
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('options')
                    ->label('Combinação')
                    ->state(fn (ArticleVariant $record): string => $record->optionsLabel()),
                TextColumn::make('reference')->label('Referência'),
                TextColumn::make('from_price')
                    ->label('Preço desde')
                    ->state(fn (ArticleVariant $record): string => ($cents = $record->fromPriceCents()) === null ? 'Sob consulta' : PriceTier::formatCents($cents)),
                ToggleColumn::make('is_active')->label('Activa'),
            ])
            ->reorderable('position')
            ->defaultSort('position')
            ->emptyStateDescription(fn (): ?string => ($this->getOwnerRecord()->variant_axes ?? []) === []
                ? 'Defina primeiro os eixos (ex.: Cor, Tamanho) no formulário do artigo.'
                : null)
            ->headerActions([
                CreateAction::make()
                    ->label('Nova variante')
                    ->createAnother(false)
                    ->disabled(fn (): bool => ($this->getOwnerRecord()->variant_axes ?? []) === []),
            ])
            ->modifyQueryUsing(fn ($query) => $query->with('priceTiers'))
            ->recordActions([
                Action::make('copyTiers')
                    ->label('Copiar escalões para as outras variantes')
                    ->icon(Heroicon::OutlinedDocumentDuplicate)
                    ->visible(fn (ArticleVariant $record): bool => $record->priceTiers->isNotEmpty() && $record->article->variants()->whereKeyNot($record->getKey())->exists())
                    ->requiresConfirmation()
                    ->modalDescription('Os escalões das outras variantes deste artigo serão substituídos pelos desta. Esta variante não é alterada.')
                    ->action(function (ArticleVariant $record): void {
                        $this->copyTiers($record);
                        Notification::make()->title('Escalões copiados')->success()->send();
                    }),
                EditAction::make()->label('Editar'),
                DeleteAction::make()->label('Apagar'),
            ]);
    }

    /** Substitui, em transacção, os escalões das outras variantes do artigo pelos de `$source`. */
    private function copyTiers(ArticleVariant $source): void
    {
        $tiers = $source->priceTiers()->get(['min_quantity', 'unit_price_cents'])->map->only(['min_quantity', 'unit_price_cents']);

        DB::transaction(function () use ($source, $tiers): void {
            foreach ($source->article->variants()->whereKeyNot($source->getKey())->get() as $other) {
                $other->priceTiers()->delete();
                $other->priceTiers()->createMany($tiers->all());
            }
        });
    }
}
