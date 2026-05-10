<?php

use App\Http\Controllers\Api\Application;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| User Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /api/application/users
|
*/
Route::prefix('/users')->group(function () {
    Route::get('/', [Application\Users\UserController::class, 'index'])->name('api.application.users');
    Route::get('/{user:id}', [Application\Users\UserController::class, 'view'])->name('api.application.users.view');
    Route::get('/external/{external_id}', [Application\Users\ExternalUserController::class, 'index'])->name('api.application.users.external');

    Route::post('/', [Application\Users\UserController::class, 'store']);
    Route::patch('/{user:id}', [Application\Users\UserController::class, 'update']);

    Route::patch('/{user:id}/roles/assign', [Application\Users\UserController::class, 'assignRoles']);
    Route::patch('/{user:id}/roles/remove', [Application\Users\UserController::class, 'removeRoles']);

    Route::delete('/{user:id}', [Application\Users\UserController::class, 'delete']);
});

/*
|--------------------------------------------------------------------------
| Node Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /api/application/nodes
|
*/
Route::prefix('/nodes')->group(function () {
    Route::get('/', [Application\Nodes\NodeController::class, 'index'])->name('api.application.nodes');
    Route::get('/deployable', Application\Nodes\NodeDeploymentController::class);
    Route::get('/{node:id}', [Application\Nodes\NodeController::class, 'view'])->name('api.application.nodes.view');
    Route::get('/{node:id}/configuration', Application\Nodes\NodeConfigurationController::class);

    Route::post('/', [Application\Nodes\NodeController::class, 'store']);
    Route::patch('/{node:id}', [Application\Nodes\NodeController::class, 'update']);

    Route::delete('/{node:id}', [Application\Nodes\NodeController::class, 'delete']);

    Route::prefix('/{node:id}/allocations')->group(function () {
        Route::get('/', [Application\Nodes\AllocationController::class, 'index'])->name('api.application.allocations');
        Route::post('/', [Application\Nodes\AllocationController::class, 'store']);
        Route::delete('/{allocation:id}', [Application\Nodes\AllocationController::class, 'delete'])->name('api.application.allocations.view');
    });
});

/*
|--------------------------------------------------------------------------
| Server Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /api/application/servers
|
*/
Route::prefix('/servers')->group(function () {
    Route::get('/', [Application\Servers\ServerController::class, 'index'])->name('api.application.servers');
    Route::get('/{server:id}', [Application\Servers\ServerController::class, 'view'])->name('api.application.servers.view');
    Route::get('/external/{external_id}', [Application\Servers\ExternalServerController::class, 'index'])->name('api.application.servers.external');

    Route::patch('/{server:id}/details', [Application\Servers\ServerDetailsController::class, 'details'])->name('api.application.servers.details');
    Route::patch('/{server:id}/build', [Application\Servers\ServerDetailsController::class, 'build'])->name('api.application.servers.build');
    Route::patch('/{server:id}/startup', [Application\Servers\StartupController::class, 'index'])->name('api.application.servers.startup');

    Route::post('/', [Application\Servers\ServerController::class, 'store']);
    Route::post('/{server:id}/suspend', [Application\Servers\ServerManagementController::class, 'suspend'])->name('api.application.servers.suspend');
    Route::post('/{server:id}/unsuspend', [Application\Servers\ServerManagementController::class, 'unsuspend'])->name('api.application.servers.unsuspend');
    Route::post('/{server:id}/reinstall', [Application\Servers\ServerManagementController::class, 'reinstall'])->name('api.application.servers.reinstall');
    Route::post('/{server:id}/transfer', [Application\Servers\ServerManagementController::class, 'startTransfer'])->name('api.application.servers.transfer');
    Route::post('/{server:id}/transfer/cancel', [Application\Servers\ServerManagementController::class, 'cancelTransfer'])->name('api.application.servers.transfer.cancel');

    Route::delete('/{server:id}', [Application\Servers\ServerController::class, 'delete']);
    Route::delete('/{server:id}/{force?}', [Application\Servers\ServerController::class, 'delete']);

    // Database Management Endpoint
    Route::prefix('/{server:id}/databases')->group(function () {
        Route::get('/', [Application\Servers\DatabaseController::class, 'index'])->name('api.application.servers.databases');
        Route::get('/{database:id}', [Application\Servers\DatabaseController::class, 'view'])->name('api.application.servers.databases.view');

        Route::post('/', [Application\Servers\DatabaseController::class, 'store']);
        Route::post('/{database:id}/reset-password', [Application\Servers\DatabaseController::class, 'resetPassword']);

        Route::delete('/{database:id}', [Application\Servers\DatabaseController::class, 'delete']);
    });
});

