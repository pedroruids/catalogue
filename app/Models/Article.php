<?php

namespace App\Models;

use App\Enums\ArticleStatus;
use Database\Factories\ArticleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $product_type_id
 * @property string $name
 * @property string $reference
 * @property string|null $description
 * @property ArticleStatus $status
 * @property array<string, mixed>|null $field_values
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ProductType $productType
 */
#[Fillable(['product_type_id', 'name', 'reference', 'description', 'status', 'field_values'])]
class Article extends Model
{
    /** @use HasFactory<ArticleFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ArticleStatus::class,
            'field_values' => 'array',
        ];
    }

    /**
     * @return BelongsTo<ProductType, $this>
     */
    public function productType(): BelongsTo
    {
        return $this->belongsTo(ProductType::class);
    }
}
