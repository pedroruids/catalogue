<?php

use App\Actions\CreateShare;
use App\Enums\ArticleStatus;
use App\Enums\ShareMode;
use App\Models\Article;
use App\Models\Share;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seller = User::factory()->create();
});

it('cria com as omissões: sem preços, 7 dias, ordem preservada', function () {
    [$a, $b, $c] = Article::factory()->published()->count(3)->create()->all();

    $result = app(CreateShare::class)->handle($this->seller, [$c->id, $a->id, $b->id]);

    expect($result['share']->mode)->toBe(ShareMode::WithoutPrices)
        ->and($result['share']->user_id)->toBe($this->seller->id)
        ->and($result['share']->expires_at->isSameDay(now()->addDays(7)))->toBeTrue()
        ->and($result['share']->articles()->pluck('articles.id')->all())->toBe([$c->id, $a->id, $b->id])
        ->and($result['url'])->toStartWith(url('/p/'));
});

it('aceita 30 e 90 dias', function (int $days) {
    $article = Article::factory()->published()->create();

    $share = app(CreateShare::class)->handle($this->seller, [$article->id], ShareMode::WithPrices, $days)['share'];

    expect($share->expires_at->isSameDay(now()->addDays($days)))->toBeTrue();
})->with([30, 90]);

it('recusa rascunho, validade inválida, lista vazia, repetidos e inexistentes, sem gravar nada', function (string $case) {
    $published = Article::factory()->published()->create();
    $draft = Article::factory()->create(['status' => ArticleStatus::Draft]);
    $action = app(CreateShare::class);

    $call = match ($case) {
        'rascunho' => fn () => $action->handle($this->seller, [$published->id, $draft->id]),
        'dias' => fn () => $action->handle($this->seller, [$published->id], ShareMode::WithoutPrices, 15),
        'vazio' => fn () => $action->handle($this->seller, []),
        'repetidos' => fn () => $action->handle($this->seller, [$published->id, $published->id]),
        'inexistente' => fn () => $action->handle($this->seller, [$published->id, 999999]),
    };

    expect($call)->toThrow(ValidationException::class);
    expect(Share::count())->toBe(0);
})->with(['rascunho', 'dias', 'vazio', 'repetidos', 'inexistente']);

it('cada partilha tem um token diferente', function () {
    $article = Article::factory()->published()->create();
    $action = app(CreateShare::class);

    expect($action->handle($this->seller, [$article->id])['url'])->not->toBe($action->handle($this->seller, [$article->id])['url']);
});
