<?php

namespace App\Services\Maps\Sharing;

use App\Enums\EggFormat;
use App\Models\Map;
use App\Models\MapVariable;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Yaml\Yaml;

class EggExporterService
{
    /**
     * Return a JSON or YAML representation of an map and its variables.
     */
    public function handle(int $map, EggFormat $format): string
    {
        $map = Map::with(['scriptFrom', 'configFrom', 'variables'])->findOrFail($map);
        $iconBase64 = $this->getEggIconAsBase64($map);

        $struct = [
            '_comment' => 'DO NOT EDIT: FILE GENERATED AUTOMATICALLY BY PANEL',
            'meta' => [
                'version' => Map::EXPORT_VERSION,
                'update_url' => $map->update_url,
            ],
            'exported_at' => Carbon::now()->toAtomString(),
            'name' => $map->name,
            'author' => $map->author,
            'uuid' => $map->uuid,
            'description' => $map->description,
            'icon' => $iconBase64,
            'tags' => $map->tags,
            'features' => $map->features,
            'docker_images' => $map->docker_images,
            'file_denylist' => Collection::make($map->inherit_file_denylist)->filter(fn ($v) => !empty($v))->values(),
            'startup_commands' => $map->startup_commands,
            'config' => [
                'files' => $map->inherit_config_files,
                'startup' => $map->inherit_config_startup,
                'logs' => $map->inherit_config_logs,
                'stop' => $map->inherit_config_stop,
            ],
            'scripts' => [
                'installation' => [
                    'script' => $map->copy_script_install,
                    'container' => $map->copy_script_container,
                    'entrypoint' => $map->copy_script_entry,
                ],
            ],
            'variables' => $map->variables->map(function (MapVariable $eggVariable) {
                return Collection::make($eggVariable->toArray())
                    ->except(['id', 'map_id', 'created_at', 'updated_at']);
            })->values()->toArray(),
        ];

        return match ($format) {
            EggFormat::JSON => json_encode($struct, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            EggFormat::YAML => Yaml::dump($this->yamlExport($struct), 10, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK | Yaml::DUMP_OBJECT_AS_MAP),
        };
    }

    /**
     * Get the map icon as base64 for export.
     */
    private function getEggIconAsBase64(Map $map): ?string
    {
        foreach (Map::$iconFormats as $ext => $mimeType) {
            $path = Map::getIconStoragePath() . "/$map->uuid.$ext";

            if (Storage::disk('public')->exists($path)) {
                return 'data:' . $mimeType . ';base64,' . base64_encode(Storage::disk('public')->get($path));
            }
        }

        return null;
    }

    protected function yamlExport(mixed $data): mixed
    {
        if ($data instanceof Collection) {
            $data = $data->all();
        }

        if (is_string($data)) {
            $decoded = json_decode($data, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $this->yamlExport($decoded);
            }

            return str_replace("\r\n", "\n", $data);
        }

        if (is_array($data)) {
            $result = [];

            foreach ($data as $key => $value) {
                if (
                    is_string($value) &&
                    strtolower($key) === 'description' &&
                    (str_contains($value, "\n") || strlen($value) > 80)
                ) {
                    $value = wordwrap($value, 100, "\n");
                } else {
                    $value = $this->yamlExport($value);
                }

                $result[$key] = $value;
            }

            return $result;
        }

        return $data;
    }
}
