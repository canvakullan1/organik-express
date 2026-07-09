<?php

namespace App\Filament\Resources\BannerResource\Pages;

use App\Filament\Resources\BannerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBanner extends EditRecord
{
    protected static string $resource = BannerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('manageImage')
                ->label('Masaüstü Görsel')
                ->icon('heroicon-o-photo')
                ->color('success')
                ->url(fn () => route('admin.image-field.show', ['key' => 'banner', 'id' => $this->getRecord()->getKey()])),
            Actions\Action::make('manageMobileImage')
                ->label('Mobil Görsel')
                ->icon('heroicon-o-device-phone-mobile')
                ->color('success')
                ->url(fn () => route('admin.image-field.show', ['key' => 'banner-mobile', 'id' => $this->getRecord()->getKey()])),
            Actions\DeleteAction::make(),
        ];
    }
}
