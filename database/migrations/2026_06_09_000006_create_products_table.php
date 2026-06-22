<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
            $table->foreignId('producer_id')->nullable()->constrained('producers')->nullOnDelete();

            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku')->nullable()->unique();
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->longText('storage_info')->nullable();   // saklama bilgisi
            $table->longText('ingredients')->nullable();     // içindekiler / besin

            // KDV oranı (%) — fiyatlar KDV dahil gösterilir
            $table->decimal('tax_rate', 5, 2)->default(0);

            $table->string('status')->default('draft'); // ProductStatus enum
            $table->boolean('is_featured')->default(false);    // öne çıkan
            $table->boolean('is_seasonal')->default(false);    // mevsim ürünü
            $table->boolean('is_new')->default(false);         // yeni ürün
            $table->unsignedInteger('sort_order')->default(0);

            // Taze ürün lojistiği
            $table->string('estimated_delivery')->nullable(); // tahmini teslim notu

            // Sertifika / analiz vurgusu
            $table->string('certificate_no')->nullable();      // organik sertifika no

            // SEO
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'is_featured']);
            $table->index(['category_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
