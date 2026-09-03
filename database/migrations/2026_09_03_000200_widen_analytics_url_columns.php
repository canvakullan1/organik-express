<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Analitik URL alanlarını genişletir.
 *
 * Reklam linkleri (gclid, gbraid, srsltid, utm_*) 255 karakteri kolayca aşıyordu;
 * "Data too long for column 'landing_page'" hatası ziyaret kaydını patlatıyordu.
 * Bu alanlar index'li olmadığı için genişletmek güvenli.
 */
return new class extends Migration
{
    private array $columns = ['referrer', 'landing_page', 'url'];

    public function up(): void
    {
        Schema::table('analytics_events', function (Blueprint $table) {
            foreach ($this->columns as $c) {
                if (Schema::hasColumn('analytics_events', $c)) {
                    $table->text($c)->nullable()->change();
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('analytics_events', function (Blueprint $table) {
            foreach ($this->columns as $c) {
                if (Schema::hasColumn('analytics_events', $c)) {
                    $table->string($c)->nullable()->change();
                }
            }
        });
    }
};
