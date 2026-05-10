<?php

namespace Database\Seeders\Concerns;

use App\Models\Map;
use App\Models\Ship;
use Illuminate\Support\Str;

trait ImportsEggsAsMaps
{
    protected function importEggsFromDirectory(string $directory, Ship $ship, string $defaultAuthor = 'community@kaneil.dev'): int
    {
        if (!is_dir($directory)) {
            $this->command->info("Directory not found: $directory, skipping.");
            return 0;
        }

        $files = $this->findEggJsonFiles($directory);
        $imported = 0;

        foreach ($files as $file) {
            try {
                $contents = @file_get_contents($file);
                if ($contents === false) {
                    continue;
                }
                $data = json_decode($contents, true);
                if (!is_array($data) || empty($data['name'])) {
                    continue;
                }
                if (Map::where('name', $data['name'])->exists()) {
                    continue;
                }
                $this->createMapFromEggData($data, $ship->id, $defaultAuthor);
                $imported++;
            } catch (\Throwable $e) {
                $this->command->warn("Failed to import {$file}: {$e->getMessage()}");
            }
        }

        return $imported;
    }

    protected function createMapFromEggData(array $data, int $shipId, string $defaultAuthor): void
    {
        // Resolve docker_images — cast:array expects a PHP array
        $dockerImages = [];
        $raw = $data['docker_images'] ?? [];
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : [];
        }
        if (is_array($raw) && !empty($raw)) {
            $first = array_key_first($raw);
            $label = $raw[$first];
            $dockerImages[(string) $first] = is_string($label) ? $label : (string) $first;
        }
        if (empty($dockerImages)) {
            $dockerImages['ghcr.io/kaneil-dev/installers:alpine'] = 'Alpine';
        }

        // Resolve startup — cast:array expects a PHP array
        $startup = $data['startup'] ?? 'echo "started"';
        if (is_array($startup)) {
            $startup = implode('; ', array_filter(array_map('strval', $startup)));
        }
        $startupCommands = [(string) $startup];

        // Resolve config_files — stored as JSON string (not cast), must be JSON
        $rawFiles = $data['config']['files'] ?? [];
        if (is_string($rawFiles)) {
            $decoded = json_decode($rawFiles, true);
            $rawFiles = is_array($decoded) ? $decoded : [];
        }
        $configFiles = json_encode(is_array($rawFiles) ? $rawFiles : []);

        // config_startup / config_logs — stored as JSON strings
        $configStartup = json_encode(['done' => 'Done']);
        $configLogs = json_encode(['custom' => false, 'location' => 'latest.log']);
        $configStop = $data['config']['stop'] ?? 'stop';

        // Scripts
        $scriptInstall = $data['scripts']['installation']['script'] ?? "#!/bin/bash\necho \"done\"";
        $scriptEntry = $data['scripts']['installation']['entrypoint'] ?? 'bash';
        $scriptContainer = $data['scripts']['installation']['container'] ?? 'ghcr.io/kaneil-dev/installers:alpine';
        $isPrivileged = ($data['scripts']['installation']['privileged'] ?? false) === true;

        Map::create([
            'ship_id'            => $shipId,
            'uuid'               => Str::uuid()->toString(),
            'name'               => $data['name'],
            'author'             => $data['author'] ?? $defaultAuthor,
            'description'        => $data['description'] ?? '',
            'features'           => null,
            'docker_images'      => $dockerImages,      // cast:array — pass PHP array
            'startup_commands'   => $startupCommands,   // cast:array — pass PHP array
            'file_denylist'      => [],                 // cast:array — pass PHP array
            'force_outgoing_ip'  => false,              // cast:boolean — required field
            'config_files'       => $configFiles,       // JSON string (not cast)
            'config_startup'     => $configStartup,     // JSON string (not cast)
            'config_logs'        => $configLogs,        // JSON string (not cast)
            'config_stop'        => $configStop,
            'config_from'        => null,
            'copy_script_from'   => null,
            'script_install'     => $scriptInstall,
            'script_entry'       => $scriptEntry,
            'script_container'   => $scriptContainer,
            'script_is_privileged' => $isPrivileged,
            'update_url'         => null,
            'tags'               => [],                 // cast:array — pass PHP array
        ]);
    }

    protected function findEggJsonFiles(string $directory): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'json' && str_starts_with($file->getFilename(), 'egg-')) {
                $files[] = $file->getPathname();
            }
        }
        return $files;
    }
}
