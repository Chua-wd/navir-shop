<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    // Orders this person placed at checkout ("My orders")
    public function checkouts(): HasMany
    {
        return $this->hasMany(Checkout::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    // "Verified buyer": only people who ordered a product may review it (returned orders don't count)
    public function hasBought(Product $product): bool
    {
        return Order::where('user_id', $this->id)
            ->where('product_id', $product->id)
            ->where('status', '!=', 'returned')
            ->exists();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Roles (users.role) and what each one can open:
     *   user         -> shop, cart, checkout, profile
     *   admin        -> everything a user can + dashboard, orders, products, product categories
     *   system_admin -> everything an admin can + user management
     */
    public const ROLES = [
        'user' => 'User',
        'admin' => 'Admin',
        'system_admin' => 'System Admin',
    ];

    // Admins and system admins can open the dashboard
    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'system_admin'], true);
    }

    // Only system admins can open User management
    public function isSystemAdmin(): bool
    {
        return $this->role === 'system_admin';
    }

    public function roleLabel(): string
    {
        return self::ROLES[$this->role] ?? ucfirst((string) $this->role);
    }

    // Public demo account (config/shop.php 'demo'): may look around, can't change things
    public function isDemo(): bool
    {
        return array_key_exists(strtolower((string) $this->email), config('shop.demo.accounts', []));
    }
}
