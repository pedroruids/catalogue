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
     * TODO: lista a confirmar com o Pedro/cliente — não vem no PRD.
     *
     * @var list<array{name: string, data_type: FieldType}>
     */
    public const DEFAULT_FIELDS = [
        ['name' => 'Material', 'data_type' => FieldType::Text],
        ['name' => 'Dimensões', 'data_type' => FieldType::Text],
        ['name' => 'Técnica de personalização', 'data_type' => FieldType::Text],
    ];

    /**
     * @return HasMany<FieldDefinition, $this>
     */
    public function fieldDefinitions(): HasMany
    {
        return $this->hasMany(FieldDefinition::class)->orderBy('position')->orderBy('id');
    }
}
