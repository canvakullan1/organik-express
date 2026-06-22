<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Order;

class OrdersController extends Controller
{
    public function index()
    {
        return view('storefront.account.orders', [
            'orders' => auth()->user()->orders()->withCount('items')->paginate(10),
        ]);
    }

    public function show(Order $order)
    {
        abort_unless($order->user_id === auth()->id(), 403);

        return view('storefront.account.order-show', [
            'order' => $order->load('items', 'statusHistory', 'shipment'),
        ]);
    }
}
