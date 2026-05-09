<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $maps = DB::table('maps')->get();

        foreach ($maps as $map) {
            $updatedEnv = str_replace(
                'server.build.environment.',
                'server.environment.',
                $map->config_files
            );

            if ($updatedEnv !== $map->config_files) {
                $map->config_files = $updatedEnv;
                echo "Processed ENV update with ID: {$map->name}\n";
            }

            DB::table('maps')
                ->where('id', $map->id)
                ->update(['config_files' => $map->config_files]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // We shouldn't revert this...
    }
};
