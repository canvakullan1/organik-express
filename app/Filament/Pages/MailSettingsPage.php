<?php

namespace App\Filament\Pages;

use App\Settings\MailSettings;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\SettingsPage;
use Illuminate\Support\Facades\Mail;

class MailSettingsPage extends SettingsPage
{
    protected static string $settings = MailSettings::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationGroup = 'Ayarlar';

    protected static ?string $navigationLabel = 'Mail (SMTP) Ayarları';

    protected static ?string $title = 'Mail (SMTP) Ayarları';

    protected static ?int $navigationSort = 6;

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Gönderim Yöntemi')
                ->description('SMTP girilene kadar "Log" seçili kalabilir — mailler storage/logs/laravel.log dosyasına yazılır.')
                ->schema([
                    Forms\Components\Select::make('mailer')
                        ->label('Sürücü')
                        ->options(['log' => 'Log (test/geliştirme)', 'smtp' => 'SMTP (gerçek gönderim)'])
                        ->default('log')->required()->live(),
                ]),

            Forms\Components\Section::make('SMTP Bilgileri')
                ->visible(fn (Forms\Get $get) => $get('mailer') === 'smtp')
                ->schema([
                    Forms\Components\TextInput::make('host')->label('Sunucu (Host)')->placeholder('smtp.gmail.com'),
                    Forms\Components\TextInput::make('port')->label('Port')->numeric()->placeholder('587'),
                    Forms\Components\TextInput::make('username')->label('Kullanıcı Adı')->placeholder('mail@firmaniz.com'),
                    Forms\Components\TextInput::make('password')->label('Şifre')->password()->revealable()
                        ->helperText('Şifreli saklanır.'),
                    Forms\Components\Select::make('encryption')->label('Şifreleme')
                        ->options(['tls' => 'TLS', 'ssl' => 'SSL', '' => 'Yok'])->default('tls'),
                ])->columns(2),

            Forms\Components\Section::make('Gönderen Bilgisi')->schema([
                Forms\Components\TextInput::make('from_address')->label('Gönderen E-posta')->email()->required(),
                Forms\Components\TextInput::make('from_name')->label('Gönderen Adı')->required(),
            ])->columns(2),
        ]);
    }

    public function getFormActions(): array
    {
        return [
            ...parent::getFormActions(),
            \Filament\Actions\Action::make('test')
                ->label('Test Maili Gönder')
                ->icon('heroicon-o-paper-airplane')
                ->color('gray')
                ->form([
                    Forms\Components\TextInput::make('to')->label('Alıcı E-posta')
                        ->email()->required()->default(fn () => auth()->user()?->email),
                ])
                ->action(function (array $data) {
                    // Önce mevcut form değerlerini kaydet, sonra gönder.
                    $this->save();
                    try {
                        Mail::raw('Bu bir test e-postasıdır. Organik Ürün mail ayarlarınız çalışıyor. ✅', function ($m) use ($data) {
                            $m->to($data['to'])->subject('Organik Ürün — Test Maili');
                        });
                        Notification::make()->title('Test maili gönderildi (' . app(MailSettings::class)->mailer . ')')->success()->send();
                    } catch (\Throwable $e) {
                        Notification::make()->title('Gönderim hatası')->body($e->getMessage())->danger()->send();
                    }
                }),
        ];
    }
}
