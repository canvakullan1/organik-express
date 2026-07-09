<?php

namespace App\Filament\Resources\BundleResource\Pages;

use App\Filament\Resources\BundleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBundle extends EditRecord
{
    protected static string $resource = BundleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('manageImage')
                ->label('Görsel Yükle')
                ->icon('heroicon-o-photo')
                ->color('success')
                ->url(fn () => route('admin.image-field.show', ['key' => 'bundle', 'id' => $this->getRecord()->getKey()])),
            Actions\DeleteAction::make(),
        ];
    }
}
