<?php

namespace App\Filament\Resources\Articles\Schemas;

use App\Enums\ArticleStatus;
use App\Enums\FieldType;
use App\Models\Article;
use App\Models\FieldDefinition;
use App\Models\ProductType;
use Closure;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ArticleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Artigo')
                    ->schema([
                        Select::make('product_type_id')
                            ->label('Tipo de produto')
                            ->relationship('productType', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(self::warnAboutLostValues(...)),
                        TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('reference')
                            ->label('Referência')
                            ->required()
                            ->maxLength(100)
                            ->unique(ignoreRecord: true),
                        Select::make('status')
                            ->label('Estado')
                            ->options(ArticleStatus::class)
                            ->default(ArticleStatus::Draft)
                            ->required()
                            ->native(false),
                        Textarea::make('description')
                            ->label('Descrição')
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Características')
                    ->description('Os campos definidos para o tipo de produto escolhido.')
                    ->visible(fn (Get $get): bool => filled($get('product_type_id')))
                    ->schema(fn (Get $get): array => self::fieldComponents($get('product_type_id')))
                    ->columns(2),
                Section::make('Variantes')
                    ->description('Os eixos em que o artigo varia (ex.: Cor, Tamanho) e os valores de cada um. As variantes criam-se depois, no separador «Variantes».')
                    ->schema([
                        Repeater::make('variant_axes')
                            ->hiddenLabel()
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                            ->collapsible()
                            ->addActionLabel('Adicionar eixo')
                            ->defaultItems(0)
                            ->rules([fn (?Article $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($record): void {
                                $problem = $record?->variantAxesProblem(is_array($value) ? $value : []);
                                if ($problem !== null) {
                                    $fail($problem);
                                }
                            }])
                            ->schema([
                                TextInput::make('name')
                                    ->label('Eixo')
                                    ->placeholder('Cor')
                                    ->required()
                                    ->maxLength(100)
                                    // O nome serve de caminho no formulário das variantes: sem pontos.
                                    ->notRegex('/\./')
                                    ->distinct(),
                                TagsInput::make('values')
                                    ->label('Valores')
                                    ->placeholder('Escreva e carregue Enter')
                                    ->required(),
                            ])
                            ->columns(2),
                    ]),
            ]);
    }

    /**
     * Um componente por campo do tipo; os valores vivem em `field_values.<chave>`.
     *
     * @return list<Field>
     */
    public static function fieldComponents(mixed $productTypeId): array
    {
        if (blank($productTypeId)) {
            return [];
        }

        $type = ProductType::query()->with('fieldDefinitions')->find((int) $productTypeId);

        if ($type === null) {
            return [];
        }

        return array_values($type->fieldDefinitions->map(self::component(...))->all());
    }

    private static function component(FieldDefinition $field): Field
    {
        $name = 'field_values.'.$field->key;

        $component = match ($field->data_type) {
            FieldType::Text => TextInput::make($name)->maxLength(65535),
            FieldType::Number => TextInput::make($name)->numeric(),
            FieldType::Boolean => Toggle::make($name),
            FieldType::Select => Select::make($name)->options(array_combine($field->options ?? [], $field->options ?? []))->native(false),
        };

        $component->label($field->name);

        // Sim/não nunca está "em falta": falso também é uma resposta.
        if ($field->required && $field->data_type !== FieldType::Boolean) {
            $component->required()->validationMessages(['required' => "O campo «{$field->name}» é obrigatório."]);
        }

        return $component;
    }

    /**
     * Ao mudar o tipo de um artigo, avisa quais os valores preenchidos que o tipo novo não tem.
     */
    private static function warnAboutLostValues(Get $get, mixed $state, mixed $old): void
    {
        if (blank($old) || blank($state) || (string) $old === (string) $state) {
            return;
        }

        $values = $get('field_values');
        $newKeys = FieldDefinition::query()->where('product_type_id', $state)->pluck('key')->all();

        $lost = FieldDefinition::query()
            ->where('product_type_id', $old)
            ->whereNotIn('key', $newKeys)
            ->get()
            ->filter(fn (FieldDefinition $f): bool => is_array($values) && ! in_array($values[$f->key] ?? null, [null, '', false, []], true))
            ->pluck('name');

        if ($lost->isEmpty()) {
            return;
        }

        Notification::make()
            ->warning()
            ->persistent()
            ->title('Valores que se vão perder')
            ->body('O tipo novo não tem os campos: '.$lost->implode(', ').'. Os valores preenchidos neles perdem-se ao gravar.')
            ->send();
    }
}
