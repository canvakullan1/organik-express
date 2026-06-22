<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // E-ticaret olay/funnel + atıf (attribution) kayıtları.
        Schema::create('analytics_events', function (Blueprint $table) {
            $table->id();
            $table->string('session_id', 64)->index();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // page_view | product_view | add_to_cart | remove_from_cart | reached_checkout | purchase
            $table->string('type', 32)->index();

            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->decimal('quantity', 10, 3)->nullable();
            $table->decimal('value', 12, 2)->default(0); // olayın parasal değeri (₺)

            // Atıf (ziyaretin geldiği kaynak) — ilk dokunuş
            $table->string('channel', 32)->default('direct'); // direct|organic|paid|social|referral|email|other
            $table->string('source')->nullable();   // google, instagram, facebook...
            $table->string('medium')->nullable();    // cpc, organic, social, email...
            $table->string('campaign')->nullable();
            $table->string('term')->nullable();
            $table->string('content')->nullable();
            $table->string('referrer')->nullable();
            $table->string('landing_page')->nullable();

            $table->string('url')->nullable();       // olayın gerçekleştiği sayfa
            $table->string('device', 16)->nullable(); // mobile|tablet|desktop

            $table->timestamp('created_at')->nullable()->index();

            $table->index(['type', 'created_at']);
            $table->index(['channel', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_events');
    }
};
