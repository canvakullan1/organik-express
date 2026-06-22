<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Bundle;

class BundleController extends Controller
{
    public function index()
    {
        return view('storefront.bundles.index', [
            'bundles' => Bundle::active()->withCount('items')->orderBy('sort_order')->get(),
        ]);
    }

    public function show(Bundle $bundle)
    {
        abort_unless($bundle->is_active, 404);

        return view('storefront.bundles.show', [
            'bundle' => $bundle->load(['items.product']),
        ]);
    }
}
