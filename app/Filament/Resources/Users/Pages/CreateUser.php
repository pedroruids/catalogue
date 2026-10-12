<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /**
     * Contas criadas pelo admin entram na app sem passar por /email/verify.
     * `role` e `email_verified_at` não são mass-assignable de propósito (o registo público não os pode definir),
     * por isso o backoffice atribui-os explicitamente.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $user = new User;
        $user->forceFill($data + ['email_verified_at' => now()])->save();

        return $user;
    }
}
