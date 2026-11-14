<?php

namespace App\Http\Controllers;

use App\Support\Cart;
use App\Support\RoutineFinder;
use Illuminate\Http\Request;

/**
 * "Find my routine" quiz (anyone, no login).
 *   GET  /routine                          the 4 questions
 *   GET  /routine?skin=..&concern=..&...   the recommended routine (shareable link)
 *   POST /routine/cart                     add the whole routine to the cart
 */
class RoutineController extends Controller
{
    public function show(Request $request)
    {
        $answers = RoutineFinder::answers($request->query());
        $complete = RoutineFinder::isComplete($answers);

        return view('routine', [
            'questions' => RoutineFinder::QUESTIONS,
            'answers' => $answers,
            // Submitted but something is missing -> tell them which question
            'missing' => $request->hasAny(array_keys(RoutineFinder::QUESTIONS)) && ! $complete
                ? array_diff_key(RoutineFinder::QUESTIONS, $answers)
                : [],
            'routine' => $complete ? (new RoutineFinder)->recommend($answers) : null,
        ]);
    }

    public function addToCart(Request $request)
    {
        $answers = RoutineFinder::answers($request->all());
        if (! RoutineFinder::isComplete($answers)) {
            return redirect()->route('routine.show');
        }

        // The server works the routine out again: the browser can't slip other products in
        $routine = (new RoutineFinder)->recommend($answers);
        $added = $routine['products']->filter(fn ($product) => $product->stock > 0)
            ->each(fn ($product) => Cart::add($product, 1));

        return redirect()->route('cart.index')
            ->with('success', $added->count() . ' ' . str('product')->plural($added->count()) . ' from your routine added to the cart.');
    }
}
