<?php

namespace App\Tests\Traits\Integration;

use App\Enums\SubuserPermission;
use App\Models\Allocation;
use App\Models\Map;
use App\Models\Node;
use App\Models\Server;
use App\Models\Subuser;
use App\Models\User;
use Ramsey\Uuid\Uuid;

trait CreatesTestModels
{
    /**
     * Creates a server model in the databases for the purpose of testing. If an attribute
     * is passed in that normally requires this function to create a model no model will be
     * created and that attribute's value will be used.
     *
     * The returned server model will have all the relationships loaded onto it.
     */
    public function createServerModel(array $attributes = []): Server
    {
        if (isset($attributes['user_id'])) {
            $attributes['owner_id'] = $attributes['user_id'];
        }

        if (!isset($attributes['owner_id'])) {
            /** @var User $user */
            $user = User::factory()->create();
            $attributes['owner_id'] = $user->id;
        }

        if (!isset($attributes['node_id'])) {
            /** @var Node $node */
            $node = Node::factory()->create();
            $attributes['node_id'] = $node->id;
        }

        if (!isset($attributes['allocation_id'])) {
            /** @var Allocation $allocation */
            $allocation = Allocation::factory()->create(['node_id' => $attributes['node_id']]);
            $attributes['allocation_id'] = $allocation->id;
        }

        if (empty($attributes['map_id'])) {
            $map = $this->getBungeecordEgg();

            $attributes['map_id'] = $map->id;
        }

        unset($attributes['user_id']);

        /** @var Server $server */
        $server = Server::factory()->create($attributes);

        Allocation::query()->where('id', $server->allocation_id)->update(['server_id' => $server->id]);

        return $server->fresh([
            'user', 'node', 'allocation', 'map',
        ]);
    }

    /**
     * Generates a user and a server for that user. If an array of permissions is passed it
     * is assumed that the user is actually a subuser of the server.
     *
     * @param  array<string|SubuserPermission>  $permissions
     * @return array{User, Server}
     */
    public function generateTestAccount(array $permissions = []): array
    {
        /** @var User $user */
        $user = User::factory()->create();

        if (empty($permissions)) {
            return [$user, $this->createServerModel(['user_id' => $user->id])];
        }

        $server = $this->createServerModel();

        Subuser::query()->create([
            'user_id' => $user->id,
            'server_id' => $server->id,
            'permissions' => array_map(fn ($permission) => $permission instanceof SubuserPermission ? $permission->value : $permission, $permissions),
        ]);

        return [$user, $server];
    }

    /**
     * Clones a given map allowing us to make modifications that don't affect other
     * tests that rely on the map existing in the correct state.
     */
    protected function cloneEggAndVariables(Map $map): Map
    {
        $model = $map->replicate(['id', 'uuid']);
        $model->uuid = Uuid::uuid4()->toString();
        $model->push();

        /** @var Map $model */
        $model = $model->fresh();

        foreach ($map->variables as $variable) {
            $variable->replicate(['id', 'map_id'])->forceFill(['map_id' => $model->id])->push();
        }

        return $model->fresh();
    }

    /**
     * Almost every test just assumes it is using BungeeCord — this is the critical
     * map model for all tests unless specified otherwise.
     */
    private function getBungeecordEgg(): Map
    {
        /** @var Map $map */
        $map = Map::query()->where('author', 'panel@example.com')->where('name', 'Bungeecord')->firstOrFail();

        return $map;
    }
}
