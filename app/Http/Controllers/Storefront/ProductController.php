<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\Analytics\AnalyticsRecorder;

class ProductController extends Controller
{
    public function show(Product $product, AnalyticsRecorder $analytics)
    {
        abort_unless($product->status->value === 'active', 404);

        $analytics->record('product_view', [
            'product_id' => $product->id,
            'value' => (float) ($product->variants->min('price') ?? 0),
        ]);

        $product->load([
            'images',
            'variants' => fn ($q) => $q->where('is_active', true)->orderByDesc('is_default')->orderBy('sort_order'),
            'certificates',
            'tags',
            'category',
            'brand',
            'producer',
        ]);

        // İlgili ürünler: aynı kategoriden.
        $related = Product::active()
            ->with(['images', 'variants', 'tags'])
            ->where('category_id', $product->category_id)
            ->whereKeyNot($product->id)
            ->take(4)
            ->get();

        return view('storefront.product', [
            'product' => $product,
            'related' => $related,
        ]);
    }
}
