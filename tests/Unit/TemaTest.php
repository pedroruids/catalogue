<?php

it('usa no painel Filament a mesma cor de marca dos tokens', function () {
    $tokens = file_get_contents(__DIR__.'/../../resources/css/tokens.css');
    preg_match('/--color-brand:\s*(#[0-9A-Fa-f]{6});/', $tokens, $marca);

    $provider = file_get_contents(__DIR__.'/../../app/Providers/Filament/AdminPanelProvider.php');

    expect($marca[1] ?? null)->not->toBeNull()
        ->and($provider)->toContain("Color::hex('{$marca[1]}')");
});

it('não pede fontes a terceiros no CSS de origem', function () {
    foreach (['tokens.css', 'app.css', 'filament/admin/theme.css'] as $ficheiro) {
        expect(file_get_contents(__DIR__."/../../resources/css/{$ficheiro}"))
            ->not->toMatch('/fonts\.(bunny|googleapis|gstatic)/');
    }
});
