<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\Request;

/**
 * Public shop pages (anyone can browse).
 * Adding, editing and deleting products lives in Admin\ProductController (dashboard).
 */
class ProductController extends Controller
{
    // Products per page (6 = two rows in the 3-column grid)
    private const PER_PAGE = 6;

    // GET /products            : all products, 6 per page
    // GET /products?category=2 : only products in category 2
    // GET /products?page=2     : page 2
    // GET /products?q=serum    : search (can be combined with category and sort)
    public function index(Request $request)
    {
        // Filter buttons: every category + its product count (uses the products() relation)
        $categories = ProductCategory::withCount('products')->orderBy('name')->get();

        $selectedCategory = null;
        if ($request->filled('category')) {
            $selectedCategory = ProductCategory::findOrFail($request->category);
            // Load the products through the relation: $category->products()
            $query = $selectedCategory->products();
        } else {
            $query = Product::query();
        }

        // Search: ?q=serum matches the product name, description or category name
        $search = trim((string) $request->q);
        if ($search !== '') {
            // "%" and "_" typed by the user are plain characters, not wildcards ("10%" finds "Niacinamide 10%").
            // "!" is the escape character: it works the same in MySQL and in SQLite (used by the tests).
            $like = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search) . '%';
            $query->where(function ($where) use ($like) {
                $where->whereRaw("products.name LIKE ? ESCAPE '!'", [$like])
                    ->orWhereRaw("products.description LIKE ? ESCAPE '!'", [$like])
                    ->orWhereHas('category', fn ($category) => $category->whereRaw("categories.name LIKE ? ESCAPE '!'", [$like]));
            });
        }

        // Average stars + number of reviews for every product (shown on the cards)
        $query->withRating();

        // Sort order: ?sort=new (default), ?sort=price_asc, ?sort=price_desc, ?sort=top (best rated first)
        $sort = in_array($request->sort, ['price_asc', 'price_desc', 'top']) ? $request->sort : 'new';
        match ($sort) {
            'price_asc'  => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'top'        => $query->orderByDesc('reviews_avg_rating')->orderByDesc('reviews_count')->latest('id'),
            default      => $query->latest('id'),
        };

        // with('category')  = load categories in one query instead of one per product
        // paginate()        = only fetch the 6 rows for the current page
        // withQueryString() = page links keep ?category=...&sort=...&q=...
        $products = $query->with('category')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('products.index', compact('products', 'categories', 'selectedCategory', 'sort', 'search'));
    }

    // GET /products/{product} : product detail page
    public function show(Product $product)
    {
        // Count this visit as a "product click" for the dashboard
        $product->clicks()->create(['user_id' => auth()->id()]);

        $product->load('category')->loadAvg('reviews', 'rating')->loadCount('reviews');

        // "You may also like": other products in the same category (through the relation)
        $related = $product->category
            ? $product->category->products()->with('category')->withRating()->whereKeyNot($product->id)->take(3)->get()
            : collect();

        // Reviews, newest first. The logged-in customer's own review is shown in the form instead.
        $user = auth()->user();
        $reviews = $product->reviews()->with('user')->latest()->get();
        $myReview = $user ? $reviews->firstWhere('user_id', $user->id) : null;
        $canReview = $user && ($myReview || $user->hasBought($product));

        // How many 5-star, 4-star, ... reviews (for the bars next to the average)
        $breakdown = collect([5, 4, 3, 2, 1])->mapWithKeys(fn ($stars) => [$stars => $reviews->where('rating', $stars)->count()]);

        return view('products.show', compact('product', 'related', 'reviews', 'myReview', 'canReview', 'breakdown'));
    }
}
