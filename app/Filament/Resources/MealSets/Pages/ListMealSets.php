<?php

namespace App\Filament\Resources\MealSets\Pages;

use App\Filament\Resources\MealSets\MealSetResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMealSets extends ListRecords
{
    protected static string $resource = MealSetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
