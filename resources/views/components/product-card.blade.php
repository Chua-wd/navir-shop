{{-- Product card for the shop grid and home page: <x-product-card :product="$product" /> --}}
@props(['product'])

<a href="{{ route('products.show', $product) }}" class="card">
    <div class="visual">
        <x-product-visual :product="$product" />
    </div>
    <div class="card-body">
        <div>
            <div class="eyebrow">{{ $product->category?->name ?? 'Uncategorized' }}</div>
            <h3 class="card-title">{{ $product->name }}</h3>
            {{-- Stars only when the query loaded them (->withRating()) and there is at least one review --}}
            @if ($product->reviews_count)
                <div class="card-rating">
                    <x-stars :rating="$product->reviews_avg_rating" size="13" />
                    <span>{{ number_format($product->reviews_avg_rating, 1) }} · {{ $product->reviews_count }}</span>
                </div>
            @endif
        </div>
        <div class="card-price">Rp {{ number_format($product->price) }}</div>
    </div>
</a>
