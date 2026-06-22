<?php

namespace App\Filament\Pages;

use App\Settings\PaymentSettings;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\SettingsPage;

class PaymentSettingsPage extends SettingsPage
{
    protected static string $settings = PaymentSettings::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Ayarlar';

    protected static ?string $navigationLabel = 'Ödeme Ayarları';

    protected static ?string $title = 'Ödeme Yöntemleri & Anahtarlar';

    protected static ?int $navigationSort = 8;

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Temel Yöntemler')->schema([
                Forms\Components\Toggle::make('bank_transfer_enabled')->label('Havale / EFT'),
                Forms\Components\Toggle::make('test_gateway_enabled')
                    ->label('Test Kartı (Demo)')
                    ->helperText('Yerel test için. Canlıda kapatın.'),
            ])->columns(2),

            Forms\Components\Section::make('iyzico')
                ->description('iyzico panelinizden alacağınız API anahtarları (şifreli saklanır).')
                ->schema([
                    Forms\Components\Toggle::make('iyzico_enabled')->label('Aktif')->live(),
                    Forms\Components\Toggle::make('iyzico_sandbox')->label('Sandbox (test ortamı)'),
                    Forms\Components\TextInput::make('iyzico_api_key')->label('API Key')->password()->revealable(),
                    Forms\Components\TextInput::make('iyzico_secret_key')->label('Secret Key')->password()->revealable(),
                ])->columns(2)->collapsed(fn (Forms\Get $get) => ! $get('iyzico_enabled')),

            Forms\Components\Section::make('PayTR')
                ->description('PayTR mağaza bilgileri (key/salt şifreli saklanır).')
                ->schema([
                    Forms\Components\Toggle::make('paytr_enabled')->label('Aktif')->live(),
                    Forms\Components\Toggle::make('paytr_sandbox')->label('Sandbox (test ortamı)'),
                    Forms\Components\TextInput::make('paytr_merchant_id')->label('Merchant ID'),
                    Forms\Components\TextInput::make('paytr_merchant_key')->label('Merchant Key')->password()->revealable(),
                    Forms\Components\TextInput::make('paytr_merchant_salt')->label('Merchant Salt')->password()->revealable(),
                ])->columns(2)->collapsed(fn (Forms\Get $get) => ! $get('paytr_enabled')),
        ]);
    }
}
