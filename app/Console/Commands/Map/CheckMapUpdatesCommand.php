<?php

namespace App\Console\Commands\Map;

use App\Enums\MapFormat;
use App\Models\Map;
use App\Services\Maps\Sharing\EggExporterService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Yaml\Yaml;

class CheckMapUpdatesCommand extends Command
{
    protected $signature = 'p:map:check-updates';

    public function handle(EggExporterService $exporterService): void
    {
        $maps = Map::all();
        foreach ($maps as $map) {
            try {
                $this->check($map, $exporterService);
            } catch (Exception $exception) {
                $this->error("$map->name: Error ({$exception->getMessage()})");
            }
        }
    }

    /** @throws Exception */
    private function check(Map $map, EggExporterService $exporterService): void
    {
        if (is_null($map->update_url)) {
            $this->comment("$map->name: Skipping (no update url set)");

            return;
        }

        $ext = strtolower(pathinfo(parse_url($map->update_url, PHP_URL_PATH), PATHINFO_EXTENSION));
        $isYaml = in_array($ext, ['yaml', 'yml']);

        $local = $isYaml
            ? Yaml::parse($exporterService->handle($map->id, MapFormat::YAML))
            : json_decode($exporterService->handle($map->id, MapFormat::JSON), true);

        $remote = Http::timeout(5)->connectTimeout(1)->get($map->update_url);

        if ($remote->failed()) {
            throw new Exception("HTTP request returned status code {$remote->status()}");
        }

        $remote = $remote->body();
        $remote = $isYaml ? Yaml::parse($remote) : json_decode($remote, true);

        unset($local['exported_at'], $remote['exported_at']);

        $localHash = md5(json_encode($local, JSON_THROW_ON_ERROR));
        $remoteHash = md5(json_encode($remote, JSON_THROW_ON_ERROR));

        $status = $localHash === $remoteHash ? 'Up-to-date' : 'Found update';
        $this->{($localHash === $remoteHash) ? 'info' : 'warn'}("$map->name: $status");

        cache()->put("maps.$map->uuid.update", $localHash !== $remoteHash, now()->addHour());
    }
}
