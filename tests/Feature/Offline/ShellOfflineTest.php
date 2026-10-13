<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('o shell offline abre sem login e é estático: sem sessão, sem cookies e sem token CSRF', function () {
    $resposta = $this->get(route('offline.shell'))
        ->assertOk()
        ->assertSee('id="offline-raiz"', false)
        ->assertSee('data-app', false)
        ->assertDontSee('csrf-token', false)
        ->assertDontSee('name="_token"', false);

    expect($resposta->headers->getCookies())->toBeEmpty()
        ->and($resposta->headers->get('Cache-Control'))->not->toContain('public');
});

it('o shell é o mesmo para toda a gente: nunca leva dados do utilizador autenticado', function () {
    $user = User::factory()->create(['name' => 'Ana Segredo', 'email' => 'ana.segredo@example.test']);

    $autenticado = $this->actingAs($user)->get(route('offline.shell'))->assertOk()->getContent();

    expect($autenticado)->not->toContain('Ana Segredo')
        ->and($autenticado)->not->toContain('ana.segredo@example.test')
        ->and($autenticado)->not->toContain('catalogo-utilizador');
});

it('o shell carrega o JS do ecrã offline e os ícones que as páginas online usam', function () {
    $html = $this->get(route('offline.shell'))->assertOk()->getContent();

    expect($html)->toContain('/build/assets/shell-')
        ->and($html)->toContain('rel="manifest"');

    foreach (['lupa', 'saco', 'olho', 'olho-cortado', 'utilizador', 'chevron-esquerda', 'sem-wifi', 'foto'] as $icone) {
        expect($html)->toContain('<template id="icone-'.$icone.'">');
    }
});

it('o service worker serve o shell só quando a rede falha, só nas rotas do vendedor e nunca guarda HTML do servidor', function () {
    $sw = (string) file_get_contents(public_path('sw.js'));

    expect($sw)
        ->toContain("const SHELL = '/offline'")
        ->toContain("pedido.mode === 'navigate'")
        ->toContain('/^\/artigos\/?$/')
        ->toContain('/^\/artigos\/\d+\/?$/')
        // rede primeiro: o shell só está no catch
        ->toMatch('/try \{\s*const resposta = await fetch\(evento\.request\);.*?return resposta;\s*\} catch \{.*?cache\.match\(SHELL/s')
        // a cache de navegação só recebe o shell (e os assets dele), nunca a resposta de um pedido do vendedor
        ->not->toContain('cache.put(evento.request')
        ->not->toContain('cache.put(pedido')
        ->toContain('cache.put(SHELL, resposta)')
        // versões antigas saem ao activar
        ->toContain('await caches.delete(nome)')
        ->toContain("'maravilha-shell-'");
});

it('o service worker continua fora do admin, das partilhas públicas, do Livewire e de tudo o que não é GET', function () {
    $sw = (string) file_get_contents(public_path('sw.js'));

    expect($sw)
        ->toContain("const FORA_DO_AMBITO = ['/admin', '/p/', '/livewire']")
        ->toContain("if (pedido.method !== 'GET') return;")
        ->toContain('if (FORA_DO_AMBITO.some');
});

it('a cópia local continua a ser apagada por inteiro no logout, incluindo o estado do interruptor offline', function () {
    $catalogo = (string) file_get_contents(resource_path('js/offline/catalogo.js'));

    expect($catalogo)
        ->toContain('indexedDB.deleteDatabase(BD)')
        ->toContain('caches.delete(CACHE_IMAGENS)')
        ->toContain('sessionStorage.removeItem(CHAVE_PRECOS)');
});
