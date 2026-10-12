<?php

use App\Enums\FieldType;
use App\Models\Article;
use App\Models\ArticleImage;
use App\Models\ArticleVariant;
use App\Models\FieldDefinition;
use App\Models\PriceTier;
use App\Models\ProductType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function artigoImagem(Article $article, int $position = 0): ArticleImage
{
    return ArticleImage::query()->create([
        'article_id' => $article->id,
        'original_path' => "originals/secreto-{$article->id}-{$position}.jpg",
        'thumb_path' => "articles/{$article->id}-{$position}-thumb.webp",
        'screen_path' => "articles/{$article->id}-{$position}-screen.webp",
        'position' => $position,
        'is_primary' => $position === 0,
    ]);
}

function artigoComVariantes(): Article
{
    $article = Article::factory()->published()->create([
        'name' => 'Caneca Teste',
        'variant_axes' => [['name' => 'Cor', 'values' => ['Azul', 'Verde']], ['name' => 'Tamanho', 'values' => ['M']]],
    ]);
    $azul = artigoImagem($article, 0);
    $verde = artigoImagem($article, 1);

    $v1 = ArticleVariant::factory()->create(['article_id' => $article->id, 'options' => ['Cor' => 'Azul', 'Tamanho' => 'M'], 'article_image_id' => $azul->id]);
    $v2 = ArticleVariant::factory()->create(['article_id' => $article->id, 'options' => ['Cor' => 'Verde', 'Tamanho' => 'M'], 'article_image_id' => $verde->id]);
    PriceTier::factory()->create(['article_variant_id' => $v1->id, 'min_quantity' => 1, 'unit_price_cents' => 1250]);
    PriceTier::factory()->create(['article_variant_id' => $v1->id, 'min_quantity' => 1000, 'unit_price_cents' => 99]);
    // v2 sem escalões → «sob consulta».

    return $article;
}

it('manda o convidado para o login', function () {
    $this->get(route('articles.show', Article::factory()->published()->create()))->assertRedirect(route('login'));
});

it('dá 404 num artigo em rascunho', function () {
    $this->actingAs(User::factory()->create())->get(route('articles.show', Article::factory()->create()))->assertNotFound();
});

it('mostra a galeria com as versões reduzidas e nunca o original', function () {
    $article = artigoComVariantes();

    $html = $this->actingAs(User::factory()->create())->get(route('articles.show', $article))->assertOk()->getContent();

    expect($html)
        ->toContain("articles/{$article->id}-0-screen.webp")
        ->toContain("articles/{$article->id}-0-thumb.webp")
        ->toContain("articles/{$article->id}-1-thumb.webp")
        ->not->toContain('originals/')
        ->not->toContain('secreto');
});

it('mostra o espaço reservado quando o artigo não tem imagens', function () {
    $article = Article::factory()->published()->create();

    $this->actingAs(User::factory()->create())->get(route('articles.show', $article))->assertOk()->assertSee('Sem imagens');
});

it('mostra os campos dinâmicos preenchidos e omite os vazios', function () {
    $type = ProductType::factory()->create();
    $text = FieldDefinition::factory()->create(['product_type_id' => $type->id, 'name' => 'Material', 'key' => 'material', 'position' => 1]);
    FieldDefinition::factory()->create(['product_type_id' => $type->id, 'name' => 'Peso', 'key' => 'peso', 'data_type' => FieldType::Number, 'position' => 2]);
    FieldDefinition::factory()->create(['product_type_id' => $type->id, 'name' => 'Lavável', 'key' => 'lavavel', 'data_type' => FieldType::Boolean, 'position' => 3]);
    FieldDefinition::factory()->create(['product_type_id' => $type->id, 'name' => 'Reciclável', 'key' => 'reciclavel', 'data_type' => FieldType::Boolean, 'position' => 4]);
    FieldDefinition::factory()->select(['Mate', 'Brilho'])->create(['product_type_id' => $type->id, 'name' => 'Acabamento', 'key' => 'acabamento', 'position' => 5]);
    FieldDefinition::factory()->create(['product_type_id' => $type->id, 'name' => 'Dimensões', 'key' => 'dimensoes', 'position' => 6]);

    $article = Article::factory()->published()->create([
        'product_type_id' => $type->id,
        'field_values' => ['material' => 'Cerâmica', 'peso' => 1250.5, 'lavavel' => true, 'reciclavel' => false, 'acabamento' => 'Mate'],
    ]);

    $this->actingAs(User::factory()->create())->get(route('articles.show', $article))->assertOk()
        ->assertSeeInOrder(['Material', 'Cerâmica', 'Peso', "1\u{00A0}250,5", 'Lavável', 'Sim', 'Reciclável', 'Não', 'Acabamento', 'Mate'])
        ->assertDontSee('Dimensões');
});

