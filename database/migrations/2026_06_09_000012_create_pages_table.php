<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // CMS sayfaları: Hakkımızda, KVKK, Mesafeli Satış, SSS, Teslimat vb.
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('content')->nullable();
            $table->boolean('is_published')->default(true);
            $table->boolean('show_in_footer')->default(false);
            $table->string('footer_group')->nullable(); // kurumsal | yardim | yasal
            $table->unsignedInteger('sort_order')->default(0);
            // SEO
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->timestamps();

            $table->index(['is_published', 'show_in_footer']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
