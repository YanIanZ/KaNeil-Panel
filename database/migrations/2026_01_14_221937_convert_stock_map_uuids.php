<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $mappings = [
            // Forge Minecraft
            'd6018085-eecc-42bf-bf8c-51ea45a69ace' => [
                'new_uuid' => 'ed072427-f209-4603-875c-f540c6dd5a65',
                'new_update_url' => 'https://raw.githubusercontent.com/kaneil-maps/minecraft/refs/heads/main/java/forge/map-forge-minecraft.yaml',
            ],

            // Paper
            '150956be-4164-4086-9057-631ae95505e9' => [
                'new_uuid' => '5da37ef6-58da-4169-90a6-e683e1721247',
                'new_update_url' => 'https://raw.githubusercontent.com/kaneil-maps/minecraft/refs/heads/main/java/paper/map-paper.yaml',
            ],

            // Garrys Mod
            'c0b2f96a-f753-4d82-a73e-6e5be2bbadd5' => [
                'new_uuid' => '60ef81d4-30a2-4d98-ab64-f59c69e2f915',
                'new_update_url' => 'https://raw.githubusercontent.com/kaneil-maps/games-steamcmd/refs/heads/main/gmod/map-garrys-mod.yaml',
            ],
        ];

        foreach ($mappings as $oldUuid => $newData) {
            if (DB::table('maps')->where('uuid', $newData['new_uuid'])->exists()) {
                DB::table('maps')->where('uuid', $newData['new_uuid'])->update([
                    'update_url' => $newData['new_update_url'],
                ]);
            } else {
                DB::table('maps')->where('uuid', $oldUuid)->update([
                    'uuid' => $newData['new_uuid'],
                    'update_url' => $newData['new_update_url'],
                ]);
            }
        }
    }

    public function down(): void
    {
        // Not needed
    }
};
