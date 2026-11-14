<?php

namespace Tests\Feature;

use App\Models\Checkout;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrdersTest extends TestCase
{
    use RefreshDatabase;

    // A customer places an order (and optionally sends the payment form), like in the real shop
    private function placeOrder(string $name = 'Budi Santoso', bool $sendPayment = true): Checkout
    {
        $category = ProductCategory::firstOrCreate(['name' => 'Serums']);
        $product = Product::create([
            'category_id' => $category->id, 'name' => 'Niacinamide 10% Serum 30ml',
            'description' => 'Test', 'price' => 159000, 'stock' => 10,
        ]);
        $customer = User::factory()->create();

        $this->actingAs($customer)->withSession(['cart' => [$product->id => 2]])->post(route('checkout.store'), [
            'recipient_name' => $name, 'phone' => '081234567890', 'email' => strtolower(strtok($name, ' ')) . '@example.com',
            'address' => 'Jl. Melati No. 12, Jakarta', 'note' => 'Ring the bell',
        ]);
        $checkout = Checkout::latest('id')->firstOrFail();

        if ($sendPayment) {
            $this->post(route('checkout.payment.send', $checkout), ['message' => "Transfer done for {$checkout->code}"]);
        }

        return $checkout->fresh();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_admin_sees_the_orders_list(): void
    {
        $checkout = $this->placeOrder();

        $this->actingAs($this->admin())->get(route('dashboard.orders.index'))
            ->assertOk()
            ->assertSee($checkout->code)
            ->assertSee('Budi Santoso')
            ->assertSee('Rp 318,000')
            ->assertSee('Confirmation sent')
            ->assertSee('Pending');
    }

    public function test_orders_without_payment_show_waiting(): void
    {
        $this->placeOrder('Sari Dewi', sendPayment: false);

        $this->actingAs($this->admin())->get(route('dashboard.orders.index'))->assertSee('Waiting');
    }

    public function test_detail_page_shows_items_address_and_payment_message(): void
    {
        $checkout = $this->placeOrder();

        $this->actingAs($this->admin())->get(route('dashboard.orders.show', $checkout))
            ->assertOk()
            ->assertSee('Niacinamide 10% Serum 30ml')
            ->assertSee('Jl. Melati No. 12, Jakarta')
            ->assertSee('081234567890')
            ->assertSee('Ring the bell')
            ->assertSee("Transfer done for {$checkout->code}");
    }

    public function test_admin_can_move_the_order_along(): void
    {
        $checkout = $this->placeOrder();
        $admin = $this->admin();

        foreach (['processing', 'shipped', 'delivered'] as $status) {
            $this->actingAs($admin)->from(route('dashboard.orders.show', $checkout))
                ->patch(route('dashboard.orders.update', $checkout), ['status' => $status])
                ->assertRedirect(route('dashboard.orders.show', $checkout))
                ->assertSessionHas('success');

            $this->assertSame($status, $checkout->fresh()->status());
        }

        // Every product row of the order has the new status (the dashboard charts read these)
        $this->assertSame(['delivered'], $checkout->orders()->pluck('status')->unique()->values()->all());
    }

    public function test_unknown_status_is_refused(): void
    {
        $checkout = $this->placeOrder();

        $this->actingAs($this->admin())->patch(route('dashboard.orders.update', $checkout), ['status' => 'lost'])
            ->assertSessionHasErrors('status');
        $this->assertSame('pending', $checkout->fresh()->status());
    }

    public function test_filter_by_status_and_search(): void
    {
        $budi = $this->placeOrder('Budi Santoso');
        $sari = $this->placeOrder('Sari Dewi');
        $sari->setStatus('shipped');
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('dashboard.orders.index', ['status' => 'shipped']))
            ->assertSee($sari->code)->assertDontSee($budi->code);

        $this->actingAs($admin)->get(route('dashboard.orders.index', ['q' => 'budi']))
            ->assertSee($budi->code)->assertDontSee($sari->code);

        $this->actingAs($admin)->get(route('dashboard.orders.index', ['q' => $sari->code]))
            ->assertSee($sari->code)->assertDontSee($budi->code);
    }

    public function test_orders_menu_shows_for_admins_with_pending_count(): void
    {
        $this->placeOrder();

        $this->actingAs($this->admin())->get(route('dashboard'))
            ->assertSee(route('dashboard.orders.index'))
            ->assertSee('All orders');
        $this->actingAs(User::factory()->create(['role' => 'system_admin']))->get(route('dashboard.orders.index'))->assertOk();
    }

    public function test_customers_and_guests_cannot_open_orders(): void
    {
        $checkout = $this->placeOrder();
        $customer = User::factory()->create(['role' => 'user']);

        $this->actingAs($customer)->get(route('dashboard.orders.index'))->assertForbidden();
        $this->actingAs($customer)->get(route('dashboard.orders.show', $checkout))->assertForbidden();
        $this->actingAs($customer)->patch(route('dashboard.orders.update', $checkout), ['status' => 'delivered'])->assertForbidden();
        $this->assertSame('pending', $checkout->fresh()->status());

        auth()->logout();
        $this->get(route('dashboard.orders.index'))->assertRedirect('/login');
    }
}
