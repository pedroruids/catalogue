<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ShareStatus: string implements HasColor, HasLabel
{
    case Active = 'active';
    case Expired = 'expired';
    case Revoked = 'revoked';

    public function getLabel(): string
    {
        return match ($this) {
            self::Active => 'Activa',
            self::Expired => 'Expirada',
            self::Revoked => 'Revogada',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Expired => 'gray',
            self::Revoked => 'danger',
        };
    }
}
