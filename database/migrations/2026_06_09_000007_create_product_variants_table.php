<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Birim/ağırlık bazlı varyantlar: 1 kg, 500 gr, 1 demet, 1 paket...
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();

            $table->string('name')->nullable();       // ör. "500 gr", "1 Demet"
            $table->string('sku')->nullable();
            $table->string('unit')->default('adet');  // ProductUnit enum
            $table->decimal('unit_amount', 10, 3)->default(1); // 0.5 (kg), 500 (gr), 1 (adet)

            $table->decimal('price', 12, 2);                    // KDV dahil satış fiyatı
            $table->decimal('compare_at_price', 12, 2)->nullable(); // üzeri çizili eski fiyat

            $table->decimal('stock', 12, 3)->default(0);
            $table->boolean('track_stock')->default(true);

            // Ağırlık bazlı üründe tartım sonrası fiyat farkı oluşabilir
            $table->boolean('is_weight_based')->default(false);

            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index(['product_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
