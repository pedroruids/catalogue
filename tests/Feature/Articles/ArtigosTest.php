<?php

use App\Enums\ArticleStatus;
use App\Enums\FieldType;
use App\Filament\Resources\Articles\Pages\CreateArticle;
use App\Filament\Resources\Articles\Pages\EditArticle;
use App\Filament\Resources\Articles\Pages\ListArticles;
use App\Filament\Resources\ProductTypes\Pages\EditProductType;
use App\Models\Article;
use App\Models\FieldDefinition;
use App\Models\ProductType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());

    $this->canetas = ProductType::factory()->create(['name' => 'Canetas']);
    FieldDefinition::factory()->for($this->canetas)->required()->create(['name' => 'Material', 'position' => 1]);
    FieldDefinition::factory()->for($this->canetas)->create(['name' => 'Tinta', 'data_type' => FieldType::Select, 'options' => ['Azul', 'Preta'], 'position' => 2]);
    FieldDefinition::factory()->for($this->canetas)->create(['name' => 'Recarregável', 'data_type' => FieldType::Boolean, 'position' => 3]);
    FieldDefinition::factory()->for($this->canetas)->create(['name' => 'Comprimento', 'data_type' => FieldType::Number, 'position' => 4]);

    $this->canecas = ProductType::factory()->create(['name' => 'Canecas']);
    FieldDefinition::factory()->for($this->canecas)->create(['name' => 'Material', 'position' => 1]);
    FieldDefinition::factory()->for($this->canecas)->create(['name' => 'Capacidade', 'data_type' => FieldType::Number, 'position' => 2]);
});

it('lista os artigos', function () {
    $artigos = Article::factory()->for($this->canetas)->count(2)->create();

    Livewire::test(ListArticles::class)->assertCanSeeTableRecords($artigos);
});

