<?php

use App\Filament\Resources\Articles\Pages\EditArticle;
use App\Filament\Resources\Articles\RelationManagers\VariantsRelationManager;
use App\Models\Article;
use App\Models\ArticleImage;
use App\Models\ArticleVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

const EIXOS = [
    ['name' => 'Cor', 'values' => ['Azul', 'Preto']],
    ['name' => 'Tamanho', 'values' => ['M', 'L']],
];

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());
    $this->article = Article::factory()->create(['variant_axes' => EIXOS]);
});

function editar(Article $article)
{
    return Livewire::test(EditArticle::class, ['record' => $article->getRouteKey()]);
}

function variantes(Article $article)
{
    return Livewire::test(VariantsRelationManager::class, ['ownerRecord' => $article, 'pageClass' => EditArticle::class]);
}

function criar(Article $article, array $data)
{
    return variantes($article)->callTableAction('create', data: $data);
}

function valida(array $extra = []): array
{
    return $extra + ['options' => ['Cor' => 'Azul', 'Tamanho' => 'M'], 'reference' => 'CAM-AZ-M', 'is_active' => true];
}

it('define os eixos e os valores no artigo', function () {
    $novo = Article::factory()->create();

    editar($novo)
        ->fillForm(['variant_axes' => EIXOS])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($novo->fresh()->variant_axes)->toBe(EIXOS);
});

it('exige nome e valores em cada eixo e recusa eixos repetidos', function () {
    editar($this->article)
        ->fillForm(['variant_axes' => [['name' => '', 'values' => []], ['name' => 'Cor', 'values' => ['A']]]])
        ->call('save')
        ->assertHasFormErrors();

    editar($this->article)
        ->fillForm(['variant_axes' => [['name' => 'Cor', 'values' => ['A']], ['name' => 'Cor', 'values' => ['B']]]])
        ->call('save')
        ->assertHasFormErrors();
});

it('cria uma variante com a combinação, referência e imagem do artigo', function () {
    $image = ArticleImage::create(['article_id' => $this->article->id, 'original_path' => 'o', 'thumb_path' => 't', 'screen_path' => 's']);

    criar($this->article, valida(['article_image_id' => $image->id]))->assertHasNoFormErrors();

    $variant = $this->article->variants()->sole();
    expect($variant->options)->toBe(['Cor' => 'Azul', 'Tamanho' => 'M'])
        ->and($variant->reference)->toBe('CAM-AZ-M')
        ->and($variant->article_image_id)->toBe($image->id)
        ->and($variant->is_active)->toBeTrue();
});

it('aceita variante sem imagem', function () {
    criar($this->article, valida())->assertHasNoFormErrors();

    expect($this->article->variants()->sole()->article_image_id)->toBeNull();
});

it('recusa uma combinação repetida no mesmo artigo', function () {
    criar($this->article, valida());
    criar($this->article, valida(['reference' => 'OUTRA']))->assertHasFormErrors();

    expect($this->article->variants()->count())->toBe(1);
});

it('aceita a mesma combinação noutro artigo', function () {
    criar($this->article, valida());
    $outro = Article::factory()->create(['variant_axes' => EIXOS]);

    criar($outro, valida(['reference' => 'OUTRA']))->assertHasNoFormErrors();

    expect(ArticleVariant::count())->toBe(2);
});

it('recusa um valor que o artigo não define', function () {
    criar($this->article, valida(['options' => ['Cor' => 'Verde', 'Tamanho' => 'M']]))->assertHasFormErrors();

    expect(ArticleVariant::count())->toBe(0);
});

it('recusa uma combinação a que falta um eixo', function () {
    criar($this->article, valida(['options' => ['Cor' => 'Azul']]))->assertHasFormErrors();

    expect(ArticleVariant::count())->toBe(0);
});

it('recusa uma referência repetida', function () {
    ArticleVariant::factory()->create(['reference' => 'CAM-AZ-M', 'options' => ['Cor' => 'Preto', 'Tamanho' => 'L']]);

    criar($this->article, valida())->assertHasFormErrors();

    expect($this->article->variants()->count())->toBe(0);
});

