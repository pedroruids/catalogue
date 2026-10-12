<?php

namespace App\Filament\Resources\Articles\RelationManagers;

use App\Models\Article;
use App\Models\ArticleVariant;
use Closure;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

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
                ->required()
                ->native(false);

            // A combinação completa tem de ser única no artigo; a mensagem fica no primeiro eixo.
            if ($index === 0) {
                $select->rules([fn (?ArticleVariant $record, Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($article, $record, $get, $axes): void {
                    $chosen = [];
                    foreach ($axes as $axis) {
                        $chosen[$axis['name']] = $get($axis['name']);
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
        ]);
    }

    /**
     * @param  array<string, mixed>  $chosen
     */
    private function failIfDuplicate(Article $article, ?ArticleVariant $record, array $chosen, Closure $fail): void
    {
        ksort($chosen);

        $duplicate = $article->variants()->get()->contains(function (ArticleVariant $variant) use ($record, $chosen): bool {
            $options = $variant->options;
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
            ->recordActions([
                EditAction::make()->label('Editar'),
                DeleteAction::make()->label('Apagar'),
            ]);
    }
}
