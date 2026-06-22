<?php

namespace App\Filament\Pages;

use App\Settings\SeoSettings;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\SettingsPage;

class SeoSettingsPage extends SettingsPage
{
    protected static string $settings = SeoSettings::class;

    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';

    protected static ?string $navigationGroup = 'Ayarlar';

    protected static ?string $navigationLabel = 'SEO & Analitik';

    protected static ?string $title = 'SEO & Analitik';

    protected static ?int $navigationSort = 5;

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Varsayılan Meta')->schema([
                Forms\Components\TextInput::make('meta_title')->label('Varsayılan Meta Başlık'),
                Forms\Components\Textarea::make('meta_description')->label('Varsayılan Meta Açıklama')->rows(2),
                Forms\Components\FileUpload::make('og_image')
                    ->label('Sosyal Paylaşım Görseli (OG Image)')
                    ->image()->directory('site'),
            ]),

            Forms\Components\Section::make('Analitik')->schema([
                Forms\Components\TextInput::make('google_analytics_id')
                    ->label('Google Analytics (GA4) ID')->placeholder('G-XXXXXXX'),
                Forms\Components\TextInput::make('gtm_id')
                    ->label('Google Tag Manager ID')->placeholder('GTM-XXXXXX'),
            ])->columns(2),

            Forms\Components\Section::make('Yasal')->schema([
                Forms\Components\TextInput::make('etbis_url')
                    ->label('ETBİS Doğrulama Bağlantısı')->url()
                    ->helperText('e-Devlet ETBİS sorgu/doğrulama linkiniz. Footer\'da rozet olarak gösterilir.'),
            ]),
        ]);
    }
}
