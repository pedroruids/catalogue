<?php

use App\Actions\CreateShare;
use App\Enums\ArticleStatus;
use App\Enums\ShareMode;
use App\Models\Article;
use App\Models\ArticleImage;
use App\Models\ArticleVariant;
use App\Models\PriceTier;
use App\Models\Share;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function shareArticle(string $name, bool $published = true, bool $withImage = true): Article
{
    $article = Article::factory()->create([
        'name' => $name,
        'status' => $published ? ArticleStatus::Published : ArticleStatus::Draft,
        'variant_axes' => [['name' => 'Cor', 'values' => ['Azul', 'Preto']]],
    ]);

    $active = ArticleVariant::factory()->for($article)->create(['options' => ['Cor' => 'Azul'], 'is_active' => true]);
    PriceTier::factory()->for($active, 'variant')->create(['min_quantity' => 1, 'unit_price_cents' => 123456]);
    PriceTier::factory()->for($active, 'variant')->create(['min_quantity' => 500, 'unit_price_cents' => 98765]);

    $inactive = ArticleVariant::factory()->for($article)->create(['options' => ['Cor' => 'Preto'], 'is_active' => false]);
    PriceTier::factory()->for($inactive, 'variant')->create(['min_quantity' => 1, 'unit_price_cents' => 77777]);

    if ($withImage) {
        ArticleImage::query()->create([
            'article_id' => $article->id,
            'original_path' => "originals/{$article->id}.jpg",
            'thumb_path' => "articles/{$article->id}-thumb.webp",
            'screen_path' => "articles/{$article->id}-screen.webp",
            'position' => 0,
            'is_primary' => true,
        ]);
    }

    return $article;
}

/** @return array{share: Share, url: string} */
function shareOf(array $articles, ShareMode $mode, int $days = 7): array
{
    $seller = User::factory()->create(['name' => 'Marta Vendedora', 'email' => 'marta@exemplo.pt']);

    return app(CreateShare::class)->handle($seller, array_map(fn (Article $a) => $a->id, $articles), $mode, $days);
}

it('gera um token de pelo menos 128 bits e guarda só o hash', function () {
    ['share' => $share, 'url' => $url] = shareOf([shareArticle('Caneca')], ShareMode::WithPrices);

    $token = basename(parse_url($url, PHP_URL_PATH));
    $raw = base64_decode(strtr($token, '-_', '+/').'==', true);

    expect(strlen($raw))->toBeGreaterThanOrEqual(16)
        ->and($share->token_hash)->toBe(hash('sha256', $token))
        ->and(DB::table('shares')->where('token_hash', $token)->exists())->toBeFalse()
        ->and(json_encode(DB::table('shares')->get()))->not->toContain($token);
});

it('abre sem login e mostra preços e escalões no modo com preços', function () {
    ['url' => $url] = shareOf([shareArticle('Caneca')], ShareMode::WithPrices);

    $this->get($url)
        ->assertOk()
        ->assertSee('Caneca')
        ->assertSee(PriceTier::formatCents(123456), false)
        ->assertSee(PriceTier::formatCents(98765), false)
        ->assertSee('€', false)
        ->assertSee('Marta Vendedora')
        ->assertDontSee('marta@exemplo.pt')
        ->assertDontSee(PriceTier::formatCents(77777), false)
        ->assertHeader('Referrer-Policy', 'no-referrer')
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
});

it('no modo sem preços nenhum preço chega ao HTML', function (ShareMode $mode) {
    ['url' => $url] = shareOf([shareArticle('Caneca')], $mode);

    $html = $this->get($url)->assertOk()->assertSee('Caneca')->getContent();

    expect($html)->not->toContain('€')
        ->not->toContain(PriceTier::formatCents(123456))->not->toContain('1234,56')->not->toContain('123456')
        ->not->toContain(PriceTier::formatCents(98765))->not->toContain('98765')
        ->not->toContain('Preço');
})->with([ShareMode::WithoutPrices, ShareMode::ImagesOnly]);

