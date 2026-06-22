<?php

namespace App\Filament\Pages;

use App\Settings\ThemeSettings;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\SettingsPage;

class ThemeSettingsPage extends SettingsPage
{
    protected static string $settings = ThemeSettings::class;

    protected static ?string $navigationIcon = 'heroicon-o-paint-brush';

    protected static ?string $navigationGroup = 'Ayarlar';

    protected static ?string $navigationLabel = 'Görünüm & Tipografi';

    protected static ?string $title = 'Görünüm & Tipografi';

    protected static ?int $navigationSort = 2;

    /** Vitrinde kullanılabilecek küratörlü font listesi (Google Fonts). */
    private const HEADING_FONTS = [
        'Manrope' => 'Manrope (modern sans — market tarzı)',
        'Nunito' => 'Nunito (yumuşak/sıcak sans)',
        'Plus Jakarta Sans' => 'Plus Jakarta Sans (temiz sans)',
        'DM Sans' => 'DM Sans (sade sans)',
        'Bricolage Grotesque' => 'Bricolage Grotesque (modern grotesk)',
        'Fraunces' => 'Fraunces (sıcak serif — butik)',
        'Playfair Display' => 'Playfair Display (klasik serif)',
        'Cormorant Garamond' => 'Cormorant Garamond (zarif serif)',
        'Lora' => 'Lora (yumuşak serif)',
        'DM Serif Display' => 'DM Serif Display (güçlü serif)',
    ];

    private const BODY_FONTS = [
        'Plus Jakarta Sans' => 'Plus Jakarta Sans (varsayılan)',
        'DM Sans' => 'DM Sans',
        'Figtree' => 'Figtree',
        'Manrope' => 'Manrope',
        'Nunito Sans' => 'Nunito Sans',
        'Work Sans' => 'Work Sans',
        'Inter' => 'Inter',
    ];

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Renkler')->schema([
                Forms\Components\ColorPicker::make('primary_color')
                    ->label('Marka Rengi (yeşil)')->required()
                    ->helperText('Butonlar, vurgular, menü.'),
                Forms\Components\ColorPicker::make('accent_color')
                    ->label('Aksan Rengi (toprak/kil)')->required()
                    ->helperText('İndirim rozetleri, ikincil vurgular.'),
            ])->columns(2),

            Forms\Components\Section::make('Tipografi (Yazı Stili)')
                ->description('Başlık ve gövde fontları. Seçim anında siteye yansır.')
                ->schema([
                    Forms\Components\Select::make('heading_font')
                        ->label('Başlık Fontu')
                        ->options(self::HEADING_FONTS)
                        ->required()->native(false)->searchable(),
                    Forms\Components\Select::make('body_font')
                        ->label('Gövde Fontu')
                        ->options(self::BODY_FONTS)
                        ->required()->native(false)->searchable(),
                ])->columns(2),

            Forms\Components\Section::make('Duyuru Bandı')->schema([
                Forms\Components\Toggle::make('announcement_enabled')
                    ->label('Duyuru bandını göster'),
                Forms\Components\TextInput::make('announcement_text')
                    ->label('Duyuru Metni')
                    ->maxLength(255),
            ])->columns(1),
        ]);
    }
}
