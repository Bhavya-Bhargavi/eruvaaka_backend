-- Non-destructive database upgrade for Eruvaaka payments and content APIs
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

CREATE TABLE IF NOT EXISTS `books` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `slug` varchar(255) NOT NULL,
    `title` varchar(255) NOT NULL,
    `author` varchar(255) DEFAULT NULL,
    `description` text DEFAULT NULL,
    `content` longtext NOT NULL,
    `cover_image` varchar(255) DEFAULT NULL,
    `is_published` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `books_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `forum_discussions` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `slug` varchar(255) NOT NULL,
    `title` varchar(255) NOT NULL,
    `body` longtext NOT NULL,
    `is_published` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `forum_discussions_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `forum_discussions` (`slug`, `title`, `body`, `is_published`, `created_at`, `updated_at`)
VALUES
    ('discussion-1', 'Discussion1', 'Share your thoughts and participate in Discussion1. Please keep comments relevant and respectful.', 1, NOW(), NOW()),
    ('discussion-2', 'Discussion2', 'Share your thoughts and participate in Discussion2. Please keep comments relevant and respectful.', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    `title` = VALUES(`title`),
    `body` = VALUES(`body`),
    `is_published` = VALUES(`is_published`),
    `updated_at` = NOW();

SET @users_id_type := (
    SELECT `COLUMN_TYPE`
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'users'
      AND column_name = 'id'
    LIMIT 1
);
SET @forum_comments_user_id_type := COALESCE(@users_id_type, 'bigint unsigned');
SET @sql := CONCAT(
    'CREATE TABLE IF NOT EXISTS `forum_comments` (',
    '`id` bigint unsigned NOT NULL AUTO_INCREMENT,',
    '`forum_discussion_id` bigint unsigned NOT NULL,',
    '`user_id` ', @forum_comments_user_id_type, ' NOT NULL,',
    '`body` text NOT NULL,',
    '`created_at` timestamp NULL DEFAULT NULL,',
    '`updated_at` timestamp NULL DEFAULT NULL,',
    'PRIMARY KEY (`id`),',
    'KEY `forum_comments_forum_discussion_id_created_at_index` (`forum_discussion_id`, `created_at`),',
    'CONSTRAINT `forum_comments_forum_discussion_id_foreign` FOREIGN KEY (`forum_discussion_id`) REFERENCES `forum_discussions` (`id`) ON DELETE CASCADE,',
    'CONSTRAINT `forum_comments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE',
    ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS `digital_publications` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `type` varchar(16) NOT NULL,
    `slug` varchar(255) NOT NULL,
    `title` varchar(255) NOT NULL,
    `issue_date` varchar(255) DEFAULT NULL,
    `file_path` varchar(255) NOT NULL,
    `is_available` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `digital_publications_type_slug_unique` (`type`, `slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `digital_publications`
    (`type`, `slug`, `title`, `issue_date`, `file_path`, `is_available`, `created_at`, `updated_at`)
VALUES
    ('epaper', 'demo-epaper', 'Eruvaaka E-Paper', '2026-09-27', 'images/eruvaaka1.pdf', 1, NOW(), NOW()),
    ('emagazine', 'demo-emagazine', 'Eruvaaka E-Magazine', '2026-09-27', 'images/eruvaaka2.pdf', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    `title` = VALUES(`title`),
    `issue_date` = VALUES(`issue_date`),
    `file_path` = VALUES(`file_path`),
    `is_available` = VALUES(`is_available`),
    `updated_at` = NOW();

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

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_09_27_000002_create_library_forum_and_publication_tables', COALESCE(MAX(`batch`), 0) + 1
FROM `migrations`
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations`
    WHERE `migration` = '2026_09_27_000002_create_library_forum_and_publication_tables'
);

SET @column_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'users'
      AND column_name = 'address'
);
SET @sql := IF(
    @column_exists = 0,
    'ALTER TABLE `users` ADD COLUMN `address` varchar(255) DEFAULT NULL AFTER `pincode`',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @column_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'payment_orders'
      AND column_name = 'shipping_address'
);
SET @sql := IF(
    @column_exists = 0,
    'ALTER TABLE `payment_orders` ADD COLUMN `shipping_address` text DEFAULT NULL AFTER `plan_type`',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @column_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'users'
      AND column_name = 'designation'
);
SET @sql := IF(
    @column_exists = 0,
    'ALTER TABLE `users` ADD COLUMN `designation` json DEFAULT NULL AFTER `address`',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @column_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'users'
      AND column_name = 'designation_other'
);
SET @sql := IF(
    @column_exists = 0,
    'ALTER TABLE `users` ADD COLUMN `designation_other` varchar(255) DEFAULT NULL AFTER `designation`',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @column_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'users'
      AND column_name = 'nationality'
);
SET @sql := IF(
    @column_exists = 0,
    'ALTER TABLE `users` ADD COLUMN `nationality` varchar(100) DEFAULT NULL AFTER `designation_other`',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS `coupons` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `code` varchar(255) NOT NULL,
    `discount_type` varchar(20) NOT NULL,
    `discount_value` decimal(12,2) NOT NULL,
    `is_active` tinyint(1) NOT NULL DEFAULT 1,
    `expires_at` date DEFAULT NULL,
    `usage_limit` int unsigned DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `coupons_code_unique` (`code`),
    KEY `coupons_is_active_expires_at_index` (`is_active`, `expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @column_exists := (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'coupons' AND column_name = 'starts_at'
);
SET @sql := IF(@column_exists = 0, 'ALTER TABLE `coupons` ADD COLUMN `starts_at` date DEFAULT NULL AFTER `is_active`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @column_exists := (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'coupons' AND column_name = 'offer_title'
);
SET @sql := IF(@column_exists = 0, 'ALTER TABLE `coupons` ADD COLUMN `offer_title` varchar(150) DEFAULT NULL AFTER `usage_limit`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @column_exists := (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'coupons' AND column_name = 'offer_message'
);
SET @sql := IF(@column_exists = 0, 'ALTER TABLE `coupons` ADD COLUMN `offer_message` text DEFAULT NULL AFTER `offer_title`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @column_exists := (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'coupons' AND column_name = 'eligible_plans'
);
SET @sql := IF(@column_exists = 0, 'ALTER TABLE `coupons` ADD COLUMN `eligible_plans` json DEFAULT NULL AFTER `offer_message`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

INSERT INTO `coupons` (`code`, `discount_type`, `discount_value`, `is_active`, `expires_at`, `usage_limit`, `created_at`, `updated_at`)
VALUES ('POSTMAN_TEST', 'percentage', 1.00, 1, NULL, 10, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    `discount_type` = VALUES(`discount_type`),
    `discount_value` = VALUES(`discount_value`),
    `is_active` = VALUES(`is_active`),
    `expires_at` = VALUES(`expires_at`),
    `usage_limit` = VALUES(`usage_limit`),
    `updated_at` = NOW();

INSERT INTO `coupons` (`code`, `discount_type`, `discount_value`, `is_active`, `starts_at`, `expires_at`, `usage_limit`, `offer_title`, `offer_message`, `eligible_plans`, `created_at`, `updated_at`)
VALUES ('DIWALI2026', 'percentage', 10.00, 1, '2026-10-27', '2026-11-15', NULL, 'Diwali Special', 'Celebrate Diwali with 10% off your magazine plan.', '["emagazine_1year","emagazine_3year","emagazine_5year","print_magazine_1year","print_magazine_3year","print_magazine_5year","combo_1year","combo_3year","combo_5year"]', NOW(), NOW())
ON DUPLICATE KEY UPDATE
    `discount_type` = VALUES(`discount_type`),
    `discount_value` = VALUES(`discount_value`),
    `is_active` = VALUES(`is_active`),
    `starts_at` = VALUES(`starts_at`),
    `expires_at` = VALUES(`expires_at`),
    `usage_limit` = VALUES(`usage_limit`),
    `offer_title` = VALUES(`offer_title`),
    `offer_message` = VALUES(`offer_message`),
    `eligible_plans` = VALUES(`eligible_plans`),
    `updated_at` = NOW();

SET @column_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'payment_orders'
      AND column_name = 'coupon_id'
);
SET @sql := IF(
    @column_exists = 0,
    'ALTER TABLE `payment_orders` ADD COLUMN `coupon_id` bigint unsigned DEFAULT NULL AFTER `plan_type`',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @column_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'payment_orders'
      AND column_name = 'discount_amount'
);
SET @sql := IF(
    @column_exists = 0,
    'ALTER TABLE `payment_orders` ADD COLUMN `discount_amount` bigint unsigned NOT NULL DEFAULT 0 AFTER `coupon_id`',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @foreign_key_exists := (
    SELECT COUNT(*)
    FROM information_schema.key_column_usage
    WHERE table_schema = DATABASE()
      AND table_name = 'payment_orders'
      AND column_name = 'coupon_id'
      AND referenced_table_name = 'coupons'
);
SET @sql := IF(
    @foreign_key_exists = 0,
    'ALTER TABLE `payment_orders` ADD CONSTRAINT `payment_orders_coupon_id_foreign` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE SET NULL',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @users_id_type := (
    SELECT `COLUMN_TYPE`
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'users'
      AND column_name = 'id'
    LIMIT 1
);
SET @sql := CONCAT(
    'CREATE TABLE IF NOT EXISTS `coupon_usages` (',
    '`id` bigint unsigned NOT NULL AUTO_INCREMENT,',
    '`coupon_id` bigint unsigned NOT NULL,',
    '`user_id` ', COALESCE(@users_id_type, 'bigint unsigned'), ' NOT NULL,',
    '`payment_order_id` bigint unsigned DEFAULT NULL,',
    '`discount_amount` bigint unsigned NOT NULL,',
    '`used_at` timestamp NOT NULL,',
    '`created_at` timestamp NULL DEFAULT NULL,',
    '`updated_at` timestamp NULL DEFAULT NULL,',
    'PRIMARY KEY (`id`),',
    'UNIQUE KEY `coupon_usages_coupon_id_user_id_unique` (`coupon_id`, `user_id`),',
    'UNIQUE KEY `coupon_usages_payment_order_id_unique` (`payment_order_id`),',
    'KEY `coupon_usages_coupon_id_used_at_index` (`coupon_id`, `used_at`),',
    'CONSTRAINT `coupon_usages_coupon_id_foreign` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE CASCADE,',
    'CONSTRAINT `coupon_usages_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,',
    'CONSTRAINT `coupon_usages_payment_order_id_foreign` FOREIGN KEY (`payment_order_id`) REFERENCES `payment_orders` (`id`) ON DELETE SET NULL',
    ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_10_25_000001_add_address_to_users_table', COALESCE(MAX(`batch`), 0) + 1
FROM `migrations`
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations`
    WHERE `migration` = '2026_10_25_000001_add_address_to_users_table'
);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_10_25_000001_create_coupons_and_coupon_usages_tables', COALESCE(MAX(`batch`), 0) + 1
FROM `migrations`
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations`
    WHERE `migration` = '2026_10_25_000001_create_coupons_and_coupon_usages_tables'
);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_10_25_000003_add_user_registration_details', COALESCE(MAX(`batch`), 0) + 1
FROM `migrations`
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations`
    WHERE `migration` = '2026_10_25_000003_add_user_registration_details'
);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_10_25_000004_add_shipping_address_to_payment_orders', COALESCE(MAX(`batch`), 0) + 1
FROM `migrations`
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations`
    WHERE `migration` = '2026_10_25_000004_add_shipping_address_to_payment_orders'
);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_10_27_000001_add_festival_offer_fields_and_diwali_coupon', COALESCE(MAX(`batch`), 0) + 1
FROM `migrations`
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations`
    WHERE `migration` = '2026_10_27_000001_add_festival_offer_fields_and_diwali_coupon'
);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_10_27_000002_add_eligible_plans_to_coupons', COALESCE(MAX(`batch`), 0) + 1
FROM `migrations`
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations`
    WHERE `migration` = '2026_10_27_000002_add_eligible_plans_to_coupons'
);