it('só imagens mostra o nome e a imagem de ecrã, nunca o original', function () {
    $article = shareArticle('Caneca Esmaltada');
    ['url' => $url] = shareOf([$article], ShareMode::ImagesOnly);

    $this->get($url)
        ->assertOk()
        ->assertSee('Caneca Esmaltada')
        ->assertSee("articles/{$article->id}-screen.webp", false)
        ->assertDontSee("originals/{$article->id}.jpg", false);
});

it('dados vivos: artigo despublicado e variante inactiva ficam de fora', function () {
    $live = shareArticle('Caneca');
    $gone = shareArticle('Cantil Retirado');
    ['url' => $url] = shareOf([$live, $gone], ShareMode::WithPrices);

    $gone->update(['status' => ArticleStatus::Draft]);

    $this->get($url)
        ->assertOk()
        ->assertSee('Caneca')
        ->assertDontSee('Cantil Retirado')
        ->assertDontSee(PriceTier::formatCents(77777), false)
        ->assertDontSee('Preto');
});

it('todos os artigos despublicados dão o estado vazio sem nome do vendedor', function () {
    $article = shareArticle('Caneca');
    ['url' => $url] = shareOf([$article], ShareMode::WithPrices);
    $article->update(['status' => ArticleStatus::Draft]);

    $this->get($url)->assertOk()->assertSee('Estes artigos já não estão disponíveis')->assertDontSee('Caneca')->assertDontSee('Marta Vendedora');
});

it('mantém a ordem escolhida', function () {
    $a = shareArticle('Aaa Primeiro');
    $b = shareArticle('Bbb Segundo');
    ['url' => $url] = shareOf([$b, $a], ShareMode::WithoutPrices);

    $this->get($url)->assertSeeInOrder(['Bbb Segundo', 'Aaa Primeiro']);
});

it('ligação expirada dá 410 sem dados do artigo nem do vendedor', function () {
    ['url' => $url, 'share' => $share] = shareOf([shareArticle('Caneca')], ShareMode::WithPrices);
    $share->update(['expires_at' => now()->subMinute()]);

    $this->get($url)->assertStatus(410)->assertSee('Esta ligação expirou')
        ->assertDontSee('Caneca')->assertDontSee('Marta Vendedora')->assertDontSee('€', false);
});

it('ligação revogada dá 410 sem dados do artigo nem do vendedor', function () {
    ['url' => $url, 'share' => $share] = shareOf([shareArticle('Caneca')], ShareMode::WithPrices);
    $share->revoke(User::factory()->admin()->create());

    $this->get($url)->assertStatus(410)->assertSee('Esta ligação já não está activa')
        ->assertDontSee('Caneca')->assertDontSee('Marta Vendedora')->assertDontSee('€', false);
});

it('token inexistente ou malformado dá 404 com página própria', function (string $token) {
    $this->get('/p/'.$token)->assertNotFound()->assertSee('Não encontrámos esta ligação');
})->with(['abcdefghijklmnopqrstuv', 'curto', str_repeat('a', 80), 'abc.def']);

it('a página leva noindex e o limite de pedidos', function () {
    ['url' => $url] = shareOf([shareArticle('Caneca')], ShareMode::WithoutPrices);

    $this->get($url)->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    expect(app('router')->getRoutes()->getByName('shares.show')->gatherMiddleware())->toContain('throttle:60,1');
});

it('o limite de pedidos devolve 429', function () {
    for ($i = 0; $i < 60; $i++) {
        $this->get('/p/abcdefghijklmnopqrstuv')->assertNotFound();
    }

    $this->get('/p/abcdefghijklmnopqrstuv')->assertStatus(429);
});

it('apagar um artigo tira-o da partilha', function () {
    $a = shareArticle('Caneca');
    $b = shareArticle('Cantil');
    ['share' => $share] = shareOf([$a, $b], ShareMode::WithoutPrices);

    $a->delete();

    expect($share->articles()->pluck('articles.id')->all())->toBe([$b->id]);
});
