<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewsTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $category = ProductCategory::create(['name' => 'Serums']);
        $this->product = Product::create(['category_id' => $category->id, 'name' => 'Niacinamide 10% Serum 30ml', 'description' => 'Test', 'price' => 159000, 'stock' => 10]);
    }

    // A customer with an order for the product (a "verified buyer")
    private function buyer(string $status = 'delivered', string $name = 'Budi Santoso'): User
    {
        $user = User::factory()->create(['name' => $name]);
        Order::create(['user_id' => $user->id, 'product_id' => $this->product->id, 'quantity' => 1, 'total' => 159000, 'status' => $status]);

        return $user;
    }

    public function test_buyer_can_post_a_review(): void
    {
        $buyer = $this->buyer();

        $this->actingAs($buyer)->post(route('reviews.store', $this->product), ['rating' => 5, 'comment' => 'Great serum!'])
            ->assertRedirect(route('products.show', $this->product) . '#reviews')
            ->assertSessionHas('review_saved', 'Thanks for your review!');

        $this->assertDatabaseHas('reviews', ['product_id' => $this->product->id, 'user_id' => $buyer->id, 'rating' => 5, 'comment' => 'Great serum!']);
    }

    public function test_posting_again_updates_the_same_review(): void
    {
        $buyer = $this->buyer();

        $this->actingAs($buyer)->post(route('reviews.store', $this->product), ['rating' => 3]);
        $this->actingAs($buyer)->post(route('reviews.store', $this->product), ['rating' => 4, 'comment' => 'Better after a week'])
            ->assertSessionHas('review_saved', 'Your review was updated.');

        $this->assertSame(1, Review::count());
        $this->assertSame(4, Review::first()->rating);
    }

    public function test_people_who_did_not_buy_cannot_review(): void
    {
        $stranger = User::factory()->create();

        $this->actingAs($stranger)->get(route('products.show', $this->product))
            ->assertSee('Only customers who ordered this product can review it.')
            ->assertDontSee('Post review');
        $this->actingAs($stranger)->post(route('reviews.store', $this->product), ['rating' => 1])->assertForbidden();

        // A returned order doesn't count either
        $this->actingAs($this->buyer('returned'))->post(route('reviews.store', $this->product), ['rating' => 1])->assertForbidden();

        $this->assertSame(0, Review::count());
    }

    public function test_guests_are_asked_to_log_in(): void
    {
        $this->get(route('products.show', $this->product))->assertSee('to review a product you ordered');
        $this->post(route('reviews.store', $this->product), ['rating' => 5])->assertRedirect('/login');
    }

    public function test_rating_must_be_one_to_five(): void
    {
        $buyer = $this->buyer();

        foreach ([0, 6, 'abc', null] as $bad) {
            $this->actingAs($buyer)->post(route('reviews.store', $this->product), ['rating' => $bad])->assertSessionHasErrors('rating');
        }
        $this->assertSame(0, Review::count());
    }

    public function test_product_page_shows_average_count_and_reviews(): void
    {
        Review::create(['product_id' => $this->product->id, 'user_id' => $this->buyer('delivered', 'Budi Santoso')->id, 'rating' => 5, 'comment' => 'Love it']);
        Review::create(['product_id' => $this->product->id, 'user_id' => $this->buyer('shipped', 'Sari Dewi')->id, 'rating' => 4]);

        $this->get(route('products.show', $this->product))->assertOk()
            ->assertSee('4.5 · 2 reviews')
            ->assertSee('Budi S.')            // last name shortened
            ->assertDontSee('Budi Santoso')
            ->assertSee('Love it')
            ->assertSee('Verified buyer');
    }

    public function test_shop_cards_show_stars_and_top_rated_sort_works(): void
    {
        $category = $this->product->category;
        $other = Product::create(['category_id' => $category->id, 'name' => 'Vitamin C Bright Serum 30ml', 'description' => 'Test', 'price' => 169000, 'stock' => 5]);
        Review::create(['product_id' => $this->product->id, 'user_id' => $this->buyer()->id, 'rating' => 3]);
        $fan = User::factory()->create();
        Order::create(['user_id' => $fan->id, 'product_id' => $other->id, 'quantity' => 1, 'total' => 1, 'status' => 'delivered']);
        Review::create(['product_id' => $other->id, 'user_id' => $fan->id, 'rating' => 5]);

        $this->get('/products')->assertSee('3.0 · 1')->assertSee('5.0 · 1');
        $this->get('/products?sort=top')->assertSeeInOrder(['Vitamin C Bright Serum 30ml', 'Niacinamide 10% Serum 30ml']);
    }

    public function test_owner_and_admins_can_remove_a_review_others_cannot(): void
    {
        $buyer = $this->buyer();
        $review = Review::create(['product_id' => $this->product->id, 'user_id' => $buyer->id, 'rating' => 2]);

        $this->actingAs(User::factory()->create())->delete(route('reviews.destroy', $review))->assertForbidden();
        $this->assertModelExists($review);

        $this->actingAs(User::factory()->create(['role' => 'admin']))->delete(route('reviews.destroy', $review))
            ->assertSessionHas('review_saved', 'The review was removed.');
        $this->assertModelMissing($review);

        $own = Review::create(['product_id' => $this->product->id, 'user_id' => $buyer->id, 'rating' => 4]);
        $this->actingAs($buyer)->delete(route('reviews.destroy', $own));
        $this->assertModelMissing($own);
    }

    public function test_demo_shopper_can_review_but_demo_admin_cannot_delete_reviews(): void
    {
        $demoUser = User::factory()->create(['email' => 'demo.user@navir.test']);
        Order::create(['user_id' => $demoUser->id, 'product_id' => $this->product->id, 'quantity' => 1, 'total' => 1, 'status' => 'pending']);
        $this->actingAs($demoUser)->post(route('reviews.store', $this->product), ['rating' => 5]);
        $review = Review::firstOrFail();

        $demoAdmin = User::factory()->create(['email' => 'demo.admin@navir.test', 'role' => 'admin']);
        $this->actingAs($demoAdmin)->delete(route('reviews.destroy', $review))->assertSessionHas('demo_blocked');
        $this->assertModelExists($review);
    }
}
