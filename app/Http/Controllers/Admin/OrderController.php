<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Checkout;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Orders placed through the shop's checkout (admins and system admins).
 *   /dashboard/orders            list, search, filter by status
 *   /dashboard/orders/{code}     details: items, shipping address, payment message, status
 */
class OrderController extends Controller
{
    // GET /dashboard/orders?status=pending&q=...
    public function index(Request $request)
    {
        $status = in_array($request->status, Order::STATUSES, true) ? $request->status : null;

        $checkouts = Checkout::with(['orders', 'user'])
            ->when($status, fn ($query) => $query->withStatus($status))
            ->when($request->filled('q'), function ($query) use ($request) {
                $q = '%' . $request->q . '%';
                $query->where(fn ($w) => $w->where('code', 'like', $q)
                    ->orWhere('recipient_name', 'like', $q)
                    ->orWhere('email', 'like', $q));
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        // Numbers on the status tabs
        $counts = collect(Order::STATUSES)->mapWithKeys(fn ($s) => [$s => Checkout::withStatus($s)->count()]);

        return view('dashboard.orders.index', [
            'checkouts' => $checkouts,
            'status' => $status,
            'counts' => $counts,
            'total' => Checkout::count(),
        ]);
    }

    // GET /dashboard/orders/{checkout}
    public function show(Checkout $checkout)
    {
        $checkout->load(['orders.product', 'user']);

        return view('dashboard.orders.show', ['checkout' => $checkout]);
    }

    // PATCH /dashboard/orders/{checkout}  (status dropdown)
    public function update(Request $request, Checkout $checkout)
    {
        $data = $request->validate(['status' => ['required', Rule::in(Order::STATUSES)]]);

        $checkout->setStatus($data['status']);

        return back()->with('success', "Order {$checkout->code} is now " . ucfirst($data['status']) . '.');
    }
}
