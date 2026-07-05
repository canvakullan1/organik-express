<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class ProducerSeeder extends Seeder
{
    /**
     * Gerçek üreticiler database/data/producers.json'dan yüklenir.
     * (Eski demo üretici verisi kaldırıldı; artık demo üretici oluşturulmaz.)
     */
    public function run(): void
    {
        Artisan::call('producers:seed', ['--prune' => true]);
    }
}
