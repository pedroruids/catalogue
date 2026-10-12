<?php

use App\Models\Article;
use App\Models\ArticleImage;
use App\Models\ArticleVariant;
use App\Models\PriceTier;
use App\Models\ProductType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/** Artigo publicado com uma variante activa e um escalão (cêntimos). */
function publicado(array $attrs = [], ?int $cents = 125): Article
{
    $article = Article::factory()->published()->create($attrs);

    if ($cents !== null) {
        $variant = ArticleVariant::factory()->create(['article_id' => $article->id]);
        PriceTier::factory()->create(['article_variant_id' => $variant->id, 'unit_price_cents' => $cents]);
    }

    return $article;
}

function comImagem(Article $article): ArticleImage
{
    return ArticleImage::query()->create([
        'article_id' => $article->id,
        'original_path' => "originals/{$article->id}.jpg",
        'thumb_path' => "articles/{$article->id}-thumb.webp",
        'screen_path' => "articles/{$article->id}-screen.webp",
        'position' => 0,
        'is_primary' => true,
    ]);
}

it('manda o convidado para o login', function () {
    $this->get(route('articles.index'))->assertRedirect(route('login'));
});

it('depois do login o vendedor e o admin caem na lista de artigos', function (User $user) {
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('articles.index', absolute: false));

    $this->get(route('articles.index'))->assertOk()->assertSee('Artigos');
})->with([
    'vendedor' => fn () => User::factory()->create(),
    'admin' => fn () => User::factory()->admin()->create(),
]);

it('lista só os artigos publicados', function () {
    publicado(['name' => 'Caneca Visível']);
    Article::factory()->create(['name' => 'Caneca Rascunho']);

    $this->actingAs(User::factory()->create())
        ->get(route('articles.index'))
        ->assertSee('Caneca Visível')
        ->assertDontSee('Caneca Rascunho');
});

it('pesquisa por nome e por referência', function () {
    publicado(['name' => 'Caneca Azul', 'reference' => 'CN-0001']);
    publicado(['name' => 'Lanyard Dourado', 'reference' => 'LD-0002']);

    $this->actingAs(User::factory()->create());

    Livewire::test('pages::articles.index')
        ->set('search', 'caneca')
        ->assertSee('Caneca Azul')->assertDontSee('Lanyard Dourado')
        ->set('search', 'LD-0002')
        ->assertSee('Lanyard Dourado')->assertDontSee('Caneca Azul');
});

it('a pesquisa trata % e _ como texto', function () {
    publicado(['name' => 'Caneca Azul']);

    $this->actingAs(User::factory()->create());

    Livewire::test('pages::articles.index')->set('search', '%')->assertSee('Nenhum artigo encontrado');
});

it('a pesquisa e o filtro ficam na query string', function () {
    $tipo = ProductType::factory()->create(['name' => 'Têxtil']);
    publicado(['name' => 'Lanyard Dourado', 'product_type_id' => $tipo->id]);
    publicado(['name' => 'Caneca Azul']);

    $this->actingAs(User::factory()->create())
        ->get(route('articles.index', ['q' => 'lanyard', 'tipo' => $tipo->id]))
        ->assertSee('Lanyard Dourado')
        ->assertDontSee('Caneca Azul');
});

it('filtra por tipo de produto e oferece só tipos com artigos publicados', function () {
    $textil = ProductType::factory()->create(['name' => 'Têxtil']);
    $vidro = ProductType::factory()->create(['name' => 'Vidro']);
    ProductType::factory()->create(['name' => 'Sem Artigos']);
    publicado(['name' => 'Lanyard Dourado', 'product_type_id' => $textil->id]);
    publicado(['name' => 'Copo Alto', 'product_type_id' => $vidro->id]);
    Article::factory()->create(['name' => 'Rascunho de Madeira', 'product_type_id' => ProductType::factory()->create(['name' => 'Madeira'])->id]);

    $this->actingAs(User::factory()->create());

    Livewire::test('pages::articles.index')
        ->assertSee('Têxtil')->assertSee('Vidro')
        ->assertDontSee('Sem Artigos')->assertDontSee('Madeira')
        ->call('selectType', (string) $textil->id)
        ->assertSee('Lanyard Dourado')->assertDontSee('Copo Alto')
        ->call('selectType', '')
        ->assertSee('Copo Alto');
});

it('um tipo forjado na URL não rebenta a página', function () {
    publicado(['name' => 'Caneca Azul']);

    $this->actingAs(User::factory()->create())
        ->get(route('articles.index', ['tipo' => 'abc']))
        ->assertOk()->assertSee('Caneca Azul');
});

it('mostra o vazio sem artigos e o vazio de filtro com «Limpar filtros»', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::articles.index')
        ->assertSee('Ainda não há artigos publicados')
        ->assertDontSee('Limpar filtros');

    publicado(['name' => 'Caneca Azul']);

    Livewire::test('pages::articles.index')
        ->set('search', 'zzz')
        ->assertSee('Nenhum artigo encontrado')->assertSee('Limpar filtros')
        ->call('clearFilters')
        ->assertSee('Caneca Azul')->assertSet('search', '');
});

