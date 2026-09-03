<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Ürün görselleri: listeleme, sıralama, alt metin ve silme.
 *
 * YÜKLEME BURADA YAPILMAZ. Filament FileUpload (FilePond + Livewire geçici-upload)
 * bu LiteSpeed/cPanel ortamında tamamlanmıyor; yükleme çubuğu %100'de takılı kalıyordu.
 * Bu yüzden yükleme, klasik multipart form kullanan "Görselleri Yönet" sayfasına
 * (admin.product-images.index) taşındı; buradaki buton oraya götürür.
 */
class ImagesRelationManager extends RelationManager
{
    protected static string $relationship = 'images';

    protected static ?string $title = 'Görseller';

    public function form(Form $form): Form
    {
        // Yalnızca alt metin düzenlenir; görsel dosyası "Görselleri Yönet" sayfasından.
        return $form->schema([
            Forms\Components\TextInput::make('alt')
                ->label('Alt Metin (SEO/erişilebilirlik)')
                ->maxLength(255)
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('path')->label('Görsel'),
                Tables\Columns\TextColumn::make('alt')->label('Alt Metin')->placeholder('—'),
            ])
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->emptyStateHeading('Henüz görsel yok')
            ->emptyStateDescription('Görsel eklemek için "Görsel Yükle" düğmesini kullanın.')
            ->headerActions([
                Tables\Actions\Action::make('uploadImages')
                    ->label('Görsel Yükle')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('success')
                    ->url(fn () => route('admin.product-images.index', $this->getOwnerRecord())),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Alt Metin'),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
