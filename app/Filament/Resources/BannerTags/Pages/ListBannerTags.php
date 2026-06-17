<?php

namespace App\Filament\Resources\BannerTags\Pages;

use App\Filament\Resources\BannerTags\BannerTagResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBannerTags extends ListRecords
{
    protected static string $resource = BannerTagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
