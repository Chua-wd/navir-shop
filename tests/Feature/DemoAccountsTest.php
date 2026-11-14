<?php

namespace Tests\Feature;

use App\Models\Checkout;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DemoAccountsTest extends TestCase
{
    use RefreshDatabase;

    private function demo(string $role): User
    {
        $email = $role === 'admin' ? 'demo.admin@navir.test' : 'demo.user@navir.test';
        $user = User::factory()->create(['email' => $email, 'password' => config('shop.demo.password')]);
        $user->forceFill(['role' => $role])->save();

        return $user;
    }

    private function product(): Product
    {
        $category = ProductCategory::firstOrCreate(['name' => 'Serums']);

        return Product::create(['category_id' => $category->id, 'name' => 'Niacinamide 10% Serum 30ml', 'description' => 'Test', 'price' => 159000, 'stock' => 10]);
    }

    public function test_login_page_shows_demo_accounts_and_they_can_log_in(): void
    {
        $this->demo('admin');

        $this->get('/login')->assertOk()
            ->assertSee('try the demo')
            ->assertSee('demo.admin@navir.test')
            ->assertSee('demo.user@navir.test')
            ->assertSee(config('shop.demo.password'));

        $this->post('/login', ['email' => 'demo.admin@navir.test', 'password' => config('shop.demo.password')])
            ->assertRedirect('/dashboard');
    }

    public function test_demo_admin_can_view_but_not_change_products_or_categories(): void
    {
        $demo = $this->demo('admin');
        $product = $this->product();

        $this->actingAs($demo)->get('/dashboard')->assertOk()->assertSee('Demo account.');
        $this->actingAs($demo)->get(route('dashboard.products.edit', $product))->assertOk();

        $this->actingAs($demo)->from(route('dashboard.products.index'))
            ->delete(route('dashboard.products.destroy', $product))
            ->assertRedirect(route('dashboard.products.index'))
            ->assertSessionHas('demo_blocked');
        $this->assertModelExists($product);

        $this->actingAs($demo)->put(route('dashboard.products.update', $product), ['name' => 'Hacked', 'price' => 1, 'stock' => 1, 'category_id' => $product->category_id]);
        $this->assertSame('Niacinamide 10% Serum 30ml', $product->fresh()->name);

        $this->actingAs($demo)->post(route('product-category.store'), ['name' => 'Spam']);
        $this->assertDatabaseMissing('categories', ['name' => 'Spam']);
    }

    public function test_blocked_save_shows_a_friendly_message(): void
    {
        $demo = $this->demo('admin');
        $product = $this->product();

        $this->actingAs($demo)->followingRedirects()->from(route('dashboard.products.index'))
            ->delete(route('dashboard.products.destroy', $product))
            ->assertSee('saving changes here is turned off');
    }

    public function test_demo_admin_can_still_move_an_order_along(): void
    {
        $product = $this->product();
        $customer = User::factory()->create();
        $this->actingAs($customer)->withSession(['cart' => [$product->id => 1]])->post(route('checkout.store'), [
            'recipient_name' => 'Budi', 'phone' => '0812', 'email' => 'budi@example.com', 'address' => 'Jakarta',
        ]);
        $checkout = Checkout::firstOrFail();

        $this->actingAs($this->demo('admin'))->patch(route('dashboard.orders.update', $checkout), ['status' => 'shipped'])
            ->assertSessionHas('success');
        $this->assertSame('shipped', $checkout->fresh()->status());
    }

    public function test_demo_accounts_cannot_change_their_profile_password_or_delete_themselves(): void
    {
        $demo = $this->demo('user');

        $this->actingAs($demo)->get('/profile')->assertOk()->assertSee('Demo account.');

        $this->actingAs($demo)->patch('/profile', ['name' => 'Hacker', 'email' => 'hacker@example.com'])->assertSessionHas('demo_blocked');
        $this->actingAs($demo)->put('/password', [
            'current_password' => config('shop.demo.password'), 'password' => 'new-password-123', 'password_confirmation' => 'new-password-123',
        ])->assertSessionHas('demo_blocked');
        $this->actingAs($demo)->delete('/profile', ['password' => config('shop.demo.password')])->assertSessionHas('demo_blocked');

        $demo->refresh();
        $this->assertSame('demo.user@navir.test', $demo->email);
        $this->assertTrue(Hash::check(config('shop.demo.password'), $demo->password));
    }

    public function test_demo_user_can_still_shop_and_place_an_order(): void
    {
        $product = $this->product();

        $this->actingAs($this->demo('user'))->withSession(['cart' => [$product->id => 1]])
            ->post(route('checkout.store'), ['recipient_name' => 'Demo', 'phone' => '0812', 'email' => 'demo@example.com', 'address' => 'Jakarta'])
            ->assertRedirect();

        $this->assertSame(1, Checkout::count());
    }

    public function test_normal_accounts_are_not_limited(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->product();

        $this->actingAs($admin)->get('/dashboard')->assertDontSee('Demo account.');
        $this->actingAs($admin)->delete(route('dashboard.products.destroy', $product))->assertSessionMissing('demo_blocked');
        $this->assertModelMissing($product);
    }

    public function test_seeder_creates_demo_accounts_without_a_public_system_admin(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $this->assertSame('admin', User::where('email', 'demo.admin@navir.test')->value('role'));
        $this->assertSame('user', User::where('email', 'demo.user@navir.test')->value('role'));
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
        $this->assertSame(0, User::where('role', 'system_admin')->count());
    }
}
