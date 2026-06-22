<?php

namespace App\Filament\Resources;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use Filament\Forms;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationGroup = 'Satış';

    protected static ?string $navigationLabel = 'Siparişler';

    protected static ?string $modelLabel = 'Sipariş';

    protected static ?string $pluralModelLabel = 'Siparişler';

    protected static ?int $navigationSort = 1;

    public static function canCreate(): bool
    {
        return false; // Siparişler vitrinden gelir
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::where('status', OrderStatus::AwaitingPayment->value)->count() ?: null;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_number')->label('Sipariş No')->searchable()->weight('bold')->copyable(),
                Tables\Columns\TextColumn::make('user.name')->label('Müşteri')->searchable()->placeholder('Misafir'),
                Tables\Columns\TextColumn::make('status')->label('Durum')->badge(),
                Tables\Columns\TextColumn::make('payment_status')->label('Ödeme')->badge(),
                Tables\Columns\TextColumn::make('payment_method')->label('Yöntem')
                    ->formatStateUsing(fn ($state) => $state?->getLabel())->toggleable(),
                Tables\Columns\TextColumn::make('grand_total')->label('Tutar')->money('TRY')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->label('Tarih')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('Durum')->options(OrderStatus::class),
                Tables\Filters\SelectFilter::make('payment_status')->label('Ödeme')->options(PaymentStatus::class),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('confirmPayment')
                    ->label('Ödemeyi Onayla')->icon('heroicon-o-check-circle')->color('success')
                    ->visible(fn (Order $r) => $r->payment_status !== PaymentStatus::Paid)
                    ->requiresConfirmation()
                    ->action(function (Order $r) {
                        $r->markPaid();
                        $r->changeStatus(OrderStatus::Preparing, 'Ödeme onaylandı (admin).', auth()->id());
                        if ($r->user) {
                            app(\App\Services\Loyalty\LoyaltyService::class)->award($r);
                        }
                        Notification::make()->title('Ödeme onaylandı, sipariş hazırlanıyor.')->success()->send();
                    }),

                Tables\Actions\Action::make('paymentReminder')
                    ->label('Ödeme Hatırlat')->icon('heroicon-o-bell-alert')->color('warning')
                    ->visible(fn (Order $r) => $r->payment_status !== PaymentStatus::Paid
                        && $r->payment_method?->value === 'bank_transfer')
                    ->requiresConfirmation()
                    ->modalHeading('Havale ödeme hatırlatması gönder')
                    ->modalDescription(fn (Order $r) => $r->contact_email . ' adresine havale bilgileriyle hatırlatma e-postası gönderilecek.')
                    ->action(function (Order $r) {
                        $ok = app(\App\Services\Mail\OrderMailService::class)->send($r, 'order_payment_reminder');
                        $ok
                            ? Notification::make()->title('Ödeme hatırlatması gönderildi.')->success()->send()
                            : Notification::make()->title('Gönderilemedi. Şablon pasif olabilir veya mail ayarlarını kontrol edin.')->warning()->send();
                    }),

                Tables\Actions\Action::make('updateStatus')
                    ->label('Durum')->icon('heroicon-o-arrow-path')->color('gray')
                    ->form([
                        Forms\Components\Select::make('status')->label('Yeni Durum')->options(OrderStatus::class)->required(),
                        Forms\Components\Textarea::make('note')->label('Not')->rows(2),
                    ])
                    ->action(function (Order $r, array $data) {
                        $r->changeStatus(OrderStatus::from($data['status']), $data['note'] ?? null, auth()->id());
                        Notification::make()->title('Durum güncellendi.')->success()->send();
                    }),

                Tables\Actions\Action::make('ship')
                    ->label('Kargoya Ver')->icon('heroicon-o-truck')->color('info')
                    ->form([
                        Forms\Components\Select::make('carrier_id')->label('Kargo Firması')
                            ->options(fn () => \App\Models\Carrier::active()->orderBy('sort_order')->pluck('name', 'id'))
                            ->required()
                            ->helperText('Firmalar "Kargo Firmaları" menüsünden eklenir.'),
                        Forms\Components\TextInput::make('tracking_number')->label('Takip No')->required(),
                    ])
                    ->action(function (Order $r, array $data) {
                        $carrier = \App\Models\Carrier::find($data['carrier_id']);
                        $r->shipment()->updateOrCreate([], [
                            'carrier' => $carrier?->name,
                            'tracking_number' => $data['tracking_number'],
                            'tracking_url' => $carrier?->trackingUrl($data['tracking_number']),
                            'status' => 'shipped',
                            'shipped_at' => now(),
                        ]);
                        $r->changeStatus(OrderStatus::Shipped, "Kargoya verildi: {$carrier?->name} - {$data['tracking_number']}", auth()->id());
                        Notification::make()->title('Sipariş kargoya verildi.')->success()->send();
                    }),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Sipariş')->schema([
                Infolists\Components\TextEntry::make('order_number')->label('Sipariş No')->weight('bold'),
                Infolists\Components\TextEntry::make('status')->label('Durum')->badge(),
                Infolists\Components\TextEntry::make('payment_status')->label('Ödeme')->badge(),
                Infolists\Components\TextEntry::make('payment_method')->label('Yöntem')->formatStateUsing(fn ($s) => $s?->getLabel()),
                Infolists\Components\TextEntry::make('grand_total')->label('Tutar')->money('TRY'),
                Infolists\Components\TextEntry::make('created_at')->label('Tarih')->dateTime('d.m.Y H:i'),
            ])->columns(3),

            Infolists\Components\Section::make('Müşteri & Teslimat')->schema([
                Infolists\Components\TextEntry::make('contact_email')->label('E-posta'),
                Infolists\Components\TextEntry::make('contact_phone')->label('Telefon'),
                Infolists\Components\TextEntry::make('shipping_address')->label('Teslimat Adresi')
                    ->formatStateUsing(fn ($state) => is_array($state)
                        ? ($state['name'] ?? '') . "\n" . ($state['address'] ?? '') . ', ' . ($state['district'] ?? '') . '/' . ($state['city'] ?? '')
                        : '—')
                    ->columnSpanFull(),
                Infolists\Components\TextEntry::make('delivery_date')->label('Teslimat Günü')->date('d.m.Y')->placeholder('—'),
                Infolists\Components\TextEntry::make('delivery_slot')->label('Zaman')->placeholder('—'),
                Infolists\Components\TextEntry::make('customer_note')->label('Not')->placeholder('—')->columnSpanFull(),
            ])->columns(2),

            Infolists\Components\Section::make('Ürünler')->schema([
                Infolists\Components\RepeatableEntry::make('items')->label('')->schema([
                    Infolists\Components\TextEntry::make('name')->label('Ürün'),
                    Infolists\Components\TextEntry::make('variant_name')->label('Varyant')->placeholder('—'),
                    Infolists\Components\TextEntry::make('quantity')->label('Adet'),
                    Infolists\Components\TextEntry::make('unit_price')->label('Birim')->money('TRY'),
                    Infolists\Components\TextEntry::make('line_total')->label('Tutar')->money('TRY'),
                ])->columns(5),
                Infolists\Components\TextEntry::make('subtotal')->label('Ara Toplam')->money('TRY'),
                Infolists\Components\TextEntry::make('shipping_cost')->label('Kargo')->money('TRY'),
            ])->columns(2),

            Infolists\Components\Section::make('Kargo')->schema([
                Infolists\Components\TextEntry::make('shipment.carrier')->label('Firma')->placeholder('Henüz kargoya verilmedi'),
                Infolists\Components\TextEntry::make('shipment.tracking_number')->label('Takip No')->placeholder('—'),
            ])->columns(2)->visible(fn (Order $r) => $r->shipment !== null),
        ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'view' => Pages\ViewOrder::route('/{record}'),
        ];
    }
}
