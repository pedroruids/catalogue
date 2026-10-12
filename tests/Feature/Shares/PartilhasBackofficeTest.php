<?php

use App\Actions\CreateShare;
use App\Filament\Resources\Shares\Pages\ListShares;
use App\Models\Article;
use App\Models\Share;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('o admin lista as partilhas sem o token', function () {
    $seller = User::factory()->create(['name' => 'Marta Vendedora']);
    $article = Article::factory()->published()->create();
    $result = app(CreateShare::class)->handle($seller, [$article->id]);

    $this->actingAs(User::factory()->admin()->create());

    $html = Livewire::test(ListShares::class)
        ->assertCanSeeTableRecords([$result['share']])
        ->assertSee('Marta Vendedora')
        ->html();

    expect($html)->not->toContain(basename($result['url']))->not->toContain($result['share']->token_hash);
});

it('o admin revoga com confirmação e a partilha não se apaga', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);
    $share = Share::factory()->create();

    Livewire::test(ListShares::class)->callTableAction('revoke', $share);

    $share->refresh();
    expect($share->revoked_at)->not->toBeNull()
        ->and($share->revoked_by)->toBe($admin->id)
        ->and(Share::count())->toBe(1);

    Livewire::test(ListShares::class)->assertTableActionHidden('revoke', $share);
});

it('filtra por estado', function () {
    $this->actingAs(User::factory()->admin()->create());
    $active = Share::factory()->create();
    $expired = Share::factory()->create(['expires_at' => now()->subDay()]);
    $revoked = Share::factory()->create(['revoked_at' => now()]);

    Livewire::test(ListShares::class)->filterTable('status', 'active')
        ->assertCanSeeTableRecords([$active])->assertCanNotSeeTableRecords([$expired, $revoked]);
    Livewire::test(ListShares::class)->filterTable('status', 'expired')
        ->assertCanSeeTableRecords([$expired])->assertCanNotSeeTableRecords([$active, $revoked]);
    Livewire::test(ListShares::class)->filterTable('status', 'revoked')
        ->assertCanSeeTableRecords([$revoked])->assertCanNotSeeTableRecords([$active, $expired]);
});

it('o estado do modelo distingue activa, expirada e revogada', function () {
    expect(Share::factory()->make()->isActive())->toBeTrue()
        ->and(Share::factory()->make(['expires_at' => now()->subDay()])->isExpired())->toBeTrue()
        ->and(Share::factory()->make(['revoked_at' => now()])->isRevoked())->toBeTrue();
});

it('o vendedor não entra no backoffice nem vê as partilhas', function () {
    $this->actingAs(User::factory()->create());

    $this->get('/admin/shares')->assertForbidden();
});
