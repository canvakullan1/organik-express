<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('coupon_code')->nullable()->after('discount_total');
            $table->decimal('coupon_discount', 12, 2)->default(0)->after('coupon_code');
            $table->decimal('loyalty_used', 12, 2)->default(0)->after('coupon_discount');   // kullanılan puan
            $table->decimal('loyalty_earned', 12, 2)->default(0)->after('loyalty_used');     // kazanılan puan
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['coupon_code', 'coupon_discount', 'loyalty_used', 'loyalty_earned']);
        });
    }
};
