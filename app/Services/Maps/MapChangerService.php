<?php

namespace App\Services\Maps;

use App\Models\Map;
use App\Models\Server;
use App\Models\ServerVariable;
use Illuminate\Support\Arr;

class MapChangerService
{
    public function handle(Server $server, Map|int $newEgg, bool $keepOldVariables = true): void
    {
        if (!$newEgg instanceof Map) {
            $newEgg = Map::findOrFail($newEgg);
        }

        if ($server->map->id === $newEgg->id) {
            return;
        }

        // Change map id, default image and startup command
        $server->forceFill([
            'map_id' => $newEgg->id,
            'image' => Arr::first($newEgg->docker_images),
            'startup' => Arr::first($newEgg->startup_commands),
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
        foreach ($newEgg->variables as $eggVariable) {
            ServerVariable::create([
                'server_id' => $server->id,
                'variable_id' => $eggVariable->id,
                'variable_value' => $oldVariables[$eggVariable->env_variable] ?? $eggVariable->default_value,
            ]);
        }
    }
}
