<?php

namespace App\Filament\Resources\MealSets\Pages;

use App\Filament\Resources\MealSets\MealSetResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMealSet extends EditRecord
{
    protected static string $resource = MealSetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
