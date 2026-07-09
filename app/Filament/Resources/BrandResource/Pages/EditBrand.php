<?php

namespace App\Filament\Resources\BrandResource\Pages;

use App\Filament\Resources\BrandResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBrand extends EditRecord
{
    protected static string $resource = BrandResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('manageImage')
                ->label('Logo Yükle')
                ->icon('heroicon-o-photo')
                ->color('success')
                ->url(fn () => route('admin.image-field.show', ['key' => 'brand', 'id' => $this->getRecord()->getKey()])),
            Actions\DeleteAction::make(),
        ];
    }
}
