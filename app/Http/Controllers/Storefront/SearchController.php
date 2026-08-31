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

    /**
     * MySQL ci collation'ı ç/ş/ü/ö'yü ASCII karşılığıyla eşler ama "ğ" ve "ı" eşlemez:
     * kullanıcı "zeytinyagi" yazınca "Zeytinyağı" bulunamıyordu. Sorguyu ve sütunu
     * aynı şekilde sadeleştirip karşılaştırıyoruz.
     */
    private function normalize(string $v): string
    {
        return str_replace(['ğ', 'Ğ', 'ı', 'İ'], ['g', 'g', 'i', 'i'], $v);
    }

    private function normalizedColumn(string $column): string
    {
        return "REPLACE(REPLACE(REPLACE(REPLACE({$column},'ğ','g'),'Ğ','g'),'ı','i'),'İ','i')";
    }

    private function query(string $q)
    {
        $like = '%' . $this->normalize($q) . '%';
        $name = $this->normalizedColumn('name');

        return Product::active()
            ->where(function ($query) use ($q, $like, $name) {
                $query->whereRaw("{$name} LIKE ?", [$like])
                    ->orWhereRaw($this->normalizedColumn('short_description') . ' LIKE ?', [$like])
                    ->orWhere('sku', 'like', "%{$q}%")
                    ->orWhereHas('brand', fn ($b) => $b->whereRaw("{$name} LIKE ?", [$like]))
                    ->orWhereHas('category', fn ($c) => $c->whereRaw("{$name} LIKE ?", [$like]));
            });
    }
}
