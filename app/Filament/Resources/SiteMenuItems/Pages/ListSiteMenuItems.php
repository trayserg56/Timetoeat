<?php

namespace App\Filament\Resources\SiteMenuItems\Pages;

use App\Filament\Resources\SiteMenuItems\SiteMenuItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSiteMenuItems extends ListRecords
{
    protected static string $resource = SiteMenuItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
