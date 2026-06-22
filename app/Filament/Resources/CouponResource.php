<?php

namespace App\Filament\Resources;

use App\Enums\DiscountType;
use App\Filament\Resources\CouponResource\Pages;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationGroup = 'Satış';

    protected static ?string $navigationLabel = 'Kuponlar';

    protected static ?string $modelLabel = 'Kupon';

    protected static ?string $pluralModelLabel = 'Kuponlar';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Kupon')->schema([
                Forms\Components\TextInput::make('code')->label('Kod')->required()
                    ->unique(ignoreRecord: true)->placeholder('TAZE10')
                    ->helperText('Otomatik büyük harfe çevrilir.'),
                Forms\Components\TextInput::make('description')->label('Açıklama'),
                Forms\Components\Select::make('type')->label('İndirim Tipi')->options(DiscountType::class)->default(DiscountType::Percent)->required()->live(),
                Forms\Components\TextInput::make('value')->label('Değer')->numeric()->required()
                    ->suffix(fn (Forms\Get $get) => $get('type') === DiscountType::Percent->value ? '%' : '₺'),
                Forms\Components\TextInput::make('min_subtotal')->label('Min. Sepet (₺)')->numeric()->default(0),
                Forms\Components\TextInput::make('max_discount')->label('Maks. İndirim (₺)')->numeric()
                    ->helperText('Yüzde indirimde tavan (opsiyonel).'),
            ])->columns(2),

            Forms\Components\Section::make('Kapsam')->schema([
                Forms\Components\Select::make('scope')->label('Uygulanacağı')->options(Coupon::SCOPES)->default('all')->required()->live(),
                Forms\Components\Select::make('scope_ids')
                    ->label(fn (Forms\Get $get) => $get('scope') === 'product' ? 'Ürünler' : 'Kategoriler')
                    ->multiple()->searchable()
                    ->options(fn (Forms\Get $get) => $get('scope') === 'product'
                        ? Product::orderBy('name')->pluck('name', 'id')
                        : Category::orderBy('name')->pluck('name', 'id'))
                    ->visible(fn (Forms\Get $get) => in_array($get('scope'), ['category', 'product'])),
            ])->columns(2),

            Forms\Components\Section::make('Limit & Tarih')->schema([
                Forms\Components\TextInput::make('usage_limit')->label('Toplam Kullanım Limiti')->numeric()->helperText('Boş = sınırsız'),
                Forms\Components\TextInput::make('per_user_limit')->label('Kişi Başı Limit')->numeric(),
                Forms\Components\DateTimePicker::make('starts_at')->label('Başlangıç')->native(false),
                Forms\Components\DateTimePicker::make('ends_at')->label('Bitiş')->native(false),
                Forms\Components\Toggle::make('is_active')->label('Aktif')->default(true),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->label('Kod')->searchable()->weight('bold')->copyable(),
                Tables\Columns\TextColumn::make('type')->label('Tip')->formatStateUsing(fn ($s) => $s?->getLabel())->badge(),
                Tables\Columns\TextColumn::make('value')->label('Değer')
                    ->formatStateUsing(fn ($state, Coupon $r) => $r->type === DiscountType::Percent ? "%{$state}" : '₺' . number_format($state, 2, ',', '.')),
                Tables\Columns\TextColumn::make('used_count')->label('Kullanım')
                    ->formatStateUsing(fn ($state, Coupon $r) => $r->usage_limit ? "{$state}/{$r->usage_limit}" : $state)->badge(),
                Tables\Columns\TextColumn::make('ends_at')->label('Bitiş')->dateTime('d.m.Y')->placeholder('—'),
                Tables\Columns\IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([Tables\Filters\TernaryFilter::make('is_active')->label('Aktiflik')])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCoupons::route('/'),
            'create' => Pages\CreateCoupon::route('/create'),
            'edit' => Pages\EditCoupon::route('/{record}/edit'),
        ];
    }
}
