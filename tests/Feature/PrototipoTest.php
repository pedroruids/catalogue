<?php

use App\Support\PrototipoDados;
use Illuminate\Support\Facades\Route;

/**
 * As rotas do protótipo só são registadas em `local` (bootstrap/app.php).
 * Os testes correm em `testing`, por isso simulam o arranque em local.
 */
function carregarPrototipoEmLocal(): void
{
    app()->detectEnvironment(fn () => 'local');
    Route::middleware('web')->group(base_path('routes/prototipo.php'));
    app('router')->getRoutes()->refreshNameLookups();
}

$rotas = [
    '/prototipo',
    '/prototipo/login',
    '/prototipo/recuperar',
    '/prototipo/artigos',
    '/prototipo/artigos/MRV-1005',
    '/prototipo/partilhar',
    '/prototipo/offline',
    '/prototipo/p/com-precos',
    '/prototipo/p/sem-precos',
    '/prototipo/p/so-imagens',
];

beforeEach(fn () => $this->withoutVite());

test('cada ecrã do protótipo dá 200 em local', function (string $url) {
    carregarPrototipoEmLocal();

    $this->get($url)->assertOk();
    $this->get($url.'?estado=erro')->assertOk();
})->with($rotas);

test('a página pública sem preços não leva nenhum preço no HTML', function (string $modo) {
    carregarPrototipoEmLocal();

    $html = $this->get("/prototipo/p/{$modo}")->assertOk()->getContent();

    expect($html)->not->toContain('€');
})->with(['sem-precos', 'so-imagens']);

test('a página pública com preços mostra os preços (controlo do teste anterior)', function () {
    carregarPrototipoEmLocal();

    expect($this->get('/prototipo/p/com-precos')->getContent())->toContain('€');
});

test('os dados da página pública já vêm sem preços do servidor', function () {
    $artigos = PrototipoDados::artigosParaPublica('sem-precos');

    expect(json_encode($artigos))->not->toContain('preco')->not->toContain('"desde":1');
    expect(collect($artigos)->pluck('desde')->filter())->toBeEmpty();
});

test('modo desconhecido da página pública dá 404', function () {
    carregarPrototipoEmLocal();

    $this->get('/prototipo/p/tudo')->assertNotFound();
});

test('fora de local as rotas do protótipo dão 404', function (string $url) {
    expect(app()->environment('local'))->toBeFalse();

    $this->get($url)->assertNotFound();
})->with($rotas);