CREATE TABLE IF NOT EXISTS `agricultural_jobs` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `title` varchar(255) NOT NULL,
    `description` text NOT NULL,
    `location` varchar(255) NOT NULL,
    `salary` varchar(255) DEFAULT NULL,
    `contact_person` varchar(255) NOT NULL,
    `contact_phone` varchar(30) NOT NULL,
    `status` varchar(30) NOT NULL DEFAULT 'active',
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `agricultural_jobs_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `products` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `source_id` varchar(255) NOT NULL,
    `name` varchar(255) NOT NULL,
    `slug` varchar(255) DEFAULT NULL,
    `description` text DEFAULT NULL,
    `permalink` text NOT NULL,
    `price_minor` bigint unsigned DEFAULT NULL,
    `currency_code` char(3) NOT NULL DEFAULT 'INR',
    `currency_minor_unit` tinyint unsigned NOT NULL DEFAULT 2,
    `image_url` text DEFAULT NULL,
    `in_stock` tinyint(1) NOT NULL DEFAULT 0,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `products_source_id_unique` (`source_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_10_26_000001_create_agricultural_jobs_table', COALESCE(MAX(`batch`), 0) + 1
FROM `migrations`
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations`
    WHERE `migration` = '2026_10_26_000001_create_agricultural_jobs_table'
);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_10_26_000002_create_products_table', COALESCE(MAX(`batch`), 0) + 1
FROM `migrations`
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations`
    WHERE `migration` = '2026_10_26_000002_create_products_table'
);

SET FOREIGN_KEY_CHECKS=1;
