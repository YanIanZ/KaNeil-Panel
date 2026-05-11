<?php

namespace App\Filament\Components\Actions;

use App\Enums\TablerIcon;
use App\Models\Map;
use App\Services\Maps\Sharing\MapImporterService;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;

class UpdateMapBulkAction extends BulkAction
{
    public static function getDefaultName(): ?string
    {
        return 'update';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(trans_choice('admin/map.update', 2));

        $this->icon(TablerIcon::CloudDownload);

        $this->color('success');

        $this->requiresConfirmation();

        $this->modalHeading(trans_choice('admin/map.update_question', 2));

        $this->modalDescription(trans_choice('admin/map.update_description', 2));

        $this->modalIconColor('danger');

        $this->modalSubmitAction(fn (Action $action) => $action->color('danger'));

        $this->action(function (Collection $records, MapImporterService $mapImporterService) {
            if ($records->count() === 0) {
                Notification::make()
                    ->title(trans('admin/map.no_updates'))
                    ->warning()
                    ->send();

                return;
            }

            $successMaps = collect();
            $failedMaps = collect();
            $skippedMaps = collect();

            /** @var Map $map */
            foreach ($records as $map) {
                if ($map->update_url === null) {
                    $skippedMaps->push($map->name);

                    continue;
                }
                try {
                    $mapImporterService->fromUrl($map->update_url, $map);

                    $successMaps->push($map->name);

                    cache()->forget("maps.$map->uuid.update");
                } catch (Exception $exception) {
                    $failedMaps->push($map->name);

                    report($exception);
                }
            }

            $bodyParts = collect([
                $successMaps->isNotEmpty() ? trans('admin/map.updated_maps', ['maps' => $successMaps->join(', ')]) : null,
                $failedMaps->isNotEmpty() ? trans('admin/map.failed_maps', ['maps' => $failedMaps->join(', ')]) : null,
                $skippedMaps->isNotEmpty() ? trans('admin/map.skipped_maps', ['maps' => $skippedMaps->join(', ')]) : null,
            ])->filter();

            Notification::make()
                ->title(trans_choice('admin/map.updated', 2, ['count' => $successMaps->count(), 'total' => $records->count()]))
                ->body($bodyParts->join(' | '))
                ->status($failedMaps->isNotEmpty() ? 'warning' : 'success')
                ->persistent()
                ->send();
        });

        $this->authorize(fn () => user()?->can('import map'));

        $this->deselectRecordsAfterCompletion();
    }
}
