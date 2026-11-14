@extends('layouts.app')

@section('title', 'Order ' . $checkout->code)

@section('content')
    @php
        $status = $checkout->status();
        $track = ['pending' => 'Order placed', 'processing' => 'Processing', 'shipped' => 'Shipped', 'delivered' => 'Delivered'];
        $reached = array_search($status, array_keys($track), true); // false when "returned"
    @endphp

    <p class="eyebrow"><a href="{{ route('orders.index') }}">&larr; My orders</a></p>
    <h1 class="display display-lg">{{ $checkout->code }}</h1>
    <p class="muted" style="margin: 4px 0 20px;">Placed {{ $checkout->created_at->format('M j, Y · H:i') }}</p>

    {{-- Where the order is now --}}
    @if ($reached === false)
        <div class="alert alert-error">This order was returned. Questions? Contact us and mention {{ $checkout->code }}.</div>
    @else
        <ol class="steps" aria-label="Order status">
            @foreach (array_values($track) as $i => $label)
                <li class="{{ $i < $reached ? 'done' : ($i === $reached ? 'on' : '') }}" @if ($i === $reached) aria-current="step" @endif>
                    <span class="n">{{ $i < $reached || $status === 'delivered' ? '✓' : $i + 1 }}</span>{{ $label }}
                </li>
            @endforeach
        </ol>
    @endif

    @if (! $checkout->payment_sent_at && $status === 'pending')
        <div class="alert" style="display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap;">
            <span>We haven't received your payment confirmation yet.</span>
            <a href="{{ route('checkout.payment', $checkout) }}" class="btn btn-sm">Send payment details</a>
        </div>
    @endif

    <div class="split" style="display: grid; grid-template-columns: 1.4fr 1fr; gap: 20px; align-items: start;">
        <div class="pill summary" style="padding: 24px;">
            <p class="eyebrow">( items )</p>
            @foreach ($checkout->orders as $order)
                <div class="mini-item" style="font-size: 14px;">
                    @if ($order->product)
                        <a href="{{ route('products.show', $order->product) }}" class="visual"><x-product-visual :product="$order->product" /></a>
                    @endif
                    <span class="grow">{{ $order->product?->name ?? 'Product no longer available' }}<br><span class="hint">&times; {{ $order->quantity }}</span></span>
                    <span>Rp {{ number_format($order->total) }}</span>
                </div>
            @endforeach
            <hr class="rule">
            <div class="row"><span>Subtotal</span><span>Rp {{ number_format($checkout->subtotal) }}</span></div>
            <div class="row"><span>Shipping</span><span>{{ $checkout->shipping ? 'Rp ' . number_format($checkout->shipping) : 'Free' }}</span></div>
            <hr class="rule">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span class="display display-md">Total</span>
                <span class="display display-md">Rp {{ number_format($checkout->total) }}</span>
            </div>
        </div>

        <div>
            <div class="pill" style="padding: 24px;">
                <p class="eyebrow">( ship to )</p>
                <div style="font-weight: 600; margin-top: 6px;">{{ $checkout->recipient_name }}</div>
                <div class="muted" style="font-size: 14px;">{{ $checkout->phone }}</div>
                <p style="margin: 8px 0 0; font-size: 14px; white-space: pre-line;">{{ $checkout->address }}</p>
                @if ($checkout->note)
                    <p class="hint" style="margin: 8px 0 0;">Note: {{ $checkout->note }}</p>
                @endif
            </div>

            <div class="pill" style="padding: 24px; margin-top: 16px;">
                <p class="eyebrow">( payment )</p>
                <p style="margin: 6px 0 0; font-size: 14px;">
                    Bank transfer &middot;
                    @if ($checkout->payment_sent_at)
                        confirmation sent {{ $checkout->payment_sent_at->format('M j, H:i') }}
                    @else
                        waiting for your confirmation
                    @endif
                </p>
            </div>
        </div>
    </div>
@endsection
