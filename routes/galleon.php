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
            'name'          => ['required','string','max:255'],
            'map_id'        => ['required','exists:maps,id'],
            'node_id'       => ['required','exists:nodes,id'],
            'allocation_id' => ['required','exists:allocations,id'],
            'memory'        => ['required','integer','min:0'],
            'swap'          => ['required','integer','min:-1'],
            'disk'          => ['required','integer','min:0'],
            'io'            => ['required','integer','between:10,1000'],
            'cpu'           => ['required','integer','min:0'],
            'startup'       => ['required','string'],
            'image'         => ['required','string'],
        ]);
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
            $map = Map::create(Arr::only($payload, [
                'name','description','author','docker_images',
                'startup','config_files','config_startup','config_logs','config_stop',
            ]));
            return redirect()->route('galleon.maps')->with('success', 'Map imported.');
        })->name('galleon.import.post');
    });
});