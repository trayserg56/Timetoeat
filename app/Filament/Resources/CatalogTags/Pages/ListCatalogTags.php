<?php

namespace App\Filament\Resources\CatalogTags\Pages;

use App\Filament\Resources\CatalogTags\CatalogTagResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCatalogTags extends ListRecords
{
    protected static string $resource = CatalogTagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
