<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ImagesRelationManager extends RelationManager
{
    protected static string $relationship = 'images';

    protected static ?string $title = 'Görseller';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\FileUpload::make('path')
                ->label('Görsel')
                ->image()
                ->disk('public')
                ->directory('products')
                ->visibility('public')
                ->imageEditor()
                // Büyük ürün fotoğrafları tarayıcıda 1600px'e küçültülür → upload payload'ı küçük
                // kalır, yükleme sunucu limitine/yavaş bağlantıya takılmaz ("Yükleniyor / Boyut
                // hesaplanıyor"). maxSize cömert (30MB): büyük PNG orijinaller de geçer, resize eder.
                ->imageResizeMode('contain')
                ->imageResizeTargetWidth('1600')
                ->imageResizeTargetHeight('1600')
                ->maxSize(30720)
                ->required()
                ->columnSpanFull(),

            Forms\Components\TextInput::make('alt')
                ->label('Alt Metin (SEO/erişilebilirlik)')
                ->maxLength(255),
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
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('Görsel Ekle'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
