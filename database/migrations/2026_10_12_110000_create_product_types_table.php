<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('field_definitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_type_id')->constrained()->cascadeOnDelete();
            // Chave estável usada nos valores dos artigos; não muda se o nome mudar.
            $table->string('key', 100);
            $table->string('name');
            $table->string('data_type', 20);
            $table->boolean('required')->default(false);
            $table->json('options')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['product_type_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('field_definitions');
        Schema::dropIfExists('product_types');
    }
};
