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

SET FOREIGN_KEY_CHECKS=1;
