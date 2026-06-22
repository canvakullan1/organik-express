<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CarrierResource\Pages;
use App\Models\Carrier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CarrierResource extends Resource
{
    protected static ?string $model = Carrier::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationGroup = 'Satış';

    protected static ?string $navigationLabel = 'Kargo Firmaları';

    protected static ?string $modelLabel = 'Kargo Firması';

    protected static ?string $pluralModelLabel = 'Kargo Firmaları';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->label('Firma Adı')->required()->placeholder('Yurtiçi Kargo'),
            Forms\Components\TextInput::make('tracking_url_template')->label('Takip URL Şablonu')
                ->placeholder('https://www.yurticikargo.com/tr/online-servisler/gonderi-sorgula?code={code}')
                ->helperText('{code} takip numarasıyla değiştirilir. Müşteri sipariş detayında tıklanabilir link görür.')
                ->columnSpanFull(),
            Forms\Components\TextInput::make('sort_order')->label('Sıra')->numeric()->default(0),
            Forms\Components\Toggle::make('is_active')->label('Aktif')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Firma')->searchable()->weight('medium'),
                Tables\Columns\TextColumn::make('tracking_url_template')->label('Takip URL')->limit(40)->placeholder('—')->toggleable(),
                Tables\Columns\IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCarriers::route('/'),
            'create' => Pages\CreateCarrier::route('/create'),
            'edit' => Pages\EditCarrier::route('/{record}/edit'),
        ];
    }
}
