<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Tag;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function show(Request $request, Category $category)
    {
        abort_unless($category->is_active, 404);

        // Bu kategori + tüm alt kategorilerinin ürünleri.
        $categoryIds = $category->children()->pluck('id')->push($category->id);

        $query = Product::active()
            ->with(['images', 'variants', 'tags', 'category'])
            ->whereIn('category_id', $categoryIds);

        $this->applyFilters($query, $request);
        $this->applySorting($query, $request->input('sort', 'default'));

        $products = $query->paginate(12)->withQueryString();

        return view('storefront.category', [
            'category' => $category,
            'products' => $products,
            'brands' => Brand::active()->orderBy('name')->get(),
            'tags' => Tag::where('is_filterable', true)->orderBy('name')->get(),
        ]);
    }

    private function applyFilters($query, Request $request): void
    {
        if ($brand = $request->input('brand')) {
            $query->whereIn('brand_id', (array) $brand);
        }

        if ($tags = $request->input('tag')) {
            $query->whereHas('tags', fn ($q) => $q->whereIn('tags.id', (array) $tags));
        }

        if ($request->filled('min')) {
            $query->whereHas('variants', fn ($q) => $q->where('price', '>=', (float) $request->input('min')));
        }

        if ($request->filled('max')) {
            $query->whereHas('variants', fn ($q) => $q->where('price', '<=', (float) $request->input('max')));
        }

        if ($request->boolean('discounted')) {
            $query->whereHas('variants', fn ($q) => $q->whereColumn('compare_at_price', '>', 'price'));
        }

        if ($request->boolean('in_stock')) {
            $query->whereHas('variants', fn ($q) => $q->where('stock', '>', 0));
        }
    }

    private function applySorting($query, string $sort): void
    {
        // En ucuz varyant fiyatı üzerinden sıralama için alt sorgu.
        $cheapest = \App\Models\ProductVariant::select('price')
            ->whereColumn('product_variants.product_id', 'products.id')
            ->orderBy('price')
            ->limit(1);

        match ($sort) {
            'price_asc' => $query->orderBy($cheapest->clone(), 'asc'),
            'price_desc' => $query->orderByDesc($cheapest->clone()),
            'newest' => $query->latest(),
            default => $query->orderBy('sort_order')->latest(),
        };
    }
}
