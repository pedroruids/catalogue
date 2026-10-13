<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Interruptor de preços da app do vendedor (D11): vale para a sessão inteira e vive no servidor,
 * e abre SEMPRE escondido (D18), online e offline,
 * para que, escondidos, os preços nem cheguem ao HTML (D5). Partilhado pelas páginas que mostram
 * a app-bar (`<x-app.app-bar :show-prices="PriceVisibility::visible()" />`, botão → `togglePrices`).
 */
final class PriceVisibility
{
    private const SESSION_KEY = 'show_prices';

    public static function visible(): bool
    {
        return (bool) session(self::SESSION_KEY, false);
    }

    public static function toggle(): void
    {
        session([self::SESSION_KEY => ! self::visible()]);
    }
}
