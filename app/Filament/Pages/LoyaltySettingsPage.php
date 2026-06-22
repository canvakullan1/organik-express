<?php

namespace App\Filament\Pages;

use App\Settings\LoyaltySettings;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\SettingsPage;

class LoyaltySettingsPage extends SettingsPage
{
    protected static string $settings = LoyaltySettings::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    protected static ?string $navigationGroup = 'Ayarlar';

    protected static ?string $navigationLabel = 'Para Puan Ayarları';

    protected static ?string $title = 'Para Puan (Sadakat) Ayarları';

    protected static ?int $navigationSort = 9;

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()
                ->description('1 puan = ₺1 değerindedir.')
                ->schema([
                    Forms\Components\Toggle::make('enabled')->label('Para puan sistemi aktif')->columnSpanFull(),
                    Forms\Components\TextInput::make('earn_rate')->label('Kazanım Oranı (%)')
                        ->numeric()->required()->suffix('%')
                        ->helperText('Sipariş tutarının yüzde kaçı puan olarak kazanılır.'),
                    Forms\Components\TextInput::make('max_redeem_percent')->label('Maks. Kullanım (%)')
                        ->numeric()->required()->suffix('%')
                        ->helperText('Sepetin en fazla yüzde kaçı puanla ödenebilir.'),
                    Forms\Components\TextInput::make('min_balance_to_redeem')->label('Min. Kullanım Bakiyesi')
                        ->numeric()->required()->suffix('puan'),
                    Forms\Components\TextInput::make('min_order_to_earn')->label('Min. Sipariş (kazanım için)')
                        ->numeric()->required()->prefix('₺'),
                ])->columns(2),
        ]);
    }
}
