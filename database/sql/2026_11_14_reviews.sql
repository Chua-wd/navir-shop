-- Product reviews (14 Nov 2026), for hosting without "php artisan migrate" (InfinityFree).
-- Run once in phpMyAdmin: pick the database -> SQL tab -> paste everything -> Go.
-- Same as database/migrations/2026_11_14_000001_create_reviews_table.php.
-- Sample reviews: export the "reviews" table from your local phpMyAdmin and import it after this.

CREATE TABLE `reviews` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `product_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `rating` tinyint unsigned NOT NULL,
  `comment` text NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  UNIQUE KEY `reviews_product_id_user_id_unique` (`product_id`, `user_id`),
  CONSTRAINT `reviews_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Tell Laravel this migration has run
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_14_000001_create_reviews_table', COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`;
