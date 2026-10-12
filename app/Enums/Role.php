<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum Role: string implements HasColor, HasLabel
{
    case Admin = 'admin';
    case Vendedor = 'vendedor';

    public function getLabel(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Vendedor => 'Vendedor',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Admin => 'warning',
            self::Vendedor => 'gray',
        };
    }
}
