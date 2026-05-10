<?php

namespace App\Transformers\Api\Client;

use App\Models\Map;

class MapTransformer extends BaseClientTransformer
{
    /**
     * Return the resource name for the JSONAPI output.
     */
    public function getResourceName(): string
    {
        return Map::RESOURCE_NAME;
    }

    /**
     * @param  Map  $map
     */
    public function transform($map): array
    {
        return [
            'uuid' => $map->uuid,
            'name' => $map->name,
        ];
    }
}
