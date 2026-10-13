<?php

use App\Models\Article;
use App\Models\ArticleImage;
use App\Models\ArticleVariant;
use App\Models\FieldDefinition;
use App\Models\PriceTier;
use App\Models\ProductType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function artigoCompleto(): Article
{
    $type = ProductType::factory()->create(['name' => 'Canetas']);
    FieldDefinition::factory()->create(['product_type_id' => $type->id, 'name' => 'Material', 'key' => 'material']);

    $article = Article::factory()->published()->create([
        'product_type_id' => $type->id,
        'name' => 'Caneta Azul',
        'field_values' => ['material' => 'Bambu'],
        'variant_axes' => [['name' => 'Cor', 'values' => ['Azul']]],
    ]);

    $image = ArticleImage::query()->create([
        'article_id' => $article->id,
        'original_path' => 'originals/segredo-original.jpg',
        'thumb_path' => 'articles/1/thumb.webp',
        'screen_path' => 'articles/1/screen.webp',
        'position' => 0,
        'is_primary' => true,
    ]);

    $variant = ArticleVariant::factory()->create(['article_id' => $article->id, 'options' => ['Cor' => 'Azul'], 'article_image_id' => $image->id, 'is_active' => true]);
    PriceTier::factory()->create(['article_variant_id' => $variant->id, 'min_quantity' => 100, 'unit_price_cents' => 125]);
    PriceTier::factory()->create(['article_variant_id' => $variant->id, 'min_quantity' => 500, 'unit_price_cents' => 99]);

    return $article;
}

it('recusa visitantes', function () {
    $this->getJson(route('offline.catalog'))->assertUnauthorized();
    $this->get(route('offline.catalog'))->assertRedirect(route('login'));
});

it('serve o snapshot a vendedores e a admins', function (User $user) {
    artigoCompleto();

    $this->actingAs($user)->getJson(route('offline.catalog'))->assertOk();
})->with([
    'vendedor' => fn () => User::factory()->create(),
    'admin' => fn () => User::factory()->admin()->create(),
]);

it('tem a forma esperada: artigos, campos, variantes, escalões e imagens reduzidas', function () {
    $article = artigoCompleto();

    $json = $this->actingAs(User::factory()->create())->getJson(route('offline.catalog'))
        ->assertOk()
        ->assertJsonStructure([
            'schema', 'version', 'generated_at',
            'product_types' => [['id', 'name', 'fields' => [['key', 'name', 'data_type', 'options']]]],
            'articles' => [[
                'id', 'product_type_id', 'name', 'reference', 'description', 'field_values', 'variant_axes', 'updated_at',
                'images' => [['id', 'is_primary', 'thumb', 'screen']],
                'variants' => [['id', 'reference', 'options', 'image_id', 'price_tiers' => [['min_quantity', 'unit_price_cents']]]],
            ]],
        ])
        ->json();

    expect($json['articles'])->toHaveCount(1)
        ->and($json['articles'][0]['id'])->toBe($article->id)
        ->and($json['articles'][0]['field_values'])->toBe(['material' => 'Bambu'])
        ->and($json['articles'][0]['images'][0]['thumb'])->toBe('/storage/articles/1/thumb.webp')
        ->and($json['articles'][0]['images'][0]['screen'])->toBe('/storage/articles/1/screen.webp')
        ->and(collect($json['articles'][0]['variants'][0]['price_tiers'])->pluck('unit_price_cents')->all())->toBe([125, 99])
        ->and($json['product_types'][0]['fields'][0]['key'])->toBe('material');
});

it('não deixa sair nada de privado nem rascunhos', function () {
    artigoCompleto();
    Article::factory()->create(['name' => 'Rascunho secreto']); // draft
    $article = Article::query()->where('name', 'Caneta Azul')->firstOrFail();
    ArticleVariant::factory()->create(['article_id' => $article->id, 'options' => ['Cor' => 'Inactiva'], 'is_active' => false]);

    $body = $this->actingAs(User::factory()->create())->getJson(route('offline.catalog'))->assertOk()->getContent();

    expect($body)
        ->not->toContain('original_path')
        ->not->toContain('segredo-original')
        ->not->toContain('Rascunho secreto')
        ->not->toContain('Inactiva');
});

it('a versão é estável, vai no ETag e muda quando o catálogo muda', function () {
    $article = artigoCompleto();
    $user = User::factory()->create();

    $first = $this->actingAs($user)->getJson(route('offline.catalog'))->assertOk();
    $version = $first->json('version');
    $first->assertHeader('ETag', '"'.$version.'"');
    expect($first->headers->get('Cache-Control'))->toContain('private')->toContain('no-cache');

    expect($this->actingAs($user)->getJson(route('offline.catalog'))->json('version'))->toBe($version);

    $article->update(['name' => 'Caneta Vermelha']);

    expect($this->actingAs($user)->getJson(route('offline.catalog'))->json('version'))->not->toBe($version);
});

it('responde 304 quando a versão do cliente é a actual', function () {
    artigoCompleto();
    $user = User::factory()->create();
    $version = $this->actingAs($user)->getJson(route('offline.catalog'))->json('version');

    $this->actingAs($user)->getJson(route('offline.catalog'), ['If-None-Match' => '"'.$version.'"'])->assertStatus(304);
    $this->actingAs($user)->getJson(route('offline.catalog'), ['If-None-Match' => '"antiga"'])->assertOk();
});

it('serve um catálogo vazio sem erros', function () {
    $this->actingAs(User::factory()->create())->getJson(route('offline.catalog'))
        ->assertOk()
        ->assertJsonPath('articles', [])
        ->assertJsonPath('product_types', []);
});
