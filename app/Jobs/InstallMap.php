<?php

namespace App\Jobs;

use App\Services\Maps\Sharing\MapImporterService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class InstallMap implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 15;

    public function __construct(public string $downloadUrl) {}

    /**
     * @throws Throwable
     */
    public function handle(MapImporterService $mapImporterService): void
    {
        try {
            $mapImporterService->fromUrl($this->downloadUrl);
        } catch (Throwable $e) {
            Log::error('Failed to install map from URL: ' . $this->downloadUrl, ['exception' => $e]);
        }
    }
}
