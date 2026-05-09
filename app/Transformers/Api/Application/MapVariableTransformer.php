<?php

namespace App\Transformers\Api\Application;

use App\Models\Map;
use App\Models\EggVariable;

class EggVariableTransformer extends BaseTransformer
{
    /**
     * Return the resource name for the JSONAPI output.
     */
    public function getResourceName(): string
    {
        return Map::RESOURCE_NAME;
    }

    /**
     * @param  EggVariable  $model
     */
    public function transform($model): array
    {
        return $model->toArray();
    }
}
