<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ana sayfa "Kategoriler" carousel'i artık kategori ağacındaki
 * show_in_menu/sort_order/image alanlarını değil, bu ayrı tabloyu kullanır —
 * admin bu vitrini genel kategori yönetiminden bağımsız düzenleyebilsin diye.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('home_category_tiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('image')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Mevcut ana sayfa kutucuklarını (o an kategori ağacından otomatik seçilenleri)
        // bire bir bu tabloya kopyala — vitrin boş başlamasın.
        $categories = DB::table('categories')
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->where('show_in_menu', true)
            ->orderBy('sort_order')
            ->take(14)
            ->get(['id', 'image', 'sort_order']);

        $now = now();
        foreach ($categories as $i => $cat) {
            DB::table('home_category_tiles')->insert([
                'category_id' => $cat->id,
                'image' => $cat->image,
                'sort_order' => $cat->sort_order ?? $i,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('home_category_tiles');
    }
};
