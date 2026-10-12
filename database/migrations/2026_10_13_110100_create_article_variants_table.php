<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            // Combinação eixo => valor, ex.: {"Cor":"Azul","Tamanho":"M"}.
            $table->json('options');
            $table->string('reference', 100)->unique();
            $table->foreignId('article_image_id')->nullable()->constrained('article_images')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['article_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_variants');
    }
};
