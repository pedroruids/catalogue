<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            // Lista ordenada [{name, values[]}] — valores dinâmicos em JSON, como field_values (D12).
            $table->json('variant_axes')->nullable()->after('field_values');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn('variant_axes');
        });
    }
};
