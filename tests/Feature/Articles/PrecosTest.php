<?php

use App\Filament\Resources\Articles\Pages\EditArticle;
use App\Filament\Resources\Articles\RelationManagers\VariantsRelationManager;
use App\Models\Article;
use App\Models\ArticleVariant;
use App\Models\PriceTier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());
    $this->article = Article::factory()->create(['variant_axes' => [['name' => 'Cor', 'values' => ['Azul', 'Preto']]]]);
});

function gestor(Article $article)
{
    return Livewire::test(VariantsRelationManager::class, ['ownerRecord' => $article, 'pageClass' => EditArticle::class]);
}

function variante(Article $article, array $tiers = [], bool $active = true): ArticleVariant
{
    $variant = ArticleVariant::factory()->for($article)->create(['is_active' => $active]);
    foreach ($tiers as $min => $cents) {
        PriceTier::factory()->for($variant, 'variant')->create(['min_quantity' => $min, 'unit_price_cents' => $cents]);
    }

    return $variant;
}

it('define escalões na variante e guarda o preço em cêntimos', function () {
    gestor($this->article)
        ->callTableAction('create', data: [
            'options' => ['Cor' => 'Azul'],
            'reference' => 'CAM-AZ',
            'is_active' => true,
            'priceTiers' => [
                ['min_quantity' => 1, 'unit_price_cents' => '1,25'],
                ['min_quantity' => 100, 'unit_price_cents' => '1050,5'],
            ],
        ])
        ->assertHasNoFormErrors();

    $tiers = ArticleVariant::firstWhere('reference', 'CAM-AZ')->priceTiers;
    expect($tiers->pluck('unit_price_cents', 'min_quantity')->all())->toBe([1 => 125, 100 => 105050]);
});

it('recusa quantidades mínimas repetidas na mesma variante', function () {
    gestor($this->article)
        ->callTableAction('create', data: [
            'options' => ['Cor' => 'Azul'],
            'reference' => 'CAM-AZ',
            'is_active' => true,
            'priceTiers' => [
                ['min_quantity' => 10, 'unit_price_cents' => '2,00'],
                ['min_quantity' => 10, 'unit_price_cents' => '1,50'],
            ],
        ])
        ->assertHasFormErrors();

    expect(ArticleVariant::count())->toBe(0);
});

it('recusa preços que não são euros válidos', function () {
    gestor($this->article)
        ->callTableAction('create', data: [
            'options' => ['Cor' => 'Azul'],
            'reference' => 'CAM-AZ',
            'is_active' => true,
            'priceTiers' => [['min_quantity' => 1, 'unit_price_cents' => '1,255']],
        ])
        ->assertHasFormErrors();
});

it('converte euros em cêntimos sem float', function (string $input, ?int $cents) {
    expect(PriceTier::centsFromInput($input))->toBe($cents);
})->with([
    ['1,25', 125], ['1.25', 125], ['0,1', 10], ['2', 200], ['1 234,50', 123450],
    ['0,29', 29], ['19,99', 1999], ['abc', null], ['1,255', null], ['-1', null], ['', null],
]);

it('edita um escalão existente mostrando o preço em euros', function () {
    $variant = variante($this->article, [1 => 125]);

    gestor($this->article)
        ->mountTableAction('edit', $variant)
        ->assertSchemaStateSet(fn (array $state): bool => array_values($state['priceTiers'])[0]['unit_price_cents'] === '1,25');
});

it('copia os escalões para as outras variantes, substituindo-os, sem tocar na origem', function () {
    $origem = variante($this->article, [1 => 500, 50 => 400]);
    $outra = variante($this->article, [1 => 999, 5 => 888]);
    $vazia = variante($this->article);
    $alheia = variante(Article::factory()->create(), [1 => 777]);

    gestor($this->article)->callTableAction('copyTiers', $origem)->assertNotified();

    expect($origem->priceTiers()->pluck('unit_price_cents', 'min_quantity')->all())->toBe([1 => 500, 50 => 400])
        ->and($outra->priceTiers()->pluck('unit_price_cents', 'min_quantity')->all())->toBe([1 => 500, 50 => 400])
        ->and($vazia->priceTiers()->pluck('unit_price_cents', 'min_quantity')->all())->toBe([1 => 500, 50 => 400])
        ->and($alheia->priceTiers()->pluck('unit_price_cents', 'min_quantity')->all())->toBe([1 => 777]);
});

it('mostra «sob consulta» sem escalões e o menor preço com escalões', function () {
    $com = variante($this->article, [1 => 500, 50 => 400]);
    $sem = variante($this->article);

    gestor($this->article)
        ->assertTableColumnStateSet('from_price', PriceTier::formatCents(400), $com)
        ->assertTableColumnStateSet('from_price', 'Sob consulta', $sem);

    expect($sem->fromPriceCents())->toBeNull();
});

it('o preço «desde» do artigo ignora variantes inactivas e sem escalões', function () {
    variante($this->article, [1 => 100], active: false);
    variante($this->article, [1 => 900, 10 => 700]);
    variante($this->article, [1 => 800]);
    variante($this->article);

    expect($this->article->fresh()->fromPriceCents())->toBe(700);
});

it('o artigo sem preços activos é «sob consulta»', function () {
    variante($this->article, [1 => 100], active: false);

    expect($this->article->fresh()->fromPriceCents())->toBeNull();
});

it('formata em euros pt-PT', function () {
    expect(PriceTier::formatCents(123450))->toBe("1\u{00A0}234,50\u{00A0}€")
        ->and(PriceTier::formatCents(125))->toBe("1,25\u{00A0}€");
});

it('apaga os escalões quando a variante é apagada', function () {
    $variant = variante($this->article, [1 => 100]);
    $variant->delete();

    expect(PriceTier::count())->toBe(0);
});
