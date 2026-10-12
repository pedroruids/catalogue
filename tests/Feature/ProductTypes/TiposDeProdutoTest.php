<?php

use App\Enums\FieldType;
use App\Filament\Resources\ProductTypes\Pages\CreateProductType;
use App\Filament\Resources\ProductTypes\Pages\EditProductType;
use App\Filament\Resources\ProductTypes\Pages\ListProductTypes;
use App\Models\FieldDefinition;
use App\Models\ProductType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->actingAs(User::factory()->admin()->create()));

it('lista os tipos de produto', function () {
    $tipos = ProductType::factory()->count(2)->create();

    Livewire::test(ListProductTypes::class)->assertCanSeeTableRecords($tipos);
});

it('mostra os campos por omissão num tipo novo e grava-os', function () {
    $form = Livewire::test(CreateProductType::class);
    $campos = collect($form->get('data.fieldDefinitions'))->pluck('name')->all();

    expect($campos)->toBe(collect(ProductType::DEFAULT_FIELDS)->pluck('name')->all());

    $form->fillForm(['name' => 'Canetas'])->call('create')->assertHasNoFormErrors();

    $tipo = ProductType::query()->where('name', 'Canetas')->firstOrFail();
    expect($tipo->fieldDefinitions->pluck('name')->all())->toBe($campos);
});

it('permite apagar os campos por omissão antes de gravar', function () {
    $form = Livewire::test(CreateProductType::class);
    $chaves = array_keys($form->get('data.fieldDefinitions'));

    $form->fillForm(['name' => 'Sem campos', 'fieldDefinitions' => []])->call('create')->assertHasNoFormErrors();

    expect(ProductType::query()->where('name', 'Sem campos')->firstOrFail()->fieldDefinitions)->toHaveCount(0)
        ->and($chaves)->not->toBeEmpty();
});

it('cria um tipo com campos de vários tipos de dado, ordenados', function () {
    Livewire::test(CreateProductType::class)
        ->fillForm([
            'name' => 'Mochilas',
            'fieldDefinitions' => [
                ['name' => 'Capacidade (L)', 'data_type' => 'number', 'required' => true],
                ['name' => 'Impermeável', 'data_type' => 'boolean', 'required' => false],
                ['name' => 'Fecho', 'data_type' => 'select', 'required' => false, 'options' => ['Zip', 'Velcro']],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $campos = ProductType::query()->where('name', 'Mochilas')->firstOrFail()->fieldDefinitions;

    expect($campos->pluck('key')->all())->toBe(['capacidade_l', 'impermeavel', 'fecho'])
        ->and($campos->pluck('data_type')->all())->toBe([FieldType::Number, FieldType::Boolean, FieldType::Select])
        ->and($campos[0]->required)->toBeTrue()
        ->and($campos[2]->options)->toBe(['Zip', 'Velcro'])
        ->and($campos->pluck('position')->all())->toBe([1, 2, 3]);
});

it('exige opções num campo de lista', function () {
    Livewire::test(CreateProductType::class)
        ->fillForm([
            'name' => 'X',
            'fieldDefinitions' => [['name' => 'Fecho', 'data_type' => 'select', 'required' => false, 'options' => []]],
        ])
        ->call('create')
        ->assertHasFormErrors();
});

it('recusa nomes de tipo repetidos e nomes de campo repetidos', function () {
    ProductType::factory()->create(['name' => 'Canetas']);

    Livewire::test(CreateProductType::class)
        ->fillForm(['name' => 'Canetas'])
        ->call('create')
        ->assertHasFormErrors(['name' => 'unique']);

    Livewire::test(CreateProductType::class)
        ->fillForm(['name' => 'Novo', 'fieldDefinitions' => [
            ['name' => 'Cor', 'data_type' => 'text', 'required' => false],
            ['name' => 'Cor', 'data_type' => 'text', 'required' => false],
        ]])
        ->call('create')
        ->assertHasFormErrors();
});

it('edita o nome e os campos, e a chave do campo não muda ao renomear', function () {
    $tipo = ProductType::factory()->create(['name' => 'Canetas']);
    $campo = FieldDefinition::factory()->for($tipo)->create(['name' => 'Cor da tinta', 'position' => 1]);

    $form = Livewire::test(EditProductType::class, ['record' => $tipo->getRouteKey()]);
    $itens = $form->get('data.fieldDefinitions');
    $uuid = array_key_first($itens);
    $itens[$uuid]['name'] = 'Tinta';
    $itens[$uuid]['required'] = true;

    $form->fillForm(['name' => 'Esferográficas', 'fieldDefinitions' => $itens])->call('save')->assertHasNoFormErrors();

    expect($tipo->refresh()->name)->toBe('Esferográficas')
        ->and($campo->refresh()->name)->toBe('Tinta')
        ->and($campo->key)->toBe('cor_da_tinta')
        ->and($campo->required)->toBeTrue();
});

it('não deixa mudar o tipo de dado de um campo existente', function () {
    $tipo = ProductType::factory()->create();
    $campo = FieldDefinition::factory()->for($tipo)->create(['data_type' => FieldType::Text]);

    $form = Livewire::test(EditProductType::class, ['record' => $tipo->getRouteKey()]);
    $itens = $form->get('data.fieldDefinitions');
    $itens[array_key_first($itens)]['data_type'] = 'number';

    $form->fillForm(['fieldDefinitions' => $itens])->call('save');

    expect($campo->refresh()->data_type)->toBe(FieldType::Text);
});

it('apaga um campo e apaga um tipo (com os campos)', function () {
    $tipo = ProductType::factory()->create();
    FieldDefinition::factory()->for($tipo)->count(2)->create();

    $form = Livewire::test(EditProductType::class, ['record' => $tipo->getRouteKey()])
        ->fillForm(['fieldDefinitions' => []])->call('save');
    expect($tipo->fieldDefinitions()->count())->toBe(0);

    Livewire::test(EditProductType::class, ['record' => $tipo->getRouteKey()])
        ->callAction('delete');
    expect(ProductType::query()->count())->toBe(0);
});

it('gera chaves únicas dentro do tipo', function () {
    $tipo = ProductType::factory()->create();
    $a = FieldDefinition::factory()->for($tipo)->create(['name' => 'Cor']);
    $b = FieldDefinition::factory()->for($tipo)->create(['name' => 'COR']);

    expect([$a->key, $b->key])->toBe(['cor', 'cor_2']);
});

it('o vendedor não acede aos tipos de produto', function () {
    $this->actingAs(User::factory()->create())->get('/admin/product-types')->assertForbidden();
});
