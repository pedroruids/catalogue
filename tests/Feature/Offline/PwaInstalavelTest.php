<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('a app do vendedor liga o manifest, as meta tags do iOS e o URL do snapshot', function () {
    $this->actingAs(User::factory()->create())->get(route('articles.index'))
        ->assertOk()
        ->assertSee('<link rel="manifest" href="/manifest.webmanifest">', false)
        ->assertSee('name="apple-mobile-web-app-capable"', false)
        ->assertSee('rel="apple-touch-icon" href="/icons/apple-touch-icon-180.png"', false)
        ->assertSee('name="catalogo-snapshot-url" content="/offline/catalogo"', false);
});

it('a app do vendedor identifica o utilizador da cópia local e o logout do /admin apaga-a', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('articles.index'))
        ->assertSee('name="catalogo-utilizador" content="'.$user->id.'"', false);

    // O script que apaga a cópia ao terminar sessão também carrega no Filament
    $this->actingAs(User::factory()->admin()->create())->get('/admin')->assertOk()->assertSee('sair-', false);
    expect(file_get_contents(resource_path('js/offline/sair.js')))->toContain("endsWith('/logout')");
});

it('o manifest é válido e os ícones que declara existem', function () {
    $manifest = json_decode((string) file_get_contents(public_path('manifest.webmanifest')), true, flags: JSON_THROW_ON_ERROR);

    expect($manifest)->toHaveKeys(['name', 'short_name', 'start_url', 'scope', 'display', 'theme_color', 'background_color', 'icons'])
        ->and($manifest['display'])->toBe('standalone')
        ->and($manifest['start_url'])->toBe(route('articles.index', absolute: false));

    $tamanhos = [];
    foreach ($manifest['icons'] as $icon) {
        expect(public_path(ltrim($icon['src'], '/')))->toBeFile();
        $tamanhos[] = $icon['sizes'].'/'.$icon['purpose'];
    }
    expect($tamanhos)->toContain('192x192/any', '512x512/any', '512x512/maskable');
});

it('o service worker não intervém no admin, nas páginas públicas nem em pedidos que não sejam GET', function () {
    $sw = (string) file_get_contents(public_path('sw.js'));

    expect($sw)
        ->toContain("'/admin'")
        ->toContain("'/p/'")
        ->toContain("pedido.method !== 'GET'")
        ->toContain("PREFIXO_IMAGENS = '/storage/'");
});
