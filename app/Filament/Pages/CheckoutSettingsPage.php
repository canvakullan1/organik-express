<?php

namespace App\Filament\Pages;

use App\Settings\CheckoutSettings;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\SettingsPage;

class CheckoutSettingsPage extends SettingsPage
{
    protected static string $settings = CheckoutSettings::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationGroup = 'Ayarlar';

    protected static ?string $navigationLabel = 'Kargo & Teslimat';

    protected static ?string $title = 'Kargo & Teslimat Ayarları';

    protected static ?int $navigationSort = 7;

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Kargo')->schema([
                Forms\Components\TextInput::make('shipping_cost')->label('Standart Kargo Ücreti (₺)')
                    ->numeric()->required()
                    ->helperText('Ücretsiz kargo eşiği "Genel Ayarlar"dadır.'),
                Forms\Components\Toggle::make('cash_on_delivery_enabled')->label('Kapıda ödeme aktif'),
                Forms\Components\TextInput::make('cash_on_delivery_fee')->label('Kapıda Ödeme Ücreti (₺)')->numeric(),
            ])->columns(3),

            Forms\Components\Section::make('Teslimat')->schema([
                Forms\Components\TextInput::make('delivery_lead_days')->label('En Erken Teslimat (gün sonra)')
                    ->numeric()->required()->helperText('0 = bugün, 1 = yarın'),
                Forms\Components\TagsInput::make('delivery_slots')->label('Zaman Aralıkları')
                    ->placeholder('09:00 - 12:00')->columnSpanFull(),
                Forms\Components\Repeater::make('delivery_zones')
                    ->label('Elden Teslim Bölgeleri ve Günleri')
                    ->columnSpanFull()
                    ->helperText('Elden teslim yaptığınız bölgeler. Müşteri ödeme sayfasında bölgesini seçer; yalnızca o bölgenin teslim günleri gösterilir. Listede olmayan iller "kargo" akışına girer.')
                    ->schema([
                        Forms\Components\TextInput::make('name')->label('Bölge Adı')
                            ->placeholder('İstanbul (Avrupa Yakası)')->required(),
                        Forms\Components\CheckboxList::make('days')->label('Teslim Günleri')
                            ->options([
                                1 => 'Pazartesi', 2 => 'Salı', 3 => 'Çarşamba', 4 => 'Perşembe',
                                5 => 'Cuma', 6 => 'Cumartesi', 0 => 'Pazar',
                            ])
                            ->columns(4)->gridDirection('row'),
                    ])
                    ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                    ->addActionLabel('Bölge Ekle')
                    ->collapsible(),
                Forms\Components\Textarea::make('delivery_info_note')
                    ->label('Diğer İller (Kargo) Bilgi Notu')
                    ->rows(3)->columnSpanFull()
                    ->helperText('Elden teslim bölgesi dışındaki iller için ödeme sayfasında gösterilen kargo bilgilendirmesi.'),
            ])->columns(2),

            Forms\Components\Section::make('Erken Sipariş İndirimi')
                ->description('Teslimat bölgesindeki adreslere, teslim gününden 1 gün önce (yarına) verilen siparişlere otomatik indirim.')
                ->schema([
                    Forms\Components\TagsInput::make('delivery_zone_cities')->label('Teslimat Bölgesi Şehirleri')
                        ->placeholder('İstanbul')
                        ->helperText('Elden teslim yapılan + indirim geçerli şehirler. Diğer iller kargoyla gider, indirim almaz.')
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('early_order_discount_percent')->label('İndirim Oranı (%)')
                        ->numeric()->minValue(0)->maxValue(100)->required()
                        ->helperText('Teslim gününden 1 gün önce sipariş → bu oranda indirim. 0 = kapalı.'),
                ])->columns(2),
        ]);
    }

    /** delivery_zones DB'de JSON metni; formda Repeater için diziye çevir. */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (isset($data['delivery_zones']) && is_string($data['delivery_zones'])) {
            $data['delivery_zones'] = json_decode($data['delivery_zones'], true) ?: [];
        }

        return $data;
    }

    /** Repeater dizisini kaydederken JSON metnine çevir. */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['delivery_zones']) && is_array($data['delivery_zones'])) {
            // days değerlerini int'e indir, boş bölgeleri ele
            $zones = [];
            foreach ($data['delivery_zones'] as $z) {
                $name = trim((string) ($z['name'] ?? ''));
                if ($name === '') {
                    continue;
                }
                $zones[] = [
                    'name' => $name,
                    'days' => array_values(array_map('intval', (array) ($z['days'] ?? []))),
                ];
            }
            $data['delivery_zones'] = json_encode($zones, JSON_UNESCAPED_UNICODE);
        }

        return $data;
    }
}
