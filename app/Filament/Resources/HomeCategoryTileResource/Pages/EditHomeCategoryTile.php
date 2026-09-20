<?php

namespace App\Filament\Resources\HomeCategoryTileResource\Pages;

use App\Filament\Resources\HomeCategoryTileResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHomeCategoryTile extends EditRecord
{
    protected static string $resource = HomeCategoryTileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('manageImage')
                ->label('Görsel Yükle')
                ->icon('heroicon-o-photo')
                ->color('success')
                ->url(fn () => route('admin.image-field.show', ['key' => 'home-category-tile', 'id' => $this->getRecord()->getKey()])),
            Actions\DeleteAction::make(),
        ];
    }
}
