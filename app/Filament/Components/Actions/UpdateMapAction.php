<?php

namespace App\Filament\Components\Actions;

use App\Enums\TablerIcon;
use App\Models\Map;
use App\Services\Maps\Sharing\MapImporterService;
use Exception;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class UpdateMapAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'update';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->tooltip(trans_choice('admin/map.update', 1));

        $this->icon(TablerIcon::CloudDownload);

        $this->color('success');

        $this->requiresConfirmation();

        $this->modalHeading(trans_choice('admin/map.update_question', 1));

        $this->modalDescription(trans_choice('admin/map.update_description', 1));

        $this->modalIconColor('danger');

        $this->modalSubmitAction(fn (Action $action) => $action->color('danger'));

        $this->action(function (Map $map, MapImporterService $eggImporterService) {
            try {
                $eggImporterService->fromUrl($map->update_url, $map);

                cache()->forget("maps.$map->uuid.update");
            } catch (Exception $exception) {
                Notification::make()
                    ->title(trans('admin/map.update_failed', ['map' => $map->name]))
                    ->body(trans('admin/map.update_error', ['error' => $exception->getMessage()]))
                    ->danger()
                    ->send();

                report($exception);

                return;
            }

            Notification::make()
                ->title(trans('admin/map.update_success', ['map' => $map->name]))
                ->body(trans('admin/map.updated_from', ['url' => $map->update_url]))
                ->success()
                ->send();
        });

        $this->authorize(fn () => user()?->can('import map'));

        $this->visible(fn (Map $map) => cache()->get("maps.$map->uuid.update", false));
    }
}
