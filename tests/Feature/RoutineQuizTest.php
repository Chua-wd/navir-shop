<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use Database\Seeders\CategorySeeder;
use Database\Seeders\ProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoutineQuizTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The real product range (the quiz picks products by name)
        $this->seed([CategorySeeder::class, ProductSeeder::class]);
    }

    private function quiz(array $answers)
    {
        return $this->get(route('routine.show', $answers));
    }

    public function test_quiz_page_shows_four_questions(): void
    {
        $this->get('/routine')->assertOk()
            ->assertSee('Find my routine')
            ->assertSee('How does your skin feel a few hours after washing?')
            ->assertSee('What would you most like to fix?')
            ->assertSee('How long are you outside on a normal day?')
            ->assertSee('Shave or beard?')
            ->assertSee('Show my routine');
    }

    public function test_quiz_is_in_the_menu_for_everyone(): void
    {
        $this->get('/products')->assertSee('href="' . route('routine.show') . '"', false)->assertSee('Find my routine');
    }

    public function test_oily_skin_with_breakouts_gets_the_oil_control_routine(): void
    {
        $this->quiz(['skin' => 'oily', 'concern' => 'breakouts', 'sun' => 'high', 'hair' => 'shave'])
            ->assertOk()
            ->assertSeeInOrder(['Cleanse', 'Oil Control Gel Cleanser 150ml', 'Treat', 'Niacinamide 10% Serum 30ml',
                'Protect', 'Matte Finish Moisturizer 50ml', 'Sport Sunscreen SPF 50 50ml'])
            ->assertSee('every 2 hours')
            ->assertSee('Sensitive Shaving Foam 200ml')   // extra for shavers
            ->assertDontSee('Cedar Beard Oil 30ml')
            ->assertSee('Rp ' . number_format(99000 + 159000 + 139000 + 109000)); // total of steps 1-3
    }

    public function test_sensitive_skin_never_gets_the_salicylic_cleanser(): void
    {
        $this->quiz(['skin' => 'sensitive', 'concern' => 'breakouts', 'sun' => 'low', 'hair' => 'beard'])
            ->assertSee('Charcoal Deep Face Wash 100ml')
            ->assertDontSee('Oil Control Gel Cleanser 150ml')
            ->assertSee('Daily Hydra Moisturizer 50ml')
            ->assertSee('Cedar Beard Oil 30ml');
    }

    public function test_concern_picks_the_serum(): void
    {
        $this->quiz(['skin' => 'dry', 'concern' => 'dull', 'sun' => 'medium', 'hair' => 'none'])->assertSee('Vitamin C Bright Serum 30ml');
        $this->quiz(['skin' => 'combination', 'concern' => 'eyes', 'sun' => 'low', 'hair' => 'none'])->assertSee('Cooling Under-Eye Serum 15ml');
    }

    public function test_incomplete_or_made_up_answers_show_the_questions_again(): void
    {
        $this->quiz(['skin' => 'oily'])->assertSee('Please answer')->assertDontSee('Add the whole routine to cart');
        $this->quiz(['skin' => 'alien', 'concern' => 'breakouts', 'sun' => 'low', 'hair' => 'none'])
            ->assertSee('Please answer')->assertDontSee('Add the whole routine to cart');
    }

    public function test_whole_routine_goes_into_the_cart(): void
    {
        $this->post(route('routine.cart'), ['skin' => 'oily', 'concern' => 'breakouts', 'sun' => 'high', 'hair' => 'shave'])
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('success', '4 products from your routine added to the cart.');

        $ids = Product::whereIn('name', ['Oil Control Gel Cleanser 150ml', 'Niacinamide 10% Serum 30ml', 'Matte Finish Moisturizer 50ml', 'Sport Sunscreen SPF 50 50ml'])->pluck('id');
        $this->assertEqualsCanonicalizing($ids->all(), array_keys(session('cart')));
    }

    public function test_out_of_stock_products_are_skipped_when_adding_the_routine(): void
    {
        Product::where('name', 'Sport Sunscreen SPF 50 50ml')->update(['stock' => 0]);

        $this->post(route('routine.cart'), ['skin' => 'oily', 'concern' => 'breakouts', 'sun' => 'high', 'hair' => 'none'])
            ->assertSessionHas('success', '3 products from your routine added to the cart.');
    }

    public function test_renamed_product_is_replaced_by_one_from_the_same_category(): void
    {
        Product::where('name', 'Daily Hydra Moisturizer 50ml')->update(['name' => 'Hydra Gel Cream 50ml']);

        $this->quiz(['skin' => 'dry', 'concern' => 'basics', 'sun' => 'low', 'hair' => 'none'])
            ->assertOk()
            ->assertSee('Protect')
            ->assertSeeInOrder(['Protect', 'Moisturizer']);
    }

    public function test_cart_page_confirms_the_routine_was_added(): void
    {
        $this->followingRedirects()
            ->post(route('routine.cart'), ['skin' => 'dry', 'concern' => 'basics', 'sun' => 'low', 'hair' => 'none'])
            ->assertSee('from your routine added to the cart');
    }
}
