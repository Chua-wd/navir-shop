<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * "Find my routine" quiz: 4 questions -> a 3-step routine (Cleanse, Treat, Protect)
 * built from the shop's own products, plus optional extras.
 * Simple rules on purpose: easy to read, test and explain. No AI, no photos.
 */
class RoutineFinder
{
    // The questions and their answers (value => label). Used by the page and for validation.
    public const QUESTIONS = [
        'skin' => [
            'title' => 'How does your skin feel a few hours after washing?',
            'options' => [
                'oily' => ['Shiny all over', 'Oily'],
                'combination' => ['Shiny forehead and nose, normal cheeks', 'Combination'],
                'dry' => ['Tight or flaky', 'Dry'],
                'sensitive' => ['Red, itchy or stings easily', 'Sensitive'],
            ],
        ],
        'concern' => [
            'title' => 'What would you most like to fix?',
            'options' => [
                'breakouts' => ['Breakouts and big pores', 'Breakouts'],
                'dull' => ['Dull or uneven skin tone', 'Dull skin'],
                'eyes' => ['Tired eyes and puffiness', 'Tired eyes'],
                'razor' => ['Razor bumps and redness', 'Razor bumps'],
                'basics' => ['Nothing special, just the basics', 'Just the basics'],
            ],
        ],
        'sun' => [
            'title' => 'How long are you outside on a normal day?',
            'options' => [
                'low' => ['Less than 1 hour', 'Mostly indoors'],
                'medium' => ['1 to 3 hours', '1–3 h outside'],
                'high' => ['More than 3 hours, or sports', 'Lots of sun'],
            ],
        ],
        'hair' => [
            'title' => 'Shave or beard?',
            'options' => [
                'shave' => ['I shave', 'Shaves'],
                'beard' => ['I keep a beard', 'Beard'],
                'none' => ['Neither', 'No shaving'],
            ],
        ],
    ];

    // Preferred product (by name) and the category to fall back to if it was renamed or deleted
    private const PRODUCTS = [
        'gel_cleanser' => ['Oil Control Gel Cleanser 150ml', 'Cleansers'],
        'charcoal_wash' => ['Charcoal Deep Face Wash 100ml', 'Cleansers'],
        'niacinamide' => ['Niacinamide 10% Serum 30ml', 'Serums'],
        'vitamin_c' => ['Vitamin C Bright Serum 30ml', 'Serums'],
        'eye_serum' => ['Cooling Under-Eye Serum 15ml', 'Serums'],
        'matte_moisturizer' => ['Matte Finish Moisturizer 50ml', 'Moisturizers'],
        'hydra_moisturizer' => ['Daily Hydra Moisturizer 50ml', 'Moisturizers'],
        'sunscreen' => ['Sport Sunscreen SPF 50 50ml', 'Sunscreen'],
        'beard_oil' => ['Cedar Beard Oil 30ml', 'Shave & Beard'],
        'shaving_foam' => ['Sensitive Shaving Foam 200ml', 'Shave & Beard'],
        'toner' => ['Hyaluronic Acid Barrier Essence Toner', 'Toners'],
    ];

    private Collection $catalog;

    /**
     * Valid answers only (anything unknown is dropped). Complete = all 4 questions answered.
     *
     * @return array<string, string>
     */
    public static function answers(array $input): array
    {
        return collect(self::QUESTIONS)
            ->map(fn ($question, $key) => array_key_exists($input[$key] ?? '', $question['options']) ? $input[$key] : null)
            ->filter()
            ->all();
    }

    public static function isComplete(array $answers): bool
    {
        return count($answers) === count(self::QUESTIONS);
    }

