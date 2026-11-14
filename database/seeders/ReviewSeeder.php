<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Review;
use Illuminate\Database\Seeder;

/**
 * Sample reviews so the shop doesn't look empty. Only "verified buyers" review:
 * each review comes from a customer who has an order for that product.
 * Run after DashboardSeeder (it creates the customers and orders).
 */
class ReviewSeeder extends Seeder
{
    // [stars, comment] - mostly positive, a few honest middle ones
    private const SAMPLES = [
        [5, 'Light, absorbs fast and no greasy feeling. My face looks less shiny by noon.'],
        [5, 'Been using it for three weeks. Skin feels smoother and the smell is subtle.'],
        [5, 'Finally something that works in Jakarta heat. Will buy again.'],
        [4, 'Good product, does what it says. A bit pricey but lasts long.'],
        [4, 'Works well for my oily skin. Packaging could be easier to open.'],
        [4, 'Noticed a difference after about two weeks. Gentle on my skin.'],
        [3, 'It is okay. Not bad, but I did not see a big change yet.'],
        [5, null],
        [4, null],
    ];

    public function run(): void
    {
        if (Review::exists()) {
            $this->command?->warn('Reviews already exist - ReviewSeeder skipped.');

            return;
        }

        mt_srand(2027); // same "random" reviews every time

        // One possible review per customer+product they ordered (returned orders don't count)
        $pairs = Order::where('status', '!=', 'returned')
            ->select('user_id', 'product_id')->distinct()->get()
            ->shuffle(2027);

        $perProduct = [];
        foreach ($pairs as $pair) {
            // 2 to 6 reviews per product is enough
            $limit = $perProduct[$pair->product_id]['limit'] ??= mt_rand(2, 6);
            $done = $perProduct[$pair->product_id]['done'] ?? 0;
            if ($done >= $limit) {
                continue;
            }

            [$rating, $comment] = self::SAMPLES[mt_rand(0, count(self::SAMPLES) - 1)];
            Review::create([
                'product_id' => $pair->product_id,
                'user_id' => $pair->user_id,
                'rating' => $rating,
                'comment' => $comment,
                'created_at' => now()->subDays(mt_rand(0, 60)),
            ]);
            $perProduct[$pair->product_id]['done'] = $done + 1;
        }
    }
}
