<?php

namespace Tests\Feature;

use App\Models\Checkout;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyOrdersTest extends TestCase
{
    use RefreshDatabase;

    private function placeOrder(User $customer, bool $sendPayment = true): Checkout
    {
        $category = ProductCategory::firstOrCreate(['name' => 'Serums']);
        $product = Product::create([
            'category_id' => $category->id, 'name' => 'Niacinamide 10% Serum 30ml',
            'description' => 'Test', 'price' => 159000, 'stock' => 10,
        ]);

        $this->actingAs($customer)->withSession(['cart' => [$product->id => 2]])->post(route('checkout.store'), [
            'recipient_name' => 'Budi Santoso', 'phone' => '081234567890', 'email' => 'budi@example.com',
            'address' => 'Jl. Melati No. 12, Jakarta',
        ]);
        $checkout = Checkout::latest('id')->firstOrFail();

        if ($sendPayment) {
            $this->post(route('checkout.payment.send', $checkout), ['message' => 'Paid']);
        }

        return $checkout->fresh();
    }

    public function test_customer_sees_own_orders_newest_first(): void
    {
        $customer = User::factory()->create();
        $first = $this->placeOrder($customer);
        $first->forceFill(['created_at' => now()->subDay()])->save();
        $second = $this->placeOrder($customer);

        $this->actingAs($customer)->get(route('orders.index'))
            ->assertOk()
            ->assertSeeInOrder([$second->code, $first->code])
            ->assertSee('Rp 318,000')
            ->assertSee('Pending');
    }

    public function test_customer_does_not_see_other_peoples_orders(): void
    {
        $mine = $this->placeOrder($me = User::factory()->create());
        $theirs = $this->placeOrder(User::factory()->create());

        $this->actingAs($me)->get(route('orders.index'))->assertSee($mine->code)->assertDontSee($theirs->code);
        $this->actingAs($me)->get(route('orders.show', $theirs))->assertNotFound();
    }

    public function test_empty_history_shows_a_friendly_message(): void
    {
        $this->actingAs(User::factory()->create())->get(route('orders.index'))
            ->assertOk()->assertSee('No orders yet.');
    }

    public function test_detail_shows_items_address_and_tracker(): void
    {
        $customer = User::factory()->create();
        $checkout = $this->placeOrder($customer);

        $this->actingAs($customer)->get(route('orders.show', $checkout))
            ->assertOk()
            ->assertSee('Niacinamide 10% Serum 30ml')
            ->assertSee('Jl. Melati No. 12, Jakarta')
            ->assertSee('Order placed')
            ->assertSee('confirmation sent')
            ->assertDontSee('Send payment details');
    }

    public function test_status_change_by_admin_shows_for_the_customer(): void
    {
        $customer = User::factory()->create();
        $checkout = $this->placeOrder($customer);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->patch(route('dashboard.orders.update', $checkout), ['status' => 'shipped']);

        $this->actingAs($customer)->get(route('orders.index'))->assertSee('Shipped');
        $this->actingAs($customer)->get(route('orders.show', $checkout))
            ->assertSee('aria-current="step"', false)
            ->assertSeeInOrder(['Processing', 'aria-current="step"', 'Shipped'], false);
    }

    public function test_unpaid_order_offers_the_payment_form_again(): void
    {
        $customer = User::factory()->create();
        $checkout = $this->placeOrder($customer, sendPayment: false);

        $this->actingAs($customer)->get(route('orders.show', $checkout))
            ->assertSee('Send payment details')
            ->assertSee(route('checkout.payment', $checkout), false);
    }

    public function test_navbar_and_thank_you_page_link_to_my_orders(): void
    {
        $customer = User::factory()->create();
        $checkout = $this->placeOrder($customer);

        $this->actingAs($customer)->get('/products')->assertSee(route('orders.index'), false);
        $this->actingAs($customer)->get(route('checkout.done', $checkout))
            ->assertSee('Track this order')
            ->assertSee(route('orders.show', $checkout), false);
    }

    public function test_guests_must_log_in(): void
    {
        $this->get(route('orders.index'))->assertRedirect('/login');
    }
}
