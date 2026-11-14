@extends('layouts.app')

@section('title', 'Thank you')

@section('content')
    <div class="pill" style="padding: 48px 24px; text-align: center; max-width: 640px; margin: 0 auto;">
        <p class="eyebrow">( thank you )</p>
        <div class="display display-lg">We got your order.</div>
        <p class="muted">
            Order <b>{{ $checkout->code }}</b> &middot; Rp {{ number_format($checkout->total) }}.
            We'll check your transfer and reply within 1 business day.
        </p>
        <div style="display: flex; gap: 8px; justify-content: center; flex-wrap: wrap; margin-top: 8px;">
            <a href="{{ route('orders.show', $checkout) }}" class="btn">Track this order</a>
            <a href="{{ url('/products') }}" class="btn btn-ghost">Back to the shop</a>
        </div>
    </div>
@endsection
