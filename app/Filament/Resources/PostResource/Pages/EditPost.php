<?php

namespace App\Filament\Resources\PostResource\Pages;

use App\Filament\Resources\PostResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPost extends EditRecord
{
    protected static string $resource = PostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('manageImage')
                ->label('Kapak Görseli Yükle')
                ->icon('heroicon-o-photo')
                ->color('success')
                ->url(fn () => route('admin.image-field.show', ['key' => 'post', 'id' => $this->getRecord()->getKey()])),
            Actions\DeleteAction::make(),
        ];
    }
}