it('o cartão usa a miniatura, nunca o original, e mostra «desde» em pt-PT', function () {
    $article = publicado(['name' => 'Caneca Azul'], 1250);
    comImagem($article);

    $html = $this->actingAs(User::factory()->create())->get(route('articles.index'))->getContent();

    expect($html)
        ->toContain("articles/{$article->id}-thumb.webp")
        ->not->toContain('originals/')
        ->not->toContain('-screen.webp')
        ->toContain('desde')
        ->toContain(PriceTier::formatCents(1250))
        ->toContain(route('articles.show', $article));
    expect(PriceTier::formatCents(1250))->toContain('12,50');
});

it('o «desde» é o menor preço das variantes activas', function () {
    $article = publicado(['name' => 'Caneca Azul'], 900);
    $inactiva = ArticleVariant::factory()->create(['article_id' => $article->id, 'is_active' => false]);
    PriceTier::factory()->create(['article_variant_id' => $inactiva->id, 'unit_price_cents' => 100]);

    $this->actingAs(User::factory()->create())
        ->get(route('articles.index'))
        ->assertSee(PriceTier::formatCents(900))
        ->assertDontSee(PriceTier::formatCents(100));
});

it('artigo sem preço mostra «Preço sob consulta»', function () {
    publicado(['name' => 'Caneca Azul'], null);

    $this->actingAs(User::factory()->create())
        ->get(route('articles.index'))
        ->assertSee('Preço sob consulta')
        ->assertDontSee('desde');
});

it('artigo sem imagens mostra o espaço «Sem imagens»', function () {
    publicado(['name' => 'Caneca Azul']);

    $this->actingAs(User::factory()->create())->get(route('articles.index'))->assertSee('Sem imagens');
});

it('com os preços escondidos o HTML não tem nenhum preço', function () {
    publicado(['name' => 'Caneca Azul'], 1250);
    publicado(['name' => 'Copo Alto'], null);

    $this->actingAs(User::factory()->create());

    // Recusa: escondidos, nem «€», nem «desde», nem «Preço sob consulta».
    $this->withSession(['show_prices' => false])->get(route('articles.index'))
        ->assertOk()->assertSee('Caneca Azul')
        ->assertDontSee('€')->assertDontSee('desde')->assertDontSee('Preço sob consulta');

    // Aceitação: por omissão os preços aparecem.
    $this->flushSession();
    $this->get(route('articles.index'))->assertSee('€', false)->assertSee('desde');
});

it('o interruptor guarda a escolha na sessão e esconde os preços no HTML', function () {
    publicado(['name' => 'Caneca Azul'], 1250);

    $this->actingAs(User::factory()->create());

    Livewire::test('pages::articles.index')
        ->assertSee('desde')
        ->call('togglePrices')
        ->assertDontSee('€')->assertDontSee('desde')
        ->call('togglePrices')
        ->assertSee('desde');

    expect(session('show_prices'))->toBeTrue();
});

it('com os preços escondidos, pesquisar, filtrar e mostrar mais também não traz preços', function () {
    $tipo = ProductType::factory()->create(['name' => 'Têxtil']);
    foreach (range(1, 26) as $i) {
        publicado(['name' => sprintf('Caneca %02d', $i), 'product_type_id' => $tipo->id], 1250);
    }

    $this->actingAs(User::factory()->create())->withSession(['show_prices' => false]);

    Livewire::test('pages::articles.index')
        ->set('search', 'caneca')->assertSee('Caneca 01')->assertDontSee('€')->assertDontSee('desde')
        ->set('type', (string) $tipo->id)->assertSee('Caneca 01')->assertDontSee('€')
        ->call('loadMore')->assertSee('Caneca 26')->assertDontSee('€')->assertDontSee('desde');
});

it('um rascunho não aparece nem pesquisado pela referência exacta', function () {
    publicado(['name' => 'Caneca Azul', 'reference' => 'CN-0001']);
    Article::factory()->create(['name' => 'Caneca Rascunho', 'reference' => 'DRAFT-XYZ']);

    $this->actingAs(User::factory()->create());

    Livewire::test('pages::articles.index')
        ->set('search', 'DRAFT-XYZ')
        ->assertDontSee('Caneca Rascunho')->assertSee('Nenhum artigo encontrado');
});

it('pagina com «Mostrar mais artigos»', function () {
    foreach (range(1, 26) as $i) {
        publicado(['name' => sprintf('Artigo %02d', $i)], null);
    }

    $this->actingAs(User::factory()->create());

    Livewire::test('pages::articles.index')
        ->assertSee('26 artigos')->assertSee('Artigo 24')->assertDontSee('Artigo 25')
        ->assertSee('Mostrar mais artigos')
        ->call('loadMore')
        ->assertSee('Artigo 26')->assertDontSee('Mostrar mais artigos');
});

it('não faz N+1: as queries não crescem com o número de artigos', function () {
    $this->actingAs(User::factory()->create());

    $queries = function (): int {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get(route('articles.index'))->assertOk();

        return count(DB::getQueryLog());
    };

    foreach (range(1, 2) as $i) {
        comImagem(publicado());
    }
    $com2 = $queries();

    foreach (range(1, 8) as $i) {
        comImagem(publicado());
    }
    $com10 = $queries();

    expect($com10)->toBe($com2);
});

it('a página do artigo existe para publicados e dá 404 a rascunhos', function () {
    $publicado = publicado(['name' => 'Caneca Azul']);
    $rascunho = Article::factory()->create();

    $this->actingAs(User::factory()->create());

    $this->get(route('articles.show', $publicado))->assertOk()->assertSee('Caneca Azul');
    $this->get(route('articles.show', $rascunho))->assertNotFound();
});
