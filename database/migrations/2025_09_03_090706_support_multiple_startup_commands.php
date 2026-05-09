<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('maps', function (Blueprint $table) {
            $table->json('startup_commands')->after('startup')->nullable();
        });

        DB::table('maps')->select(['id', 'startup'])->cursor()->each(function ($map) {
            DB::table('maps')->where('id', $map->id)->update(['startup_commands' => json_encode(['Default' => $map->startup], JSON_UNESCAPED_SLASHES)]);
        });

        Schema::table('maps', function (Blueprint $table) {
            $table->dropColumn('startup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::transaction(function () {
            Schema::table('maps', function (Blueprint $table) {
                $table->text('startup')->after('startup_commands');
            });

            DB::table('maps')->select(['id', 'startup_commands'])->cursor()->each(function ($map) {
                DB::table('maps')->where('id', $map->id)->update([
                    'startup' => Arr::first(json_decode($map->startup_commands, true, 512, JSON_THROW_ON_ERROR)),
                ]);
            });

            Schema::table('maps', function (Blueprint $table) {
                $table->dropColumn('startup_commands');
            });
        });
    }
};
