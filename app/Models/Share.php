<?php

namespace App\Models;

use App\Enums\ShareMode;
use App\Enums\ShareStatus;
use Database\Factories\ShareFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $token_hash
 * @property ShareMode $mode
 * @property Carbon $expires_at
 * @property Carbon|null $revoked_at
 * @property int|null $revoked_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read User|null $revoker
 * @property-read Collection<int, Article> $articles
 */
#[Fillable(['user_id', 'token_hash', 'mode', 'expires_at'])]
class Share extends Model
{
    /** @use HasFactory<ShareFactory> */
    use HasFactory;

    /** Validades permitidas, em dias (D10). */
    public const VALIDITY_DAYS = [7, 30, 90];

    public const DEFAULT_DAYS = 7;

    /** Hash com que o token é guardado e procurado. */
    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'mode' => ShareMode::class,
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function revoker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    /**
     * Artigos pela ordem escolhida pelo vendedor.
     *
     * @return BelongsToMany<Article, $this>
     */
    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'share_article')->withPivot('position')->orderBy('share_article.position');
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isActive(): bool
    {
        return ! $this->isRevoked() && ! $this->isExpired();
    }

    public function status(): ShareStatus
    {
        return match (true) {
            $this->isRevoked() => ShareStatus::Revoked,
            $this->isExpired() => ShareStatus::Expired,
            default => ShareStatus::Active,
        };
    }

    public function revoke(User $by): void
    {
        $this->forceFill(['revoked_at' => now(), 'revoked_by' => $by->id])->save();
    }
}
