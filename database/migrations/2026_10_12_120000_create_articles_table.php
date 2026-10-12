<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            // Um tipo com artigos não se apaga (evita perder artigos em silêncio).
            $table->foreignId('product_type_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('reference', 100)->unique();
            $table->text('description')->nullable();
            $table->string('status', 20)->default('draft')->index();
            // Valores dos campos dinâmicos do tipo, por chave de FieldDefinition.
            $table->json('field_values')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
