<?php

namespace App\Services\Maps;

use App\Models\Map;
use App\Models\Server;
use App\Models\ServerVariable;
use Illuminate\Support\Arr;

class MapChangerService
{
    public function handle(Server $server, Map|int $newMap, bool $keepOldVariables = true): void
    {
        if (!$newMap instanceof Map) {
            $newMap = Map::findOrFail($newMap);
        }

        if ($server->map->id === $newMap->id) {
            return;
        }

        // Change map id, default image and startup command
        $server->forceFill([
            'map_id' => $newMap->id,
            'image' => Arr::first($newMap->docker_images),
            'startup' => Arr::first($newMap->startup_commands),
        ])->saveOrFail();

        $oldVariables = [];
        if ($keepOldVariables) {
            // Keep copy of old server variables
            foreach ($server->serverVariables as $serverVariable) {
                $oldVariables[$serverVariable->variable->env_variable] = $serverVariable->variable_value;
            }
        }

        // Delete old server variables
        ServerVariable::where('server_id', $server->id)->delete();

        // Create new server variables
        foreach ($newMap->variables as $mapVariable) {
            ServerVariable::create([
                'server_id' => $server->id,
                'variable_id' => $mapVariable->id,
                'variable_value' => $oldVariables[$mapVariable->env_variable] ?? $mapVariable->default_value,
            ]);
        }
    }
}
