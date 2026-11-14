<?php

namespace App\Http\Controllers;

use App\Models\Checkout;
use Illuminate\Http\Request;

/**
 * "My orders": a logged-in customer's own order history.
 *   /orders          list (newest first)
 *   /orders/{code}   one order: status tracker, items, shipping address, payment
 */
class MyOrderController extends Controller
{
    // GET /orders
    public function index(Request $request)
    {
        $checkouts = $request->user()->checkouts()
            ->with('orders.product')
            ->latest()
            ->paginate(10);

        return view('orders.index', ['checkouts' => $checkouts]);
    }

    // GET /orders/{checkout}
    public function show(Request $request, Checkout $checkout)
    {
        // Someone else's order: act as if it doesn't exist
        abort_unless((int) $checkout->user_id === $request->user()->id, 404);

        $checkout->load('orders.product');

        return view('orders.show', ['checkout' => $checkout]);
    }
}
