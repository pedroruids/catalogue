<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $article_id
 * @property string $original_path
 * @property string $thumb_path
 * @property string $screen_path
 * @property int $position
 * @property bool $is_primary
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Article $article
 * @property-read string $thumb_url
 * @property-read string $screen_url
 */
#[Fillable(['article_id', 'original_path', 'thumb_path', 'screen_path', 'position', 'is_primary'])]
class ArticleImage extends Model
{
    /** O original nunca é exposto: o modelo só dá URLs dos derivados. */
    protected $hidden = ['original_path'];

    protected static function booted(): void
    {
        static::deleted(function (ArticleImage $image): void {
            Storage::disk('local')->delete($image->original_path);
            Storage::disk('public')->delete([$image->thumb_path, $image->screen_path]);

            // Mantém exactamente uma principal enquanto houver imagens.
            if ($image->is_primary) {
                self::query()->where('article_id', $image->article_id)->orderBy('position')->orderBy('id')->first()
                    ?->makePrimary();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }

    /**
     * @return BelongsTo<Article, $this>
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function getThumbUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->thumb_path);
    }

    public function getScreenUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->screen_path);
    }

    /** Marca esta como principal e desmarca as restantes do artigo. */
    public function makePrimary(): void
    {
        DB::transaction(function (): void {
            // Serializa cliques concorrentes no mesmo artigo.
            Article::query()->whereKey($this->article_id)->lockForUpdate()->first();
            self::query()->where('article_id', $this->article_id)->where('id', '!=', $this->id)->update(['is_primary' => false]);
            self::query()->whereKey($this->id)->update(['is_primary' => true]);
            // O modelo pode estar desactualizado: fixa o estado em vez de depender do dirty-check do save().
            $this->setAttribute('is_primary', true)->syncOriginalAttribute('is_primary');
        });
    }
}
