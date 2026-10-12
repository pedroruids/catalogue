<?php

namespace App\Models;

use Database\Factories\ArticleVariantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $article_id
 * @property array<string, string> $options
 * @property string $reference
 * @property int|null $article_image_id
 * @property bool $is_active
 * @property int $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Article $article
 * @property-read ArticleImage|null $image
 */
#[Fillable(['article_id', 'options', 'reference', 'article_image_id', 'is_active', 'position'])]
class ArticleVariant extends Model
{
    /** @use HasFactory<ArticleVariantFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'options' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Article, $this>
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /**
     * @return BelongsTo<ArticleImage, $this>
     */
    public function image(): BelongsTo
    {
        return $this->belongsTo(ArticleImage::class, 'article_image_id');
    }

    /** «Cor: Azul · Tamanho: M» */
    public function optionsLabel(): string
    {
        return collect($this->options)->map(fn (string $value, string $axis): string => "{$axis}: {$value}")->implode(' · ');
    }
}
