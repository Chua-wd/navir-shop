<x-admin-layout>
    <x-slot name="title">Order {{ $checkout->code }}</x-slot>

    <x-slot name="header">
        <div class="page-head">
            <div>
                <p class="eyebrow"><a href="{{ route('dashboard.orders.index') }}">&larr; Orders</a></p>
                <h1 class="display display-lg">{{ $checkout->code }}</h1>
                <p class="muted" style="margin: 4px 0 0;">Placed {{ $checkout->created_at->format('M j, Y · H:i') }}</p>
            </div>
            <span class="pill-status {{ $checkout->status() }}" style="font-size: 14px; padding: 6px 14px;">{{ ucfirst($checkout->status()) }}</span>
        </div>
    </x-slot>

    @if (session('success'))
        <div class="alert">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-error">{{ $errors->first() }}</div>
    @endif

    <div class="split" style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 16px; align-items: start;">
        <div>
            {{-- Items --}}
            <section class="dash-card">
                <div class="card-head"><h2>Items</h2><span class="muted" style="font-size: 12px;">{{ $checkout->itemCount() }} {{ Str::plural('item', $checkout->itemCount()) }}</span></div>
                @foreach ($checkout->orders as $order)
                    <div class="mini-item" style="font-size: 14px;">
                        @if ($order->product)
                            <div class="visual"><x-product-visual :product="$order->product" /></div>
                        @endif
                        <span class="grow">
                            {{ $order->product?->name ?? 'Deleted product' }}
                            <br><span class="hint">{{ $order->quantity }} &times; Rp {{ number_format(intdiv($order->total, max(1, $order->quantity))) }}</span>
                        </span>
                        <span class="num">Rp {{ number_format($order->total) }}</span>
                    </div>
                @endforeach
                <hr class="rule">
                <div class="summary">
                    <div class="row"><span>Subtotal</span><span>Rp {{ number_format($checkout->subtotal) }}</span></div>
                    <div class="row"><span>Shipping</span><span>{{ $checkout->shipping ? 'Rp ' . number_format($checkout->shipping) : 'Free' }}</span></div>
                    <div class="row" style="font-weight: 600; font-size: 15px;"><span>Total</span><span>Rp {{ number_format($checkout->total) }}</span></div>
                </div>
            </section>

            {{-- Payment confirmation from the customer --}}
            <section class="dash-card" style="margin-top: 16px;">
                <div class="card-head">
                    <h2>Payment confirmation</h2>
                    @if ($checkout->payment_sent_at)
                        <span class="pill-status delivered">Sent {{ $checkout->payment_sent_at->format('M j, H:i') }}</span>
                    @else
                        <span class="pill-status">Not sent yet</span>
                    @endif
                </div>
                @if ($checkout->payment_message)
                    <pre class="payment-message">{{ $checkout->payment_message }}</pre>
                @else
                    <p class="muted" style="margin: 0; font-size: 14px;">The customer hasn't sent the payment form yet. Check your bank account before moving the order on.</p>
                @endif
            </section>
        </div>

        <div>
            {{-- Status --}}
            <section class="dash-card">
                <div class="card-head"><h2>Status</h2></div>
                <form method="POST" action="{{ route('dashboard.orders.update', $checkout) }}" style="display: flex; gap: 8px; flex-wrap: wrap;">
                    @csrf
                    @method('PATCH')
                    <select name="status" class="role-select" aria-label="Order status" style="flex: 1;">
                        @foreach (\App\Models\Order::STATUSES as $value)
                            <option value="{{ $value }}" @selected($checkout->status() === $value)>{{ ucfirst($value) }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-sm" style="height: 38px;">Update</button>
                </form>
                <p class="hint" style="margin: 10px 0 0;">Pending &rarr; Processing &rarr; Shipped &rarr; Delivered.</p>
            </section>

            {{-- Customer & shipping --}}
            <section class="dash-card" style="margin-top: 16px;">
                <div class="card-head"><h2>Ship to</h2></div>
                <dl class="spec" style="grid-template-columns: 90px 1fr; font-size: 14px;">
                    <dt>Name</dt><dd>{{ $checkout->recipient_name }}</dd>
                    <dt>Phone</dt><dd>{{ $checkout->phone }}</dd>
                    <dt>Email</dt><dd><a href="mailto:{{ $checkout->email }}">{{ $checkout->email }}</a></dd>
                    <dt>Address</dt><dd style="white-space: pre-line;">{{ $checkout->address }}</dd>
                    @if ($checkout->note)
                        <dt>Note</dt><dd>{{ $checkout->note }}</dd>
                    @endif
                    <dt>Account</dt><dd>{{ $checkout->user?->name ?? 'Deleted user' }}</dd>
                </dl>
            </section>
        </div>
    </div>
</x-admin-layout>
