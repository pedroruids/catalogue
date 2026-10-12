<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shares;

use App\Enums\ArticleStatus;
use App\Enums\ShareMode;
use App\Models\Article;
use App\Models\Share;
use Illuminate\Http\Response;

/** Página pública de uma partilha (sem login). Os dados são vivos: lê os artigos de agora. */
final class ShowController
{
    /** Formato do token emitido por CreateShare: 22 caracteres base64url. */
    private const TOKEN_PATTERN = '/\A[A-Za-z0-9_-]{22}\z/';

    public function __invoke(string $token): Response
    {
        $share = preg_match(self::TOKEN_PATTERN, $token) === 1
            ? Share::query()->with('user')->where('token_hash', Share::hashToken($token))->first()
            : null;

        if ($share === null) {
            return $this->respond(response()->view('shares.unavailable', ['reason' => 'missing'], 404));
        }

        if ($share->isRevoked() || $share->isExpired()) {
            return $this->respond(response()->view('shares.unavailable', ['reason' => $share->isRevoked() ? 'revoked' : 'expired'], 410));
        }

        $withPrices = $share->mode->showsPrices();

        $articles = $share->articles()
            ->where('articles.status', ArticleStatus::Published)
            ->with([
                'images',
                'variants' => fn ($q) => $q->where('is_active', true),
            ])
            ->get();

        if ($withPrices) {
            $articles->each(fn (Article $a) => $a->variants->load('priceTiers'));
        }

        return $this->respond(response()->view('shares.show', [
            'share' => $share,
            'seller' => $share->user->name,
            'articles' => $articles,
            'withPrices' => $withPrices,
            'imagesOnly' => $share->mode === ShareMode::ImagesOnly,
        ]));
    }

    private function respond(Response $response): Response
    {
        return $response->withHeaders([
            'X-Robots-Tag' => 'noindex, nofollow',
            'Referrer-Policy' => 'no-referrer',
            'Cache-Control' => 'no-store, private',
        ]);
    }
}
