<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Dados fictícios de desenvolvimento (repo público): nunca usar em produção.
        User::query()->updateOrCreate(['email' => 'admin@maravilha.test'], [
            'name' => 'Administrador',
            'password' => 'password',
            'role' => Role::Admin,
            'email_verified_at' => now(),
        ]);
    }
}
