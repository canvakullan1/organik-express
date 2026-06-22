<?php

namespace Database\Seeders;

use App\Models\Carrier;
use Illuminate\Database\Seeder;

/**
 * Türkiye'deki yaygın kargo firmaları + takip URL şablonları.
 * {code} yerine takip numarası otomatik yazılır (Carrier::trackingUrl).
 * URL'ler firma panellerinden değişebilir; admin "Kargo Firmaları"ndan güncelleyebilir.
 */
class CarrierSeeder extends Seeder
{
    public function run(): void
    {
        $carriers = [
            ['name' => 'Yurtiçi Kargo', 'tracking_url_template' => 'https://www.yurticikargo.com/tr/online-servisler/gonderi-sorgula?code={code}'],
            ['name' => 'Aras Kargo', 'tracking_url_template' => 'https://kargotakip.araskargo.com.tr/CargoTrace.aspx?code={code}'],
            ['name' => 'MNG Kargo', 'tracking_url_template' => 'https://www.mngkargo.com.tr/gonderitakip/?takipNo={code}'],
            ['name' => 'Sürat Kargo', 'tracking_url_template' => 'https://www.suratkargo.com.tr/KargoTakip/?kargotakipno={code}'],
            ['name' => 'PTT Kargo', 'tracking_url_template' => 'https://gonderitakip.ptt.gov.tr/Track/Verify?q={code}'],
            ['name' => 'HepsiJET', 'tracking_url_template' => 'https://www.hepsijet.com/gonderi-takibi?trackingNumber={code}'],
            ['name' => 'Sendeo', 'tracking_url_template' => 'https://www.sendeo.com.tr/gonderi-takip?kod={code}'],
            ['name' => 'UPS Kargo', 'tracking_url_template' => 'https://www.ups.com/track?tracknum={code}'],
        ];

        foreach ($carriers as $i => $c) {
            Carrier::updateOrCreate(
                ['name' => $c['name']],
                [
                    'tracking_url_template' => $c['tracking_url_template'],
                    'is_active' => true,
                    'sort_order' => $i,
                ]
            );
        }
    }
}
