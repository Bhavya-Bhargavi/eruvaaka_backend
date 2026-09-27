-- Non-destructive database upgrade for Eruvaaka payment integration
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

SET @column_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'users'
      AND column_name = 'subscription_status'
);
SET @sql := IF(
    @column_exists = 0,
    'ALTER TABLE `users` ADD COLUMN `subscription_status` varchar(255) NOT NULL DEFAULT ''expired'' AFTER `is_active`',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS `payment_orders` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `user_id` int unsigned NOT NULL,
    `razorpay_order_id` varchar(100) NOT NULL,
    `razorpay_payment_id` varchar(100) DEFAULT NULL,
    `amount` bigint unsigned NOT NULL,
    `currency` varchar(3) NOT NULL DEFAULT 'INR',
    `receipt` varchar(100) NOT NULL,
    `plan_type` varchar(255) DEFAULT NULL,
    `signature` varchar(255) DEFAULT NULL,
    `status` varchar(50) NOT NULL DEFAULT 'created',
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `payment_orders_razorpay_order_id_unique` (`razorpay_order_id`),
    UNIQUE KEY `payment_orders_receipt_unique` (`receipt`),
    UNIQUE KEY `payment_orders_razorpay_payment_id_unique` (`razorpay_payment_id`),
    KEY `payment_orders_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @column_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'payment_orders'
      AND column_name = 'plan_type'
);
SET @sql := IF(
    @column_exists = 0,
    'ALTER TABLE `payment_orders` ADD COLUMN `plan_type` varchar(255) DEFAULT NULL AFTER `receipt`',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS `subscriptions` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `user_id` int unsigned NOT NULL,
    `start_date` datetime NOT NULL,
    `end_date` datetime NOT NULL,
    `plan_type` varchar(255) NOT NULL,
    `status` varchar(255) NOT NULL DEFAULT 'active',
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `subscriptions_user_status_end_index` (`user_id`, `status`, `end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `contact_messages` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(120) NOT NULL,
    `phone` varchar(20) NOT NULL,
    `email` varchar(255) DEFAULT NULL,
    `subject` varchar(200) NOT NULL,
    `message` text NOT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_09_05_000001_add_payment_subscription_fields', COALESCE(MAX(`batch`), 0) + 1
FROM `migrations`
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations`
    WHERE `migration` = '2026_09_05_000001_add_payment_subscription_fields'
);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_09_27_000001_create_contact_messages_table', COALESCE(MAX(`batch`), 0) + 1
FROM `migrations`
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations`
    WHERE `migration` = '2026_09_27_000001_create_contact_messages_table'
);

SET FOREIGN_KEY_CHECKS=1;