    /**
     * @return array{summary: string, steps: array, extras: array, products: Collection, total: int}
     */
    public function recommend(array $answers): array
    {
        ['skin' => $skin, 'concern' => $concern, 'sun' => $sun, 'hair' => $hair] = $answers;
        $this->catalog = Product::with('category')->get();

        // Step 1: Cleanse. Salicylic gel for oily/breakout-prone skin, but never for sensitive skin.
        $cleanser = ! in_array($skin, ['sensitive', 'dry']) && ($skin === 'oily' || $concern === 'breakouts')
            ? $this->item('gel_cleanser', 'Salicylic acid clears extra oil and keeps pores from clogging.')
            : $this->item('charcoal_wash', 'Lifts dirt and oil without leaving your skin tight or dry.');

        // Step 2: Treat, based on the main concern
        $serum = match ($concern) {
            'dull' => $this->item('vitamin_c', 'Vitamin C evens out your tone for brighter-looking skin.'),
            'eyes' => $this->item('eye_serum', 'Caffeine and a cooling tip to reduce puffiness and tired-looking eyes.'),
            'razor' => $this->item('niacinamide', 'Niacinamide calms redness and irritation after shaving.'),
            'breakouts' => $this->item('niacinamide', 'Niacinamide reduces breakouts, shrinks the look of pores and controls oil.'),
            default => $this->item('niacinamide', 'One all-round serum: smoother skin, smaller-looking pores, less shine.'),
        };

        // Step 3: Protect. Moisturizer for the skin type + sunscreen (every day, outdoors or not).
        $moisturizer = $skin === 'oily'
            ? $this->item('matte_moisturizer', 'Hydrates without the shine. Matte finish all day.')
            : $this->item('hydra_moisturizer', 'Light hyaluronic acid hydration that never feels sticky.');
        $sunscreen = $this->item('sunscreen', match ($sun) {
            'high' => 'SPF 50, sweat-resistant. Outdoors a lot: put more on every 2 hours.',
            'medium' => 'SPF 50 every morning stops sun damage and dark spots.',
            default => 'Even indoors, daylight through windows ages skin. One layer each morning is enough.',
        });

        $steps = [
            ['title' => 'Cleanse', 'when' => 'Morning and night', 'items' => array_filter([$cleanser])],
            ['title' => 'Treat', 'when' => $concern === 'dull' ? 'Morning' : 'Morning and night', 'items' => array_filter([$serum])],
            ['title' => 'Protect', 'when' => 'Moisturizer morning and night, sunscreen in the morning', 'items' => array_filter([$moisturizer, $sunscreen])],
        ];

        // Optional extras for shaving, beards and very dry or sensitive skin
        $extras = array_values(array_filter([
            $hair === 'shave' ? $this->item('shaving_foam', $concern === 'razor'
                ? 'Aloe vera foam for a smooth shave: the best way to prevent razor bumps.'
                : 'Aloe vera foam for a smooth shave without irritation.') : null,
            $hair === 'beard' ? $this->item('beard_oil', 'Softens your beard and stops the skin underneath from itching.') : null,
            in_array($skin, ['dry', 'sensitive']) ? $this->item('toner', 'Extra hydration before your moisturizer.') : null,
        ]));

        // Every product once (the "Add the whole routine to cart" button and the total)
        $products = collect($steps)->flatMap(fn ($step) => $step['items'])->pluck('product')->unique('id')->values();

        $labels = collect($answers)->map(fn ($value, $key) => self::QUESTIONS[$key]['options'][$value][1]);

        return [
            'summary' => $labels->implode(' · '),
            'steps' => $steps,
            'extras' => $extras,
            'products' => $products,
            'total' => (int) $products->sum('price'),
        ];
    }

    // Find the product (or a stand-in from the same category); null when the shop has none
    private function item(string $key, string $why): ?array
    {
        [$name, $category] = self::PRODUCTS[$key];

        $product = $this->catalog->firstWhere('name', $name)
            ?? $this->catalog->first(fn ($p) => $p->category?->name === $category && $p->stock > 0);

        return $product ? ['product' => $product, 'why' => $why] : null;
    }
}
