<?php

namespace App\Livewire\Installer\Steps;

use App\Console\Commands\Map\UpdateEggIndexCommand;
use App\Enums\TablerIcon;
use Exception;
use Filament\Forms\Components\CheckboxList;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Wizard\Step;
use Illuminate\Support\Facades\Artisan;

class EggSelectionStep
{
    public static function make(): Step
    {
        try {
            Artisan::call(UpdateEggIndexCommand::class);
        } catch (Exception $exception) {
            Notification::make()
                ->title(trans('installer.map.exceptions.failed_to_update'))
                ->icon(TablerIcon::Map)
                ->body($exception->getMessage())
                ->danger()
                ->persistent()
                ->send();
        }

        $maps = cache()->get('maps.index', []);

        $categories = array_keys($maps);

        $tabs = array_map(function (string $label) use ($maps) {
            $id = str_slug($label, '_');
            $eggCount = count($maps[$label]);

            return Tab::make($id)
                ->label($label)
                ->badge($eggCount)
                ->schema([
                    CheckboxList::make("maps.$id")
                        ->hiddenLabel()
                        ->options(fn () => array_sort($maps[$label]))
                        ->searchable($eggCount > 0)
                        ->bulkToggleable($eggCount > 0)
                        ->columns(4),
                ]);
        }, $categories);

        if (empty($tabs)) {
            $tabs[] = Tab::make('no_eggs')
                ->label(trans('installer.map.no_eggs'))
                ->schema([
                    TextEntry::make('no_eggs')
                        ->hiddenLabel()
                        ->state(trans('installer.map.exceptions.no_eggs')),
                ]);
        }

        return Step::make('map')
            ->label(trans('installer.map.title'))
            ->columnSpanFull()
            ->schema([
                Tabs::make('egg_tabs')
                    ->tabs($tabs),
            ]);
    }
}
