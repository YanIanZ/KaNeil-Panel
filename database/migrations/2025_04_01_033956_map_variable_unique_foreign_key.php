<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('map_variables', function (Blueprint $table) {
            $table->unique(['map_id', 'env_variable']);
            $table->unique(['map_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('map_variables', function (Blueprint $table) {
            $table->dropUnique(['map_id', 'env_variable']);
            $table->dropUnique(['map_id', 'name']);
        });
    }
};
