<?php

namespace App\Filament\Pages;

use App\Settings\CheckoutSettings;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\SettingsPage;

class CheckoutSettingsPage extends SettingsPage
{
    protected static string $settings = CheckoutSettings::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationGroup = 'Ayarlar';

    protected static ?string $navigationLabel = 'Kargo & Teslimat';

    protected static ?string $title = 'Kargo & Teslimat Ayarları';

    protected static ?int $navigationSort = 7;

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Kargo')->schema([
                Forms\Components\TextInput::make('shipping_cost')->label('Standart Kargo Ücreti (₺)')
                    ->numeric()->required()
                    ->helperText('Ücretsiz kargo eşiği "Genel Ayarlar"dadır.'),
                Forms\Components\Toggle::make('cash_on_delivery_enabled')->label('Kapıda ödeme aktif'),
                Forms\Components\TextInput::make('cash_on_delivery_fee')->label('Kapıda Ödeme Ücreti (₺)')->numeric(),
            ])->columns(3),

            Forms\Components\Section::make('Havale / EFT Bilgileri')->schema([
                Forms\Components\TextInput::make('bank_name')->label('Banka'),
                Forms\Components\TextInput::make('bank_account_holder')->label('Hesap Sahibi'),
                Forms\Components\TextInput::make('bank_iban')->label('IBAN')->columnSpanFull(),
            ])->columns(2),

            Forms\Components\Section::make('Teslimat')->schema([
                Forms\Components\TextInput::make('delivery_lead_days')->label('En Erken Teslimat (gün sonra)')
                    ->numeric()->required()->helperText('0 = bugün, 1 = yarın'),
                Forms\Components\TagsInput::make('delivery_slots')->label('Zaman Aralıkları')
                    ->placeholder('09:00 - 12:00')->columnSpanFull(),
            ])->columns(2),

            Forms\Components\Section::make('Erken Sipariş İndirimi')
                ->description('Teslimat bölgesindeki adreslere, teslim gününden 1 gün önce (yarına) verilen siparişlere otomatik indirim.')
                ->schema([
                    Forms\Components\TagsInput::make('delivery_zone_cities')->label('Teslimat Bölgesi Şehirleri')
                        ->placeholder('İstanbul')
                        ->helperText('Elden teslim yapılan + indirim geçerli şehirler. Diğer iller kargoyla gider, indirim almaz.')
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('early_order_discount_percent')->label('İndirim Oranı (%)')
                        ->numeric()->minValue(0)->maxValue(100)->required()
                        ->helperText('Teslim gününden 1 gün önce sipariş → bu oranda indirim. 0 = kapalı.'),
                ])->columns(2),
        ]);
    }
}
