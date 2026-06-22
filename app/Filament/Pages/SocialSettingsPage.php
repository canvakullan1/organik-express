<?php

namespace App\Filament\Pages;

use App\Settings\SocialSettings;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\SettingsPage;

class SocialSettingsPage extends SettingsPage
{
    protected static string $settings = SocialSettings::class;

    protected static ?string $navigationIcon = 'heroicon-o-share';

    protected static ?string $navigationGroup = 'Ayarlar';

    protected static ?string $navigationLabel = 'Sosyal Medya';

    protected static ?string $title = 'Sosyal Medya';

    protected static ?int $navigationSort = 4;

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()
                ->description('Tam URL girin. Boş bırakılan platform footer\'da gösterilmez.')
                ->schema([
                    Forms\Components\TextInput::make('instagram')->label('Instagram')->url()->prefixIcon('heroicon-o-camera'),
                    Forms\Components\TextInput::make('facebook')->label('Facebook')->url(),
                    Forms\Components\TextInput::make('x')->label('X (Twitter)')->url(),
                    Forms\Components\TextInput::make('youtube')->label('YouTube')->url(),
                    Forms\Components\TextInput::make('linkedin')->label('LinkedIn')->url(),
                    Forms\Components\TextInput::make('tiktok')->label('TikTok')->url(),
                ])->columns(2),
        ]);
    }
}
