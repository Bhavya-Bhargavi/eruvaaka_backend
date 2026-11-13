-- Eruvaaka Admin Panel schema only; this is not a full database export.
-- In phpMyAdmin, select the production application database and take a backup before importing.
-- This script does not add admin accounts or content records.

SELECT DATABASE() AS target_database;

CREATE TABLE IF NOT EXISTS `admins` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `email` varchar(255) NOT NULL,
    `password` varchar(255) NOT NULL,
    `role` varchar(32) NOT NULL DEFAULT 'content_manager',
    `is_active` tinyint(1) NOT NULL DEFAULT 1,
    `remember_token` varchar(100) DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `admins_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `articles` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `slug` varchar(255) NOT NULL,
    `title` varchar(255) NOT NULL,
    `category` varchar(255) DEFAULT NULL,
    `description` text DEFAULT NULL,
    `content` longtext NOT NULL,
    `cover_image` varchar(255) DEFAULT NULL,
    `is_published` tinyint(1) NOT NULL DEFAULT 0,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `articles_slug_unique` (`slug`),
    KEY `articles_is_published_created_at_index` (`is_published`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sessions` (
    `id` varchar(255) NOT NULL,
    `user_id` bigint unsigned DEFAULT NULL,
    `ip_address` varchar(45) DEFAULT NULL,
    `user_agent` text DEFAULT NULL,
    `payload` longtext NOT NULL,
    `last_activity` int NOT NULL,
    PRIMARY KEY (`id`),
    KEY `sessions_user_id_index` (`user_id`),
    KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @column_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'books'
      AND column_name = 'category'
);
SET @admin_panel_sql := IF(
    @column_exists = 0,
    'ALTER TABLE `books` ADD COLUMN `category` varchar(255) DEFAULT NULL AFTER `author`',
    'SELECT 1'
);
PREPARE admin_panel_stmt FROM @admin_panel_sql;
EXECUTE admin_panel_stmt;
DEALLOCATE PREPARE admin_panel_stmt;

SET @column_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'books'
      AND column_name = 'pdf_path'
);
SET @admin_panel_sql := IF(
    @column_exists = 0,
    'ALTER TABLE `books` ADD COLUMN `pdf_path` varchar(255) DEFAULT NULL AFTER `cover_image`',
    'SELECT 1'
);
PREPARE admin_panel_stmt FROM @admin_panel_sql;
EXECUTE admin_panel_stmt;
DEALLOCATE PREPARE admin_panel_stmt;

SET @column_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'digital_publications'
      AND column_name = 'month'
);
SET @admin_panel_sql := IF(
    @column_exists = 0,
    'ALTER TABLE `digital_publications` ADD COLUMN `month` tinyint unsigned DEFAULT NULL AFTER `issue_date`',
    'SELECT 1'
);
PREPARE admin_panel_stmt FROM @admin_panel_sql;
EXECUTE admin_panel_stmt;
DEALLOCATE PREPARE admin_panel_stmt;

SET @column_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'digital_publications'
      AND column_name = 'year'
);
SET @admin_panel_sql := IF(
    @column_exists = 0,
    'ALTER TABLE `digital_publications` ADD COLUMN `year` smallint unsigned DEFAULT NULL AFTER `month`',
    'SELECT 1'
);
PREPARE admin_panel_stmt FROM @admin_panel_sql;
EXECUTE admin_panel_stmt;
DEALLOCATE PREPARE admin_panel_stmt;

SET @column_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'digital_publications'
      AND column_name = 'description'
);
SET @admin_panel_sql := IF(
    @column_exists = 0,
    'ALTER TABLE `digital_publications` ADD COLUMN `description` text NULL AFTER `title`',
    'SELECT 1'
);
PREPARE admin_panel_stmt FROM @admin_panel_sql;
EXECUTE admin_panel_stmt;
DEALLOCATE PREPARE admin_panel_stmt;

SET @column_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'digital_publications'
      AND column_name = 'cover_image'
);
SET @admin_panel_sql := IF(
    @column_exists = 0,
    'ALTER TABLE `digital_publications` ADD COLUMN `cover_image` varchar(255) DEFAULT NULL AFTER `file_path`',
    'SELECT 1'
);
PREPARE admin_panel_stmt FROM @admin_panel_sql;
EXECUTE admin_panel_stmt;
DEALLOCATE PREPARE admin_panel_stmt;

SET @column_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'admins'
      AND column_name = 'pending_email'
);
SET @admin_panel_sql := IF(
    @column_exists = 0,
    'ALTER TABLE `admins` ADD COLUMN `pending_email` varchar(255) DEFAULT NULL',
    'SELECT 1'
);
PREPARE admin_panel_stmt FROM @admin_panel_sql;
EXECUTE admin_panel_stmt;
DEALLOCATE PREPARE admin_panel_stmt;

SET @index_exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'admins'
      AND index_name = 'admins_pending_email_index'
);
SET @admin_panel_sql := IF(
    @index_exists = 0,
    'ALTER TABLE `admins` ADD INDEX `admins_pending_email_index` (`pending_email`)',
    'SELECT 1'
);
PREPARE admin_panel_stmt FROM @admin_panel_sql;
EXECUTE admin_panel_stmt;
DEALLOCATE PREPARE admin_panel_stmt;

SET @column_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'admins'
      AND column_name = 'email_change_token'
);
SET @admin_panel_sql := IF(
    @column_exists = 0,
    'ALTER TABLE `admins` ADD COLUMN `email_change_token` varchar(64) DEFAULT NULL',
    'SELECT 1'
);
PREPARE admin_panel_stmt FROM @admin_panel_sql;
EXECUTE admin_panel_stmt;
DEALLOCATE PREPARE admin_panel_stmt;

SET @column_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'admins'
      AND column_name = 'email_change_expires_at'
);
SET @admin_panel_sql := IF(
    @column_exists = 0,
    'ALTER TABLE `admins` ADD COLUMN `email_change_expires_at` timestamp NULL DEFAULT NULL',
    'SELECT 1'
);
PREPARE admin_panel_stmt FROM @admin_panel_sql;
EXECUTE admin_panel_stmt;
DEALLOCATE PREPARE admin_panel_stmt;

CREATE TABLE IF NOT EXISTS `migrations` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `migration` varchar(255) NOT NULL,
    `batch` int NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @admin_panel_batch := (
    SELECT COALESCE(MAX(`batch`), 0) + 1
    FROM `migrations`
);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_13_000001_create_admin_panel_tables', @admin_panel_batch
WHERE NOT EXISTS (
    SELECT 1
    FROM `migrations`
    WHERE `migration` = '2026_11_13_000001_create_admin_panel_tables'
);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_13_000002_add_admin_email_change_fields', @admin_panel_batch
WHERE NOT EXISTS (
    SELECT 1
    FROM `migrations`
    WHERE `migration` = '2026_11_13_000002_add_admin_email_change_fields'
);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_13_000003_create_sessions_table', @admin_panel_batch
WHERE NOT EXISTS (
    SELECT 1
    FROM `migrations`
    WHERE `migration` = '2026_11_13_000003_create_sessions_table'
);

SELECT `migration`, `batch`
FROM `migrations`
WHERE `migration` IN (
    '2026_11_13_000001_create_admin_panel_tables',
    '2026_11_13_000002_add_admin_email_change_fields',
    '2026_11_13_000003_create_sessions_table'
)
ORDER BY `migration`;
