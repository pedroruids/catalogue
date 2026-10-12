<?php

namespace App\Models;

use App\Enums\ArticleStatus;
use Database\Factories\ArticleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
 * @property-read Collection<int, ArticleImage> $images
 * @property-read ArticleImage|null $primaryImage
 */
#[Fillable(['product_type_id', 'name', 'reference', 'description', 'status', 'field_values'])]
class Article extends Model
{
    /** @use HasFactory<ArticleFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        // A FK em cascata apaga as linhas mas não os ficheiros: apagar pelo modelo.
        static::deleting(fn (Article $article) => $article->images->each->delete());
    }

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

    /**
     * @return HasMany<ArticleImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(ArticleImage::class)->orderBy('position')->orderBy('id');
    }

    /**
     * @return HasOne<ArticleImage, $this>
     */
    public function primaryImage(): HasOne
    {
        return $this->hasOne(ArticleImage::class)->where('is_primary', true);
    }
}
