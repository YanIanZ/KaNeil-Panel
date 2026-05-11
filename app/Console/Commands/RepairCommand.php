<?php

namespace App\Console\Commands;

use App\Models\Map;
use App\Models\MapVariable;
use App\Models\Server;
use App\Models\ServerVariable;
use Illuminate\Console\Command;

class RepairCommand extends Command
{
    protected $signature = 'p:repair {--eggs-dir=* : Egg JSON directories to scan for variable backfill}';

    protected $description = 'Repair map/server data after upgrades: docker_images, startup_commands, variables, image refs.';

    public function handle(): int
    {
        $this->info('=== Repair: docker_images normalization ===');
        $this->repairDockerImages();

        $this->info('=== Repair: rewrite ghcr.io/kaneil-dev/* to ghcr.io/parkervcp/* ===');
        $this->rewriteRegistry();

        $this->info('=== Repair: startup_commands relabel to {Default: ...} ===');
        $this->relabelStartup();

        $this->info('=== Repair: backfill MapVariable from egg JSON ===');
        $dirs = $this->option('eggs-dir');
        if (empty($dirs)) {
            $dirs = [
                storage_path('eggs/game-wings'),
                storage_path('eggs/application-wings'),
                storage_path('eggs/game-eggs'),
                storage_path('eggs/application-eggs'),
            ];
        }
        foreach ($dirs as $d) {
            if (is_dir($d)) {
                $this->info("Scanning $d");
                $this->backfillVariables($d);
            }
        }

        $this->info('=== Repair: backfill ServerVariable rows ===');
        $this->backfillServerVariables();

        $this->info('=== Repair: fix vessel image when label literal saved ===');
        $this->repairServerImage();

        $this->info('Done.');

        return self::SUCCESS;
    }

    private function repairDockerImages(): void
    {
        $fixed = 0;
        foreach (Map::all() as $m) {
            $imgs = $m->docker_images ?? [];
            if (!is_array($imgs) || empty($imgs)) {
                continue;
            }
            $new = [];
            foreach ($imgs as $k => $v) {
                $label = (string) $k;
                $uri = is_string($v) ? $v : '';
                $keyLooksUri = (str_contains($label, '/') || str_contains($label, ':'))
                    && !preg_match('/\s/', $label)
                    && !preg_match('/^[A-Z][a-z]+ ?\d/', $label);
                $valLooksLabel = preg_match('/\s/', $uri) || preg_match('/^[A-Z][a-z]+ ?\d/', $uri);
                if ($keyLooksUri && $valLooksLabel) {
                    $new[$uri] = $label;

                    continue;
                }
                if ($uri === '' || preg_match('/\s/', $uri) || preg_match('/[A-Z]/', explode(':', $uri, 2)[0] ?? '')) {
                    continue;
                }
                $new[$label] = $uri;
            }
            if (empty($new)) {
                $new = ['Java 21' => 'ghcr.io/parkervcp/yolks:java_21'];
            }
            if ($new !== $imgs) {
                $m->docker_images = $new;
                $m->save();
                $fixed++;
            }
        }
        $this->line("  Maps repaired: $fixed");
    }

    private function rewriteRegistry(): void
    {
        $rw = fn (string $s) => str_replace('ghcr.io/kaneil-dev/', 'ghcr.io/parkervcp/', $s);
        $mapFixed = 0;
        $svrFixed = 0;
        foreach (Map::all() as $m) {
            $changed = false;
            $imgs = $m->docker_images ?? [];
            if (is_array($imgs)) {
                $new = [];
                foreach ($imgs as $k => $v) {
                    $nk = is_string($k) ? $rw($k) : $k;
                    $nv = is_string($v) ? $rw($v) : $v;
                    $new[$nk] = $nv;
                    if ($nk !== $k || $nv !== $v) {
                        $changed = true;
                    }
                }
                if ($changed) {
                    $m->docker_images = $new;
                }
            }
            if (is_string($m->script_container) && str_contains($m->script_container, 'kaneil-dev')) {
                $m->script_container = $rw($m->script_container);
                $changed = true;
            }
            if ($changed) {
                $m->save();
                $mapFixed++;
            }
        }
        foreach (Server::all() as $s) {
            if (is_string($s->image) && str_contains($s->image, 'kaneil-dev')) {
                $s->image = $rw($s->image);
                $s->save();
                $svrFixed++;
            }
        }
        $this->line("  Maps rewritten: $mapFixed, Vessels rewritten: $svrFixed");
    }

