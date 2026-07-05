<?php

namespace App\Filament\Pages;

use App\Settings\GeneralSettings;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\SettingsPage;

class GeneralSettingsPage extends SettingsPage
{
    protected static string $settings = GeneralSettings::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Ayarlar';

    protected static ?string $navigationLabel = 'Genel Ayarlar';

    protected static ?string $title = 'Genel Ayarlar';

    protected static ?int $navigationSort = 1;

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Site Kimliği')->schema([
                Forms\Components\TextInput::make('site_name')
                    ->label('Site Adı')->required(),
                Forms\Components\TextInput::make('tagline')
                    ->label('Slogan')
                    ->helperText('Logo yanında / SEO başlığında kullanılır.'),
                Forms\Components\FileUpload::make('logo')
                    ->label('Logo')
                    ->image()
                    ->directory('site')
                    ->imageEditor()
                    // Yüklemeden önce tarayıcıda küçült — büyük dosyada yükleme takılmasını önler
                    ->imageResizeMode('contain')
                    ->imageResizeUpscale(false)
                    ->imageResizeTargetWidth('800')
                    ->imageResizeTargetHeight('400')
                    ->maxSize(8192)
                    ->helperText('Şeffaf arka planlı PNG önerilir (yatay ~800×200). Büyük görseller otomatik küçültülür. Boş bırakılırsa yazı logosu kullanılır.'),
                Forms\Components\FileUpload::make('favicon')
                    ->label('Favicon')
                    ->image()
                    ->directory('site')
                    ->imageResizeMode('contain')
                    ->imageResizeUpscale(false)
                    ->imageResizeTargetWidth('256')
                    ->imageResizeTargetHeight('256')
                    ->maxSize(4096)
                    ->helperText('Tarayıcı sekmesi ikonu (kare .png önerilir). Büyük görseller otomatik küçültülür.'),
            ])->columns(2),

            Forms\Components\Section::make('Vitrin')->schema([
                Forms\Components\Textarea::make('footer_about')
                    ->label('Footer Tanıtım Metni')->rows(3),
                Forms\Components\TextInput::make('free_shipping_threshold')
                    ->label('Ücretsiz Kargo Eşiği (₺)')
                    ->numeric()->required()
                    ->helperText('Sepet ve duyuru bandı bu değeri kullanır.'),
                Forms\Components\Toggle::make('maintenance_mode')
                    ->label('Bakım Modu')
                    ->helperText('Açıkça vitrin ziyaretçilere kapatılır (yönetici erişebilir).'),
            ])->columns(2),
        ]);
    }
}
