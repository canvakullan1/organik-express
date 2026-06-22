<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('status')->default('awaiting_payment');   // OrderStatus
            $table->string('payment_status')->default('pending');    // PaymentStatus
            $table->string('payment_method')->nullable();            // PaymentMethod

            // Tutarlar (KDV dahil)
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('shipping_cost', 12, 2)->default(0);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2)->default(0);
            $table->string('currency', 3)->default('TRY');

            // İletişim + adres anlık görüntüleri (snapshot, JSON)
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->json('shipping_address')->nullable();
            $table->json('billing_address')->nullable();

            // Teslimat
            $table->date('delivery_date')->nullable();
            $table->string('delivery_slot')->nullable();
            $table->text('customer_note')->nullable();

            // Sözleşme onayları
            $table->boolean('agreed_distance_sale')->default(false);
            $table->boolean('agreed_preinfo')->default(false);

            // Atıf (analitik için)
            $table->string('channel')->nullable();
            $table->string('source')->nullable();
            $table->string('medium')->nullable();

            $table->string('ip')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('payment_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
