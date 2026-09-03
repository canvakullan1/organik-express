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
        $norm = $this->normalize($q);
        $like = '%' . $norm . '%';
        $name = $this->normalizedColumn('name');

        // KELİME BAŞI eşleşmesi: "un" araması "gün"/"kurutulmuş" içinde eşleşmesin,
        // "Un"/"Unu" eşleşsin. Harf-rakam dışındaki her şey kelime ayıracı sayılır.
        // Girdi regex'e girmeden sadeleştirilir (sözdizimi/enjeksiyon riski kalmasın).
        $safe = trim(preg_replace('/[^\p{L}\p{N}]+/u', ' ', $norm));
        $re = $safe === '' ? null : '(^|[^[:alnum:]])' . preg_replace('/\s+/', '[^[:alnum:]]+', $safe);

        // AÇIKLAMADA ARAMA YOK: açıklamalar şablon metin olduğu için alakasız
        // sonuç üretiyordu — "incir" araması "soğuk zincirle" geçen 28 süt/et
        // ürününü getiriyordu. Arama artık ürün adı, marka, kategori ve stok kodu
        // üzerinden; isimde geçenler listenin başında.
        // Regex kurulamadıysa (yalnız noktalama girildiyse) eski LIKE davranışına düş.
        $match = fn ($col) => $re ? ["{$col} RLIKE ?", [$re]] : ["{$col} LIKE ?", [$like]];
        [$nameSql, $nameBind] = $match($name);

        return Product::active()
            ->where(function ($query) use ($q, $nameSql, $nameBind, $match) {
                $query->whereRaw($nameSql, $nameBind)
                    ->orWhere('sku', 'like', "%{$q}%")
                    ->orWhereHas('brand', function ($b) use ($match) {
                        [$sql, $bind] = $match($this->normalizedColumn('name'));
                        $b->whereRaw($sql, $bind);
                    })
                    ->orWhereHas('category', function ($c) use ($match) {
                        [$sql, $bind] = $match($this->normalizedColumn('name'));
                        $c->whereRaw($sql, $bind);
                    });
            })
            ->orderByRaw("CASE WHEN {$nameSql} THEN 0 ELSE 1 END", $nameBind);
    }
}
