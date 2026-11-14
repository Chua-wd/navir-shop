<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;

/**
 * Product reviews (logged-in customers).
 *   POST   /products/{product}/reviews  write or update your review (only if you ordered it)
 *   DELETE /reviews/{review}            remove your own review, or any review as an admin
 */
class ReviewController extends Controller
{
    public function store(Request $request, Product $product)
    {
        abort_unless($request->user()->hasBought($product), 403, 'Only customers who ordered this product can review it.');

        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        // One review per customer per product: a second submit updates the first
        $review = Review::updateOrCreate(
            ['product_id' => $product->id, 'user_id' => $request->user()->id],
            $data
        );

        return redirect(route('products.show', $product) . '#reviews')
            ->with('review_saved', $review->wasRecentlyCreated ? 'Thanks for your review!' : 'Your review was updated.');
    }

    public function destroy(Request $request, Review $review)
    {
        $user = $request->user();
        abort_unless((int) $review->user_id === $user->id || $user->isAdmin(), 403);

        $product = $review->product;
        $review->delete();

        return redirect(route('products.show', $product) . '#reviews')->with('review_saved', 'The review was removed.');
    }
}
