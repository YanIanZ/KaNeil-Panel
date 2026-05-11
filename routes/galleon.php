<?php

use App\Http\Controllers\Auth\GalleonLoginController;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Map;
use App\Models\Node;
use App\Models\Server;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Inertia\Inertia;
use Illuminate\Support\Facades\Route;

// ── Login (guest) — OUTSIDE galleon group to avoid redirect loop ───────────
// HandleInertiaRequests must be present so Inertia uses the 'galleon' root view.

Route::get('/login', function () {
    if (auth()->check()) return redirect('/');
    return Inertia::render('Login');
})->middleware(HandleInertiaRequests::class)->name('galleon.login');

Route::post('/auth/login', [GalleonLoginController::class, 'login'])->middleware(HandleInertiaRequests::class)->name('galleon.auth.login');
Route::post('/auth/logout', [GalleonLoginController::class, 'logout'])->middleware(['auth', HandleInertiaRequests::class])->name('galleon.auth.logout');

// ── Authenticated ──────────────────────────────────────────────────────────

Route::middleware('galleon')->group(function () {

    Route::get('/', function () {
        $servers = auth()->user()->accessibleServers()->with(['node'])->get();

        $base = $servers->map(fn ($s) => array_merge(
            $s->only(['id','uuid','uuid_short','name','memory','cpu','disk','node_id']),
            [
                'condition'       => $s->condition->value,
                'condition_label' => $s->condition->getLabel(),
                'allocation'      => $s->allocation?->only(['id','ip','ip_alias','port']),
                'node'            => $s->node?->only(['id','name']),
            ]
        ));

        return Inertia::render('Dashboard', [
            'servers' => $base->toArray(),
            'serverStats' => Inertia::defer(fn () => $servers->mapWithKeys(function ($s) {
                $r = $s->retrieveResources();
                return [$s->uuid => [
                    'condition'       => $s->condition->value,
                    'condition_label' => $s->condition->getLabel(),
                    'cpu_absolute'    => $r['cpu_absolute'] ?? 0,
                    'memory_bytes'    => $r['memory_bytes'] ?? 0,
                    'memory_pct'      => $s->memory > 0
                        ? round(($r['memory_bytes'] ?? 0) / ($s->memory * 1048576) * 100, 1) : 0,
                    'cpu_pct'         => $s->cpu > 0
                        ? round(($r['cpu_absolute'] ?? 0) / $s->cpu * 100, 1) : 0,
                ]];
            })->toArray()),
        ]);
    })->name('galleon.dashboard');

    Route::get('/server/create', function () {
        return Inertia::render('Create', [
            'maps'  => Map::all(['id','name'])->toArray(),
            'nodes' => Node::all(['id','name','fqdn'])->toArray(),
        ]);
    })->name('galleon.create');

    Route::post('/server', function (Request $request) {
        $data = $request->validate([
            'name'             => ['required','string','max:255'],
            'description'      => ['nullable','string'],
            'map_id'           => ['required','exists:maps,id'],
            'node_id'          => ['required','exists:nodes,id'],
            'allocation_id'    => ['required','exists:allocations,id'],
            'memory'           => ['required','integer','min:0'],
            'swap'             => ['required','integer','min:-1'],
            'disk'             => ['required','integer','min:0'],
            'io'               => ['required','integer','between:10,1000'],
            'cpu'              => ['required','integer','min:0'],
            'threads'          => ['nullable','string'],
            'startup'          => ['required','string'],
            'image'            => ['required','string'],
            'oom_killer'       => ['nullable','boolean'],
            'database_limit'   => ['nullable','integer','min:0'],
            'allocation_limit' => ['nullable','integer','min:0'],
            'backup_limit'     => ['nullable','integer','min:0'],
            'environment'      => ['nullable','array'],
            'environment.*'    => ['nullable','string'],
        ]);

        // Default env to MapVariable defaults so install scripts don't fail on empty vars.
        $map = \App\Models\Map::with('variables')->findOrFail($data['map_id']);
        $env = $data['environment'] ?? [];
        foreach ($map->variables as $mv) {
            if (!array_key_exists($mv->env_variable, $env) || $env[$mv->env_variable] === null || $env[$mv->env_variable] === '') {
                $env[$mv->env_variable] = (string) $mv->default_value;
            }
        }
        $data['environment'] = $env;

        $server = app(\App\Services\Servers\ServerCreationService::class)->handle(array_merge($data, [
            'owner_id' => auth()->id(),
        ]));
        return redirect()->route('galleon.server', ['server' => $server->uuid, 'tab' => 'console']);
    })->name('galleon.server.create.post');

    Route::get('/server/{server:uuid}/{tab?}', function (Server $server, string $tab = 'console') {
        $user = auth()->user();
        abort_unless(
            $user->isRootAdmin()
            || $user->servers()->where('uuid', $server->uuid)->exists()
            || $server->subusers()->where('user_id', $user->id)->exists(),
            403
        );

        $server->load(['node','databases.host','backups','schedules','subusers.user']);

        $page = match ($tab) {
            'files'     => 'server/Files',
            'databases' => 'server/Databases',
            'backups'   => 'server/Backups',
            'schedules' => 'server/Schedules',
            'network'   => 'server/Network',
            'subusers'  => 'server/Subusers',
            'startup'   => 'server/Startup',
            default     => 'server/Console',
        };

        return Inertia::render($page, [
            'server' => array_merge(
                $server->only([
                    'id','uuid','uuid_short','name','description',
                    'memory','swap','disk','io','cpu','threads',
                    'oom_killer','database_limit','allocation_limit','backup_limit',
                    'node_id','map_id','allocation_id',
                ]),
                [
                    'condition'       => $server->condition->value,
                    'condition_label' => $server->condition->getLabel(),
                    'allocation'      => $server->allocation?->only(['id','ip','ip_alias','port']),
                    'node'            => $server->node?->only(['id','name','fqdn','scheme']),
                    'databases'       => $server->databases->map(fn ($db) => array_merge(
                        $db->only(['id','database','username']),
                        ['host' => $db->host?->only(['id','host','port'])]
                    )),
                    'backups'   => $server->backups->map->only(['uuid','name','is_successful','bytes','created_at']),
                    'schedules' => $server->schedules->map->only(['id','name','cron_minute','cron_hour','cron_day_of_week','is_active']),
                    'subusers'  => $server->subusers->map(fn ($s) => [
                        'id' => $s->id,
                        'permissions' => $s->permissions,
                        'user' => $s->user?->only(['id','username','email']),
                    ]),
                ]
            ),
        ]);
    })->name('galleon.server');

    Route::prefix('admin')->group(function () {
        Route::get('/nodes', function () {
            abort_unless(auth()->user()->isRootAdmin(), 403);
            return Inertia::render('admin/Nodes', [
                'nodes' => Node::withCount('servers')->get()->toArray(),
            ]);
        })->name('galleon.nodes');

        Route::get('/users', function () {
            abort_unless(auth()->user()->isRootAdmin(), 403);
            return Inertia::render('admin/Users', [
                'users' => User::withCount('servers')->get()->toArray(),
            ]);
        })->name('galleon.users');

        Route::get('/maps', function () {
            abort_unless(auth()->user()->isRootAdmin(), 403);
            return Inertia::render('admin/Maps', [
                'maps' => Map::withCount('servers')->get()->toArray(),
            ]);
        })->name('galleon.maps');

        Route::get('/maps/import', function () {
            abort_unless(auth()->user()->isRootAdmin(), 403);
            return Inertia::render('Import');
        })->name('galleon.import');

        Route::post('/maps/import', function (Request $request) {
            abort_unless(auth()->user()->isRootAdmin(), 403);
            $request->validate(['json_content' => ['required','string']]);
            $payload = json_decode($request->input('json_content'), true, 512, JSON_THROW_ON_ERROR);

            // Build a payload that satisfies Map::$validationRules.
            $ship = \App\Models\Ship::firstOrCreate(
                ['name' => 'Default'],
                ['author' => 'KaNeil', 'description' => 'Default ship for imported maps']
            );

            $author = $payload['author'] ?? 'unknown@kaneil.dev';
            if (!filter_var($author, FILTER_VALIDATE_EMAIL)) {
                $author = 'unknown@kaneil.dev';
            }

            // docker_images: must be ['label' => 'uri', ...] with valid refs.
            $dockerImages = [];
            $raw = $payload['docker_images'] ?? [];
            if (is_string($raw)) { $raw = json_decode($raw, true) ?? []; }
            if (is_array($raw)) {
                foreach ($raw as $k => $v) {
                    $uri = is_string($v) ? $v : '';
                    if ($uri === '' || preg_match('/\s/', $uri) || preg_match('/[A-Z]/', explode(':', $uri, 2)[0] ?? '')) {
                        continue;
                    }
                    $dockerImages[(string) $k] = $uri;
                }
            }
            if (empty($dockerImages)) {
                $dockerImages = ['Java 21' => 'ghcr.io/parkervcp/yolks:java_21'];
            }

            // startup_commands
            $startup = $payload['startup'] ?? 'echo "started"';
            if (is_array($startup)) { $startup = implode('; ', $startup); }

            $ensureJsonString = function (mixed $v): string {
                if (is_string($v)) {
                    $d = json_decode($v, true);
                    return json_last_error() === JSON_ERROR_NONE ? json_encode($d) : '{}';
                }
                if (is_array($v) || is_object($v)) { return json_encode($v); }
                return '{}';
            };

            $map = Map::create([
                'ship_id'         => $ship->id,
                'uuid'            => \Illuminate\Support\Str::uuid()->toString(),
                'name'            => $payload['name'] ?? 'Imported Map',
                'author'          => $author,
                'description'    => $payload['description'] ?? '',
                'features'        => is_array($payload['features'] ?? null) ? $payload['features'] : null,
                'docker_images'   => $dockerImages,
                'startup_commands' => ['Default' => $startup],
                'file_denylist'   => is_array($payload['file_denylist'] ?? null) ? $payload['file_denylist'] : [],
                'config_files'    => $ensureJsonString($payload['config']['files']   ?? '{}'),
                'config_startup'  => $ensureJsonString($payload['config']['startup'] ?? '{"done":"Done"}'),
                'config_logs'     => $ensureJsonString($payload['config']['logs']    ?? '{}'),
                'config_stop'     => $payload['config']['stop'] ?? 'stop',
                'script_install'  => $payload['scripts']['installation']['script']     ?? "#!/bin/bash\necho \"done\"",
                'script_entry'    => $payload['scripts']['installation']['entrypoint'] ?? 'bash',
                'script_container'=> $payload['scripts']['installation']['container']  ?? 'ghcr.io/parkervcp/installers:alpine',
                'script_is_privileged' => ($payload['scripts']['installation']['privileged'] ?? false) === true,
                'update_url'      => null,
                'tags'            => [],
            ]);

            // Variables
            foreach (($payload['variables'] ?? []) as $sort => $var) {
                if (!is_array($var)) { continue; }
                $env = $var['env_variable'] ?? null;
                if (!$env || in_array($env, \App\Models\MapVariable::RESERVED_ENV_NAMES)) { continue; }
                $rules = $var['rules'] ?? '';
                if (is_string($rules)) {
                    $rules = array_values(array_filter(array_map('trim', explode('|', $rules))));
                }
                if (!is_array($rules) || empty($rules)) { $rules = ['nullable', 'string']; }
                \App\Models\MapVariable::create([
                    'map_id'         => $map->id,
                    'sort'           => $sort,
                    'name'           => (string) ($var['name'] ?? $env),
                    'description'    => (string) ($var['description'] ?? ''),
                    'env_variable'   => $env,
                    'default_value'  => (string) ($var['default_value'] ?? ''),
                    'user_viewable'  => (bool) ($var['user_viewable'] ?? true),
                    'user_editable'  => (bool) ($var['user_editable'] ?? true),
                    'rules'          => $rules,
                ]);
            }

            return redirect()->route('galleon.maps')->with('success', 'Map imported.');
        })->name('galleon.import.post');
    });
});