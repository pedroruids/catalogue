<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    // O dashboard do starter kit deu lugar à lista de artigos (Xavier #7); o nome mantém-se.
    Route::redirect('dashboard', 'artigos')->name('dashboard');

    Route::livewire('artigos', 'pages::articles.index')->name('articles.index');
    Route::livewire('artigos/{article}', 'pages::articles.show')->name('articles.show');
});

require __DIR__.'/settings.php';
