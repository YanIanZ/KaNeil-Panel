<?php

namespace App\Tests\Integration\Api\Application;

use App\Models\Map;
use App\Services\Acl\Api\AdminAcl;
use App\Transformers\Api\Application\EggTransformer;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;

class EggControllerTest extends ApplicationApiIntegrationTestCase
{
    /**
     * Test that all the maps can be returned.
     */
    public function test_list_all_eggs(): void
    {
        $maps = Map::query()->get();

        $response = $this->getJson('/api/application/maps');
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(count($maps), 'data');
        $response->assertJsonStructure([
            'object',
            'data' => [
                [
                    'object',
                    'attributes' => [
                        'id', 'uuid', 'author', 'description', 'docker_image', 'startup', 'created_at', 'updated_at',
                        'script' => ['privileged', 'install', 'entry', 'container', 'extends'],
                        'config' => [
                            'files' => [],
                            'startup' => ['done'],
                            'stop',
                            'logs' => [],
                            'extends',
                        ],
                    ],
                ],
            ],
        ]);

        foreach (array_get($response->json(), 'data') as $datum) {
            $map = $maps->where('id', '=', $datum['attributes']['id'])->first();

            $expected = json_encode(Arr::sortRecursive($datum['attributes']));
            $actual = json_encode(Arr::sortRecursive($this->getTransformer(EggTransformer::class)->transform($map)));

            $this->assertSame(
                $expected,
                $actual,
                'Unable to find JSON fragment: ' . PHP_EOL . PHP_EOL . "[$expected]" . PHP_EOL . PHP_EOL . 'within' . PHP_EOL . PHP_EOL . "[$actual]."
            );
        }
    }

    /**
     * Test that a single map can be returned.
     */
    public function test_return_single_egg(): void
    {
        $map = Map::query()->findOrFail(1);

        $response = $this->getJson('/api/application/maps/' . $map->id);
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonStructure([
            'object',
            'attributes' => [
                'id', 'uuid', 'author', 'description', 'docker_image', 'startup', 'script' => [], 'config' => [], 'created_at', 'updated_at',
            ],
        ]);

        $response->assertJson([
            'object' => 'map',
            'attributes' => $this->getTransformer(EggTransformer::class)->transform($map),
        ], true);
    }

    /**
     * Test that a single map and all the defined relationships can be returned.
     */
    public function test_return_single_egg_with_relationships(): void
    {
        $map = Map::query()->findOrFail(1);

        $response = $this->getJson('/api/application/maps/' . $map->id . '?include=servers,variables');
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonStructure([
            'object',
            'attributes' => [
                'relationships' => [
                    'servers' => ['object', 'data' => []],
                    'variables' => ['object', 'data' => []],
                ],
            ],
        ]);
    }

    /**
     * Test that a missing map returns a 404 error.
     */
    public function test_get_missing_egg(): void
    {
        $response = $this->getJson('/api/application/maps/12345');
        $this->assertNotFoundJson($response);
    }

    /**
     * Test that an authentication error occurs if a key does not have permission
     * to access a resource.
     */
    public function test_error_returned_if_no_permission(): void
    {
        $map = Map::query()->findOrFail(1);
        $this->createNewDefaultApiKey($this->getApiUser(), [Map::RESOURCE_NAME => AdminAcl::NONE]);

        $response = $this->getJson('/api/application/maps');
        $this->assertAccessDeniedJson($response);
    }
}
