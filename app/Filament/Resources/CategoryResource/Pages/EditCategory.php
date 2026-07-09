<?php

namespace App\Filament\Resources\CategoryResource\Pages;

use App\Filament\Resources\CategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCategory extends EditRecord
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('manageImage')
                ->label('Görsel Yükle')
                ->icon('heroicon-o-photo')
                ->color('success')
                ->url(fn () => route('admin.image-field.show', ['key' => 'category', 'id' => $this->getRecord()->getKey()])),
            Actions\DeleteAction::make(),
        ];
    }
}