it('recusa a imagem de outro artigo', function () {
    $outra = ArticleImage::create(['article_id' => Article::factory()->create()->id, 'original_path' => 'o', 'thumb_path' => 't', 'screen_path' => 's']);

    criar($this->article, valida(['article_image_id' => $outra->id]))->assertHasFormErrors();

    expect(ArticleVariant::count())->toBe(0);
});

it('desactiva uma variante sem a apagar', function () {
    $variant = ArticleVariant::factory()->for($this->article)->create(['options' => ['Cor' => 'Azul', 'Tamanho' => 'M']]);

    variantes($this->article)->call('updateTableColumnState', 'is_active', $variant->getKey(), false);

    expect($variant->fresh())->not->toBeNull()
        ->and($variant->fresh()->is_active)->toBeFalse();
});

it('edita uma variante sem a contar como duplicada de si própria', function () {
    $variant = ArticleVariant::factory()->for($this->article)->create(['options' => ['Cor' => 'Azul', 'Tamanho' => 'M'], 'reference' => 'X1']);

    variantes($this->article)
        ->callTableAction('edit', $variant, data: valida(['reference' => 'X2']))
        ->assertHasNoFormErrors();

    expect($variant->fresh()->reference)->toBe('X2');
});

it('recusa retirar um valor em uso e diz quantas variantes o usam', function () {
    ArticleVariant::factory()->for($this->article)->create(['options' => ['Cor' => 'Azul', 'Tamanho' => 'M'], 'reference' => 'A']);
    ArticleVariant::factory()->for($this->article)->create(['options' => ['Cor' => 'Azul', 'Tamanho' => 'L'], 'reference' => 'B']);

    editar($this->article)
        ->fillForm(['variant_axes' => [
            ['name' => 'Cor', 'values' => ['Preto']],
            ['name' => 'Tamanho', 'values' => ['M', 'L']],
        ]])
        ->call('save')
        ->assertHasFormErrors(['variant_axes' => 'O valor «Azul» do eixo «Cor» está em uso por 2 variantes. Retire-o das variantes primeiro.']);

    expect($this->article->fresh()->variant_axes)->toBe(EIXOS);
});

it('recusa retirar um eixo em uso', function () {
    ArticleVariant::factory()->for($this->article)->create(['options' => ['Cor' => 'Azul', 'Tamanho' => 'M'], 'reference' => 'A']);

    editar($this->article)
        ->fillForm(['variant_axes' => [['name' => 'Cor', 'values' => ['Azul', 'Preto']]]])
        ->call('save')
        ->assertHasFormErrors(['variant_axes']);
});

it('aceita retirar um valor livre e acrescentar valores', function () {
    ArticleVariant::factory()->for($this->article)->create(['options' => ['Cor' => 'Azul', 'Tamanho' => 'M'], 'reference' => 'A']);
    $novos = [
        ['name' => 'Cor', 'values' => ['Azul', 'Verde']],
        ['name' => 'Tamanho', 'values' => ['M']],
    ];

    editar($this->article)->fillForm(['variant_axes' => $novos])->call('save')->assertHasNoFormErrors();

    expect($this->article->fresh()->variant_axes)->toBe($novos);
});

it('apagar o artigo apaga as variantes e apagar a imagem só limpa a ligação', function () {
    $image = ArticleImage::create(['article_id' => $this->article->id, 'original_path' => 'o', 'thumb_path' => 't', 'screen_path' => 's']);
    $variant = ArticleVariant::factory()->for($this->article)->create(['options' => ['Cor' => 'Azul', 'Tamanho' => 'M'], 'article_image_id' => $image->id]);

    $image->delete();
    expect($variant->fresh()->article_image_id)->toBeNull();

    $this->article->delete();
    expect(ArticleVariant::count())->toBe(0);
});

it('aceita eixos com valores numéricos do princípio ao fim', function () {
    $artigo = Article::factory()->create(['variant_axes' => [['name' => 'Nº', 'values' => ['38', '40']]]]);

    criar($artigo, ['options' => ['Nº' => 38], 'reference' => 'N38'])->assertHasNoFormErrors();
    // O Select devolve a chave do array: int quando o valor é numérico (38); testa-se int e string.
    variantes($artigo)->callTableAction('create', data: ['options' => ['Nº' => '40'], 'reference' => 'N40'])->assertHasNoFormErrors();

    expect($artigo->variants()->pluck('options')->all())->toBe([['Nº' => '38'], ['Nº' => '40']]);

    editar($artigo)
        ->fillForm(['variant_axes' => [['name' => 'Nº', 'values' => ['38', '40', '42']]]])
        ->call('save')
        ->assertHasNoFormErrors();
});

