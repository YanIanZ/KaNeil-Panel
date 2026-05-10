<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('map_variables')->select(['id', 'rules'])->cursor()->each(function ($mapVariable) {
            DB::table('map_variables')->where('id', $mapVariable->id)->update(['rules' => explode('|', $mapVariable->rules)]);
        });

        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE map_variables ALTER COLUMN rules TYPE JSON USING rules::json');

            return;
        }

        Schema::table('map_variables', function (Blueprint $table) {
            $table->json('rules')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('map_variables', function (Blueprint $table) {
            $table->text('rules')->change();
        });

        DB::table('map_variables')->select(['id', 'rules'])->cursor()->each(function ($mapVariable) {
            DB::table('map_variables')->where('id', $mapVariable->id)->update(['rules' => implode('|', json_decode($mapVariable->rules))]);
        });
    }
};
