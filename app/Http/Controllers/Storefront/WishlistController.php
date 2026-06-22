<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\Wishlist\WishlistService;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function __construct(private WishlistService $wishlist)
    {
    }

    public function index()
    {
        return view('storefront.wishlist', [
            'products' => $this->wishlist->products(),
        ]);
    }

    public function toggle(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
        ]);

        $added = $this->wishlist->toggle((int) $data['product_id']);

        if ($request->wantsJson()) {
            return response()->json([
                'added' => $added,
                'count' => $this->wishlist->count(),
                'message' => $added ? 'Favorilere eklendi.' : 'Favorilerden çıkarıldı.',
            ]);
        }

        return back()->with('success', $added ? 'Favorilere eklendi.' : 'Favorilerden çıkarıldı.');
    }

    public function remove(Request $request)
    {
        $data = $request->validate(['product_id' => ['required', 'integer']]);
        $this->wishlist->remove((int) $data['product_id']);

        return back()->with('success', 'Favorilerden çıkarıldı.');
    }
}
