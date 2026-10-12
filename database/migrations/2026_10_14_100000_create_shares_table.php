<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shares', function (Blueprint $table) {
            $table->id();
            // Vendedor que criou a partilha.
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // SHA-256 (hex) do token: o token em claro só existe no momento da criação.
            $table->char('token_hash', 64)->unique();
            $table->string('mode', 20);
            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shares');
    }
};
