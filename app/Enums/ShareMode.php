<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ShareMode: string implements HasLabel
{
    case WithPrices = 'with_prices';
    case WithoutPrices = 'without_prices';
    case ImagesOnly = 'images_only';

    public function getLabel(): string
    {
        return match ($this) {
            self::WithPrices => 'Com preços',
            self::WithoutPrices => 'Sem preços',
            self::ImagesOnly => 'Só imagens',
        };
    }

    public function showsPrices(): bool
    {
        return $this === self::WithPrices;
    }
}
