<?php

namespace App\Filament\Admin\Resources\Maps;

use App\Enums\CustomizationKey;
use App\Enums\TablerIcon;
use App\Filament\Admin\Resources\Maps\Pages\CreateMap;
use App\Filament\Admin\Resources\Maps\Pages\EditMap;
use App\Filament\Admin\Resources\Maps\Pages\ListMaps;
use App\Filament\Admin\Resources\Maps\RelationManagers\ServersRelationManager;
use App\Models\Map;
use App\Traits\Filament\CanCustomizePages;
use App\Traits\Filament\CanCustomizeRelations;
use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Resource;

class MapResource extends Resource
{
    use CanCustomizePages;
    use CanCustomizeRelations;

    protected static ?string $model = Map::class;

    protected static string|BackedEnum|null $navigationIcon = TablerIcon::Maps;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationBadge(): ?string
    {
        return ($count = static::getModel()::count()) > 0 ? (string) $count : null;
    }

    public static function getNavigationGroup(): ?string
    {
        return user()?->getCustomization(CustomizationKey::TopNavigation) ? false : 'Templates';
    }

    public static function getNavigationLabel(): string
    {
        return trans('admin/map.nav_title');
    }

    public static function getModelLabel(): string
    {
        return trans('admin/map.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return trans('admin/map.model_label_plural');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'tags', 'uuid', 'id'];
    }

    /** @return class-string<RelationManager>[] */
    public static function getDefaultRelations(): array
    {
        return [
            ServersRelationManager::class,
        ];
    }

    /** @return array<string, PageRegistration> */
    public static function getDefaultPages(): array
    {
        return [
            'index' => ListMaps::route('/'),
            'create' => CreateMap::route('/create'),
            'edit' => EditMap::route('/{record}/edit'),
        ];
    }
}
