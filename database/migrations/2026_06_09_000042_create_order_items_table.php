<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();

            // Sipariş anındaki anlık görüntü (ürün/varyant silinse de korunur)
            $table->string('name');
            $table->string('variant_name')->nullable();
            $table->string('unit')->nullable();
            $table->decimal('unit_price', 12, 2);
            $table->decimal('quantity', 10, 3);
            $table->decimal('line_total', 12, 2);
            $table->boolean('is_weight_based')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
