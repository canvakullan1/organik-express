<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ürün bazlı teslimat kapsamı geçersiz kılma (override).
 *
 * Varsayılan (NULL): otomatik — kategoriye göre belirlenir (Product::shipsNationwide()).
 * true : bu ürün Türkiye geneline gönderilir (kategori kısıtlı olsa bile).
 * false: bu ürün yalnızca İstanbul içine gönderilir (kategori serbest olsa bile).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('ships_nationwide_override')->nullable()->after('estimated_delivery');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('ships_nationwide_override');
        });
    }
};
