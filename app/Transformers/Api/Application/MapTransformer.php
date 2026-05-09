<?php

namespace App\Transformers\Api\Application;

use App\Models\Map;
use App\Models\MapVariable;
use App\Models\Server;
use Illuminate\Support\Arr;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\NullResource;

class EggTransformer extends BaseTransformer
{
    /**
     * Relationships that can be loaded onto this transformation.
     */
    protected array $availableIncludes = [
        'servers',
        'config',
        'script',
        'variables',
    ];

    /**
     * Return the resource name for the JSONAPI output.
     */
    public function getResourceName(): string
    {
        return Map::RESOURCE_NAME;
    }

    /**
     * @param  Map  $model
     */
    public function transform($model): array
    {
        $model->loadMissing('configFrom');

        $files = json_decode($model->inherit_config_files ?: '{}', true, 512, JSON_THROW_ON_ERROR);

        $model->loadMissing('scriptFrom');

        return [
            'id' => $model->id,
            'uuid' => $model->uuid,
            'name' => $model->name,
            'author' => $model->author,
            'description' => $model->description,
            'icon' => $model->icon,
            'features' => $model->features,
            'tags' => $model->tags,
            'docker_image' => Arr::first($model->docker_images, default: ''), // deprecated, use docker_images
            'docker_images' => $model->docker_images,
            'config' => [
                'files' => $files,
                'startup' => json_decode($model->inherit_config_startup ?: '{}', true),
                'stop' => $model->inherit_config_stop,
                'logs' => json_decode($model->inherit_config_logs ?: '{}', true),
                'file_denylist' => $model->inherit_file_denylist,
                'extends' => $model->config_from,
            ],
            'startup' => Arr::first($model->startup_commands, default: ''), // deprecated, use startup_commands
            'startup_commands' => $model->startup_commands,
            'script' => [
                'privileged' => $model->script_is_privileged,
                'install' => $model->copy_script_install,
                'entry' => $model->copy_script_entry,
                'container' => $model->copy_script_container,
                'extends' => $model->copy_script_from,
            ],
            $model->getCreatedAtColumn() => $this->formatTimestamp($model->created_at),
            $model->getUpdatedAtColumn() => $this->formatTimestamp($model->updated_at),
        ];
    }

    /**
     * Include the Servers relationship for the given Map in the transformation.
     */
    public function includeServers(Map $model): Collection|NullResource
    {
        if (!$this->authorize(Server::RESOURCE_NAME)) {
            return $this->null();
        }

        $model->loadMissing('servers');

        return $this->collection($model->getRelation('servers'), $this->makeTransformer(ServerTransformer::class), Server::RESOURCE_NAME);
    }

    /**
     * Include the variables that are defined for this Map.
     */
    public function includeVariables(Map $model): Collection|NullResource
    {
        if (!$this->authorize(Map::RESOURCE_NAME)) {
            return $this->null();
        }

        $model->loadMissing('variables');

        return $this->collection(
            $model->getRelation('variables'),
            $this->makeTransformer(MapVariableTransformer::class),
            MapVariable::RESOURCE_NAME
        );
    }
}