it('reconhece uma variante guardada com valor inteiro legado', function () {
    $artigo = Article::factory()->create(['variant_axes' => [['name' => 'Nº', 'values' => ['38', '40']]]]);
    ArticleVariant::factory()->for($artigo)->create(['options' => ['Nº' => 38], 'reference' => 'L']);

    editar($artigo)
        ->fillForm(['variant_axes' => [['name' => 'Nº', 'values' => ['38', '40', '42']]]])
        ->call('save')
        ->assertHasNoFormErrors();

    editar($artigo)
        ->fillForm(['variant_axes' => [['name' => 'Nº', 'values' => ['40']]]])
        ->call('save')
        ->assertHasFormErrors(['variant_axes']);

    variantes($artigo)->callTableAction('create', data: ['options' => ['Nº' => '38'], 'reference' => 'L2'])->assertHasFormErrors();
});

it('recusa a combinação repetida quando os eixos estão por outra ordem que as chaves guardadas', function () {
    // O MySQL reordena as chaves do JSON; com os eixos invertidos, só o ksort torna as duas ordens comparáveis.
    $artigo = Article::factory()->create(['variant_axes' => array_reverse(EIXOS)]);
    ArticleVariant::factory()->for($artigo)->create(['options' => ['Tamanho' => 'M', 'Cor' => 'Azul'], 'reference' => 'A']);

    criar($artigo, valida(['reference' => 'B']))->assertHasFormErrors();

    expect($artigo->variants()->count())->toBe(1);
});

it('não deixa acrescentar um eixo enquanto houver variantes', function () {
    ArticleVariant::factory()->for($this->article)->create(['options' => ['Cor' => 'Azul', 'Tamanho' => 'M'], 'reference' => 'A']);

    editar($this->article)
        ->fillForm(['variant_axes' => [...EIXOS, ['name' => 'Material', 'values' => ['Algodão']]]])
        ->call('save')
        ->assertHasFormErrors(['variant_axes']);

    expect($this->article->fresh()->variant_axes)->toBe(EIXOS);
});

it('recusa nomes de eixo com pontos ou repetidos em maiúsculas/minúsculas, em PT-PT', function () {
    editar($this->article)
        ->fillForm(['variant_axes' => [['name' => 'Peso.kg', 'values' => ['1']]]])
        ->call('save')
        ->assertHasFormErrors(['variant_axes.0.name' => 'O nome do eixo não pode conter pontos.']);

    editar($this->article)
        ->fillForm(['variant_axes' => [['name' => 'Cor', 'values' => ['A']], ['name' => ' cor ', 'values' => ['B']]]])
        ->call('save')
        ->assertHasFormErrors(['variant_axes' => 'Os nomes dos eixos não se podem repetir (maiúsculas e espaços nas pontas não contam).']);
});

it('recusa a combinação repetida quando a ordem por comprimento das chaves difere da alfabética', function () {
    // O MySQL guarda as chaves do JSON por comprimento e depois alfabeticamente (Cor, Alfa); o formulário
    // pode dar outra ordem. Sem o ksort do lado guardado as duas ordens não se igualam.
    $artigo = Article::factory()->create(['variant_axes' => [
        ['name' => 'Cor', 'values' => ['Azul', 'Preto']],
        ['name' => 'Alfa', 'values' => ['X', 'Y']],
    ]]);
    ArticleVariant::factory()->for($artigo)->create(['options' => ['Alfa' => 'X', 'Cor' => 'Azul'], 'reference' => 'A']);

    criar($artigo, ['options' => ['Cor' => 'Azul', 'Alfa' => 'X'], 'reference' => 'B', 'is_active' => true])->assertHasFormErrors();

    expect($artigo->variants()->count())->toBe(1);
});
