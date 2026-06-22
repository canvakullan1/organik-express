<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmailTemplateResource\Pages;
use App\Models\EmailTemplate;
use App\Models\Order;
use App\Services\Mail\OrderMailService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EmailTemplateResource extends Resource
{
    protected static ?string $model = EmailTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationGroup = 'Ayarlar';

    protected static ?string $navigationLabel = 'E-posta Şablonları';

    protected static ?string $modelLabel = 'E-posta Şablonu';

    protected static ?string $pluralModelLabel = 'E-posta Şablonları';

    protected static ?int $navigationSort = 20;

    private const PLACEHOLDERS = 'Kullanılabilir alanlar: {{ customer_name }}, {{ order_number }}, {{ order_date }}, {{ subtotal }}, {{ shipping_cost }}, {{ grand_total }}, {{ payment_method }}, {{ carrier }}, {{ tracking_number }}, {{ shipping_address }}, {{ delivery_info }}, {{ site_name }} — Blok alanlar (otomatik tablo/buton): {{ items_table }}, {{ order_button }}, {{ tracking_button }}, {{ bank_details }}';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Şablon Adı')->required()
                    ->helperText('Sadece panelde görünür; müşteriye gitmez.'),

                Forms\Components\Select::make('key')
                    ->label('Tetikleyici (Sistem Anahtarı)')
                    ->options(EmailTemplate::KEYS)
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->disabled(fn (?EmailTemplate $record) => $record !== null)
                    ->dehydrated()
                    ->helperText('Bu şablonun hangi olayda otomatik gönderileceğini belirler. Oluşturduktan sonra değiştirilemez.'),

                Forms\Components\TextInput::make('subject')
                    ->label('E-posta Konusu')->required()
                    ->helperText('Örn: Siparişiniz alındı — {{ order_number }}'),

                Forms\Components\TextInput::make('heading')
                    ->label('Üst Başlık (renkli şerit)')->required()
                    ->helperText('E-postanın en üstündeki büyük başlık. Örn: Siparişiniz Yola Çıktı'),

                Forms\Components\Toggle::make('is_enabled')
                    ->label('Aktif (otomatik gönderilsin)')->default(true),
            ])->columns(2),

            Forms\Components\Section::make('İçerik (HTML)')->schema([
                Forms\Components\Textarea::make('body_html')
                    ->label('Gövde')
                    ->required()
                    ->rows(20)
                    ->extraInputAttributes(['style' => 'font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:13px;line-height:1.6;'])
                    ->helperText(self::PLACEHOLDERS)
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Şablon')->searchable()->weight('medium'),
                Tables\Columns\TextColumn::make('key')->label('Tetikleyici')
                    ->badge()->formatStateUsing(fn (string $state) => EmailTemplate::KEYS[$state] ?? $state),
                Tables\Columns\TextColumn::make('subject')->label('Konu')->limit(40)->color('gray'),
                Tables\Columns\IconColumn::make('is_enabled')->label('Aktif')->boolean(),
                Tables\Columns\TextColumn::make('updated_at')->label('Güncelleme')->since()->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('test')
                    ->label('Test Gönder')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('Test e-postası gönder')
                    ->modalDescription(fn () => 'Bu şablon, en son siparişin verileriyle ' . (auth()->user()?->email ?? 'hesabınıza') . ' adresine gönderilecek.')
                    ->action(function (EmailTemplate $record) {
                        $order = Order::with(['items', 'shipment'])->latest()->first();
                        $email = auth()->user()?->email;

                        if (! $order) {
                            Notification::make()->warning()->title('Test için en az bir sipariş gerekli.')->send();

                            return;
                        }
                        if (! $email) {
                            Notification::make()->warning()->title('Hesabınızda e-posta adresi yok.')->send();

                            return;
                        }

                        $ok = app(OrderMailService::class)->send($order, $record->key, $email);

                        $ok
                            ? Notification::make()->success()->title('Test e-postası gönderildi: ' . $email)->body('Mail ayarları "log" modundaysa storage/logs/laravel.log dosyasına yazılır.')->send()
                            : Notification::make()->danger()->title('Gönderilemedi. Mail ayarlarını (SMTP) kontrol edin.')->send();
                    }),
                Tables\Actions\EditAction::make(),
            ])
            ->defaultSort('id');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmailTemplates::route('/'),
            'create' => Pages\CreateEmailTemplate::route('/create'),
            'edit' => Pages\EditEmailTemplate::route('/{record}/edit'),
        ];
    }
}
