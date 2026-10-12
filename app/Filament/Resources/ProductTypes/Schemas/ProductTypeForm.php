<?php

namespace App\Filament\Resources\ProductTypes\Schemas;

use App\Enums\FieldType;
use App\Models\FieldDefinition;
use App\Models\ProductType;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ProductTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nome do tipo')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Section::make('Campos')
                    ->description('Os campos que cada artigo deste tipo preenche. Arraste para mudar a ordem.')
                    ->schema([
                        Repeater::make('fieldDefinitions')
                            ->hiddenLabel()
                            ->relationship()
                            ->orderColumn('position')
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                            ->collapsible()
                            ->deleteAction(fn (Action $action): Action => $action
                                ->requiresConfirmation()
                                // Títulos/descrições abrem o modal sempre; só se mostra quando o campo tem valores.
                                ->modalHidden(fn (array $arguments): bool => self::affectedArticles($arguments) === 0)
                                ->modalHeading('Apagar campo com valores')
                                ->modalDescription(fn (array $arguments): string => self::affectedArticles($arguments) === 1
                                    ? 'Este campo tem valores em 1 artigo. Ao gravar, esse valor é apagado.'
                                    : 'Este campo tem valores em '.self::affectedArticles($arguments).' artigos. Ao gravar, esses valores são apagados.')
                                ->modalSubmitActionLabel('Apagar campo'))
                            ->addActionLabel('Adicionar campo')
                            // Só na criação: o admin vê os campos por omissão e pode apagá-los antes de gravar.
                            ->default(fn (): array => array_map(
                                fn (array $field): array => ['name' => $field['name'], 'data_type' => $field['data_type']->value, 'required' => false, 'options' => []],
                                ProductType::DEFAULT_FIELDS,
                            ))
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nome')
                                    ->required()
                                    ->maxLength(255)
                                    ->distinct()
                                    ->live(onBlur: true),
                                Select::make('data_type')
                                    ->label('Tipo de dado')
                                    ->options(FieldType::class)
                                    ->default(FieldType::Text)
                                    ->required()
                                    ->native(false)
                                    ->live()
                                    // Mudar o tipo de um campo existente invalidaria os valores já gravados.
                                    // Não se desidrata: o valor do cliente não conta, fica o da BD.
                                    ->disabled(fn (?FieldDefinition $record): bool => $record !== null && $record->exists)
                                    ->dehydrated(fn (?FieldDefinition $record): bool => $record === null || ! $record->exists),
                                TagsInput::make('options')
                                    ->label('Opções')
                                    ->placeholder('Escreva e carregue Enter')
                                    ->visible(fn (Get $get): bool => self::isSelect($get('data_type')))
                                    ->required(fn (Get $get): bool => self::isSelect($get('data_type')))
                                    ->columnSpanFull(),
                                Toggle::make('required')
                                    ->label('Obrigatório'),
                            ])
                            ->columns(3),
                    ]),
            ]);
    }

    /**
     * Artigos com valor no campo que o admin está a apagar (0 se o campo ainda não foi gravado).
     *
     * @param  array<string, mixed>  $arguments
     */
    private static function affectedArticles(array $arguments): int
    {
        $item = (string) ($arguments['item'] ?? '');

        if (! str_starts_with($item, 'record-')) {
            return 0;
        }

        $field = FieldDefinition::query()->find((int) substr($item, 7));

        return $field?->articlesWithValueCount() ?? 0;
    }

    private static function isSelect(mixed $type): bool
    {
        return ($type instanceof FieldType ? $type : FieldType::tryFrom((string) $type)) === FieldType::Select;
    }
}
