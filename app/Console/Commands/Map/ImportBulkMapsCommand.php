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

                // Normalize docker images to [label => image_uri].
                // Source egg JSON uses the same shape. Drop entries that aren't
                // valid docker references (lowercase repo, no spaces) so the
                // UI never offers a label-as-image footgun.
                $dockerImages = [];
                $raw = $data['docker_images'] ?? [];
                if (is_string($raw)) $raw = json_decode($raw, true) ?? [];
                if (is_array($raw)) {
                    foreach ($raw as $key => $value) {
                        $label = (string) $key;
                        $image = is_string($value) ? $value : (string) $value;
                        // valid docker reference: no spaces, lowercase repo path before optional :tag
                        if ($image === '' || preg_match('/\s/', $image) || preg_match('/[A-Z]/', explode(':', $image, 2)[0])) {
                            continue;
                        }
                        $dockerImages[$label] = $image;
                    }
                }
                if (empty($dockerImages)) {
                    $dockerImages['Java 21'] = 'ghcr.io/parkervcp/yolks:java_21';
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
                $scriptContainer = $data['scripts']['installation']['container'] ?? 'ghcr.io/parkervcp/installers:alpine';
                $isPrivileged = ($data['scripts']['installation']['privileged'] ?? false) === true;

                $map = Map::create([
                    'ship_id' => $ship->id,
                    'uuid' => Str::uuid()->toString(),
                    'name' => $name,
                    'author' => $data['author'] ?? 'unknown@kaneil.dev',
                    'description' => $data['description'] ?? '',
                    'features' => null,
                    'docker_images' => $dockerImages,
                    'startup_commands' => ['Default' => $startup],
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

                // Import variables from egg JSON
                $variables = $data['variables'] ?? [];
                $varCount = 0;
                if (is_array($variables)) {
                    foreach ($variables as $sort => $var) {
                        if (!is_array($var)) continue;
                        $envName = $var['env_variable'] ?? null;
                        if (!$envName || in_array($envName, \App\Models\MapVariable::RESERVED_ENV_NAMES)) continue;
                        $rules = $var['rules'] ?? '';
                        if (is_string($rules)) {
                            $rules = array_values(array_filter(array_map('trim', explode('|', $rules))));
                        }
                        if (!is_array($rules) || empty($rules)) $rules = ['nullable', 'string'];
                        \App\Models\MapVariable::create([
                            'map_id' => $map->id,
                            'sort' => $sort,
                            'name' => (string) ($var['name'] ?? $envName),
                            'description' => (string) ($var['description'] ?? ''),
                            'env_variable' => $envName,
                            'default_value' => (string) ($var['default_value'] ?? ''),
                            'user_viewable' => (bool) ($var['user_viewable'] ?? true),
                            'user_editable' => (bool) ($var['user_editable'] ?? true),
                            'rules' => $rules,
                        ]);
                        $varCount++;
                    }
                }

                $this->info("  OK: $name (vars=$varCount)");
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
