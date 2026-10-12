<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\Role;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nome')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->minLength(8)
                    ->helperText(fn (string $operation): ?string => $operation === 'edit' ? 'Deixe em branco para manter a actual.' : null),
                Select::make('role')
                    ->label('Papel')
                    ->options(Role::class)
                    ->default(Role::Vendedor)
                    ->required()
                    ->native(false)
                    // O próprio papel não se muda (o servidor também o ignora).
                    ->disabled(fn (?User $record): bool => $record !== null && $record->is(auth()->user()))
                    ->dehydrated(fn (?User $record): bool => $record === null || ! $record->is(auth()->user())),
            ]);
    }
}
