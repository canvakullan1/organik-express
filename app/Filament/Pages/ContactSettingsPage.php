<?php

namespace App\Filament\Pages;

use App\Settings\ContactSettings;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\SettingsPage;

class ContactSettingsPage extends SettingsPage
{
    protected static string $settings = ContactSettings::class;

    protected static ?string $navigationIcon = 'heroicon-o-phone';

    protected static ?string $navigationGroup = 'Ayarlar';

    protected static ?string $navigationLabel = 'İletişim Bilgileri';

    protected static ?string $title = 'İletişim Bilgileri';

    protected static ?int $navigationSort = 3;

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->schema([
                Forms\Components\TextInput::make('phone')->label('Telefon')->tel(),
                Forms\Components\TextInput::make('email')->label('E-posta')->email(),
                Forms\Components\TextInput::make('whatsapp')->label('WhatsApp Numarası')
                    ->helperText('Sadece rakam, ülke koduyla. Örn: 905xxxxxxxxx'),
                Forms\Components\TextInput::make('working_hours')->label('Çalışma Saatleri'),
                Forms\Components\Textarea::make('address')->label('Adres')->rows(2)->columnSpanFull(),
                Forms\Components\Textarea::make('map_embed')->label('Google Harita Embed Kodu')
                    ->rows(3)->columnSpanFull()
                    ->helperText('Google Maps > Paylaş > Harita yerleştir > iframe kodu.'),
            ])->columns(2),
        ]);
    }
}
