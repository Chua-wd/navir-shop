@extends('layouts.app')

@section('title', $selectedCategory ? $selectedCategory->name : 'Shop')

@section('content')
    @php
        // Link helpers: keep the active category / sort order / search when switching one of them
        $categoryId = $selectedCategory?->id;
        $sortLink = fn ($value) => route('products.index', array_filter(['category' => $categoryId, 'sort' => $value === 'new' ? null : $value, 'q' => $search ?: null]));
        $catLink = fn ($id) => route('products.index', array_filter(['category' => $id, 'sort' => $sort === 'new' ? null : $sort, 'q' => $search ?: null]));
    @endphp

    @if (session('success'))
        <div class="alert">{{ session('success') }}</div>
    @endif

    <div style="display: flex; justify-content: space-between; align-items: end; gap: 16px; flex-wrap: wrap;">
        <div>
            <p class="eyebrow">( {{ $products->total() }} {{ \Illuminate\Support\Str::plural($search !== '' ? 'result' : 'product', $products->total()) }} )</p>
            <h1 class="display display-lg">
                @if ($search !== '')
                    “{{ $search }}”
                @else
                    {{ $selectedCategory ? $selectedCategory->name : 'All products' }}
                @endif
            </h1>
        </div>
        <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            {{-- Search: keeps the chosen category and sort order --}}
            <form method="GET" action="{{ route('products.index') }}" class="side-search shop-search" role="search">
                <x-icon name="search" size="14" />
                <input type="search" name="q" value="{{ $search }}" placeholder="Search products..." aria-label="Search products">
                @if ($categoryId)
                    <input type="hidden" name="category" value="{{ $categoryId }}">
                @endif
                @if ($sort !== 'new')
                    <input type="hidden" name="sort" value="{{ $sort }}">
                @endif
            </form>
            @if (auth()->user()?->isAdmin())
                <a href="{{ route('dashboard.products.create') }}" class="btn btn-ghost btn-sm">+ Add product</a>
            @endif
        </div>
    </div>

    @if ($search !== '')
        <div class="chips" style="margin-top: 12px; align-items: center;">
            <span class="muted" style="font-size: 13px;">Searching for</span>
            <span class="chip active">“{{ $search }}”</span>
            @if ($selectedCategory)
                <span class="muted" style="font-size: 13px;">in {{ $selectedCategory->name }}</span>
            @endif
            <a href="{{ route('products.index', array_filter(['category' => $categoryId, 'sort' => $sort === 'new' ? null : $sort])) }}" class="chip">Clear search &times;</a>
        </div>
    @endif

    {{-- Toolbar: sort order (left) + categories from ProductCategory (right) --}}
    <div class="toolbar">
        <div class="tabs">
            <a href="{{ $sortLink('new') }}" class="{{ $sort === 'new' ? 'active' : '' }}">Newest</a>
            <a href="{{ $sortLink('price_asc') }}" class="{{ $sort === 'price_asc' ? 'active' : '' }}">Price: low to high</a>
            <a href="{{ $sortLink('price_desc') }}" class="{{ $sort === 'price_desc' ? 'active' : '' }}">Price: high to low</a>
        </div>
        <div class="cat-links">
            <a href="{{ $catLink(null) }}" class="{{ $categoryId ? '' : 'active' }}">All</a>
            @foreach ($categories as $category)
                <a href="{{ $catLink($category->id) }}" class="{{ $categoryId === $category->id ? 'active' : '' }}">
                    {{ $category->name }} <span class="muted">{{ $category->products_count }}</span>
                </a>
            @endforeach
        </div>
    </div>

    <div class="grid">
        @forelse ($products as $product)
            <x-product-card :product="$product" />
        @empty
            <p class="muted">
                @if ($products->total() > 0)
                    There are no products on this page.
                    <a href="{{ $products->url(1) }}">Back to page 1</a>
                @elseif ($search !== '')
                    Nothing matches “{{ $search }}”{{ $selectedCategory ? ' in ' . $selectedCategory->name : '' }}.
                    Try another word, or <a href="{{ route('products.index') }}">see all products</a>.
                @else
                    No products yet.
                @endif
            </p>
        @endforelse
    </div>

    {{-- Pagination: Laravel builds the 1, 2, 3 ... links automatically --}}
    {{ $products->links() }}

    @if ($products->isNotEmpty())
        <p class="page-info">
            Showing {{ $products->firstItem() }}–{{ $products->lastItem() }}
            of {{ $products->total() }} products · page {{ $products->currentPage() }} of {{ $products->lastPage() }}
        </p>
    @endif
@endsection
