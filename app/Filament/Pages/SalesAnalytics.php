<?php

namespace App\Filament\Pages;

use App\Models\AnalyticsEvent;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Group;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class SalesAnalytics extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Analitik';

    protected static ?string $navigationLabel = 'Satış Analitiği';

    protected static ?string $title = 'Satış Analitiği';

    protected static ?int $navigationSort = 0;

    protected static string $view = 'filament.pages.sales-analytics';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'from' => now()->subDays(29)->startOfDay()->format('Y-m-d'),
            'to' => now()->endOfDay()->format('Y-m-d'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make([
                    DatePicker::make('from')
                        ->label('Başlangıç')->native(false)->displayFormat('d.m.Y')
                        ->live()->required(),
                    DatePicker::make('to')
                        ->label('Bitiş')->native(false)->displayFormat('d.m.Y')
                        ->live()->required(),
                ])->columns(2),
            ])
            ->statePath('data');
    }

    /** Hızlı tarih ön ayarı (blade'deki butonlardan çağrılır). */
    public function setPreset(string $preset): void
    {
        [$from, $to] = match ($preset) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            '7d' => [now()->subDays(6)->startOfDay(), now()->endOfDay()],
            '30d' => [now()->subDays(29)->startOfDay(), now()->endOfDay()],
            'month' => [now()->startOfMonth(), now()->endOfMonth()],
            'prev_month' => [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()],
            default => [now()->subDays(29)->startOfDay(), now()->endOfDay()],
        };

        $this->form->fill([
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
        ]);
    }

    private function range(): array
    {
        $from = Carbon::parse($this->data['from'] ?? now()->subDays(29))->startOfDay();
        $to = Carbon::parse($this->data['to'] ?? now())->endOfDay();

        return [$from, $to];
    }

    /** Olay türlerine göre sayım + değer toplamları. */
    public function getMetrics(): array
    {
        [$from, $to] = $this->range();

        return $this->metricsFor($from, $to);
    }

    /** Belirli bir tarih aralığı için metrikleri hesaplar. */
    private function metricsFor(Carbon $from, Carbon $to): array
    {
        $rows = AnalyticsEvent::query()
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('type, COUNT(*) as cnt, COALESCE(SUM(value),0) as total')
            ->groupBy('type')
            ->get()
            ->keyBy('type');

        $cnt = fn ($t) => (int) ($rows[$t]->cnt ?? 0);
        $sum = fn ($t) => (float) ($rows[$t]->total ?? 0);

        $sessions = (int) AnalyticsEvent::whereBetween('created_at', [$from, $to])
            ->distinct('session_id')->count('session_id');

        $purchases = $cnt('purchase');
        $addToCart = $cnt('add_to_cart');

        return [
            'revenue' => $sum('purchase'),
            'purchases' => $purchases,
            'avg_order' => $purchases > 0 ? $sum('purchase') / $purchases : 0,
            'product_views' => $cnt('product_view'),
            'add_to_cart' => $addToCart,
            'add_to_cart_value' => $sum('add_to_cart'),
            'remove_from_cart' => $cnt('remove_from_cart'),
            'reached_checkout' => $cnt('reached_checkout'),
            'sessions' => $sessions,
            'conversion' => $sessions > 0 ? round($purchases / $sessions * 100, 2) : 0.0,
            'cart_conversion' => $addToCart > 0 ? round($purchases / $addToCart * 100, 2) : 0.0,
        ];
    }

    /**
     * Seçili dönemi, hemen öncesindeki eşit uzunlukta dönemle karşılaştırır.
     * Her metrik için yüzde değişim (delta) döner.
     */
    public function getComparison(): array
    {
        [$from, $to] = $this->range();
        $len = $from->diffInDays($to) + 1;

        $prevTo = $from->copy()->subDay()->endOfDay();
        $prevFrom = $prevTo->copy()->subDays($len - 1)->startOfDay();

        $cur = $this->getMetrics();
        $prev = $this->metricsFor($prevFrom, $prevTo);

        $delta = function (float $now, float $was): ?float {
            if ($was <= 0) {
                return $now > 0 ? 100.0 : null; // önceki dönem sıfırsa kıyas yok
            }

            return round(($now - $was) / $was * 100, 1);
        };

        $keys = ['revenue', 'purchases', 'conversion', 'avg_order', 'sessions', 'add_to_cart'];
        $out = [];
        foreach ($keys as $k) {
            $out[$k] = [
                'prev' => $prev[$k] ?? 0,
                'delta' => $delta((float) ($cur[$k] ?? 0), (float) ($prev[$k] ?? 0)),
            ];
        }

        return $out;
    }

    /** Dönüşüm hunisi adımları. */
    public function getFunnel(): array
    {
        $m = $this->getMetrics();
        $top = max($m['product_views'], 1);

        $steps = [
            ['Ürün Görüntüleme', $m['product_views']],
            ['Sepete Ekleme', $m['add_to_cart']],
            ['Ödemeye Ulaşma', $m['reached_checkout']],
            ['Satın Alma', $m['purchases']],
        ];

        $out = [];
        $prev = null;
        foreach ($steps as $s) {
            [$label, $count] = $s;
            $stepConv = ($prev !== null && $prev > 0) ? round($count / $prev * 100, 1) : null;
            $out[] = [
                'label' => $label,
                'count' => $count,
                'pct' => round($count / $top * 100, 1),
                'step_conv' => $stepConv,                      // önceki adımdan dönüşüm
                'drop' => $stepConv !== null ? round(100 - $stepConv, 1) : null,
            ];
            $prev = $count;
        }

        return $out;
    }

    /** Trafik kaynakları (kanal bazlı): oturum, ciro, dönüşüm. */
    public function getChannels(): array
    {
        [$from, $to] = $this->range();

        $rows = AnalyticsEvent::query()
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('channel')
            ->selectRaw('COUNT(DISTINCT session_id) as sessions')
            ->selectRaw("SUM(CASE WHEN type='purchase' THEN value ELSE 0 END) as revenue")
            ->selectRaw("SUM(CASE WHEN type='purchase' THEN 1 ELSE 0 END) as orders")
            ->selectRaw("SUM(CASE WHEN type='add_to_cart' THEN 1 ELSE 0 END) as carts")
            ->groupBy('channel')
            ->orderByDesc('sessions')
            ->get();

        return $rows->map(fn ($r) => [
            'channel' => AnalyticsEvent::CHANNELS[$r->channel] ?? $r->channel,
            'key' => $r->channel,
            'sessions' => (int) $r->sessions,
            'carts' => (int) $r->carts,
            'orders' => (int) $r->orders,
            'revenue' => (float) $r->revenue,
        ])->toArray();
    }

    /** Belirli kaynaklar (utm_source / referrer host) bazlı kırılım. */
    public function getSources(): array
    {
        [$from, $to] = $this->range();

        return AnalyticsEvent::query()
            ->whereBetween('created_at', [$from, $to])
            ->whereNotNull('source')
            ->selectRaw('source, channel, COUNT(DISTINCT session_id) as sessions')
            ->selectRaw("SUM(CASE WHEN type='add_to_cart' THEN 1 ELSE 0 END) as carts")
            ->selectRaw("SUM(CASE WHEN type='purchase' THEN value ELSE 0 END) as revenue")
            ->groupBy('source', 'channel')
            ->orderByDesc('sessions')
            ->limit(10)
            ->get()
            ->map(fn ($r) => [
                'source' => $r->source,
                'channel' => AnalyticsEvent::CHANNELS[$r->channel] ?? $r->channel,
                'sessions' => (int) $r->sessions,
                'carts' => (int) $r->carts,
                'revenue' => (float) $r->revenue,
            ])->toArray();
    }

    /** Günlük zaman serisi (sepete ekleme + ciro) — grafik için. */
    public function getDailySeries(): array
    {
        [$from, $to] = $this->range();

        $rows = AnalyticsEvent::query()
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('DATE(created_at) as d')
            ->selectRaw("SUM(CASE WHEN type='add_to_cart' THEN 1 ELSE 0 END) as carts")
            ->selectRaw("SUM(CASE WHEN type='purchase' THEN value ELSE 0 END) as revenue")
            ->groupByRaw('DATE(created_at)')
            ->orderBy('d')
            ->get()
            ->keyBy('d');

        // Tarih aralığını boş günlerle doldur
        $series = [];
        $cursor = $from->copy();
        while ($cursor <= $to && count($series) < 92) {
            $key = $cursor->format('Y-m-d');
            $series[] = [
                'date' => $cursor->format('d.m'),
                'carts' => (int) ($rows[$key]->carts ?? 0),
                'revenue' => (float) ($rows[$key]->revenue ?? 0),
            ];
            $cursor->addDay();
        }

        return $series;
    }
}
