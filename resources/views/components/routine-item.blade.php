{{-- One recommended product in the routine result: <x-routine-item :product="$p" why="..." /> --}}
@props(['product', 'why'])

<div class="routine-item">
    <a href="{{ route('products.show', $product) }}" class="visual"><x-product-visual :product="$product" /></a>
    <div style="min-width: 0;">
        <a href="{{ route('products.show', $product) }}" class="routine-item-name">{{ $product->name }}</a>
        <p class="hint" style="margin: 2px 0 6px;">{{ $why }}</p>
        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <span class="num" style="font-weight: 600;">Rp {{ number_format($product->price) }}</span>
            @if ($product->stock > 0)
                <form method="POST" action="{{ route('cart.store', $product) }}">
                    @csrf
                    <input type="hidden" name="quantity" value="1">
                    <button type="submit" class="btn btn-ghost btn-sm">Add to cart</button>
                </form>
            @else
                <span class="hint">Out of stock</span>
            @endif
        </div>
    </div>
</div>
