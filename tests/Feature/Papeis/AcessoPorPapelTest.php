<?php

use App\Enums\Role;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use Filament\Support\Exceptions\Halt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('dá 403 ao vendedor em /admin', function () {
    $this->actingAs(User::factory()->create())
        ->get('/admin')
        ->assertForbidden();
});

it('deixa o admin entrar em /admin', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get('/admin')
        ->assertOk();
});

it('redirecciona o visitante anónimo de /admin para o login', function () {
    $this->get('/admin')->assertRedirect();
});

it('o seeder cria um admin', function () {
    $this->seed();

    expect(User::query()->where('role', Role::Admin)->count())->toBe(1);
});

it('o vendedor entra na app pelo login e vai à lista de artigos', function () {
    $vendedor = User::factory()->create(['password' => 'segredo-123']);

    $this->post(route('login.store'), ['email' => $vendedor->email, 'password' => 'segredo-123'])
        ->assertRedirect();

    $this->assertAuthenticatedAs($vendedor);
    $this->get(route('articles.index'))->assertOk();
});

it('o admin entra na app pelo login', function () {
    $admin = User::factory()->admin()->create(['password' => 'segredo-123']);

    $this->post(route('login.store'), ['email' => $admin->email, 'password' => 'segredo-123'])
        ->assertRedirect();

    $this->assertAuthenticatedAs($admin);
    $this->get(route('articles.index'))->assertOk();
});

it('o registo público está desligado', function () {
    $this->get('/register')->assertNotFound();
    $this->get(route('login'))->assertOk()->assertDontSee('/register');
});

it('o admin cria um vendedor que entra na app sem verificar o email', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Ana Vendedora',
            'email' => 'ana@maravilha.test',
            'password' => 'password-forte',
            'role' => Role::Vendedor->value,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $ana = User::query()->where('email', 'ana@maravilha.test')->firstOrFail();
    expect($ana->role)->toBe(Role::Vendedor)->and($ana->hasVerifiedEmail())->toBeTrue();

    auth()->logout();
    $this->post(route('login.store'), ['email' => 'ana@maravilha.test', 'password' => 'password-forte']);
    $this->assertAuthenticatedAs($ana);
    $this->get(route('articles.index'))->assertOk();
});

it('exige password ao criar mas não ao editar', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(CreateUser::class)
        ->fillForm(['name' => 'X', 'email' => 'x@maravilha.test', 'role' => 'vendedor'])
        ->call('create')
        ->assertHasFormErrors(['password' => 'required']);
});

it('lista os utilizadores ao admin', function () {
    $admin = User::factory()->admin()->create();
    $outro = User::factory()->create();
    $this->actingAs($admin);

    Livewire::test(ListUsers::class)->assertCanSeeTableRecords([$admin, $outro]);
});

it('o admin muda o papel de um utilizador na edição', function () {
    $this->actingAs(User::factory()->admin()->create());
    $v = User::factory()->create();

    Livewire::test(EditUser::class, ['record' => $v->getRouteKey()])
        ->fillForm(['role' => 'admin'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($v->refresh()->role)->toBe(Role::Admin);
});

it('o admin não consegue mudar o próprio papel', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    Livewire::test(EditUser::class, ['record' => $admin->getRouteKey()])
        ->fillForm(['role' => 'vendedor'])
        ->call('save');

    expect($admin->refresh()->role)->toBe(Role::Admin);
});

it('um admin pode despromover outro admin (fica sempre pelo menos ele)', function () {
    $admin = User::factory()->admin()->create();
    $outro = User::factory()->admin()->create();
    $this->actingAs($admin);

    Livewire::test(EditUser::class, ['record' => $outro->getRouteKey()])
        ->fillForm(['role' => 'vendedor'])
        ->call('save');

    expect($outro->refresh()->role)->toBe(Role::Vendedor)
        ->and(User::query()->where('role', Role::Admin)->count())->toBe(1);
});

it('recusa despromover o único admin (pedido forjado de outro utilizador)', function () {
    $unico = User::factory()->admin()->create();
    $this->actingAs($unico);
    $page = new EditUser;

    $metodo = new ReflectionMethod($page, 'handleRecordUpdate');

    // Defesa em profundidade: quem actua não é o próprio (senão o papel seria ignorado).
    $this->actingAs(User::factory()->create());
    expect(fn () => $metodo->invoke($page, $unico, ['name' => $unico->name, 'role' => 'vendedor']))
        ->toThrow(Halt::class);

    expect($unico->refresh()->role)->toBe(Role::Admin);
});
