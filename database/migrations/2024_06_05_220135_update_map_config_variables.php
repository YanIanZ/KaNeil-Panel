<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $maps = DB::table('maps')->get();

        foreach ($maps as $map) {
            $updatedPort = str_replace(
                'server.build.default.port',
                'server.allocations.default.port',
                $map->config_files
            );

            if ($updatedPort !== $map->config_files) {
                $map->config_files = $updatedPort;
                echo "Processed Port update with ID: {$map->name}\n";
            }

            $updatedIp = str_replace(
                'server.build.default.ip',
                'server.allocations.default.ip',
                $map->config_files
            );

            if ($updatedIp !== $map->config_files) {
                $map->config_files = $updatedIp;
                echo "Processed IP update with ID: {$map->name}\n";
            }

            $updatedEnv = str_replace(
                'server.build.env.',
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

    public function down(): void
    {
        $maps = DB::table('maps')->get();

        foreach ($maps as $map) {
            $revertedEnv = str_replace(
                'server.environment.',
                'server.build.env.',
                $map->config_files
            );

            if ($revertedEnv !== $map->config_files) {
                $map->config_files = $revertedEnv;
            }

            $revertedIp = str_replace(
                'server.allocations.default.ip',
                'server.build.default.ip',
                $map->config_files
            );

            if ($revertedIp !== $map->config_files) {
                $map->config_files = $revertedIp;
            }

            $revertedPort = str_replace(
                'server.allocations.default.port',
                'server.build.default.port',
                $map->config_files
            );

            if ($revertedPort !== $map->config_files) {
                $map->config_files = $revertedPort;
            }

            DB::table('maps')
                ->where('id', $map->id)
                ->update(['config_files' => $map->config_files]);
        }
    }
};
