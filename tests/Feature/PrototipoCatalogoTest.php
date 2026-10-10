<?php

use Illuminate\Support\Facades\Route;

/**
 * Ecrãs 3 (lista de artigos) e 4 (página do artigo) do protótipo: cada estado dá 200 e mostra o
 * texto que o distingue. As rotas só existem em `local`, por isso o teste simula esse arranque.
 */
beforeEach(function () {
    $this->withoutVite();
    app()->detectEnvironment(fn () => 'local');
    Route::middleware('web')->group(base_path('routes/prototipo.php'));
    app('router')->getRoutes()->refreshNameLookups();
});

test('lista de artigos: cada estado mostra o seu conteúdo', function (string $estado, array $textos, array $ausentes) {
    $resposta = $this->get("/prototipo/artigos?estado={$estado}")->assertOk();

    foreach ($textos as $texto) {
        $resposta->assertSee($texto, false);
    }
    foreach ($ausentes as $texto) {
        $resposta->assertDontSee($texto, false);
    }
})->with([
    'cheio' => ['cheio', ['26 artigos', 'Pesquisar por nome ou referência', 'Tipo de produto', 'Garrafa térmica de aço inox', 'Juntar'], ['Ainda não há artigos no catálogo', 'Não foi possível carregar os artigos']],
    'a-carregar' => ['a-carregar', ['aria-busy="true"'], ['Garrafa térmica de aço inox']],
    'vazio' => ['vazio', ['Ainda não há artigos no catálogo', 'Quando o admin publicar artigos no backoffice'], ['Garrafa térmica de aço inox']],
    'vazio-filtro' => ['vazio-filtro', ['Nenhum artigo encontrado', 'lanyard', 'Limpar filtros', 'Têxtil', 'Dourado'], []],
    'erro' => ['erro', ['Não foi possível carregar os artigos', 'Sem rede e sem catálogo descarregado', 'Tentar outra vez', 'Como usar sem rede'], ['Garrafa térmica de aço inox']],
    'cheio-sem-rede' => ['cheio-sem-rede', ['$store.proto.forcarOffline()', 'Precisa de rede', 'Garrafa térmica de aço inox'], ['Não foi possível carregar os artigos']],
]);

test('página do artigo: cada estado mostra o seu conteúdo', function (string $estado, array $textos, array $ausentes) {
    $resposta = $this->get("/prototipo/artigos/MRV-1005?estado={$estado}")->assertOk();

    foreach ($textos as $texto) {
        $resposta->assertSee($texto, false);
    }
    foreach ($ausentes as $texto) {
        $resposta->assertDontSee($texto, false);
    }
})->with([
    'cheio' => ['cheio', ['Garrafa térmica de aço inox', 'Ref. MRV-1005', '1 / 6', 'Preço por quantidade', 'Quantidade mínima', 'Preços escondidos', 'Características', '<dl', 'Partilhar precisa de rede'], ['Este artigo já não está no catálogo', 'Sem imagens']],
    'a-carregar' => ['a-carregar', ['aria-busy="true"'], ['Ref. MRV-1005']],
    'vazio' => ['vazio', ['Sem imagens', 'Preço sob consulta', 'Ref. MRV-1005'], ['1 / 6']],
    'erro' => ['erro', ['Este artigo já não está no catálogo', 'Voltar aos artigos'], ['Ref. MRV-1005']],
]);

test('ref inexistente dá o estado de erro', function () {
    $this->get('/prototipo/artigos/MRV-9999')
        ->assertOk()
        ->assertSee('Este artigo já não está no catálogo')
        ->assertDontSee('Preço por quantidade');
});

test('os escalões vêm da variante (MRV-1005: 750 ml custa mais, Branco 750 ml sem escalões)', function () {
    $html = $this->get('/prototipo/artigos/MRV-1005')->assertOk()->getContent();

    expect($html)->toContain('7,90')->toContain('9,88');
    // A variante sem escalões vai vazia para o cliente → «Preço sob consulta».
    expect($html)->toContain('Preço sob consulta');
});
