<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $serums = ProductCategory::create(['name' => 'Serums']);
        $cleansers = ProductCategory::create(['name' => 'Cleansers']);

        Product::create(['category_id' => $serums->id, 'name' => 'Niacinamide 10% Serum 30ml', 'description' => 'Minimizes pores.', 'price' => 159000, 'stock' => 5]);
        Product::create(['category_id' => $serums->id, 'name' => 'Vitamin C Bright Serum 30ml', 'description' => 'Brighter skin.', 'price' => 169000, 'stock' => 5]);
        Product::create(['category_id' => $cleansers->id, 'name' => 'Charcoal Deep Face Wash 100ml', 'description' => 'Lifts oil and dirt.', 'price' => 89000, 'stock' => 5]);
    }

    public function test_search_box_is_on_the_shop_page(): void
    {
        $this->get('/products')->assertOk()->assertSee('Search products...');
    }

    public function test_product_page_links_back_to_all_products(): void
    {
        $product = Product::first();

        $this->get(route('products.show', $product))->assertOk()
            ->assertSee('All products')
            ->assertSee('href="' . route('products.index') . '" class="back-link"', false);
    }

    public function test_search_matches_product_names(): void
    {
        $this->get('/products?q=charcoal')->assertOk()
            ->assertSee('Charcoal Deep Face Wash 100ml')
            ->assertDontSee('Niacinamide 10% Serum 30ml')
            ->assertSee('1 result');
    }

    public function test_search_matches_descriptions_and_categories(): void
    {
        $this->get('/products?q=pores')->assertSee('Niacinamide 10% Serum 30ml')->assertDontSee('Vitamin C Bright Serum 30ml');

        $this->get('/products?q=cleansers')->assertSee('Charcoal Deep Face Wash 100ml')->assertDontSee('Niacinamide 10% Serum 30ml');
    }

    public function test_search_works_together_with_category_and_sort(): void
    {
        $cleansers = ProductCategory::where('name', 'Cleansers')->first();

        $this->get("/products?q=serum&category={$cleansers->id}")->assertSee('Nothing matches');

        $this->get('/products?q=serum&sort=price_desc')
            ->assertSeeInOrder(['Vitamin C Bright Serum 30ml', 'Niacinamide 10% Serum 30ml'])
            ->assertSee('name="sort" value="price_desc"', false);
    }

    public function test_no_results_shows_a_friendly_message(): void
    {
        $this->get('/products?q=shampoo')->assertOk()
            ->assertSee('Nothing matches')
            ->assertSee('see all products');
    }

    public function test_percent_sign_is_searched_as_text(): void
    {
        $this->get('/products?q=' . urlencode('10%'))
            ->assertSee('Niacinamide 10% Serum 30ml')
            ->assertDontSee('Charcoal Deep Face Wash 100ml');
    }
}
