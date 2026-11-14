<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Demo accounts (config/shop.php 'demo') are shared with everyone who visits the portfolio,
 * so they can open every page but can't save changes: no deleting or editing products,
 * categories or users, and no changing the demo account's own name, email or password.
 * Allowed on purpose: moving an order's status (so the order flow can be tried).
 */
class DemoReadOnly
{
    private const ALLOWED_ROUTES = ['dashboard.orders.update'];

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isDemo() && ! $request->isMethodSafe() && ! $request->routeIs(...self::ALLOWED_ROUTES)) {
            return back()->with('demo_blocked', "This is a shared demo account, so saving changes here is turned off. Feel free to look around.");
        }

        return $next($request);
    }
}
