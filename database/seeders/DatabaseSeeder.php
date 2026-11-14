<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Public demo accounts (config/shop.php 'demo'), shown on the login page
        foreach (config('shop.demo.accounts') as $email => $account) {
            $user = User::firstOrNew(['email' => $email]);
            $user->forceFill(['name' => $account['name'], 'password' => config('shop.demo.password'), 'role' => $account['role']])->save();
        }

        // Your own System Admin: its password stays out of the code.
        // Put ADMIN_EMAIL and ADMIN_PASSWORD in .env before running "php artisan db:seed".
        if (env('ADMIN_EMAIL') && env('ADMIN_PASSWORD')) {
            $admin = User::firstOrNew(['email' => env('ADMIN_EMAIL')]);
            $admin->forceFill(['name' => $admin->name ?? 'System Admin', 'password' => env('ADMIN_PASSWORD'), 'role' => 'system_admin'])->save();
        }

        // Order matters: categories, then products (need category_id),
        // then dashboard data (clicks & orders need products), then reviews (need orders)
        $this->call([
            CategorySeeder::class,
            ProductSeeder::class,
            DashboardSeeder::class,
            ReviewSeeder::class,
        ]);
    }
}
