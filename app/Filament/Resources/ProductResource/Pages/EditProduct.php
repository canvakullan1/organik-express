<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('manageImages')
                ->label('Görselleri Yönet')
                ->icon('heroicon-o-photo')
                ->color('success')
                ->url(fn () => route('admin.product-images.index', $this->getRecord())),
            Actions\DeleteAction::make(),
        ];
    }
}
