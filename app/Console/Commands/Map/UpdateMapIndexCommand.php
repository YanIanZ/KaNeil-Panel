<?php

namespace App\Console\Commands\Map;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class UpdateMapIndexCommand extends Command
{
    protected $signature = 'p:map:update-index';

    public function handle(): int
    {
        try {
            $data = Http::timeout(5)->connectTimeout(1)->get(config('panel.cdn.map_index_url'))->throw()->json();
        } catch (Exception $exception) {
            $this->error($exception->getMessage());

            return 1;
        }

        $index = [];
        foreach ($data['nests'] as $nest) {
            $nestName = $nest['nest_type'];

            $this->info("Nest: $nestName");

            $nestMaps = [];
            foreach ($nest['Maps'] as $map) {
                $mapName = $map['map']['name'];

                $this->comment("Map: $mapName");

                $nestMaps[$map['download_url']] = $mapName;
            }
            $index[$nestName] = $nestMaps;

            $this->info('');
        }

        cache()->forever('maps.index', $index);

        return 0;
    }
}
