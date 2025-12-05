-- 001_init_schema.sql
-- Initial database schema for VanLife News Aggregator
-- This migration creates all core tables including cache for decoded Google News URLs.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Reference tables
CREATE TABLE IF NOT EXISTS `countries` (
    `code` CHAR(2) NOT NULL PRIMARY KEY COMMENT 'ISO 3166-1 alpha-2',
    `name_ru` VARCHAR(100) NOT NULL,
    `name_en` VARCHAR(100) NOT NULL,
    `flag_emoji` VARCHAR(10) DEFAULT NULL,
    `languages` JSON COMMENT '["de", "fr"] - языки страны',
    `is_active` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `languages` (
    `code` CHAR(5) NOT NULL PRIMARY KEY COMMENT 'ISO 639-1 или 639-2',
    `name_ru` VARCHAR(50) NOT NULL,
    `name_native` VARCHAR(50) NOT NULL,
    `is_active` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `categories` (
    `slug` VARCHAR(50) NOT NULL PRIMARY KEY,
    `name_ru` VARCHAR(100) NOT NULL,
    `name_en` VARCHAR(100) NOT NULL,
    `icon` VARCHAR(50) DEFAULT NULL COMMENT 'CSS class или emoji',
    `color` CHAR(7) DEFAULT NULL COMMENT 'HEX цвет метки',
    `priority` TINYINT UNSIGNED DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sources
CREATE TABLE IF NOT EXISTS `sources` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `type` ENUM('google_news_rss', 'rss', 'custom') DEFAULT 'google_news_rss',
    `url` VARCHAR(500) NOT NULL,
    `query` VARCHAR(255) DEFAULT NULL COMMENT 'Поисковый запрос для Google News',
    `language_code` CHAR(5) NOT NULL,
    `country_code` CHAR(2) DEFAULT NULL,
    `category` VARCHAR(50) DEFAULT NULL,
    `is_enabled` TINYINT(1) DEFAULT 1,
    `fetch_interval_hours` TINYINT UNSIGNED DEFAULT 24,
    `last_fetched_at` DATETIME DEFAULT NULL,
    `last_error` TEXT DEFAULT NULL,
    `articles_count` INT UNSIGNED DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_enabled_type` (`is_enabled`, `type`),
    INDEX `idx_language` (`language_code`),
    INDEX `idx_country` (`country_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Clusters
CREATE TABLE IF NOT EXISTS `clusters` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `title_ru` VARCHAR(500) NOT NULL COMMENT 'Обобщённый заголовок на русском',
    `slug` VARCHAR(200) NOT NULL UNIQUE,
    `summary_ru` TEXT COMMENT 'Общее саммари темы',
    `main_article_id` INT UNSIGNED DEFAULT NULL COMMENT 'Главная новость кластера',
    `category_slug` VARCHAR(50) DEFAULT NULL,
    `articles_count` SMALLINT UNSIGNED DEFAULT 1,
    `countries` JSON COMMENT '["DE", "FR", "US"]',
    `first_published_at` DATETIME NOT NULL,
    `last_updated_at` DATETIME NOT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_category` (`category_slug`),
    INDEX `idx_active_date` (`is_active`, `last_updated_at`),
    FULLTEXT INDEX `ft_title_summary` (`title_ru`, `summary_ru`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Articles
CREATE TABLE IF NOT EXISTS `articles` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `source_id` INT UNSIGNED NOT NULL,
    `cluster_id` INT UNSIGNED DEFAULT NULL,

    `external_id` VARCHAR(500) NOT NULL COMMENT 'GUID или URL из RSS',
    `original_title` VARCHAR(500) NOT NULL,
    `original_summary` TEXT,
    `original_content` TEXT,
    `original_url` VARCHAR(1000) NOT NULL,
    `original_language` CHAR(5) NOT NULL,

    `title_ru` VARCHAR(500) DEFAULT NULL,
    `summary_ru` TEXT DEFAULT NULL COMMENT 'Краткое описание от ИИ',
    `slug` VARCHAR(200) DEFAULT NULL UNIQUE,

    `image_url` VARCHAR(1000) DEFAULT NULL,
    `country_code` CHAR(2) DEFAULT NULL COMMENT 'О какой стране новость',
    `category_slug` VARCHAR(50) DEFAULT NULL,
    `tags` JSON DEFAULT NULL COMMENT '["vanlife", "germany", "ban"]',
    `ai_relevance_score` TINYINT UNSIGNED DEFAULT NULL COMMENT '0-100',
    `ai_processed_at` DATETIME DEFAULT NULL,

    `published_at` DATETIME NOT NULL COMMENT 'Дата публикации оригинала',
    `fetched_at` DATETIME DEFAULT CURRENT_TIMESTAMP,

    `status` ENUM('new', 'processing', 'published', 'moderation', 'rejected', 'duplicate') DEFAULT 'new',
    `moderation_reason` VARCHAR(255) DEFAULT NULL,
    `moderated_at` DATETIME DEFAULT NULL,

    `views_count` INT UNSIGNED DEFAULT 0,

    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE INDEX `idx_external_id` (`external_id`(255)),
    INDEX `idx_source` (`source_id`),
    INDEX `idx_cluster` (`cluster_id`),
    INDEX `idx_status_date` (`status`, `published_at`),
    INDEX `idx_country` (`country_code`),
    INDEX `idx_category` (`category_slug`),
    INDEX `idx_language` (`original_language`),
    FULLTEXT INDEX `ft_search` (`original_title`, `title_ru`, `summary_ru`),

    FOREIGN KEY (`source_id`) REFERENCES `sources`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`cluster_id`) REFERENCES `clusters`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`country_code`) REFERENCES `countries`(`code`) ON DELETE SET NULL,
    FOREIGN KEY (`category_slug`) REFERENCES `categories`(`slug`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add circular FK after articles table exists
ALTER TABLE `clusters`
    ADD CONSTRAINT `fk_clusters_main_article` FOREIGN KEY (`main_article_id`) REFERENCES `articles`(`id`) ON DELETE SET NULL;

-- Translations
CREATE TABLE IF NOT EXISTS `translations` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `article_id` INT UNSIGNED NOT NULL,
    `target_language` CHAR(5) NOT NULL,
    `title` VARCHAR(500) NOT NULL,
    `summary` TEXT,
    `provider` VARCHAR(50) DEFAULT 'openai',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,

    UNIQUE INDEX `idx_article_lang` (`article_id`, `target_language`),
    FOREIGN KEY (`article_id`) REFERENCES `articles`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Logs
CREATE TABLE IF NOT EXISTS `logs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `level` ENUM('debug', 'info', 'warning', 'error', 'critical') NOT NULL,
    `context` VARCHAR(50) NOT NULL COMMENT 'fetcher, processor, api, admin',
    `message` VARCHAR(500) NOT NULL,
    `details` JSON DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,

    INDEX `idx_level_context` (`level`, `context`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Metrics
CREATE TABLE IF NOT EXISTS `metrics` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `type` VARCHAR(50) NOT NULL COMMENT 'fetch_run, process_run, cluster_run',
    `status` ENUM('success', 'partial', 'error') NOT NULL,
    `duration_ms` INT UNSIGNED DEFAULT NULL,
    `items_processed` INT UNSIGNED DEFAULT 0,
    `items_created` INT UNSIGNED DEFAULT 0,
    `items_skipped` INT UNSIGNED DEFAULT 0,
    `errors_count` INT UNSIGNED DEFAULT 0,
    `details` JSON DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,

    INDEX `idx_type_date` (`type`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admins
CREATE TABLE IF NOT EXISTS `admins` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `last_login_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `admin_sessions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `admin_id` INT UNSIGNED NOT NULL,
    `token` CHAR(64) NOT NULL UNIQUE,
    `expires_at` DATETIME NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,

    INDEX `idx_token` (`token`),
    INDEX `idx_expires` (`expires_at`),
    FOREIGN KEY (`admin_id`) REFERENCES `admins`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Settings
CREATE TABLE IF NOT EXISTS `settings` (
    `key` VARCHAR(100) NOT NULL PRIMARY KEY,
    `value` TEXT,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Google News decoded URL cache
CREATE TABLE IF NOT EXISTS `decoded_urls_cache` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `google_url_hash` CHAR(32) NOT NULL COMMENT 'MD5 хеш Google URL',
    `google_url` VARCHAR(1000) NOT NULL,
    `decoded_url` VARCHAR(1000) DEFAULT NULL,
    `decode_method` ENUM('base64', 'api', 'redirect', 'failed') DEFAULT NULL,
    `status` ENUM('success', 'failed', 'pending') DEFAULT 'pending',
    `attempts` TINYINT UNSIGNED DEFAULT 0,
    `last_error` VARCHAR(255) DEFAULT NULL,
    `last_attempt_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,

    UNIQUE INDEX `idx_hash` (`google_url_hash`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migrations table to track applied files
CREATE TABLE IF NOT EXISTS `migrations` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `filename` VARCHAR(255) NOT NULL UNIQUE,
    `checksum` CHAR(32) NOT NULL,
    `applied_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
