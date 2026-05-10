<?php

namespace App\Console\Commands\Map;

use App\Models\Map;
use App\Models\Ship;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ImportBulkMapsCommand extends Command
{
    protected $signature = 'p:map:import-bulk {directory : Path to directory containing wing JSON files}';
    protected $description = 'Import all game/application wings from JSON files as Maps';

    public function handle(): int
    {
        $directory = $this->argument('directory');

        if (!is_dir($directory)) {
            $this->error("Directory not found: $directory");
            return 1;
        }

        $ship = Ship::firstOrCreate(
            ['name' => 'Default'],
            ['author' => 'KaNeil', 'description' => 'Default ship for imported maps']
        );

        $files = $this->findJsonFiles($directory);
        $this->info('Found ' . count($files) . ' wing JSON files');

        $imported = 0;
        $skipped = 0;

        foreach ($files as $file) {
            try {
                $data = json_decode(file_get_contents($file), true);
                if (!$data || empty($data['name'])) {
                    $this->warn("Skipping invalid JSON: $file");
                    $skipped++;
                    continue;
                }

                $name = $data['name'];

                if (Map::where('name', $name)->exists()) {
                    $skipped++;
                    continue;
                }

                // Simplify docker images
                $dockerImages = [];
                $raw = $data['docker_images'] ?? [];
                if (is_string($raw)) $raw = json_decode($raw, true) ?? [];
                if (is_array($raw)) {
                    $first = array_key_first($raw);
                    if ($first) {
                        $label = $raw[$first];
                        $dockerImages[$first] = is_string($label) ? $label : $first;
                    }
                }
                if (empty($dockerImages)) {
                    $dockerImages['ghcr.io/kaneil-dev/yolks:java_21'] = 'Java 21';
                }

                // Simplify startup
                $startup = $data['startup'] ?? 'echo "started"';
                if (is_array($startup)) $startup = implode('; ', $startup);

                // Simplify config
                $configStartup = json_encode(['done' => 'Done']);

                $configFiles = [];
                $rawFiles = $data['config']['files'] ?? [];
                if (is_string($rawFiles)) $rawFiles = json_decode($rawFiles, true) ?? [];
                $configFiles = is_array($rawFiles) ? $rawFiles : [];

                $configLogs = json_encode(['custom' => false, 'location' => 'latest.log']);
                $configStop = $data['config']['stop'] ?? 'stop';

                // Scripts
                $scriptInstall = $data['scripts']['installation']['script'] ?? '#!/bin/bash\necho "done"';
                $scriptEntry = $data['scripts']['installation']['entrypoint'] ?? 'bash';
                $scriptContainer = $data['scripts']['installation']['container'] ?? 'ghcr.io/kaneil-dev/installers:alpine';
                $isPrivileged = ($data['scripts']['installation']['privileged'] ?? false) === true;

                Map::create([
                    'ship_id' => $ship->id,
                    'uuid' => Str::uuid()->toString(),
                    'name' => $name,
                    'author' => $data['author'] ?? 'unknown@kaneil.dev',
                    'description' => $data['description'] ?? '',
                    'features' => null,
                    'docker_images' => $dockerImages,
                    'startup_commands' => [$startup],
                    'file_denylist' => [],
                    'config_files' => $configFiles,
                    'config_startup' => $configStartup,
                    'config_logs' => $configLogs,
                    'config_stop' => $configStop,
                    'script_install' => $scriptInstall,
                    'script_entry' => $scriptEntry,
                    'script_container' => $scriptContainer,
                    'script_is_privileged' => $isPrivileged,
                    'update_url' => null,
                    'tags' => json_encode([]),
                ]);

                $this->info("  OK: $name");
                $imported++;
            } catch (\Exception $e) {
                $this->error("  FAIL: " . ($data['name'] ?? basename($file)) . " - " . $e->getMessage());
                $skipped++;
            }
        }

        $this->info("Done. Imported: $imported, Skipped: $skipped");
        return 0;
    }

    private function findJsonFiles(string $directory): array
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
