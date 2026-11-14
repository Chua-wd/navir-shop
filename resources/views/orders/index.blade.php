@extends('layouts.app')

@section('title', 'My orders')

@section('content')
    <p class="eyebrow">( my orders )</p>
    <h1 class="display display-lg" style="margin-bottom: 20px;">My orders</h1>

    @if ($checkouts->isEmpty())
        <div class="pill" style="padding: 48px 24px; text-align: center;">
            <div class="display display-md">No orders yet.</div>
            <p class="muted">When you place an order, you can follow it here.</p>
            <a href="{{ url('/products') }}" class="btn" style="margin-top: 8px;">Shop now</a>
        </div>
    @else
        <div class="pill">
            @foreach ($checkouts as $checkout)
                <a href="{{ route('orders.show', $checkout) }}" class="order-row">
                    {{-- Up to 3 product photos --}}
                    <div class="order-thumbs">
                        @foreach ($checkout->orders->take(3) as $order)
                            @if ($order->product)
                                <div class="visual"><x-product-visual :product="$order->product" /></div>
                            @endif
                        @endforeach
                    </div>
                    <div style="min-width: 0;">
                        <div style="font-weight: 600;">{{ $checkout->code }}</div>
                        <div class="hint">
                            {{ $checkout->created_at->format('M j, Y') }} &middot;
                            {{ $checkout->itemCount() }} {{ Str::plural('item', $checkout->itemCount()) }}
                        </div>
                    </div>
                    <div class="order-row-end">
                        <span class="pill-status {{ $checkout->status() }}">{{ ucfirst($checkout->status()) }}</span>
                        <span class="num">Rp {{ number_format($checkout->total) }}</span>
                    </div>
                </a>
            @endforeach
        </div>

        {{ $checkouts->links() }}
    @endif
@endsection
