<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('eggs', 'maps');
        Schema::rename('egg_variables', 'map_variables');
    }

    public function down(): void
    {
        Schema::rename('maps', 'eggs');
        Schema::rename('map_variables', 'egg_variables');
    }
};
