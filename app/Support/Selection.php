<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\ArticleStatus;
use App\Models\Article;

/**
 * Selecção de artigos a partilhar (D16): vive na sessão do servidor, pela ordem em que se juntou.
 * Só guarda ids de artigos publicados — ao ler, os que entretanto deixaram de o ser saem.
 */
final class Selection
{
    private const SESSION_KEY = 'share_selection';

    /** @return list<int> */
    public static function ids(): array
    {
        $stored = array_values(array_unique(array_map(intval(...), (array) session(self::SESSION_KEY, []))));

        if ($stored === []) {
            return [];
        }

        $published = Article::query()->whereIn('id', $stored)->where('status', ArticleStatus::Published)->pluck('id')->all();
        $ids = array_values(array_intersect($stored, $published));

        if ($ids !== $stored) {
            session([self::SESSION_KEY => $ids]);
        }

        return $ids;
    }

    public static function count(): int
    {
        return count(self::ids());
    }

    public static function has(int $id): bool
    {
        return in_array($id, self::ids(), true);
    }

    /** Junta ou retira. Um artigo que não está publicado não se junta. */
    public static function toggle(int $id): void
    {
        if (self::has($id)) {
            self::remove($id);

            return;
        }

        if (Article::query()->whereKey($id)->where('status', ArticleStatus::Published)->exists()) {
            session([self::SESSION_KEY => [...self::ids(), $id]]);
        }
    }

    public static function remove(int $id): void
    {
        session([self::SESSION_KEY => array_values(array_diff(self::ids(), [$id]))]);
    }

    public static function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }
}
