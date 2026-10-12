<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ArticleStatus;
use App\Enums\ShareMode;
use App\Models\Article;
use App\Models\Share;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CreateShare
{
    /**
     * Cria a partilha e devolve-a com o URL público. O token só existe aqui (na BD fica o hash).
     *
     * @param  list<int|string>  $articleIds  pela ordem em que aparecem na página
     * @return array{share: Share, url: string}
     *
     * @throws ValidationException
     */
    public function handle(User $user, array $articleIds, ShareMode $mode = ShareMode::WithoutPrices, int $days = Share::DEFAULT_DAYS): array
    {
        $ids = array_map(intval(...), $articleIds);

        if ($ids === []) {
            throw ValidationException::withMessages(['articles' => 'Escolha pelo menos um artigo.']);
        }

        if (count($ids) !== count(array_unique($ids))) {
            throw ValidationException::withMessages(['articles' => 'Há artigos repetidos na selecção.']);
        }

        if (! in_array($days, Share::VALIDITY_DAYS, true)) {
            throw ValidationException::withMessages(['days' => 'A validade tem de ser de 7, 30 ou 90 dias.']);
        }

        $published = Article::query()->whereIn('id', $ids)->where('status', ArticleStatus::Published)->count();
        if ($published !== count($ids)) {
            throw ValidationException::withMessages(['articles' => 'Só se partilham artigos publicados.']);
        }

        // 128 bits aleatórios em base64url (22 caracteres).
        $token = rtrim(strtr(base64_encode(random_bytes(16)), '+/', '-_'), '=');

        $share = DB::transaction(function () use ($user, $ids, $mode, $days, $token): Share {
            $share = Share::query()->create([
                'user_id' => $user->id,
                'token_hash' => Share::hashToken($token),
                'mode' => $mode,
                'expires_at' => now()->addDays($days),
            ]);

            $share->articles()->attach(
                collect($ids)->mapWithKeys(fn (int $id, int $position): array => [$id => ['position' => $position]])->all()
            );

            return $share;
        });

        return ['share' => $share, 'url' => route('shares.show', ['token' => $token])];
    }
}