/*
|--------------------------------------------------------------------------
| Map Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /api/application/maps
|
*/
Route::prefix('/maps')->group(function () {
    Route::get('/', [Application\Maps\MapController::class, 'index'])->name('api.application.maps.maps');
    Route::get('/{map:id}', [Application\Maps\MapController::class, 'view'])->name('api.application.maps.maps.view');
    Route::get('/{map:id}/export', [Application\Maps\MapController::class, 'export'])->name('api.application.maps.maps.export');
    Route::post('/import', [Application\Maps\MapController::class, 'import'])->name('api.application.maps.maps.import');
    Route::delete('/{map:id}', [Application\Maps\MapController::class, 'delete'])->name('api.application.maps.maps.delete');
    Route::delete('/uuid/{map:uuid}', [Application\Maps\MapController::class, 'delete'])->name('api.application.maps.maps.delete.uuid');
});

/*
|--------------------------------------------------------------------------
| Database Host Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /api/application/database-hosts
|
*/
Route::prefix('/database-hosts')->group(function () {
    Route::get('/', [Application\DatabaseHosts\DatabaseHostController::class, 'index'])->name('api.application.databasehosts');
    Route::get('/{database_host:id}', [Application\DatabaseHosts\DatabaseHostController::class, 'view'])->name('api.application.databasehosts.view');

    Route::post('/', [Application\DatabaseHosts\DatabaseHostController::class, 'store']);

    Route::patch('/{database_host:id}', [Application\DatabaseHosts\DatabaseHostController::class, 'update']);

    Route::delete('/{database_host:id}', [Application\DatabaseHosts\DatabaseHostController::class, 'delete']);
});

/*
|--------------------------------------------------------------------------
| Mount Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /api/application/mounts
|
*/
Route::prefix('mounts')->group(function () {
    Route::get('/', [Application\Mounts\MountController::class, 'index'])->name('api.application.mounts');
    Route::get('/{mount:id}', [Application\Mounts\MountController::class, 'view'])->name('api.application.mounts.view');
    Route::get('/{mount:id}/maps', [Application\Mounts\MountController::class, 'getMaps']);
    Route::get('/{mount:id}/nodes', [Application\Mounts\MountController::class, 'getNodes']);
    Route::get('/{mount:id}/servers', [Application\Mounts\MountController::class, 'getServers']);

    Route::post('/', [Application\Mounts\MountController::class, 'store']);
    Route::post('/{mount:id}/maps', [Application\Mounts\MountController::class, 'addMaps'])->name('api.application.mounts.maps');
    Route::post('/{mount:id}/nodes', [Application\Mounts\MountController::class, 'addNodes'])->name('api.application.mounts.nodes');
    Route::post('/{mount:id}/servers', [Application\Mounts\MountController::class, 'addServers'])->name('api.application.mounts.servers');

    Route::patch('/{mount:id}', [Application\Mounts\MountController::class, 'update']);

    Route::delete('/{mount:id}', [Application\Mounts\MountController::class, 'delete']);
    Route::delete('/{mount:id}/maps/{map_id}', [Application\Mounts\MountController::class, 'deleteMap']);
    Route::delete('/{mount:id}/nodes/{node_id}', [Application\Mounts\MountController::class, 'deleteNode']);
    Route::delete('/{mount:id}/servers/{server_id}', [Application\Mounts\MountController::class, 'deleteServer']);
});

/*
|--------------------------------------------------------------------------
| Role Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /api/application/roles
|
*/
Route::prefix('/roles')->group(function () {
    Route::get('/', [Application\Roles\RoleController::class, 'index'])->name('api.application.roles');
    Route::get('/{role:id}', [Application\Roles\RoleController::class, 'view'])->name('api.application.roles.view');

    Route::post('/', [Application\Roles\RoleController::class, 'store']);

    Route::patch('/{role:id}', [Application\Roles\RoleController::class, 'update']);

    Route::delete('/{role:id}', [Application\Roles\RoleController::class, 'delete']);
});

/*
|--------------------------------------------------------------------------
| Plugin Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /api/application/plugins
|
*/
Route::prefix('/plugins')->group(function () {
    Route::get('/', [Application\Plugins\PluginController::class, 'index'])->name('api.application.plugins');
    Route::get('/{plugin:id}', [Application\Plugins\PluginController::class, 'view'])->name('api.application.plugins.view');

    Route::post('/import/file', [Application\Plugins\PluginController::class, 'importFile']);
    Route::post('/import/url', [Application\Plugins\PluginController::class, 'importUrl']);

    Route::post('/{plugin:id}/install', [Application\Plugins\PluginController::class, 'install']);
    Route::post('/{plugin:id}/update', [Application\Plugins\PluginController::class, 'update']);
    Route::post('/{plugin:id}/uninstall', [Application\Plugins\PluginController::class, 'uninstall']);

    Route::post('/{plugin:id}/enable', [Application\Plugins\PluginController::class, 'enable']);
    Route::post('/{plugin:id}/disable', [Application\Plugins\PluginController::class, 'disable']);
});
