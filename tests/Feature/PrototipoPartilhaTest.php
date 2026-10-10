<?php

use Illuminate\Support\Facades\Route;

/**
 * Ecrãs 5 (partilhar), 6 (offline) e 7 (página pública): cada estado abre e mostra o seu texto-chave;
 * a página pública sem preços não leva «€» em nenhum estado (D5).
 * Nome de helper próprio: o do PrototipoTest.php não pode ser redeclarado.
 */
function prototipoPartilhaEmLocal(): void
{
    app()->detectEnvironment(fn () => 'local');
    Route::middleware('web')->group(base_path('routes/prototipo.php'));
    app('router')->getRoutes()->refreshNameLookups();
}

beforeEach(function () {
    $this->withoutVite();
    prototipoPartilhaEmLocal();
});

test('partilhar: cada estado dá 200 e mostra o texto-chave', function (string $estado, string $texto) {
    $this->get("/prototipo/partilhar?estado={$estado}")->assertOk()->assertSee($texto, false);
})->with([
    ['form', 'O que o cliente vê'],
    ['a-carregar', 'A gerar ligação…'],
    ['erro-sem-rede', 'Partilhar precisa de rede.'],
    ['erro-falhou', 'Não foi possível gerar a ligação.'],
    ['vazio', 'Ainda não escolheu artigos'],
    ['gerada', 'Ligação pronta'],
]);

test('partilhar: omissões da D10 (sem preços, 7 dias) e URL de exemplo', function () {
    $html = $this->get('/prototipo/partilhar')->assertOk()->getContent();

    expect($html)
        ->toMatch('/value="sem-precos"[^>]*checked/')
        ->toContain('Expira a')
        ->toContain(now()->addDays(7)->format('d/m/Y'))
        ->toContain('example.com');
});

test('offline: cada estado dá 200 e mostra o texto-chave', function (string $estado, string $texto) {
    $this->get("/prototipo/offline?estado={$estado}")->assertOk()
        ->assertSee($texto)
        ->assertSee('Partilhar e juntar à selecção precisam de rede.');
})->with([
    ['vazio', 'Ainda não descarregou o catálogo'],
    ['a-carregar', 'A descarregar…'],
    ['interrompida', 'A descarga foi interrompida'],
    ['sem-espaco', 'Sem espaço no dispositivo'],
    ['cheio', 'Catálogo descarregado'],
    ['cheio-sem-rede', 'A usar a cópia do dispositivo'],
]);

test('offline: a descarga em curso tem barra de progresso acessível', function () {
    $this->get('/prototipo/offline?estado=a-carregar')->assertOk()
        ->assertSee('role="progressbar" aria-valuenow="62"', false);
});

$estadosPublica = [
    ['cheio', 'Rita Sousa'],
    ['a-carregar', 'aria-busy="true"'],
    ['vazio', 'Estes artigos já não estão disponíveis'],
    ['expirada', 'Esta ligação expirou'],
    ['revogada', 'Esta ligação já não está activa'],
    ['inexistente', 'Não encontrámos esta ligação'],
];

test('pública: cada estado dá 200 e mostra o texto-chave, em cada modo', function (string $modo, string $estado, string $texto) {
    $this->get("/prototipo/p/{$modo}?estado={$estado}")->assertOk()->assertSee($texto, false);
})->with(['com-precos', 'sem-precos', 'so-imagens'])->with($estadosPublica);

test('pública sem preços e só imagens: nenhum «€» em nenhum estado', function (string $modo, string $estado) {
    $html = $this->get("/prototipo/p/{$modo}?estado={$estado}")->assertOk()->getContent();

    expect($html)->not->toContain('€');
})->with(['sem-precos', 'so-imagens'])->with(array_column($estadosPublica, 0));

test('pública com preços: mostra a tabela de preços', function () {
    expect($this->get('/prototipo/p/com-precos')->getContent())
        ->toContain('€')
        ->toContain('Preço unitário')
        ->toContain('desde 50 un.');
});

test('pública: erros sem dados nem intro', function (string $estado) {
    $this->get("/prototipo/p/com-precos?estado={$estado}")->assertOk()
        ->assertDontSee('Veja os artigos</h1>', false)
        ->assertDontSee('Ref. MRV-');
})->with(['expirada', 'revogada', 'inexistente', 'vazio']);

test('pública só imagens: nome de cada artigo na galeria (D11)', function () {
    $this->get('/prototipo/p/so-imagens')->assertOk()
        ->assertSee('<figcaption', false)
        ->assertSee('Garrafa térmica de aço inox');
});
