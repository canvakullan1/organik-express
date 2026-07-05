<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class CertificateSeeder extends Seeder
{
    /**
     * Sertifikalar database/data/certificates.json'dan yüklenir
     * (standartlar + üretici/tedarikçi belgeleri). Demo sertifika verisi kaldırıldı.
     */
    public function run(): void
    {
        Artisan::call('certificates:seed', ['--prune' => true]);
    }
}
