<?php

namespace App\Filament\Components\Actions;

use App\Console\Commands\Map\UpdateMapIndexCommand;
use App\Enums\TablerIcon;
use App\Jobs\InstallMap;
use App\Models\Map;
use App\Services\Maps\Sharing\MapImporterService;
use Closure;
use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Enums\Width;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ImportMapAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'import';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->tooltip(trans('filament-actions::import.modal.actions.import.label'));

        $this->hiddenLabel();

        $this->icon(TablerIcon::FileImport);

        $this->modalWidth(Width::ScreenExtraLarge);

        $this->authorize(fn () => user()?->can('import map'));

        $this->action(function (array $data, MapImporterService $mapImportService): void {

            $gitHubEggs = array_get($this->data, 'maps', []);
            $maps = array_merge(collect($data['urls'])->flatten()->whereNotNull()->unique()->all(), Arr::wrap($data['files']));

            if ($gitHubEggs) {
                foreach ($gitHubEggs as $category => $sortedEggs) {
                    foreach ($sortedEggs as $downloadUrl) {
                        InstallMap::dispatch($downloadUrl);
                    }
                }

                Notification::make()
                    ->title(trans('installer.map.background_install_started'))
                    ->body(trans('installer.map.background_install_description', ['count' => array_sum(array_map('count', $gitHubEggs))]))
                    ->success()
                    ->persistent()
                    ->send();

            }

            if (empty($maps)) {
                return;
            }

            [$success, $failed] = [collect(), collect()];

            foreach ($maps as $map) {
                if ($map instanceof TemporaryUploadedFile) {
                    $originalName = $map->getClientOriginalName();
                    $filename = str($originalName)->afterLast('map-');
                    $ext = str($originalName)->afterLast('.')->lower()->toString();

                    $name = match ($ext) {
                        'json' => $filename->before('.json')->headline(),
                        'yaml' => $filename->before('.yaml')->headline(),
                        'yml' => $filename->before('.yml')->headline(),
                        default => $filename->headline(),
                    };
                    $method = 'fromFile';
                } else {
                    $map = str($map);
                    $map = $map->contains('github.com') ? $map->replaceFirst('blob', 'raw') : $map;
                    $method = 'fromUrl';

                    $filename = $map->afterLast('/map-');
                    $ext = $filename->afterLast('.')->lower()->toString();

                    $name = match ($ext) {
                        'json' => $filename->before('.json')->headline(),
                        'yaml' => $filename->before('.yaml')->headline(),
                        'yml' => $filename->before('.yml')->headline(),
                        default => $filename->headline(),
                    };
                }
                try {
                    $mapImportService->$method($map);
                    $success->push($name);
                } catch (Exception $exception) {
                    $failed->push($name);
                    report($exception);
                }
            }

            $bodyParts = collect([
                $success->isNotEmpty() ? trans('admin/map.import.imported_maps', ['maps' => $success->join(', ')]) : null,
                $failed->isNotEmpty() ? trans('admin/map.import.failed_import_maps', ['maps' => $failed->join(', ')]) : null,
            ])->filter();

            if ($bodyParts->isNotEmpty()) {
                Notification::make()
                    ->title(trans('admin/map.import.import_result', [
                        'success' => $success->count(),
                        'failed' => $failed->count(),
                        'total' => $success->count() + $failed->count(),
                    ]))
                    ->body($bodyParts->join(' | '))
                    ->status($failed->isEmpty() ? 'success' : ($success->isEmpty() ? 'danger' : 'warning'))
                    ->send();
            }
        });
    }

    public function multiple(bool|Closure $condition = true): static
    {
        $isMultiple = (bool) $this->evaluate($condition);
        $this->schema([
            Tabs::make('Tabs')
                ->contained(false)
                ->tabs([
                    $this->importEggsFromGitHub(),
                    Tab::make('file')
                        ->label(trans('admin/map.import.file'))
                        ->icon(TablerIcon::FileUpload)
                        ->schema([
                            FileUpload::make('files')
                                ->label(trans('admin/map.model_label'))
                                ->hint(trans('admin/map.import.map_help'))
                                ->acceptedFileTypes(['application/json', 'application/x-yaml', 'text/yaml', '.yaml', '.yml'])
                                ->preserveFilenames()
                                ->previewable(false)
                                ->storeFiles(false)
                                ->multiple($isMultiple),
                        ]),
                    Tab::make('url')
                        ->label(trans('admin/map.import.url'))
                        ->icon(TablerIcon::WorldUpload)
                        ->schema([
                            Repeater::make('urls')
                                ->hiddenLabel()
                                ->itemLabel(fn (array $state) => str($state['url'])->afterLast('/map-')->beforeLast('.')->headline())
                                ->hint(trans('admin/map.import.url_help'))
                                ->addActionLabel(trans('admin/map.import.add_url'))
                                ->grid($isMultiple ? 2 : null)
                                ->reorderable(false)
                                ->addable($isMultiple)
                                ->deletable(fn (array $state) => count($state) > 1)
                                ->schema([
                                    TextInput::make('url')
                                        ->default(fn (?Map $map) => $map->update_url ?? '')
                                        ->live()
                                        ->label(trans('admin/map.import.url'))
                                        ->placeholder('https://github.com/kaneil-maps/generic/blob/main/nodejs/map-node-js-generic.json')
                                        ->url()
                                        ->endsWith(['.json', '.yaml', '.yml'])
                                        ->validationAttribute(trans('admin/map.import.url')),
                                ]),
                        ]),
                ]),
        ]);

        return $this;
    }

    public function importEggsFromGitHub(): Tab
    {
        if (!cache()->get('maps.index')) {
            Artisan::call(UpdateMapIndexCommand::class);
        }

        $maps = cache()->get('maps.index', []);
        $categories = array_keys($maps);
        $tabs = array_map(function (string $label) use ($maps) {
            $id = str_slug($label, '_');
            $mapCount = count($maps[$label]);

            return Tab::make($id)
                ->label($label)
                ->badge($mapCount)
                ->schema([
                    CheckboxList::make("maps.$id")
                        ->hiddenLabel()
                        ->options(fn () => array_sort($maps[$label]))
                        ->searchable($mapCount > 0)
                        ->bulkToggleable($mapCount > 0)
                        ->columns(4),
                ]);
        }, $categories);

        if (empty($tabs)) {
            $tabs[] = Tab::make('no_maps')
                ->label(trans('installer.map.no_maps'))
                ->schema([
                    TextEntry::make('no_maps')
                        ->hiddenLabel()
                        ->state(trans('installer.map.exceptions.no_maps')),
                ]);
        }

        return Tab::make('github')
            ->label(trans('admin/map.import.github'))
            ->icon(TablerIcon::BrandGithub)
            ->columnSpanFull()
            ->schema([
                Tabs::make('map_tabs')
                    ->tabs($tabs),
            ]);
    }
}
