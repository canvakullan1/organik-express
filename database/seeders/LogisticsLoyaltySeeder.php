<?php

namespace Database\Seeders;

use App\Models\Carrier;
use App\Models\Coupon;
use Illuminate\Database\Seeder;

class LogisticsLoyaltySeeder extends Seeder
{
    public function run(): void
    {
        $carriers = [
            ['Yurtiçi Kargo', 'https://www.yurticikargo.com/tr/online-servisler/gonderi-sorgula?code={code}'],
            ['Aras Kargo', 'https://www.araskargo.com.tr/tr/cargo-tracking?code={code}'],
            ['MNG Kargo', 'https://www.mngkargo.com.tr/gonderitakip/?takipNo={code}'],
            ['Sürat Kargo', 'https://www.suratkargo.com.tr/KargoTakip/?kargotakipno={code}'],
        ];
        foreach ($carriers as $i => [$name, $tpl]) {
            Carrier::firstOrCreate(['name' => $name], ['tracking_url_template' => $tpl, 'sort_order' => $i, 'is_active' => true]);
        }

        Coupon::firstOrCreate(['code' => 'TAZE10'], [
            'description' => '%10 indirim (min ₺200, maks ₺100)',
            'type' => 'percent', 'value' => 10, 'min_subtotal' => 200, 'max_discount' => 100,
            'scope' => 'all', 'is_active' => true,
        ]);
        Coupon::firstOrCreate(['code' => 'HOSGELDIN'], [
            'description' => 'İlk siparişe ₺50 indirim (min ₺300)',
            'type' => 'amount', 'value' => 50, 'min_subtotal' => 300,
            'scope' => 'all', 'per_user_limit' => 1, 'is_active' => true,
        ]);
    }
}
