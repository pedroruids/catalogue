<?php

namespace App\Models;

use App\Enums\FieldType;
use Database\Factories\ProductTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name'])]
class ProductType extends Model
{
    /** @use HasFactory<ProductTypeFactory> */
    use HasFactory;

    /**
     * Campos que um tipo novo traz por omissão (o admin pode apagá-los).
     * Lista confirmada pelo Pedro em 2026-10-12 (D13).
     *
     * @var list<array{name: string, data_type: FieldType}>
     */
    public const DEFAULT_FIELDS = [
        ['name' => 'Material', 'data_type' => FieldType::Text],
        ['name' => 'Dimensões', 'data_type' => FieldType::Text],
        ['name' => 'Técnica de personalização', 'data_type' => FieldType::Text],
    ];

    /**
     * @return HasMany<Article, $this>
     */
    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    /**
     * @return HasMany<FieldDefinition, $this>
     */
    public function fieldDefinitions(): HasMany
    {
        return $this->hasMany(FieldDefinition::class)->orderBy('position')->orderBy('id');
    }

    /**
     * Valores a gravar num artigo deste tipo: só as chaves dos campos do tipo, com o tipo de dado certo
     * e sem valores vazios. É o que faz os valores de campos inexistentes no tipo "perderem-se".
     *
     * @param  array<string, mixed>|null  $values
     * @return array<string, mixed>
     */
    public function normalizeFieldValues(?array $values): array
    {
        $clean = [];

        foreach ($this->fieldDefinitions as $field) {
            $value = $values[$field->key] ?? null;

            $value = match ($field->data_type) {
                FieldType::Text => is_scalar($value) && trim((string) $value) !== '' ? trim((string) $value) : null,
                FieldType::Number => is_numeric($value) ? $value + 0 : null,
                FieldType::Boolean => $value === null ? null : filter_var($value, FILTER_VALIDATE_BOOLEAN),
                FieldType::Select => is_string($value) && in_array($value, $field->options ?? [], true) ? $value : null,
            };

            if ($value !== null) {
                $clean[$field->key] = $value;
            }
        }

        return $clean;
    }
}
