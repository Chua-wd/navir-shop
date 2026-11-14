@extends('layouts.app')

@section('title', $product->name)

@section('content')
    @php
        preg_match('/(\d+)\s?(ml|g)\b/i', $product->name, $m);
        $size = $m ? $m[1] . ' ' . strtolower($m[2]) : null;
        $title = trim(preg_replace('/\s*\d+\s?(ml|g)\b/i', '', $product->name));
        $views = ['front' => 'Front', 'back' => 'Back', 'detail' => 'Close-up'];
        $inCart = \App\Support\Cart::quantities()[$product->id] ?? 0;
    @endphp

    @if (session('success'))
        <div class="alert">{{ session('success') }}</div>
    @endif

    {{-- "Added to cart" popup after pressing Add to cart --}}
    @if ($added = session('added'))
        <div class="toast" role="status" id="added-toast">
            <div class="visual"><x-product-visual :product="$product" /></div>
            <div>
                <div style="font-weight: 600; font-size: 13px;">Added to cart</div>
                <div class="hint">{{ $added['name'] }} &times; {{ $added['quantity'] }}</div>
            </div>
            <a href="{{ route('cart.index') }}" class="btn btn-sm">View cart</a>
        </div>
    @endif

    {{-- Back to the full product list --}}
    <a href="{{ route('products.index') }}" class="back-link">&larr; All products</a>

    <section class="split" style="display: grid; grid-template-columns: 1.55fr 1fr; gap: 20px; align-items: start;">
        {{-- Left: gallery --}}
        <div class="visual" style="position: relative; aspect-ratio: auto; height: min(78vh, 760px);">
            <div style="position: absolute; top: 14px; left: 14px; z-index: 2; display: flex; gap: 8px;">
                @foreach ($views as $key => $label)
                    <button type="button" class="thumb {{ $loop->first ? 'active' : '' }}" data-view="{{ $key }}" aria-label="{{ $label }}" title="{{ $label }}"
                            style="width: 64px; height: 44px; padding: 0; border-radius: 6px; cursor: pointer; overflow: hidden;
                                   background: var(--bg); border: 1px solid {{ $loop->first ? 'var(--navy)' : 'transparent' }};">
                        <x-product-visual :product="$product" :view="$key" style="width: 100%; height: 100%;" preserveAspectRatio="xMidYMid slice" />
                    </button>
                @endforeach
            </div>

            @foreach ($views as $key => $label)
                <div class="main-view" data-view="{{ $key }}" style="height: 100%; {{ $loop->first ? '' : 'display: none;' }}">
                    <x-product-visual :product="$product" :view="$key" />
                </div>
            @endforeach
        </div>

        {{-- Right: product info --}}
        <div style="display: flex; flex-direction: column; min-height: min(78vh, 760px);">
            <p class="eyebrow">Navir · {{ $product->category?->name ?? 'Uncategorized' }}</p>
            <h1 class="display display-lg">{{ $title }}@if ($size)<br>{{ $size }}@endif</h1>

            {{-- Rating summary: jumps to the reviews below --}}
            <a href="#reviews" class="rating-link">
                @if ($product->reviews_count)
                    <x-stars :rating="$product->reviews_avg_rating" size="15" />
                    <span>{{ number_format($product->reviews_avg_rating, 1) }} · {{ $product->reviews_count }} {{ Str::plural('review', $product->reviews_count) }}</span>
                @else
                    <span>No reviews yet</span>
                @endif
            </a>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 16px;">
                <div class="chips">
                    @if ($size)
                        <span class="chip active">{{ strtoupper($size) }}</span>
                    @endif
                    <span class="chip">{{ $product->stock > 0 ? $product->stock . ' in stock' : 'Out of stock' }}</span>
                </div>
            </div>

            <hr class="rule">

            <p class="eyebrow">( product info )</p>
            <p class="lead" style="margin: 6px 0 0;">{{ $product->description }}</p>

            <div class="display display-md" style="margin-top: 28px;">Rp {{ number_format($product->price) }}</div>

            {{-- Quantity + Add to cart --}}
            @if ($product->stock > 0)
                <form method="POST" action="{{ route('cart.store', $product) }}" style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 14px;">
                    @csrf
                    <div class="qty">
                        <button type="button" data-step="-1" aria-label="Less">−</button>
                        <input type="number" name="quantity" id="quantity" value="1" min="1" max="{{ $product->stock }}" aria-label="Quantity">
                        <button type="button" data-step="1" aria-label="More">+</button>
                    </div>
                    <button type="submit" class="btn" style="flex: 1;">Add to cart</button>
                </form>
                @if ($inCart)
                    <p class="hint" style="margin-top: 8px;">{{ $inCart }} already in your <a href="{{ route('cart.index') }}">cart</a>.</p>
                @endif
            @else
                <button type="button" class="btn" style="margin-top: 14px;" disabled>Out of stock</button>
            @endif
            @error('quantity')
                <p class="hint" style="margin-top: 8px; color: #b42318;">{{ $message }}</p>
            @enderror

            <div class="accordion" style="margin-top: auto; padding-top: 28px;">
                <details>
                    <summary>Product overview</summary>
                    <div class="content">{{ $product->description }}</div>
                </details>
                <details>
                    <summary>Specifications</summary>
                    <div class="content">
                        <dl class="spec">
                            <dt>Category</dt><dd>{{ $product->category?->name ?? '–' }}</dd>
                            <dt>Size</dt><dd>{{ $size ?? '–' }}</dd>
                            <dt>Stock</dt><dd>{{ $product->stock }}</dd>
                            <dt>SKU</dt><dd>NVR-{{ str_pad($product->id, 4, '0', STR_PAD_LEFT) }}</dd>
                        </dl>
                    </div>
                </details>
                <details>
                    <summary>Delivery &amp; returns</summary>
                    <div class="content">
                        Ships within 1–2 business days. Free shipping on orders over Rp 200,000.
                        Unopened products can be returned within 7 days.
                    </div>
                </details>
            </div>

            {{-- Admin actions (dashboard), only for logged-in users --}}
             @if (auth()->user()?->isAdmin())
                <div style="display: flex; gap: 8px; margin-top: 16px;">
                    <a href="{{ route('dashboard.products.edit', $product) }}" class="btn btn-ghost btn-sm">Edit</a>
                    <details class="confirm-delete">
                        <summary class="btn btn-danger btn-sm">Delete</summary>
                        <form action="{{ route('dashboard.products.destroy', $product) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">Yes, delete</button>
                        </form>
                    </details>
                </div>
            @endif
        </div>
    </section>

    {{-- ===== Reviews ===== --}}
    <section id="reviews" class="reviews" style="margin-top: 56px;">
        <h2 class="display display-lg" style="margin-bottom: 18px;">Reviews</h2>

        @if (session('review_saved'))
            <div class="alert">{{ session('review_saved') }}</div>
        @endif
        @if (session('demo_blocked'))
            <div class="alert alert-error">{{ session('demo_blocked') }}</div>
        @endif

        <div class="split" style="display: grid; grid-template-columns: 1fr 1.6fr; gap: 20px; align-items: start;">
            <div>
                {{-- Average + how the stars are spread --}}
                <div class="pill" style="padding: 22px;">
                    @if ($reviews->isNotEmpty())
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <span class="display display-lg">{{ number_format($product->reviews_avg_rating, 1) }}</span>
                            <div>
                                <x-stars :rating="$product->reviews_avg_rating" size="18" />
                                <div class="hint">{{ $reviews->count() }} {{ Str::plural('review', $reviews->count()) }}</div>
                            </div>
                        </div>
                        <div style="margin-top: 14px;">
                            @foreach ($breakdown as $stars => $count)
                                <div class="rating-bar">
                                    <span>{{ $stars }} ★</span>
                                    <span class="track"><span style="width: {{ $reviews->count() ? round($count / $reviews->count() * 100) : 0 }}%;"></span></span>
                                    <span class="hint">{{ $count }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="display display-md">No reviews yet.</div>
                        <p class="muted" style="margin: 6px 0 0; font-size: 14px;">Bought it? Be the first to say how it works for you.</p>
                    @endif
                </div>

                {{-- Write / edit your review --}}
                <div class="pill" style="padding: 22px; margin-top: 16px;">
                    @guest
                        <p style="margin: 0; font-size: 14px;"><a href="{{ route('login') }}">Log in</a> to review a product you ordered.</p>
                    @else
                        @if ($canReview)
                            <p class="eyebrow" style="margin: 0 0 10px;">( {{ $myReview ? 'your review' : 'write a review' }} )</p>
                            <form method="POST" action="{{ route('reviews.store', $product) }}">
                                @csrf
                                <fieldset class="star-input">
                                    <legend class="sr-only">Your rating</legend>
                                    {{-- Shown right-to-left so hovering a star also lights up the ones before it --}}
                                    @foreach ([5, 4, 3, 2, 1] as $stars)
                                        <input type="radio" name="rating" id="rating-{{ $stars }}" value="{{ $stars }}" required
                                               @checked((int) old('rating', $myReview?->rating) === $stars)>
                                        <label for="rating-{{ $stars }}" title="{{ $stars }} {{ Str::plural('star', $stars) }}">★<span class="sr-only">{{ $stars }} {{ Str::plural('star', $stars) }}</span></label>
                                    @endforeach
                                </fieldset>
                                @error('rating')
                                    <p class="hint" style="color: #b42318; margin: 4px 0 0;">Please pick 1 to 5 stars.</p>
                                @enderror
                                <div class="field" style="margin: 12px 0;">
                                    <label for="comment">Comment (optional)</label>
                                    <textarea id="comment" name="comment" maxlength="1000" placeholder="How did it work for your skin?">{{ old('comment', $myReview?->comment) }}</textarea>
                                </div>
                                <button type="submit" class="btn btn-sm">{{ $myReview ? 'Update review' : 'Post review' }}</button>
                            </form>
                        @else
                            <p style="margin: 0; font-size: 14px;" class="muted">Only customers who ordered this product can review it.</p>
                        @endif
                    @endguest
                </div>
            </div>

            {{-- The reviews --}}
            <div class="pill">
                @forelse ($reviews as $review)
                    <article class="review">
                        <div style="display: flex; justify-content: space-between; gap: 12px; align-items: start;">
                            <div>
                                <x-stars :rating="$review->rating" size="14" />
                                <div style="font-weight: 600; margin-top: 4px;">
                                    {{ $review->authorName() }}
                                    <span class="tag">Verified buyer</span>
                                    @if ((int) $review->user_id === auth()->id())
                                        <span class="tag">you</span>
                                    @endif
                                </div>
                            </div>
                            <span class="hint">{{ $review->created_at->format('M j, Y') }}</span>
                        </div>
                        @if ($review->comment)
                            <p style="margin: 8px 0 0; font-size: 14px; white-space: pre-line;">{{ $review->comment }}</p>
                        @endif

                        {{-- Remove: your own review, or any review as an admin --}}
                        @auth
                            @if ((int) $review->user_id === auth()->id() || auth()->user()->isAdmin())
                                <details class="confirm-delete" style="margin-top: 8px;">
                                    <summary class="btn btn-ghost btn-sm">Remove</summary>
                                    <form method="POST" action="{{ route('reviews.destroy', $review) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">Yes, remove</button>
                                    </form>
                                </details>
                            @endif
                        @endauth
                    </article>
                @empty
                    <p class="muted" style="margin: 0; padding: 22px;">Reviews from customers will appear here.</p>
                @endforelse
            </div>
        </div>
    </section>

    @if ($related->isNotEmpty())
        <section style="margin-top: 56px;">
            <h2 class="display display-lg" style="margin-bottom: 18px;">You may also like</h2>
            <div class="grid">
                @foreach ($related as $item)
                    <x-product-card :product="$item" />
                @endforeach
            </div>
        </section>
    @endif

    <script>
        // − / + next to the quantity box (stays between 1 and the stock)
        document.querySelectorAll('[data-step]').forEach(function (button) {
            button.addEventListener('click', function () {
                var input = document.getElementById('quantity');
                var next = (parseInt(input.value, 10) || 1) + parseInt(button.dataset.step, 10);
                input.value = Math.min(Math.max(next, 1), parseInt(input.max, 10));
            });
        });

        // Hide the "Added to cart" popup after a few seconds
        var toast = document.getElementById('added-toast');
        if (toast) {
            setTimeout(function () { toast.remove(); }, 5000);
        }

        // Click a thumbnail -> show the matching large image
        document.querySelectorAll('.thumb').forEach(function (thumb) {
            thumb.addEventListener('click', function () {
                document.querySelectorAll('.thumb').forEach(function (t) { t.style.borderColor = 'transparent'; });
                thumb.style.borderColor = 'var(--navy)';
                document.querySelectorAll('.main-view').forEach(function (v) {
                    v.style.display = v.dataset.view === thumb.dataset.view ? '' : 'none';
                });
            });
        });
    </script>
@endsection
