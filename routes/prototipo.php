<?php

declare(strict_types=1);

/*
| Protótipo estático navegável (sem BD). Só é carregado em `local` — ver bootstrap/app.php.
| Cada rota aceita ?estado= (ver PrototipoDados::ESTADOS) e passa à view $estado e $estados.
*/

use App\Support\PrototipoDados;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

$ecra = fn (string $ecra, array $dados = []) => function (Request $request) use ($ecra, $dados) {
    return view('prototipo.'.$ecra, [
        'estado' => PrototipoDados::estado($ecra, $request->query('estado')),
        'estados' => PrototipoDados::estados($ecra),
        ...$dados,
    ]);
};

Route::prefix('prototipo')->name('prototipo.')->group(function () use ($ecra): void {
    Route::get('/', $ecra('indice'))->name('indice');
    Route::get('login', $ecra('login'))->name('login');
    Route::get('recuperar', $ecra('recuperar'))->name('recuperar');

    Route::get('artigos', $ecra('artigos', ['artigos' => PrototipoDados::artigos()]))->name('artigos');

    Route::get('artigos/{ref}', function (Request $request, string $ref) use ($ecra) {
        return $ecra('artigo', ['ref' => $ref, 'artigo' => PrototipoDados::artigo($ref)])($request);
    })->name('artigo');

    Route::get('partilhar', $ecra('partilhar', [
        'seleccao' => array_map(PrototipoDados::artigo(...), PrototipoDados::SELECCAO_EXEMPLO),
    ]))->name('partilhar');

    Route::get('offline', $ecra('offline'))->name('offline');

    // Página pública: os preços são retirados AQUI, no servidor (D5). O HTML de
    // sem-precos/so-imagens não pode conter nenhum preço.
    Route::get('p/{modo}', function (Request $request, string $modo) use ($ecra) {
        return $ecra('publica', [
            'modo' => $modo,
            'precosPermitidos' => PrototipoDados::precosVisiveisPermitidos($modo),
            'artigos' => PrototipoDados::artigosParaPublica($modo),
            'vendedora' => PrototipoDados::VENDEDORA,
        ])($request);
    })->whereIn('modo', PrototipoDados::MODOS)->name('publica');
});
