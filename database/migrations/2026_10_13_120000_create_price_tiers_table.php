<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_variant_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('min_quantity');
            // Cêntimos: nunca float para dinheiro.
            $table->unsignedInteger('unit_price_cents');
            $table->timestamps();

            $table->unique(['article_variant_id', 'min_quantity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_tiers');
    }
};
