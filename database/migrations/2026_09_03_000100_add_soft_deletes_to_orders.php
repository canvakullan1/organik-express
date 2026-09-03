<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Siparişlerde geri alınabilir silme.
 *
 * Panelden sipariş silinebilmesi istendi; kalıcı silmek muhasebe/kayıt açısından
 * riskli olduğu için soft-delete kullanılıyor (silinen sipariş listeden kalkar,
 * "Silinmiş" filtresinden geri alınabilir; kalıcı silme ayrı bir işlem).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
