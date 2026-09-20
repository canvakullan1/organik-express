<?php

namespace App\Filament\Resources\HomeCategoryTileResource\Pages;

use App\Filament\Resources\HomeCategoryTileResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHomeCategoryTiles extends ListRecords
{
    protected static string $resource = HomeCategoryTileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
