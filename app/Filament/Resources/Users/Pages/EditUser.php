<?php

namespace App\Filament\Resources\Users\Pages;

use App\Enums\Role;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Ninguém muda o próprio papel, e o sistema nunca fica sem admin (validado aqui, não só na UI).
        if ($record->is(auth()->user())) {
            unset($data['role']);
        }

        $demotes = isset($data['role']) && $data['role'] !== Role::Admin->value && $record instanceof User && $record->isAdmin();

        if ($demotes && User::query()->where('role', Role::Admin)->whereKeyNot($record->getKey())->doesntExist()) {
            Notification::make()->danger()->title('Tem de existir pelo menos um administrador.')->send();

            $this->halt();
        }

        $record->forceFill($data)->save();

        return $record;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Apagar')
                ->hidden(fn (): bool => $this->getRecord()->is(auth()->user())),
        ];
    }
}
