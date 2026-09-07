<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/**
 * Oturum (session) yapılandırmasını teşhis eder.
 *
 *   php artisan session:diagnose
 */
class DiagnoseSession extends Command
{
    protected $signature = 'session:diagnose';

    protected $description = 'SESSION_DRIVER ve sessions tablosunu kontrol eder';

    public function handle(): int
    {
        $driver = config('session.driver');
        $this->line("SESSION_DRIVER: {$driver}");
        $this->line('SESSION_LIFETIME: ' . config('session.lifetime'));
        $this->line('SESSION_DOMAIN: ' . (config('session.domain') ?: '(boş)'));
        $this->line('SESSION_SECURE_COOKIE: ' . var_export(config('session.secure'), true));
        $this->line('SESSION_SAME_SITE: ' . config('session.same_site'));

        if ($driver === 'database') {
            $table = config('session.table', 'sessions');
            $exists = Schema::hasTable($table);
            $this->line("sessions tablosu ({$table}): " . ($exists ? 'VAR' : 'YOK <<<'));
            if ($exists) {
                $count = \DB::table($table)->count();
                $this->line("kayıtlı oturum sayısı: {$count}");
            } else {
                $this->warn('DRIVER=database AMA TABLO YOK -> her session write/read hata verir, oturumlar kalıcı olmaz, CSRF her formda mismatch olur.');
            }
        }

        return self::SUCCESS;
    }
}
