<?php

namespace App\Http\Controllers\Api\Application\Maps;

use App\Enums\EggFormat;
use App\Http\Controllers\Api\Application\ApplicationApiController;
use App\Http\Requests\Api\Application\Maps\ExportEggRequest;
use App\Http\Requests\Api\Application\Maps\GetEggRequest;
use App\Http\Requests\Api\Application\Maps\GetEggsRequest;
use App\Http\Requests\Api\Application\Maps\ImportEggRequest;
use App\Models\Map;
use App\Services\Maps\Sharing\EggExporterService;
use App\Services\Maps\Sharing\EggImporterService;
use App\Transformers\Api\Application\EggTransformer;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class EggController extends ApplicationApiController
{
    public function __construct(
        private EggExporterService $exporterService,
        private EggImporterService $importService
    ) {
        parent::__construct();
    }

    /**
     * List maps
     *
     * Return all maps
     *
     * @return array<mixed>
     */
    public function index(GetEggsRequest $request): array
    {
        return $this->fractal->collection(Map::all())
            ->transformWith($this->getTransformer(EggTransformer::class))
            ->toArray();
    }

    /**
     * View map
     *
     * Return a single map that exists
     *
     * @return array<mixed>
     */
    public function view(GetEggRequest $request, Map $map): array
    {
        return $this->fractal->item($map)
            ->transformWith($this->getTransformer(EggTransformer::class))
            ->toArray();
    }

    /**
     * Delete map
     *
     * Delete an map from the Panel.
     *
     * @throws Exception
     */
    public function delete(GetEggRequest $request, Map $map): Response
    {
        $map->delete();

        return $this->returnNoContent();
    }

    /**
     * Export map
     *
     * Return a single map as yaml or json file (defaults to YAML)
     */
    public function export(ExportEggRequest $request, Map $map): StreamedResponse
    {
        $format = EggFormat::tryFrom($request->input('format')) ?? EggFormat::YAML;

        return response()->streamDownload(function () use ($map, $format) {
            echo $this->exporterService->handle($map->id, $format);
        }, 'map-' . $map->getKebabName() . '.' . $format->value, [
            'Content-Type' => 'application/' . $format->value,
        ]);
    }

    /**
     * Import map
     *
     * Create a new map on the Panel. Returns the created map and an HTTP/201 status response on success
     * If no uuid is supplied a new one will be generated
     * If an uuid is supplied, and it already exists the old configuration get overwritten
     *
     * @throws Exception|Throwable
     */
    public function import(ImportEggRequest $request): JsonResponse
    {
        $map = $this->importService->fromContent($request->getContent());

        return $this->fractal->item($map)
            ->transformWith($this->getTransformer(EggTransformer::class))
            ->respond(201);
    }
}
