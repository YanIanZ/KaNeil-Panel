<?php

namespace App\Transformers\Api\Application;

use App\Models\Map;
use App\Models\Mount;
use App\Models\Node;
use App\Models\Server;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\NullResource;

class MountTransformer extends BaseTransformer
{
    /**
     * List of resources that can be included.
     */
    protected array $availableIncludes = ['maps', 'nodes', 'servers'];

    /**
     * Return the resource name for the JSONAPI output.
     */
    public function getResourceName(): string
    {
        return Mount::RESOURCE_NAME;
    }

    /**
     * @param  Mount  $model
     */
    public function transform($model): array
    {
        return $model->toArray();
    }

    /**
     * Return the maps associated with this mount.
     */
    public function includeMaps(Mount $mount): Collection|NullResource
    {
        if (!$this->authorize(Map::RESOURCE_NAME)) {
            return $this->null();
        }

        $mount->loadMissing('maps');

        return $this->collection(
            $mount->getRelation('maps'),
            $this->makeTransformer(MapTransformer::class),
            'map'
        );
    }

    /**
     * @deprecated Use ?include=maps. Kept so existing API consumers continue to work.
     */
    public function includeEggs(Mount $mount): Collection|NullResource
    {
        return $this->includeMaps($mount);
    }

    /**
     * Return the nodes associated with this mount.
     */
    public function includeNodes(Mount $mount): Collection|NullResource
    {
        if (!$this->authorize(Node::RESOURCE_NAME)) {
            return $this->null();
        }

        $mount->loadMissing('nodes');

        return $this->collection(
            $mount->getRelation('nodes'),
            $this->makeTransformer(NodeTransformer::class),
            'node'
        );
    }

    /**
     * Return the servers associated with this mount.
     */
    public function includeServers(Mount $mount): Collection|NullResource
    {
        if (!$this->authorize(Server::RESOURCE_NAME)) {
            return $this->null();
        }

        $mount->loadMissing('servers');

        return $this->collection(
            $mount->getRelation('servers'),
            $this->makeTransformer(ServerTransformer::class),
            'server'
        );
    }
}