    private function relabelStartup(): void
    {
        $fixed = 0;
        foreach (Map::all() as $m) {
            $cmds = $m->startup_commands;
            if (!is_array($cmds) || empty($cmds)) {
                continue;
            }
            $hasStringKey = false;
            foreach (array_keys($cmds) as $k) {
                if (!is_int($k)) {
                    $hasStringKey = true;
                    break;
                }
            }
            if ($hasStringKey) {
                continue;
            }
            $new = [];
            foreach (array_values($cmds) as $i => $v) {
                $new[$i === 0 ? 'Default' : ('Command ' . ($i + 1))] = $v;
            }
            $m->startup_commands = $new;
            $m->save();
            $fixed++;
        }
        $this->line("  Maps relabeled: $fixed");
    }

    private function backfillVariables(string $dir): void
    {
        $rii = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS));
        $touched = 0;
        $added = 0;
        foreach ($rii as $f) {
            if (!$f->isFile() || $f->getExtension() !== 'json' || !str_starts_with($f->getFilename(), 'egg-')) {
                continue;
            }
            $data = json_decode(file_get_contents($f->getPathname()), true);
            if (!$data || empty($data['name'])) {
                continue;
            }
            $map = Map::where('name', $data['name'])->first();
            if (!$map) {
                continue;
            }
            $vars = $data['variables'] ?? [];
            if (!is_array($vars) || empty($vars)) {
                continue;
            }
            $touched++;
            foreach ($vars as $sort => $var) {
                if (!is_array($var)) {
                    continue;
                }
                $env = $var['env_variable'] ?? null;
                if (!$env || in_array($env, MapVariable::RESERVED_ENV_NAMES)) {
                    continue;
                }
                if (MapVariable::where('map_id', $map->id)->where('env_variable', $env)->exists()) {
                    continue;
                }
                $rules = $var['rules'] ?? '';
                if (is_string($rules)) {
                    $rules = array_values(array_filter(array_map('trim', explode('|', $rules))));
                }
                if (!is_array($rules) || empty($rules)) {
                    $rules = ['nullable', 'string'];
                }
                try {
                    MapVariable::create([
                        'map_id' => $map->id,
                        'sort' => $sort,
                        'name' => (string) ($var['name'] ?? $env),
                        'description' => (string) ($var['description'] ?? ''),
                        'env_variable' => $env,
                        'default_value' => (string) ($var['default_value'] ?? ''),
                        'user_viewable' => (bool) ($var['user_viewable'] ?? true),
                        'user_editable' => (bool) ($var['user_editable'] ?? true),
                        'rules' => $rules,
                    ]);
                    $added++;
                } catch (\Throwable) {
                }
            }
        }
        $this->line("  Maps touched: $touched, vars added: $added");
    }

    private function backfillServerVariables(): void
    {
        $added = 0;
        foreach (Server::with('map.variables')->get() as $s) {
            if (!$s->map) {
                continue;
            }
            foreach ($s->map->variables as $mv) {
                if (ServerVariable::where('server_id', $s->id)->where('variable_id', $mv->id)->exists()) {
                    continue;
                }
                ServerVariable::create([
                    'server_id' => $s->id,
                    'variable_id' => $mv->id,
                    'variable_value' => (string) $mv->default_value,
                ]);
                $added++;
            }
        }
        $this->line("  Server variables added: $added");
    }

    private function repairServerImage(): void
    {
        $fixed = 0;
        foreach (Server::with('map')->get() as $s) {
            $img = (string) $s->image;
            $invalid = $img === '' || preg_match('/\s/', $img) || preg_match('/[A-Z]/', explode(':', $img, 2)[0] ?? '');
            if (!$invalid) {
                continue;
            }
            $available = $s->map?->docker_images ?? [];
            $first = null;
            foreach ($available as $label => $uri) {
                if (is_string($uri) && !preg_match('/\s/', $uri) && !preg_match('/[A-Z]/', explode(':', $uri, 2)[0] ?? '')) {
                    $first = $uri;
                    break;
                }
            }
            if ($first) {
                $s->image = $first;
                $s->save();
                $fixed++;
                $this->line("  Vessel $s->id: \"$img\" -> \"$first\"");
            }
        }
        $this->line("  Vessels repaired: $fixed");
    }
}
