<?php

namespace App\Filament\Admin\Resources\SubCategoryResource\Pages;

use App\Filament\Admin\Resources\SubCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSubCategory extends ViewRecord
{
    protected static string $resource = SubCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
