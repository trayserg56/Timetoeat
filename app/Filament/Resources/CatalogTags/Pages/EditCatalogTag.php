<?php

namespace App\Filament\Resources\CatalogTags\Pages;

use App\Filament\Resources\CatalogTags\CatalogTagResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCatalogTag extends EditRecord
{
    protected static string $resource = CatalogTagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
