<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            // Original no disco privado; derivados no público (D14).
            $table->string('original_path');
            $table->string('thumb_path');
            $table->string('screen_path');
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->index(['article_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_images');
    }
};
