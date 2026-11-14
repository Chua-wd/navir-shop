<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;

class PageController extends Controller
{
    // Home page: categories and the 3 newest products (the hero card links to the routine quiz)
    public function home()
    {
        $categories = ProductCategory::withCount('products')->orderBy('name')->get();

        $products = Product::with('category')->withRating()->latest('id')->take(3)->get();

        return view('pages.home', compact('categories', 'products'));
    }

    public function about()
    {
        return view('pages.about');
    }

    public function contact()
    {
        return view('pages.contact');
    }
}
