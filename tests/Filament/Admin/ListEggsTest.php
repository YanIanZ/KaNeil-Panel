<?php

use App\Enums\RolePermissionModels;
use App\Filament\Admin\Resources\Maps\Pages\ListEggs;
use App\Models\Map;
use App\Models\Role;

use function Pest\Livewire\livewire;

it('root admin can see all maps', function () {
    $maps = Map::all();
    [$admin] = generateTestAccount([]);
    $admin = $admin->syncRoles(Role::getRootAdmin());

    $this->actingAs($admin);
    livewire(ListEggs::class)
        ->assertSuccessful()
        ->assertCountTableRecords($maps->count())
        ->assertCanSeeTableRecords($maps);
});

it('non root admin cannot see any maps', function () {
    $role = Role::factory()->create(['name' => 'Node Viewer', 'guard_name' => 'web']);
    // Node Permission is on purpose, we check the wrong permissions.
    $permission = Permission::factory()->create(['name' => RolePermissionModels::Node->viewAny(), 'guard_name' => 'web']);
    $role->permissions()->attach($permission);
    [$user] = generateTestAccount([]);

    $this->actingAs($user);
    livewire(ListEggs::class)
        ->assertForbidden();
});

it('non root admin with permissions can see maps', function () {
    $role = Role::factory()->create(['name' => 'Map Viewer', 'guard_name' => 'web']);
    $permission = Permission::factory()->create(['name' => RolePermissionModels::Map->viewAny(), 'guard_name' => 'web']);
    $role->permissions()->attach($permission);

    $maps = Map::all();
    [$user] = generateTestAccount([]);
    $user = $user->syncRoles($role);

    $this->actingAs($user);
    livewire(ListEggs::class)
        ->assertSuccessful()
        ->assertCountTableRecords($maps->count())
        ->assertCanSeeTableRecords($maps);
});
