<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\Loyalty\LoyaltyService;
use App\Services\Wishlist\WishlistService;

class AccountController extends Controller
{
    public function index(WishlistService $wishlist, LoyaltyService $loyalty)
    {
        return view('storefront.account.index', [
            'user' => auth()->user(),
            'wishlistCount' => $wishlist->count(),
            'loyaltyBalance' => $loyalty->balance(auth()->user()),
        ]);
    }

    public function loyalty(LoyaltyService $loyalty)
    {
        $user = auth()->user();

        return view('storefront.account.loyalty', [
            'balance' => $loyalty->balance($user),
            'transactions' => $user->loyaltyTransactions()->paginate(20),
        ]);
    }
}