it('cria um artigo e grava os valores dos campos do tipo', function () {
    Livewire::test(CreateArticle::class)
        ->fillForm([
            'product_type_id' => $this->canetas->id,
            'name' => 'Caneta Bic',
            'reference' => 'CAN-001',
            'description' => 'Clássica.',
            'status' => ArticleStatus::Published->value,
        ])
        ->fillForm([
            'field_values' => ['material' => 'Plástico', 'tinta' => 'Azul', 'recarregavel' => true, 'comprimento' => '14.5'],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $a = Article::query()->where('reference', 'CAN-001')->firstOrFail();

    expect($a->status)->toBe(ArticleStatus::Published)
        ->and($a->field_values)->toEqual(['material' => 'Plástico', 'tinta' => 'Azul', 'recarregavel' => true, 'comprimento' => 14.5]);
});

it('mostra no formulário os campos do tipo escolhido', function () {
    Livewire::test(CreateArticle::class)
        ->fillForm(['product_type_id' => $this->canetas->id])
        ->assertFormFieldExists('field_values.material')
        ->assertFormFieldExists('field_values.tinta')
        ->assertFormFieldDoesNotExist('field_values.capacidade');
});

it('impede gravar com campo obrigatório em falta, com mensagem por campo', function () {
    Livewire::test(CreateArticle::class)
        ->fillForm([
            'product_type_id' => $this->canetas->id,
            'name' => 'Caneta',
            'reference' => 'CAN-002',
        ])
        ->call('create')
        ->assertHasFormErrors(['field_values.material' => 'required']);

    expect(Article::query()->count())->toBe(0);
});

it('exige nome, referência única e tipo', function () {
    Article::factory()->for($this->canetas)->create(['reference' => 'DUP-1']);

    Livewire::test(CreateArticle::class)
        ->fillForm(['name' => null, 'reference' => 'DUP-1'])
        ->call('create')
        ->assertHasFormErrors(['product_type_id' => 'required', 'name' => 'required', 'reference' => 'unique']);
});

it('ao mudar o tipo avisa e, ao gravar, perde os valores dos campos inexistentes', function () {
    $a = Article::factory()->for($this->canetas)->create([
        'field_values' => ['material' => 'Metal', 'tinta' => 'Preta', 'comprimento' => 12],
    ]);

    Livewire::test(EditArticle::class, ['record' => $a->getRouteKey()])
        ->fillForm(['product_type_id' => $this->canecas->id])
        ->assertNotified('Valores que se vão perder')
        ->call('save')
        ->assertHasNoFormErrors();

    // "material" existe nas canecas (mesma chave) e fica; "tinta" e "comprimento" perdem-se.
    expect($a->refresh()->product_type_id)->toBe($this->canecas->id)
        ->and($a->field_values)->toBe(['material' => 'Metal']);
});

it('não avisa quando não há valores a perder', function () {
    $a = Article::factory()->for($this->canetas)->create(['field_values' => ['material' => 'Metal']]);

    Livewire::test(EditArticle::class, ['record' => $a->getRouteKey()])
        ->fillForm(['product_type_id' => $this->canecas->id])
        ->assertNotNotified('Valores que se vão perder');
});

it('descarta valores de opção inválida ou de tipo errado', function () {
    $tipo = $this->canetas;

    expect($tipo->normalizeFieldValues([
        'material' => '  Metal ', 'tinta' => 'Verde', 'comprimento' => 'abc', 'recarregavel' => false, 'extra' => 'x',
    ]))->toBe(['material' => 'Metal', 'recarregavel' => false]);
});

it('apagar um campo com valores pede confirmação, diz quantos artigos afecta e limpa os valores', function () {
    $material = $this->canetas->fieldDefinitions()->where('key', 'material')->firstOrFail();
    Article::factory()->for($this->canetas)->count(2)->create(['field_values' => ['material' => 'Metal', 'tinta' => 'Azul']]);
    Article::factory()->for($this->canetas)->create(['field_values' => ['tinta' => 'Preta']]);

    expect($material->articlesWithValueCount())->toBe(2);

    $form = Livewire::test(EditProductType::class, ['record' => $this->canetas->getRouteKey()]);
    $item = 'record-'.$material->id;

    $form->mountFormComponentAction('fieldDefinitions', 'delete', ['item' => $item]);
    $acao = $form->instance()->getMountedAction();
    expect($acao->isConfirmationRequired())->toBeTrue()
        ->and((string) $acao->getModalDescription())->toContain('2 artigos');

    // confirma: a acção corre e o campo sai do formulário; ao gravar apaga-se.
    $form->callMountedAction()->call('save')->assertHasNoFormErrors();

    expect(FieldDefinition::query()->whereKey($material->id)->exists())->toBeFalse()
        ->and(Article::query()->whereNotNull('field_values->material')->count())->toBe(0)
        ->and(Article::query()->whereNotNull('field_values->tinta')->count())->toBe(3);
});

it('apagar um campo sem valores não pede confirmação', function () {
    $campo = $this->canecas->fieldDefinitions()->where('key', 'capacidade')->firstOrFail();

    expect($campo->articlesWithValueCount())->toBe(0);

    $form = Livewire::test(EditProductType::class, ['record' => $this->canecas->getRouteKey()]);
    $item = 'record-'.$campo->id;
    $form->mountFormComponentAction('fieldDefinitions', 'delete', ['item' => $item]);

    expect($form->instance()->getMountedAction()->isConfirmationRequired())->toBeFalse();
});

it('um tipo com artigos não se apaga; sem artigos apaga-se', function () {
    Article::factory()->for($this->canetas)->create();

    Livewire::test(EditProductType::class, ['record' => $this->canetas->getRouteKey()])
        ->callAction('delete')
        ->assertNotified('Não é possível apagar este tipo');
    expect(ProductType::query()->whereKey($this->canetas->id)->exists())->toBeTrue();

    Livewire::test(EditProductType::class, ['record' => $this->canecas->getRouteKey()])->callAction('delete');
    expect(ProductType::query()->whereKey($this->canecas->id)->exists())->toBeFalse();
});

it('o vendedor não acede aos artigos', function () {
    $this->actingAs(User::factory()->create())->get('/admin/articles')->assertForbidden();
});
