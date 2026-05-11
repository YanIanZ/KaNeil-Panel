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
        $url = config('panel.cdn.map_index_url');
        if (!$url) {
            $this->warn('No map_index_url configured, skipping.');

            return self::SUCCESS;
        }

        try {
            $data = Http::timeout(10)->connectTimeout(5)->get($url)->throw()->json();
        } catch (Exception $exception) {
            // Don't fail the scheduler when the remote registry is unreachable.
            $this->warn('Map index fetch failed: ' . $exception->getMessage());

            return self::SUCCESS;
        }

        if (!is_array($data) || !is_array($data['nests'] ?? null)) {
            $this->warn('Map index response missing "nests" array, skipping.');

            return self::SUCCESS;
        }

        $index = [];
        foreach ($data['nests'] as $nest) {
            if (!is_array($nest) || !isset($nest['nest_type'])) {
                continue;
            }
            $nestName = $nest['nest_type'];
            $this->info("Nest: $nestName");

            $nestMaps = [];
            foreach ($nest['Maps'] ?? [] as $map) {
                if (!isset($map['map']['name'], $map['download_url'])) {
                    continue;
                }
                $this->comment('Map: ' . $map['map']['name']);
                $nestMaps[$map['download_url']] = $map['map']['name'];
            }
            $index[$nestName] = $nestMaps;
        }

        cache()->forever('maps.index', $index);

        return self::SUCCESS;
    }
}
