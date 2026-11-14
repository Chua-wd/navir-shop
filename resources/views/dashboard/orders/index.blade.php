<x-admin-layout>
    <x-slot name="title">Orders</x-slot>

    <x-slot name="header">
        <div class="page-head">
            <div>
                <p class="eyebrow">( dashboard )</p>
                <h1 class="display display-lg">Orders</h1>
            </div>
        </div>
    </x-slot>

    @if (session('success'))
        <div class="alert">{{ session('success') }}</div>
    @endif

    {{-- Status tabs + search --}}
    <div class="toolbar" style="margin-top: 0;">
        <nav class="tabs" aria-label="Filter by status">
            <a href="{{ route('dashboard.orders.index', request()->only('q')) }}" class="{{ $status ? '' : 'active' }}">All · {{ $total }}</a>
            @foreach ($counts as $value => $count)
                <a href="{{ route('dashboard.orders.index', array_merge(request()->only('q'), ['status' => $value])) }}"
                   class="{{ $status === $value ? 'active' : '' }}">{{ ucfirst($value) }} · {{ $count }}</a>
            @endforeach
        </nav>
        <form method="GET" action="{{ route('dashboard.orders.index') }}" class="side-search user-search" role="search" style="margin: 0;">
            <x-icon name="search" size="14" />
            @if ($status)
                <input type="hidden" name="status" value="{{ $status }}">
            @endif
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Search order no, name or email..." aria-label="Search orders">
        </form>
    </div>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th style="text-align: right;">Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($checkouts as $checkout)
                    <tr>
                        <td><a href="{{ route('dashboard.orders.show', $checkout) }}" class="num" style="font-weight: 600;">{{ $checkout->code }}</a></td>
                        <td>
                            <div>{{ $checkout->recipient_name }}</div>
                            <div class="hint">{{ $checkout->email }}</div>
                        </td>
                        <td class="num">{{ $checkout->itemCount() }}</td>
                        <td class="num">Rp {{ number_format($checkout->total) }}</td>
                        <td>
                            @if ($checkout->payment_sent_at)
                                <span class="pill-status delivered">Confirmation sent</span>
                            @else
                                <span class="pill-status">Waiting</span>
                            @endif
                        </td>
                        <td><span class="pill-status {{ $checkout->status() }}">{{ ucfirst($checkout->status()) }}</span></td>
                        <td class="num muted" style="text-align: right;">{{ $checkout->created_at->format('M j, H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="muted">
                            @if ($status || request()->filled('q'))
                                No orders match. <a href="{{ route('dashboard.orders.index') }}">Show all</a>
                            @else
                                No orders yet. They appear here when a customer clicks "Place order".
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $checkouts->links() }}
</x-admin-layout>