it('lista só as variantes activas e os escalões em pt-PT, com «sob consulta» quando não há', function () {
    $article = artigoComVariantes();
    ArticleVariant::factory()->create(['article_id' => $article->id, 'options' => ['Cor' => 'Verde', 'Tamanho' => 'M'], 'is_active' => false]);
    $inactive = ArticleVariant::factory()->create(['article_id' => $article->id, 'options' => ['Cor' => 'Azul', 'Tamanho' => 'L'], 'is_active' => false]);
    PriceTier::factory()->create(['article_variant_id' => $inactive->id, 'unit_price_cents' => 777777]);

    $response = $this->actingAs(User::factory()->create())->get(route('articles.show', $article))->assertOk();

    $response->assertSee(PriceTier::formatCents(1250), false)
        ->assertSee(PriceTier::formatCents(99), false)
        ->assertSee("1\u{00A0}000 un.", false)
        ->assertSee('Preço sob consulta')
        ->assertDontSee(PriceTier::formatCents(777777), false);
    expect(substr_count($response->getContent(), 'data-escaloes'))->toBe(1)
        ->and(substr_count($response->getContent(), 'Esta variante ainda não tem escalões'))->toBe(1);
});

it('com os preços escondidos o HTML não tem preços nem escalões', function () {
    $article = artigoComVariantes();

    $this->actingAs(User::factory()->create())->get(route('articles.show', $article))->assertSee('€');

    $this->withSession(['show_prices' => false])->actingAs(User::factory()->create())
        ->get(route('articles.show', $article))
        ->assertOk()
        ->assertDontSee('€')
        ->assertDontSee('data-escaloes', false)
        ->assertDontSee('Preço sob consulta')
        ->assertDontSee("1\u{00A0}000 un.", false)
        ->assertSee('Preços escondidos');
});

it('a escolha do interruptor persiste entre a lista e o artigo', function () {
    $article = artigoComVariantes();
    $user = User::factory()->create();

    Livewire::actingAs($user)->test('pages::articles.show', ['article' => $article])->call('togglePrices');

    $this->actingAs($user)->get(route('articles.index'))->assertOk()->assertDontSee('€');
    $this->get(route('articles.show', $article))->assertOk()->assertDontSee('€');
});

it('os dados enviados ao cliente apontam cada variante para a sua imagem e não têm preços', function () {
    $article = artigoComVariantes();
    $html = $this->actingAs(User::factory()->create())->get(route('articles.show', $article))->getContent();

    // Variante azul → imagem 0, verde → imagem 1 (posições na galeria).
    expect($html)
        ->toContain('\u0022Azul\u0022,\u0022Tamanho\u0022:\u0022M\u0022},\u0022image\u0022:0')
        ->toContain('\u0022Verde\u0022,\u0022Tamanho\u0022:\u0022M\u0022},\u0022image\u0022:1');
    expect(preg_match('/paginaArtigo\((.*?)\)" class/s', $html, $m))->toBe(1)
        ->and($m[1])->not->toContain('1250')->not->toContain('€');
});

it('o voltar repõe a pesquisa e o filtro da lista', function () {
    $article = artigoComVariantes();
    $url = route('articles.index', ['q' => 'caneca', 'tipo' => '3']);

    $this->actingAs(User::factory()->create())->withHeader('referer', $url)
        ->get(route('articles.show', $article))
        ->assertSee('href="'.route('articles.index').'?q=caneca&amp;tipo=3"', false);
});

it('abre o artigo sem consultas a mais com muitas variantes', function () {
    $queries = function (int $variants): int {
        $article = Article::factory()->published()->create(['variant_axes' => [['name' => 'Cor', 'values' => ['A']]]]);
        foreach (range(1, $variants) as $n) {
            $v = ArticleVariant::factory()->create(['article_id' => $article->id, 'options' => ['Cor' => "C{$n}"]]);
            PriceTier::factory()->count(3)->sequence(['min_quantity' => 1], ['min_quantity' => 10], ['min_quantity' => 100])->create(['article_variant_id' => $v->id]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get(route('articles.show', $article))->assertOk()->assertSee('€', false);
        DB::disableQueryLog();

        return count(DB::getQueryLog());
    };

    $this->actingAs(User::factory()->create());

    // As consultas não crescem com o número de variantes (escalões carregados de uma vez).
    expect($queries(12))->toBe($queries(1));
});

it('o voltar ignora um referer que não é a lista', function () {
    $article = artigoComVariantes();

    $this->actingAs(User::factory()->create())->withHeader('referer', url('/settings/profile?q=caneca&tipo=3'))
        ->get(route('articles.show', $article))
        ->assertSee('href="'.route('articles.index').'"', false)
        ->assertDontSee('q=caneca', false);
});
