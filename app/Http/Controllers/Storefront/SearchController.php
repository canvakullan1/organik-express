<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->input('q', ''));

        $products = $q === ''
            ? Product::query()->whereRaw('1=0')->paginate(12)
            : $this->query($q)->with(['images', 'variants', 'tags', 'category'])->paginate(12)->withQueryString();

        return view('storefront.search', [
            'query' => $q,
            'products' => $products,
        ]);
    }

    /** Header otomatik tamamlama için JSON. */
    public function suggest(Request $request)
    {
        $q = trim((string) $request->input('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $results = $this->query($q)
            ->with('images')
            ->take(6)
            ->get()
            ->map(fn (Product $p) => [
                'name' => $p->name,
                'url' => route('product.show', $p->slug),
                'image' => $p->images->first()?->path
                    ? asset('storage/' . $p->images->first()->path)
                    : null,
                'price' => $p->variants->min('price'),
            ]);

        return response()->json($results);
    }

    private function query(string $q)
    {
        return Product::active()
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('short_description', 'like', "%{$q}%")
                    ->orWhere('sku', 'like', "%{$q}%")
                    ->orWhereHas('brand', fn ($b) => $b->where('name', 'like', "%{$q}%"))
                    ->orWhereHas('category', fn ($c) => $c->where('name', 'like', "%{$q}%"));
            });
    }
}
