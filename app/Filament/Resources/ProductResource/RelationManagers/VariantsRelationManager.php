<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use App\Enums\ProductUnit;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class VariantsRelationManager extends RelationManager
{
    protected static string $relationship = 'variants';

    protected static ?string $title = 'Varyantlar (Birim / Fiyat / Stok)';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Varyant Adı')
                ->placeholder('ör. 500 gr, 1 Demet')
                ->maxLength(255),

            Forms\Components\Select::make('unit')
                ->label('Birim')
                ->options(ProductUnit::class)
                ->default(ProductUnit::Adet)
                ->required()
                ->live()
                ->afterStateUpdated(function ($state, Forms\Set $set) {
                    $unit = $state instanceof ProductUnit ? $state : ProductUnit::tryFrom((string) $state);
                    $set('is_weight_based', $unit?->isWeightBased() ?? false);
                }),

            Forms\Components\TextInput::make('unit_amount')
                ->label('Miktar')
                ->numeric()->default(1)->required()
                ->helperText('ör. 0.5 (kg), 500 (gr), 1 (adet)'),

            Forms\Components\TextInput::make('price')
                ->label('Fiyat (KDV dahil)')
                ->numeric()->prefix('₺')->required(),

            Forms\Components\TextInput::make('compare_at_price')
                ->label('Eski Fiyat (üzeri çizili)')
                ->numeric()->prefix('₺'),

            Forms\Components\TextInput::make('stock')
                ->label('Stok')
                ->numeric()->default(0),

            Forms\Components\TextInput::make('sku')->label('SKU'),

            Forms\Components\Toggle::make('track_stock')->label('Stok takibi')->default(true),
            Forms\Components\Toggle::make('is_weight_based')
                ->label('Ağırlık bazlı (tartımla fiyat değişebilir)'),
            Forms\Components\Toggle::make('is_default')->label('Varsayılan varyant'),
            Forms\Components\Toggle::make('is_active')->label('Aktif')->default(true),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Varyant')->placeholder('—'),
                Tables\Columns\TextColumn::make('unit')->label('Birim')->badge(),
                Tables\Columns\TextColumn::make('unit_amount')->label('Miktar'),
                Tables\Columns\TextColumn::make('price')->label('Fiyat')->money('TRY'),
                Tables\Columns\TextColumn::make('compare_at_price')->label('Eski Fiyat')->money('TRY')->placeholder('—'),
                Tables\Columns\TextColumn::make('stock')->label('Stok'),
                Tables\Columns\IconColumn::make('is_weight_based')->label('Tartılı')->boolean(),
                Tables\Columns\IconColumn::make('is_default')->label('Varsayılan')->boolean(),
                Tables\Columns\IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('Varyant Ekle'),
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
