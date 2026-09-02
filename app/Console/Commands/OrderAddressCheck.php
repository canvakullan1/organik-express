<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;

/**
 * Son siparişlerin adres anlık görüntüsünü ve panelde görünecek metni yazdırır.
 * Panelde "adres görünmüyor" tipi sorunları doğrulamak için.
 *
 *   php artisan orders:address-check
 */
class OrderAddressCheck extends Command
{
    protected $signature = 'orders:address-check {--limit=3}';

    protected $description = 'Son siparişlerin adres verisini ve panel metnini gösterir';

    public function handle(): int
    {
        $total = Order::count();
        $this->line("Toplam sipariş: {$total}");

        if ($total === 0) {
            $this->warn('Sipariş yok — kontrol edilemiyor.');

            return self::SUCCESS;
        }

        $orders = Order::latest('id')->take((int) $this->option('limit'))->get();

        foreach ($orders as $o) {
            $this->line(str_repeat('-', 50));
            $this->line("#{$o->order_number} ({$o->created_at?->format('d.m.Y H:i')})");
            $raw = $o->shipping_address;
            $this->line('  ham JSON tipi : ' . gettype($raw) . (is_array($raw) ? ' (' . count($raw) . ' alan)' : ''));
            if (is_array($raw)) {
                $this->line('  dolu alanlar  : ' . implode(', ', array_keys(array_filter($raw, fn ($v) => $v !== null && $v !== ''))));
            }
            $this->line('  PANEL METNI   :');
            foreach (explode("\n", $o->addressText($raw)) as $line) {
                $this->line('    ' . $line);
            }
        }

        return self::SUCCESS;
    }
}
