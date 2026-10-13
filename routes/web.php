<?php

use App\Http\Controllers\Offline\CatalogSnapshotController;
use App\Http\Controllers\Shares\ShowController as ShowShareController;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

Route::view('/', 'welcome')->name('home');

// Shell offline (Onda 5b, Xavier #13): página estática que o service worker guarda e serve no lugar das
// rotas do vendedor quando a rede falha. Sem sessão nem cookies, igual para todos: nunca leva dados de ninguém.
Route::view('offline', 'offline.shell')
    ->withoutMiddleware([EncryptCookies::class, AddQueuedCookiesToResponse::class, StartSession::class, ShareErrorsFromSession::class, PreventRequestForgery::class])
    ->name('offline.shell');

Route::middleware(['auth', 'verified'])->group(function () {
    // O dashboard do starter kit deu lugar à lista de artigos (Xavier #7); o nome mantém-se.
    Route::redirect('dashboard', 'artigos')->name('dashboard');

    Route::livewire('artigos', 'pages::articles.index')->name('articles.index');
    Route::livewire('artigos/{article}', 'pages::articles.show')->name('articles.show');
    // Cópia local do catálogo (Onda 5, Xavier #10): snapshot JSON versionado por ETag.
    Route::get('offline/catalogo', CatalogSnapshotController::class)->name('offline.catalog');

    Route::livewire('partilhar', 'pages::shares.create')->name('shares.create');
});

require __DIR__.'/settings.php';

// Página pública de uma partilha (sem login); o token é o segredo e o limite é por IP.
// Sem sessão nem cookies: uma visita anónima (ou de um robô) não grava nada na tabela de sessões.
Route::get('p/{token}', ShowShareController::class)
    ->middleware('throttle:60,1')
    ->withoutMiddleware([EncryptCookies::class, AddQueuedCookiesToResponse::class, StartSession::class, ShareErrorsFromSession::class, PreventRequestForgery::class])
    ->name('shares.show');
