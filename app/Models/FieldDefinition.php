<?php

namespace App\Models;

use App\Enums\FieldType;
use Database\Factories\FieldDefinitionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $product_type_id
 * @property string $key
 * @property string $name
 * @property FieldType $data_type
 * @property bool $required
 * @property list<string>|null $options
 * @property int $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ProductType $productType
 */
#[Fillable(['name', 'data_type', 'required', 'options', 'position'])]
class FieldDefinition extends Model
{
    /** @use HasFactory<FieldDefinitionFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (self $field): void {
            if (blank($field->key)) {
                $field->key = $field->makeUniqueKey();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data_type' => FieldType::class,
            'required' => 'boolean',
            'options' => 'array',
        ];
    }

    /**
     * @return BelongsTo<ProductType, $this>
     */
    public function productType(): BelongsTo
    {
        return $this->belongsTo(ProductType::class);
    }

    private function makeUniqueKey(): string
    {
        $base = Str::slug($this->name, '_') ?: 'campo';
        $key = $base;
        $n = 2;

        while (self::query()->where('product_type_id', $this->product_type_id)->where('key', $key)->exists()) {
            $key = $base.'_'.$n++;
        }

        return $key;
    }
}
