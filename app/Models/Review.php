<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A customer's review of a product: rating 1-5 and an optional comment.
 */
class Review extends Model
{
    protected $fillable = ['product_id', 'user_id', 'rating', 'comment'];

    protected function casts(): array
    {
        return ['rating' => 'integer'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // "Budi Santoso" -> "Budi S." (reviews are public, so no full names)
    public function authorName(): string
    {
        $parts = preg_split('/\s+/', trim($this->user?->name ?? 'Customer'));
        $first = $parts[0];
        $last = count($parts) > 1 ? ' ' . mb_strtoupper(mb_substr(end($parts), 0, 1)) . '.' : '';

        return $first . $last;
    }
}
