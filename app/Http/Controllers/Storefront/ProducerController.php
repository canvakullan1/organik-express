<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Producer;

class ProducerController extends Controller
{
    public function index()
    {
        return view('storefront.producers.index', [
            'producers' => Producer::active()
                ->withCount(['products' => fn ($q) => $q->active()])
                ->orderBy('sort_order')
                ->get(),
        ]);
    }

    public function show(Producer $producer)
    {
        abort_unless($producer->is_active, 404);

        return view('storefront.producers.show', [
            'producer' => $producer,
            'products' => $producer->products()
                ->active()
                ->with(['images', 'variants', 'category', 'tags'])
                ->latest()
                ->paginate(12),
        ]);
    }
}
