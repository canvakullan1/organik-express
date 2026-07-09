<?php

namespace App\Filament\Resources\ProducerResource\Pages;

use App\Filament\Resources\ProducerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProducer extends EditRecord
{
    protected static string $resource = ProducerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('manageImage')
                ->label('Görsel Yükle')
                ->icon('heroicon-o-photo')
                ->color('success')
                ->url(fn () => route('admin.image-field.show', ['key' => 'producer', 'id' => $this->getRecord()->getKey()])),
            Actions\DeleteAction::make(),
        ];
    }
}
