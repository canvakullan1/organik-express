<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();          // order_placed, order_shipped, ...
            $table->string('name');                    // Görünen ad (admin)
            $table->string('subject');                 // Konu (placeholder destekli)
            $table->string('heading');                 // Renkli şerit başlığı
            $table->longText('body_html');             // Gövde (HTML + placeholder)
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};
