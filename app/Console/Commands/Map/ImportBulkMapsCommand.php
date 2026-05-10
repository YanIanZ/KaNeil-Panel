<?php

namespace App\Console\Commands\Map;

use App\Models\Map;
use App\Models\Ship;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ImportBulkMapsCommand extends Command
{
    protected $signature = 'p:map:import-bulk {directory : Path to directory containing egg JSON files}';
    protected $description = 'Import all game/application eggs from JSON files as Maps';

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
        $this->info('Found ' . count($files) . ' egg JSON files');

        $imported = 0;
        $skipped = 0;

        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);
            if (!$data || empty($data['name'])) {
                $this->warn("Skipping invalid JSON: $file");
                $skipped++;
                continue;
            }

            $name = $data['name'];
            $slug = Str::slug($name);

            // Check if already exists
            if (Map::where('name', $name)->exists()) {
                $this->line("Skipping existing: $name");
                $skipped++;
                continue;
            }

            // Prepare docker images
            $dockerImages = [];
            $rawImages = $data['docker_images'] ?? [];
            if (is_string($rawImages)) {
                $rawImages = json_decode($rawImages, true) ?? [];
            }
            if (is_array($rawImages)) {
                foreach ($rawImages as $image => $label) {
                    $dockerImages[$image] = is_string($label) ? $label : (is_array($label) ? ($label[0] ?? $image) : $image);
                }
            }
            if (empty($dockerImages)) {
                $dockerImages['ghcr.io/kaneil-dev/yolks:java_21'] = 'Java 21';
            }

            // Prepare startup command
            $startup = $data['startup'] ?? 'echo "Server started"';
            if (is_array($startup)) {
                $startup = implode('; ', $startup);
            }
            $startupCommands = [is_string($startup) ? $startup : 'echo "Server started"'];

            // Prepare scripts
            $scriptInstall = $data['scripts']['installation']['script'] ?? '#!/bin/bash\necho "No install script"';
            $scriptEntry = $data['scripts']['installation']['entrypoint'] ?? 'bash';
            $scriptContainer = $data['scripts']['installation']['container'] ?? 'ghcr.io/kaneil-dev/installers:alpine';
            $isPrivileged = ($data['scripts']['installation']['privileged'] ?? false) === true;

            // Prepare config files
            $configFiles = [];
            $rawFiles = $data['config']['files'] ?? [];
            if (is_string($rawFiles)) {
                $rawFiles = json_decode($rawFiles, true) ?? [];
            }
            if (is_array($rawFiles)) {
                foreach ($rawFiles as $path => $config) {
                    if (is_string($config)) {
                        $configFiles[$path] = ['parser' => 'file', 'find' => []];
                    } else {
                        $configFiles[$path] = [
                            'parser' => $config['parser'] ?? 'file',
                            'find' => $config['find'] ?? [],
                        ];
                    }
                }
            }

            // Prepare config startup
            $configStartup = json_encode([
                'done' => $data['config']['startup']['done'] ?? 'Done',
            ]);

            // Prepare config logs
            $configLogs = json_encode([
                'custom' => $data['config']['logs']['custom'] ?? false,
                'location' => $data['config']['logs']['location'] ?? 'latest.log',
            ]);

            // Prepare config stop
            $configStop = $data['config']['stop'] ?? 'stop';

            // Prepare variables
            $variables = [];
            foreach ($data['variables'] ?? [] as $var) {
                $variables[] = [
                    'name' => $var['name'] ?? '',
                    'description' => $var['description'] ?? '',
                    'env_variable' => $var['env_variable'] ?? '',
                    'default_value' => $var['default_value'] ?? '',
                    'user_viewable' => ($var['user_viewable'] ?? true) === true,
                    'user_editable' => ($var['user_editable'] ?? true) === true,
                    'rules' => $var['rules'] ?? 'required|string|max:255',
                ];
            }

            try {
                $map = Map::create([
                    'ship_id' => $ship->id,
                    'uuid' => Str::uuid()->toString(),
                    'name' => $name,
                    'author' => $data['author'] ?? 'unknown@kaneil.dev',
                    'description' => $data['description'] ?? '',
                    'features' => $data['features'] ?? null,
                    'docker_images' => $dockerImages,
                    'startup_commands' => $startupCommands,
                    'file_denylist' => $data['file_denylist'] ?? [],
                    'config_files' => $configFiles,
                    'config_startup' => $configStartup,
                    'config_logs' => $configLogs,
                    'config_stop' => $configStop,
                    'script_install' => $scriptInstall,
                    'script_entry' => $scriptEntry,
                    'script_container' => $scriptContainer,
                    'script_is_privileged' => $isPrivileged,
                    'update_url' => $data['meta']['update_url'] ?? null,
                    'tags' => json_encode([$slug]),
                ]);

                // Create variables
                foreach ($variables as $var) {
                    $map->variables()->create(array_merge($var, [
                        'map_id' => $map->id,
                        'user_viewable' => $var['user_viewable'] ?? true,
                        'user_editable' => $var['user_editable'] ?? true,
                        'rules' => is_array($var['rules']) ? implode('|', $var['rules']) : ($var['rules'] ?? 'required|string'),
                    ]));
                }

                $this->info("Imported: $name");
                $imported++;
            } catch (\Exception $e) {
                $this->error("Failed to import $name: " . $e->getMessage());
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
