<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum FieldType: string implements HasLabel
{
    case Text = 'text';
    case Number = 'number';
    case Boolean = 'boolean';
    case Select = 'select';

    public function getLabel(): string
    {
        return match ($this) {
            self::Text => 'Texto',
            self::Number => 'Número',
            self::Boolean => 'Sim/não',
            self::Select => 'Lista de opções',
        };
    }
}
