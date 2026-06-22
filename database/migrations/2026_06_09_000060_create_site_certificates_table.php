<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Site geneli sertifikalar (ECOCERT, ISO vb.) — ürün sertifikalarından ayrı.
        Schema::create('site_certificates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('label')->nullable();       // ör. Organik, ISO 9001
            $table->text('description')->nullable();
            $table->string('image')->nullable();        // sertifika görseli/logosu
            $table->string('file')->nullable();         // PDF
            $table->date('valid_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_certificates');
    }
};
