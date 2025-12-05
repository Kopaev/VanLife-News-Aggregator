# 🚐 МАСТЕР-ПРОМТ: VanLife News Aggregator

## ИНСТРУКЦИЯ ДЛЯ ИИ-РАЗРАБОТЧИКА

Ты — senior full-stack разработчик. Твоя задача — **поэтапно** спроектировать и реализовать новостной агрегатор на стеке **PHP 8+ / MySQL / Vanilla JS**.

### ⚠️ КРИТИЧЕСКИ ВАЖНО: Система работы с проектом

1. **Перед началом каждой сессии** — проверь файл `PROGRESS.md` в корне репозитория
2. **После завершения каждого этапа** — обнови `PROGRESS.md` с отметками выполнения
3. **Не переходи к следующему этапу**, пока текущий не завершён и не протестирован
4. **Каждый коммит** должен содержать осмысленное описание изменений
5. **Документируй всё** — код, решения, проблемы и их решения

---

## 📋 СПЕЦИФИКАЦИЯ ПРОЕКТА

### Общая информация
- **Название:** VanLife News Aggregator
- **Домен:** `news.vanlife.bez.coffee`
- **Назначение:** Агрегатор новостей о vanlife/автодомах со всего мира
- **Язык интерфейса (v1):** Русский
- **Языки сбора:** 30+ языков (см. список ниже)

### Тематика контента (по приоритету)
1. Законы / запреты / штрафы / правила ночёвок и парковки
2. Открытия / закрытия кемпингов / стоянок / парков
3. Происшествия / инциденты
4. Индустрия / фестивали / выставки / анонсы
5. Обзоры автодомов и аксессуаров

### Категории (рубрики)
```
law          — Законы и правила
ban          — Запреты и штрафы  
opening      — Открытия (кемпинги, стоянки)
closing      — Закрытия
incident     — Происшествия
festival     — Фестивали и события
expo         — Выставки
industry     — Индустрия и бизнес
review       — Обзоры техники
other        — Прочее
```

### Технологический стек
- **Backend:** PHP 8.2+ (без фреймворков)
- **Database:** MySQL 8.0+
- **Frontend:** Vanilla JS (ES6+), CSS3
- **AI API:** OpenAI (gpt-4o-mini для экономии)
- **Деплой:** GitHub → автодеплой на сервер

### Ограничения
- Бюджет OpenAI: ~$10/месяц
- Минимум Composer-зависимостей
- Никаких JS/CSS фреймворков

---

## 🏗️ АРХИТЕКТУРА

### Структура директорий
```
vanlife-news/
├── .github/
│   └── workflows/
│       └── deploy.yml          # GitHub Actions для автодеплоя
├── public/                     # Document root веб-сервера
│   ├── index.php               # Единая точка входа (роутер)
│   ├── css/
│   │   ├── style.css           # Основные стили
│   │   └── themes.css          # Светлая/тёмная тема
│   ├── js/
│   │   ├── app.js              # Основная логика
│   │   ├── filters.js          # Фильтры
│   │   ├── theme.js            # Переключение темы
│   │   └── admin.js            # Логика админки
│   └── images/
│       ├── flags/              # Флаги стран (SVG)
│       └── placeholders/       # Заглушки для картинок
├── src/
│   ├── Core/
│   │   ├── App.php             # Инициализация приложения
│   │   ├── Router.php          # Роутер
│   │   ├── Database.php        # PDO-обёртка
│   │   ├── Config.php          # Загрузка конфигов
│   │   └── Response.php        # HTTP-ответы (JSON, HTML)
│   ├── Controller/
│   │   ├── HomeController.php      # Главная страница
│   │   ├── ArticleController.php   # Страница новости
│   │   ├── ApiController.php       # API endpoints
│   │   ├── AdminController.php     # Админка
│   │   └── HealthController.php    # Healthcheck
│   ├── Service/
│   │   ├── GoogleNewsUrlDecoder.php  # Декодирование Google News URL
│   │   ├── NewsFetcher.php         # Сбор новостей из RSS
│   │   ├── NewsProcessor.php       # ИИ-обработка новостей
│   │   ├── TranslationService.php  # Переводы
│   │   ├── ClusteringService.php   # Группировка похожих
│   │   ├── ModerationService.php   # Фильтрация контента
│   │   ├── SeoService.php          # SEO-генерация
│   │   └── Logger.php              # Логирование
│   ├── Model/
│   │   ├── Article.php             # Модель новости
│   │   ├── Source.php              # Модель источника
│   │   ├── Translation.php         # Модель перевода
│   │   ├── Cluster.php             # Модель кластера
│   │   └── Log.php                 # Модель лога
│   ├── Repository/
│   │   ├── ArticleRepository.php
│   │   ├── SourceRepository.php
│   │   ├── TranslationRepository.php
│   │   ├── ClusterRepository.php
│   │   ├── DecodedUrlRepository.php  # Кеш decoded URLs
│   │   └── LogRepository.php
│   ├── AI/
│   │   ├── AIProviderInterface.php # Интерфейс провайдера
│   │   ├── OpenAIProvider.php      # Реализация OpenAI
│   │   └── PromptBuilder.php       # Генерация промтов
│   └── Helper/
│       ├── TextHelper.php          # Работа с текстом
│       ├── DateHelper.php          # Работа с датами
│       ├── SlugHelper.php          # Генерация ЧПУ
│       └── CountryHelper.php       # Определение страны
├── templates/
│   ├── layout/
│   │   ├── base.php                # Базовый шаблон
│   │   ├── header.php
│   │   └── footer.php
│   ├── pages/
│   │   ├── home.php                # Главная
│   │   ├── article.php             # Страница новости
│   │   └── 404.php
│   ├── admin/
│   │   ├── login.php
│   │   ├── dashboard.php
│   │   ├── moderation.php
│   │   └── sources.php
│   └── components/
│       ├── news-card.php           # Карточка новости
│       ├── cluster-card.php        # Карточка кластера
│       ├── filters.php             # Блок фильтров
│       └── pagination.php
├── config/
│   ├── config.php                  # Основной конфиг
│   ├── sources.php                 # RSS-источники
│   ├── countries.php               # Страны и языки
│   ├── categories.php              # Категории
│   └── moderation.php              # Правила модерации
├── scripts/
│   ├── fetch_news.php              # Cron: сбор новостей
│   ├── process_news.php            # Cron: ИИ-обработка
│   ├── cluster_news.php            # Cron: кластеризация
│   └── generate_sitemap.php        # Cron: sitemap
├── sql/
│   ├── schema.sql                  # Структура БД
│   ├── seeds/
│   │   ├── sources.sql             # Начальные источники
│   │   └── countries.sql           # Страны и флаги
│   └── migrations/                 # Будущие миграции
├── logs/                           # Логи (gitignore)
├── .env.example                    # Пример переменных окружения
├── .gitignore
├── composer.json
├── README.md                       # Документация проекта
├── PROGRESS.md                     # Трекер прогресса разработки
└── CHANGELOG.md                    # История изменений
```

### Маршруты (Routes)
```
GET  /                          → HomeController::index()      # Главная
GET  /news/{slug}               → ArticleController::show()    # Новость
GET  /category/{slug}           → HomeController::category()   # По категории
GET  /country/{code}            → HomeController::country()    # По стране

# API
GET  /api/news                  → ApiController::list()        # Список новостей
GET  /api/news/{id}             → ApiController::get()         # Одна новость
GET  /api/clusters              → ApiController::clusters()    # Кластеры
GET  /api/filters               → ApiController::filters()     # Данные для фильтров

# Admin
GET  /admin/login               → AdminController::loginForm()
POST /admin/login               → AdminController::login()
GET  /admin                     → AdminController::dashboard()
GET  /admin/moderation          → AdminController::moderation()
POST /admin/article/{id}/approve → AdminController::approve()
POST /admin/article/{id}/reject  → AdminController::reject()
GET  /admin/sources             → AdminController::sources()
POST /admin/logout              → AdminController::logout()

# System
GET  /health                    → HealthController::check()
GET  /sitemap.xml               → SeoController::sitemap()
GET  /robots.txt                → статический файл
```

---

## 🗄️ СХЕМА БАЗЫ ДАННЫХ

```sql
-- Файл: sql/schema.sql

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------
-- Таблица стран
-- -----------------------------------------------------
CREATE TABLE `countries` (
    `code` CHAR(2) NOT NULL PRIMARY KEY COMMENT 'ISO 3166-1 alpha-2',
    `name_ru` VARCHAR(100) NOT NULL,
    `name_en` VARCHAR(100) NOT NULL,
    `flag_emoji` VARCHAR(10) DEFAULT NULL,
    `languages` JSON COMMENT '["de", "fr"] — языки страны',
    `is_active` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Таблица языков
-- -----------------------------------------------------
CREATE TABLE `languages` (
    `code` CHAR(5) NOT NULL PRIMARY KEY COMMENT 'ISO 639-1 или 639-2',
    `name_ru` VARCHAR(50) NOT NULL,
    `name_native` VARCHAR(50) NOT NULL,
    `is_active` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Таблица источников (RSS-ленты)
-- -----------------------------------------------------
CREATE TABLE `sources` (
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

-- -----------------------------------------------------
-- Таблица категорий
-- -----------------------------------------------------
CREATE TABLE `categories` (
    `slug` VARCHAR(50) NOT NULL PRIMARY KEY,
    `name_ru` VARCHAR(100) NOT NULL,
    `name_en` VARCHAR(100) NOT NULL,
    `icon` VARCHAR(50) DEFAULT NULL COMMENT 'CSS class или emoji',
    `color` CHAR(7) DEFAULT NULL COMMENT 'HEX цвет метки',
    `priority` TINYINT UNSIGNED DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Таблица кластеров (группы похожих новостей)
-- -----------------------------------------------------
CREATE TABLE `clusters` (
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

-- -----------------------------------------------------
-- Таблица статей (новостей)
-- -----------------------------------------------------
CREATE TABLE `articles` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `source_id` INT UNSIGNED NOT NULL,
    `cluster_id` INT UNSIGNED DEFAULT NULL,
    
    -- Оригинальные данные
    `external_id` VARCHAR(500) NOT NULL COMMENT 'GUID или URL из RSS',
    `original_title` VARCHAR(500) NOT NULL,
    `original_summary` TEXT,
    `original_content` TEXT,
    `original_url` VARCHAR(1000) NOT NULL,
    `original_language` CHAR(5) NOT NULL,
    
    -- Переведённые/обработанные данные
    `title_ru` VARCHAR(500) DEFAULT NULL,
    `summary_ru` TEXT DEFAULT NULL COMMENT 'Краткое описание от ИИ',
    `slug` VARCHAR(200) DEFAULT NULL UNIQUE,
    
    -- Метаданные
    `image_url` VARCHAR(1000) DEFAULT NULL,
    `country_code` CHAR(2) DEFAULT NULL COMMENT 'О какой стране новость',
    `category_slug` VARCHAR(50) DEFAULT NULL,
    `tags` JSON DEFAULT NULL COMMENT '["vanlife", "germany", "ban"]',
    `ai_relevance_score` TINYINT UNSIGNED DEFAULT NULL COMMENT '0-100',
    `ai_processed_at` DATETIME DEFAULT NULL,
    
    -- Даты
    `published_at` DATETIME NOT NULL COMMENT 'Дата публикации оригинала',
    `fetched_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    -- Статусы
    `status` ENUM('new', 'processing', 'published', 'moderation', 'rejected', 'duplicate') DEFAULT 'new',
    `moderation_reason` VARCHAR(255) DEFAULT NULL,
    `moderated_at` DATETIME DEFAULT NULL,
    
    -- Счётчики
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

-- Обновляем clusters после создания articles
ALTER TABLE `clusters` 
ADD FOREIGN KEY (`main_article_id`) REFERENCES `articles`(`id`) ON DELETE SET NULL;

-- -----------------------------------------------------
-- Таблица переводов (для будущей многоязычности)
-- -----------------------------------------------------
CREATE TABLE `translations` (
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

-- -----------------------------------------------------
-- Таблица логов
-- -----------------------------------------------------
CREATE TABLE `logs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `level` ENUM('debug', 'info', 'warning', 'error', 'critical') NOT NULL,
    `context` VARCHAR(50) NOT NULL COMMENT 'fetcher, processor, api, admin',
    `message` VARCHAR(500) NOT NULL,
    `details` JSON DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX `idx_level_context` (`level`, `context`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Таблица метрик
-- -----------------------------------------------------
CREATE TABLE `metrics` (
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

-- -----------------------------------------------------
-- Таблица админов
-- -----------------------------------------------------
CREATE TABLE `admins` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `last_login_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Таблица сессий админов
-- -----------------------------------------------------
CREATE TABLE `admin_sessions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `admin_id` INT UNSIGNED NOT NULL,
    `token` CHAR(64) NOT NULL UNIQUE,
    `expires_at` DATETIME NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX `idx_token` (`token`),
    INDEX `idx_expires` (`expires_at`),
    FOREIGN KEY (`admin_id`) REFERENCES `admins`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Таблица настроек
-- -----------------------------------------------------
CREATE TABLE `settings` (
    `key` VARCHAR(100) NOT NULL PRIMARY KEY,
    `value` TEXT,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Таблица кеша декодированных Google News URL
-- -----------------------------------------------------
CREATE TABLE `decoded_urls_cache` (
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

SET FOREIGN_KEY_CHECKS = 1;
```

---

## 🔗 GOOGLE NEWS URL DECODER

### Проблема
Google News RSS возвращает закодированные URL вместо оригинальных:
```
❌ https://news.google.com/rss/articles/CBMivAFBVV95cUxQQ2J4MGNn...
✅ https://www.reuters.com/world/europe/germany-bans-rv-parking...
```

### Решение
Создать класс `GoogleNewsUrlDecoder` с двумя методами декодирования:

1. **Быстрый метод (base64)** — для простых URL
2. **API метод** — для сложных URL (fallback)

### Реализация PHP

```php
<?php
// Файл: src/Service/GoogleNewsUrlDecoder.php

namespace App\Service;

/**
 * Декодер URL из Google News RSS
 * 
 * Google News возвращает закодированные ссылки вида:
 * https://news.google.com/rss/articles/CBMi...
 * 
 * Этот класс извлекает оригинальный URL статьи.
 * 
 * Основан на:
 * - https://github.com/SSujitX/google-news-url-decoder
 * - https://gist.github.com/huksley/bc3cb046157a99cd9d1517b32f91a99e
 */
class GoogleNewsUrlDecoder
{
    private const GOOGLE_NEWS_URL_PREFIX = 'https://news.google.com/';
    private const ARTICLES_PATH = '/rss/articles/';
    private const READ_PATH = '/read/';
    
    private LoggerService $logger;
    private int $requestDelay = 1000; // ms между запросами к Google
    private ?string $lastRequestTime = null;
    
    public function __construct(LoggerService $logger)
    {
        $this->logger = $logger;
    }
    
    /**
     * Декодирует Google News URL в оригинальный URL
     * 
     * @param string $googleNewsUrl URL из Google News RSS
     * @return string|null Оригинальный URL или null при ошибке
     */
    public function decode(string $googleNewsUrl): ?string
    {
        // Проверяем, что это Google News URL
        if (!$this->isGoogleNewsUrl($googleNewsUrl)) {
            return $googleNewsUrl; // Уже обычный URL
        }
        
        // Извлекаем закодированную часть
        $encodedPart = $this->extractEncodedPart($googleNewsUrl);
        if (!$encodedPart) {
            $this->logger->warning('GoogleNewsDecoder', 'Cannot extract encoded part', [
                'url' => $googleNewsUrl
            ]);
            return null;
        }
        
        // Метод 1: Пробуем быстрое base64 декодирование
        $decoded = $this->decodeBase64($encodedPart);
        if ($decoded && filter_var($decoded, FILTER_VALIDATE_URL)) {
            return $decoded;
        }
        
        // Метод 2: Запрос к Google для получения параметров декодирования
        $decoded = $this->decodeViaGoogleApi($encodedPart);
        if ($decoded) {
            return $decoded;
        }
        
        // Метод 3: Fallback — делаем HTTP запрос и следуем редиректам
        $decoded = $this->decodeViaRedirect($googleNewsUrl);
        if ($decoded) {
            return $decoded;
        }
        
        $this->logger->error('GoogleNewsDecoder', 'All decode methods failed', [
            'url' => $googleNewsUrl
        ]);
        
        return null;
    }
    
    /**
     * Пакетное декодирование URL (оптимизировано для множества ссылок)
     * 
     * @param array $urls Массив Google News URL
     * @return array Ассоциативный массив [original_url => decoded_url]
     */
    public function decodeBatch(array $urls): array
    {
        $results = [];
        
        foreach ($urls as $url) {
            $results[$url] = $this->decode($url);
            
            // Задержка между запросами для избежания rate limiting
            if ($this->requestDelay > 0) {
                usleep($this->requestDelay * 1000);
            }
        }
        
        return $results;
    }
    
    /**
     * Проверяет, является ли URL ссылкой Google News
     */
    public function isGoogleNewsUrl(string $url): bool
    {
        return str_starts_with($url, self::GOOGLE_NEWS_URL_PREFIX);
    }
    
    /**
     * Извлекает закодированную часть из URL
     */
    private function extractEncodedPart(string $url): ?string
    {
        $parsed = parse_url($url);
        if (!$parsed || !isset($parsed['path'])) {
            return null;
        }
        
        $path = $parsed['path'];
        
        // Формат: /rss/articles/CBMi...
        if (str_contains($path, '/articles/')) {
            $parts = explode('/articles/', $path);
            if (isset($parts[1])) {
                // Убираем query параметры
                return explode('?', $parts[1])[0];
            }
        }
        
        // Формат: /read/CBMi...
        if (str_contains($path, '/read/')) {
            $parts = explode('/read/', $path);
            if (isset($parts[1])) {
                return explode('?', $parts[1])[0];
            }
        }
        
        return null;
    }
    
    /**
     * Метод 1: Декодирование через base64
     * 
     * Работает для URL формата CBMi... (старый формат Google News)
     * Новый формат (2024+) требует API-запрос
     */
    private function decodeBase64(string $encoded): ?string
    {
        try {
            // Добавляем padding для base64
            $padded = $encoded . str_repeat('=', (4 - strlen($encoded) % 4) % 4);
            
            // Декодируем base64 URL-safe
            $decoded = base64_decode(strtr($padded, '-_', '+/'), true);
            if ($decoded === false) {
                return null;
            }
            
            // Ищем URL в декодированных данных
            // Формат: \x08\x13"<length><url>\xd2\x01\x00
            
            // Паттерн для извлечения URL
            if (preg_match('/https?:\/\/[^\x00-\x1f\x7f-\xff]+/', $decoded, $matches)) {
                $url = $matches[0];
                
                // Очищаем от мусора в конце
                $url = preg_replace('/[\x00-\x1f\x7f-\xff].*$/', '', $url);
                
                // Проверяем валидность
                if (filter_var($url, FILTER_VALIDATE_URL)) {
                    return $url;
                }
            }
            
            // Альтернативный метод: ищем по структуре protobuf
            $bytes = array_values(unpack('C*', $decoded));
            
            // Пропускаем header bytes (обычно \x08\x13")
            $startIndex = 0;
            for ($i = 0; $i < min(10, count($bytes)); $i++) {
                if ($bytes[$i] === 0x22) { // "
                    $startIndex = $i + 1;
                    break;
                }
            }
            
            if ($startIndex > 0 && $startIndex < count($bytes)) {
                $length = $bytes[$startIndex];
                
                // Обработка varint для длины > 127
                if ($length >= 0x80 && $startIndex + 1 < count($bytes)) {
                    $length = ($length & 0x7f) | ($bytes[$startIndex + 1] << 7);
                    $startIndex++;
                }
                
                $startIndex++;
                $endIndex = min($startIndex + $length, count($bytes));
                
                $urlBytes = array_slice($bytes, $startIndex, $endIndex - $startIndex);
                $url = implode('', array_map('chr', $urlBytes));
                
                // Обрезаем по первому невалидному символу
                if (preg_match('/^(https?:\/\/[^\x00-\x1f\x7f-\xff]+)/', $url, $matches)) {
                    if (filter_var($matches[1], FILTER_VALIDATE_URL)) {
                        return $matches[1];
                    }
                }
            }
            
            return null;
            
        } catch (\Throwable $e) {
            $this->logger->debug('GoogleNewsDecoder', 'Base64 decode failed', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Метод 2: Декодирование через API Google
     * 
     * Для нового формата URL (2024+) нужно:
     * 1. Получить параметры декодирования (signature, timestamp)
     * 2. Отправить запрос на batchexecute endpoint
     */
    private function decodeViaGoogleApi(string $encodedPart): ?string
    {
        try {
            // Шаг 1: Получаем параметры декодирования
            $params = $this->getDecodingParams($encodedPart);
            if (!$params) {
                return null;
            }
            
            // Шаг 2: Декодируем через batchexecute API
            return $this->callBatchExecute($params);
            
        } catch (\Throwable $e) {
            $this->logger->warning('GoogleNewsDecoder', 'Google API decode failed', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Получает параметры для декодирования (signature, timestamp)
     */
    private function getDecodingParams(string $gnArtId): ?array
    {
        // Пробуем оба формата URL
        $urls = [
            "https://news.google.com/articles/{$gnArtId}",
            "https://news.google.com/rss/articles/{$gnArtId}",
        ];
        
        foreach ($urls as $url) {
            $response = $this->httpGet($url);
            if (!$response) {
                continue;
            }
            
            // Парсим HTML для извлечения data-n-a-sg и data-n-a-ts
            $dom = new \DOMDocument();
            @$dom->loadHTML($response, LIBXML_NOERROR);
            $xpath = new \DOMXPath($dom);
            
            // Ищем div с нужными атрибутами
            $divs = $xpath->query("//c-wiz/div[@data-n-a-sg]");
            if ($divs->length > 0) {
                $div = $divs->item(0);
                return [
                    'signature' => $div->getAttribute('data-n-a-sg'),
                    'timestamp' => $div->getAttribute('data-n-a-ts'),
                    'gn_art_id' => $gnArtId,
                ];
            }
            
            // Альтернативный поиск
            $divs = $xpath->query("//*[@data-n-a-sg]");
            if ($divs->length > 0) {
                $div = $divs->item(0);
                return [
                    'signature' => $div->getAttribute('data-n-a-sg'),
                    'timestamp' => $div->getAttribute('data-n-a-ts'),
                    'gn_art_id' => $gnArtId,
                ];
            }
        }
        
        return null;
    }
    
    /**
     * Вызывает Google batchexecute API для декодирования
     */
    private function callBatchExecute(array $params): ?string
    {
        $reqData = [
            [
                "Fbv4je",
                json_encode([
                    [
                        "garturlreq",
                        [
                            ["X", "X", ["X", "X"], null, null, 1, 1, "US:en", null, 1, null, null, null, null, null, 0, 1],
                            "X",
                            "X",
                            1,
                            [1, 1, 1],
                            1,
                            1,
                            null,
                            0,
                            0,
                            null,
                            0
                        ],
                        $params['gn_art_id'],
                        $params['timestamp'],
                        $params['signature']
                    ]
                ])
            ]
        ];
        
        $payload = 'f.req=' . urlencode(json_encode([$reqData]));
        
        $response = $this->httpPost(
            'https://news.google.com/_/DotsSplashUi/data/batchexecute',
            $payload,
            [
                'Content-Type: application/x-www-form-urlencoded;charset=UTF-8',
            ]
        );
        
        if (!$response) {
            return null;
        }
        
        // Парсим ответ — URL находится в JSON внутри response
        // Формат: )]}'\n\n<length>\n[["wrb.fr","Fbv4je","[\"<url>\"]",...
        if (preg_match('/"(https?:[^"\\\\]+(?:\\\\.[^"\\\\]*)*)"/', $response, $matches)) {
            $url = $matches[1];
            // Декодируем экранированные символы
            $url = stripcslashes($url);
            if (filter_var($url, FILTER_VALIDATE_URL)) {
                return $url;
            }
        }
        
        return null;
    }
    
    /**
     * Метод 3: Декодирование через HTTP редирект (fallback)
     * 
     * Просто делаем запрос и следуем редиректам
     */
    private function decodeViaRedirect(string $url): ?string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            CURLOPT_HTTPHEADER => [
                'Accept: text/html,application/xhtml+xml',
                'Accept-Language: en-US,en;q=0.9',
            ],
        ]);
        
        curl_exec($ch);
        $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Проверяем, что мы получили другой URL (не Google News)
        if ($httpCode === 200 && $finalUrl && !$this->isGoogleNewsUrl($finalUrl)) {
            return $finalUrl;
        }
        
        return null;
    }
    
    /**
     * HTTP GET запрос
     */
    private function httpGet(string $url): ?string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return ($httpCode === 200) ? $response : null;
    }
    
    /**
     * HTTP POST запрос
     */
    private function httpPost(string $url, string $body, array $headers = []): ?string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            CURLOPT_HTTPHEADER => $headers,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return ($httpCode === 200) ? $response : null;
    }
    
    /**
     * Устанавливает задержку между запросами (для избежания rate limiting)
     */
    public function setRequestDelay(int $milliseconds): void
    {
        $this->requestDelay = $milliseconds;
    }
}
```

### Интеграция в NewsFetcher

```php
// В классе NewsFetcher при парсинге RSS:

public function fetchFromSource(Source $source): array
{
    $rssItems = $this->parseRss($source->url);
    $articles = [];
    
    foreach ($rssItems as $item) {
        // Декодируем Google News URL
        $originalUrl = $this->urlDecoder->decode($item['link']);
        
        if (!$originalUrl) {
            $this->logger->warning('NewsFetcher', 'Failed to decode URL', [
                'source' => $source->name,
                'url' => $item['link']
            ]);
            continue;
        }
        
        $article = new Article();
        $article->original_url = $originalUrl;
        $article->external_id = md5($originalUrl); // Используем оригинальный URL для дедупликации
        // ... остальные поля
        
        $articles[] = $article;
    }
    
    return $articles;
}
```

### Обработка Rate Limiting

Google может возвращать 429 (Too Many Requests). Стратегия:

1. **Задержка между запросами**: минимум 1 секунда
2. **Экспоненциальный backoff**: при 429 ждём 2, 4, 8 секунд
3. **Кеширование**: сохраняем decoded URLs в БД
4. **Приоритет base64**: использовать API только как fallback

```php
// Таблица для кеширования декодированных URL
CREATE TABLE `decoded_urls_cache` (
    `google_url_hash` CHAR(32) NOT NULL PRIMARY KEY,
    `google_url` VARCHAR(1000) NOT NULL,
    `decoded_url` VARCHAR(1000) DEFAULT NULL,
    `status` ENUM('success', 'failed', 'pending') DEFAULT 'pending',
    `attempts` TINYINT UNSIGNED DEFAULT 0,
    `last_attempt_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Тестовые URL для проверки

```php
$testUrls = [
    // Старый формат (base64 должен работать)
    'https://news.google.com/rss/articles/CBMiQmh0dHBzOi8vd3d3LmV1cmVrYWxlcnQub3JnL3B1Yl9yZWxlYXNlcy8yMDE5LTExL2RwcGwtYmJwMTExODE5LnBocNIBAA?oc=5',
    
    // Новый формат (требует API)
    'https://news.google.com/rss/articles/CBMivAFBVV95cUxQQ2J4MGNnRjR2SzdKM2hjQzdkVV83cE8xU3BYN0RhbVhGWTZfXzFrcWMxZnpONmFrbE53ZEVXVy1CSnZZT1ZPQl9UblljRHdBelRJdEdXLUhBajFQMlRidEZ1dUtqY0U0dXI5R2tnWU9uT1cteGg2UTVtNU9oaU1TRGJuLXAxa3A2cnhqeURaUG1YdjRwZk9IZ0VFaG1ON01xLVVPVVYwZmMtSmFva09vZ2JicVFCal91UVNsTA?oc=5',
];

foreach ($testUrls as $url) {
    $decoded = $decoder->decode($url);
    echo "Original: {$url}\n";
    echo "Decoded: {$decoded}\n\n";
}
```

---

## 🔧 КОНФИГУРАЦИЯ

### Файл .env.example
```env
# Database
DB_HOST=localhost
DB_PORT=3306
DB_NAME=vanlife_news
DB_USER=vanlife
DB_PASS=your_secure_password

# OpenAI
OPENAI_API_KEY=sk-your-api-key
OPENAI_MODEL=gpt-4o-mini
OPENAI_MAX_TOKENS=1000

# App
APP_ENV=production
APP_DEBUG=false
APP_URL=https://news.vanlife.bez.coffee
APP_TIMEZONE=Europe/Moscow

# Admin
ADMIN_USERNAME=admin
ADMIN_PASSWORD=change_this_password

# Cron
FETCH_ENABLED=true
PROCESS_ENABLED=true
```

### Файл config/sources.php (Google News RSS запросы)
```php
<?php
/**
 * Конфигурация источников Google News RSS
 * 
 * Формат URL: https://news.google.com/rss/search?q={query}&hl={lang}&gl={country}&ceid={country}:{lang}
 */

return [
    // ===== РУССКИЙ =====
    [
        'name' => 'Google News RU - Автодом',
        'query' => '(автодом OR кемпер OR "дом на колёсах" OR ванлайф) (запрет OR штраф OR правила OR кемпинг OR фестиваль)',
        'language' => 'ru',
        'country' => 'RU',
    ],
    
    // ===== АНГЛИЙСКИЙ (US) =====
    [
        'name' => 'Google News US - RV',
        'query' => '(RV OR campervan OR motorhome OR vanlife) (ban OR rules OR ordinance OR opening OR festival)',
        'language' => 'en',
        'country' => 'US',
    ],
    
    // ===== АНГЛИЙСКИЙ (UK) =====
    [
        'name' => 'Google News UK - Campervan',
        'query' => '(campervan OR motorhome OR "wild camping") (ban OR rules OR opening OR festival)',
        'language' => 'en',
        'country' => 'GB',
    ],
    
    // ===== НЕМЕЦКИЙ =====
    [
        'name' => 'Google News DE - Wohnmobil',
        'query' => '(Wohnmobil OR Wohnwagen OR Reisemobil) (Verbot OR Stellplatz OR Eröffnung OR Messe)',
        'language' => 'de',
        'country' => 'DE',
    ],
    
    // ===== ФРАНЦУЗСКИЙ =====
    [
        'name' => 'Google News FR - Camping-car',
        'query' => '("camping-car" OR "van aménagé" OR "fourgon aménagé") (interdiction OR stationnement OR ouverture OR salon)',
        'language' => 'fr',
        'country' => 'FR',
    ],
    
    // ===== ИСПАНСКИЙ =====
    [
        'name' => 'Google News ES - Autocaravana',
        'query' => '(autocaravana OR "furgoneta camper") (prohibido OR pernocta OR apertura OR feria)',
        'language' => 'es',
        'country' => 'ES',
    ],
    
    // ===== ИТАЛЬЯНСКИЙ =====
    [
        'name' => 'Google News IT - Camper',
        'query' => '(camper OR autocaravan) (divieto OR sosta OR apertura OR fiera)',
        'language' => 'it',
        'country' => 'IT',
    ],
    
    // ===== ПОРТУГАЛЬСКИЙ =====
    [
        'name' => 'Google News PT - Autocaravana',
        'query' => '(autocaravana OR motorhome) (proibição OR pernoita OR abertura)',
        'language' => 'pt',
        'country' => 'PT',
    ],
    
    // ===== НИДЕРЛАНДСКИЙ =====
    [
        'name' => 'Google News NL - Camper',
        'query' => '(kampeerauto OR camper) (verboden OR overnachten OR opening)',
        'language' => 'nl',
        'country' => 'NL',
    ],
    
    // ===== ТУРЕЦКИЙ =====
    [
        'name' => 'Google News TR - Karavan',
        'query' => '(karavan OR motokaravan) (yasağı OR açılışı OR festival)',
        'language' => 'tr',
        'country' => 'TR',
    ],
    
    // ===== ПОЛЬСКИЙ =====
    [
        'name' => 'Google News PL - Kamper',
        'query' => '(kamper OR przyczepa) (zakaz OR otwarcie OR festiwal)',
        'language' => 'pl',
        'country' => 'PL',
    ],
    
    // ===== ШВЕДСКИЙ =====
    [
        'name' => 'Google News SE - Husbil',
        'query' => '(husbil OR husvagn) (förbud OR ställplats OR öppning)',
        'language' => 'sv',
        'country' => 'SE',
    ],
    
    // ===== НОРВЕЖСКИЙ =====
    [
        'name' => 'Google News NO - Bobil',
        'query' => '(bobil OR bobilplass) (forbud OR åpning)',
        'language' => 'no',
        'country' => 'NO',
    ],
    
    // ===== ЯПОНСКИЙ =====
    [
        'name' => 'Google News JP - キャンピングカー',
        'query' => '(キャンピングカー OR 車中泊) (禁止 OR オープン OR ショー)',
        'language' => 'ja',
        'country' => 'JP',
    ],
    
    // ===== КИТАЙСКИЙ =====
    [
        'name' => 'Google News CN - 房车',
        'query' => '房车 (禁停 OR 营地 OR 展)',
        'language' => 'zh-CN',
        'country' => 'CN',
    ],
    
    // ===== КОРЕЙСКИЙ =====
    [
        'name' => 'Google News KR - 캠핑카',
        'query' => '(캠핑카 OR 차박) (금지 OR 개장 OR 축제)',
        'language' => 'ko',
        'country' => 'KR',
    ],
];
```

### Файл config/moderation.php (Правила модерации)
```php
<?php
/**
 * Правила автоматической модерации контента
 */

return [
    // Слова/фразы, отправляющие новость на ручную модерацию
    'require_moderation' => [
        // Наркотики (контекст может быть легитимным — проверка на границе)
        'наркотик', 'drug', 'droga', 'Drogen', 'narcotic',
        'марихуана', 'marijuana', 'cannabis', 'конопля',
        'кокаин', 'cocaine', 'героин', 'heroin',
        
        // Контрабанда
        'контрабанда', 'smuggling', 'contrebande', 'Schmuggel',
        
        // Сексуальный контент
        'секс', 'sex', 'эротик', 'erotic', 'порно', 'porn',
        'интим', 'intimate', 'проститу', 'prostitut',
        
        // Насилие
        'убийство', 'murder', 'meurtre', 'Mord',
        'изнасилован', 'rape', 'viol',
        'похищен', 'kidnap', 'enlèvement',
        
        // Оружие
        'оружие', 'weapon', 'arme', 'Waffe',
        'взрывчат', 'explosive', 'bomb',
    ],
    
    // Слова/фразы для автоматического отклонения
    'auto_reject' => [
        // Откровенно нерелевантный контент
        'рецепт наркотик', 'drug recipe', 'how to make drugs',
        'sex in camper', 'секс в автодоме', 'sex im wohnmobil',
        'escort', 'эскорт',
    ],
    
    // Минимальный порог релевантности (0-100)
    'min_relevance_score' => 30,
    
    // Автоматически публиковать, если score выше
    'auto_publish_score' => 70,
];
```

---

## 🤖 ИИ-ОБРАБОТКА

### Промты для OpenAI

#### 1. Оценка релевантности + категоризация
```
Ты — эксперт по vanlife/автодомам. Проанализируй новость и верни JSON.

НОВОСТЬ:
Заголовок: {title}
Описание: {summary}
Источник: {source}
Язык: {language}

ЗАДАЧА:
1. Оцени релевантность для vanlife-аудитории (0-100)
2. Определи категорию
3. Определи страну, о которой новость (если есть)
4. Сгенерируй 3-5 тегов
5. Проверь на "опасный" контент

КАТЕГОРИИ:
- law — законы и правила
- ban — запреты и штрафы
- opening — открытия кемпингов/стоянок
- closing — закрытия
- incident — происшествия
- festival — фестивали
- expo — выставки
- industry — индустрия
- review — обзоры
- other — прочее

ФОРМАТ ОТВЕТА (только JSON, без markdown):
{
  "relevance_score": 85,
  "category": "ban",
  "country_code": "DE",
  "tags": ["germany", "parking-ban", "munich"],
  "is_dangerous": false,
  "danger_reason": null
}
```

#### 2. Генерация саммари + перевод
```
Ты — редактор новостного портала о vanlife. 

ОРИГИНАЛ ({language}):
Заголовок: {title}
Текст: {content}

ЗАДАЧА:
1. Переведи заголовок на русский (если не на русском)
2. Напиши краткое саммари (2-3 предложения) на русском
3. Саммари должно отвечать на: Что? Где? Когда? Почему важно?

ФОРМАТ ОТВЕТА (только JSON):
{
  "title_ru": "Переведённый заголовок",
  "summary_ru": "Краткое описание новости на русском языке."
}
```

#### 3. Кластеризация (определение похожести)
```
Сравни две новости и определи, об одном ли событии они.

НОВОСТЬ 1:
{title1}
{summary1}

НОВОСТЬ 2:
{title2}
{summary2}

Ответь JSON:
{
  "is_same_event": true/false,
  "confidence": 0.95,
  "reason": "Обе новости о запрете ночёвки в Мюнхене"
}
```

---

## 📱 ФРОНТЕНД

### Дизайн карточки новости
```
┌─────────────────────────────────────────────────┐
│ ┌─────────┐                                     │
│ │  IMAGE  │  🇩🇪 DE  │  🏷️ Запреты            │
│ │ 16:9    │                                     │
│ └─────────┘                                     │
│                                                 │
│ Мюнхен запретил ночёвку в автодомах            │
│ в центре города                                 │
│                                                 │
│ Городские власти приняли новое постанов-       │
│ ление, запрещающее парковку кемперов...        │
│                                                 │
│ 📅 2 часа назад  │  🌐 ru (оригинал: de)       │
│                                                 │
│ ▼ Также об этом: Süddeutsche, Der Spiegel (+3) │
└─────────────────────────────────────────────────┘
```

### Фильтры
```
┌─ Фильтры ──────────────────────────────────────┐
│                                                 │
│ Страны:     [🇩🇪 DE] [🇫🇷 FR] [🇺🇸 US] [...]   │
│                                                 │
│ Категории:  [Все] [Законы] [Запреты] [...]     │
│                                                 │
│ Язык оригинала: [Все] [RU] [EN] [DE] [...]     │
│                                                 │
│ Период:     [Сегодня] [Неделя] [Месяц] [Все]   │
│                                                 │
└─────────────────────────────────────────────────┘
```

### Переключатель темы
```
☀️ / 🌙  — в шапке сайта, сохраняется в localStorage
```

---

## 📅 ЭТАПЫ РАЗРАБОТКИ

### PHASE 1: Фундамент (MVP без ИИ)
**Цель:** Работающий сайт, который собирает и показывает новости

**Задачи:**
- [ ] 1.1 Инициализация проекта (GitHub, структура папок)
- [ ] 1.2 База данных (схема, миграции, кеш decoded URLs)
- [ ] 1.3 Core-классы (Router, Database, Config, Response)
- [ ] 1.4 GoogleNewsUrlDecoder — декодирование URL из RSS
- [ ] 1.5 NewsFetcher — сбор из Google News RSS
- [ ] 1.6 Базовые модели и репозитории
- [ ] 1.7 HomeController + шаблон главной
- [ ] 1.8 ArticleController + шаблон новости
- [ ] 1.9 Базовый CSS (адаптив, тёмная тема)
- [ ] 1.10 Cron-скрипт сбора новостей
- [ ] 1.11 README с инструкцией деплоя

**Критерий завершения:** Сайт показывает реальные новости из RSS с оригинальными URL

---

### PHASE 2: ИИ-обработка
**Цель:** Новости переводятся, категоризируются, фильтруются

**Задачи:**
- [ ] 2.1 OpenAI Provider (интерфейс + реализация)
- [ ] 2.2 NewsProcessor — оценка релевантности
- [ ] 2.3 NewsProcessor — категоризация и теги
- [ ] 2.4 TranslationService — перевод на русский
- [ ] 2.5 ModerationService — фильтрация опасного контента
- [ ] 2.6 Cron-скрипт обработки новостей
- [ ] 2.7 Обновление шаблонов (русские заголовки, категории)
- [ ] 2.8 Оптимизация расхода токенов

**Критерий завершения:** Новости автоматически переводятся и категоризируются

---

### PHASE 3: Кластеризация
**Цель:** Похожие новости группируются

**Задачи:**
- [ ] 3.1 ClusteringService — определение похожести
- [ ] 3.2 Алгоритм создания/обновления кластеров
- [ ] 3.3 Выбор "главной" новости кластера
- [ ] 3.4 UI кластера ("Также об этом пишут...")
- [ ] 3.5 Страница кластера
- [ ] 3.6 Cron-скрипт кластеризации

**Критерий завершения:** Похожие новости объединены в группы

---

### PHASE 4: Фильтры и поиск
**Цель:** Пользователь может фильтровать новости

**Задачи:**
- [ ] 4.1 API endpoint для фильтров
- [ ] 4.2 JS-логика фильтрации (без перезагрузки)
- [ ] 4.3 Фильтр по странам
- [ ] 4.4 Фильтр по категориям
- [ ] 4.5 Фильтр по языку оригинала
- [ ] 4.6 Фильтр по периоду
- [ ] 4.7 Сохранение фильтров в URL

**Критерий завершения:** Все фильтры работают

---

### PHASE 5: Админка
**Цель:** Админ может модерировать контент

**Задачи:**
- [ ] 5.1 Авторизация (login/logout)
- [ ] 5.2 Dashboard со статистикой
- [ ] 5.3 Список новостей на модерации
- [ ] 5.4 Approve/Reject новостей
- [ ] 5.5 Управление источниками
- [ ] 5.6 Просмотр логов

**Критерий завершения:** Админ может модерировать новости

---

### PHASE 6: SEO и продакшен
**Цель:** Сайт готов к индексации

**Задачи:**
- [ ] 6.1 ЧПУ (slugs) для новостей
- [ ] 6.2 Meta-теги (title, description)
- [ ] 6.3 Open Graph теги
- [ ] 6.4 Автогенерация sitemap.xml
- [ ] 6.5 robots.txt
- [ ] 6.6 Канонические URL
- [ ] 6.7 Микроразметка (Schema.org NewsArticle)
- [ ] 6.8 Healthcheck endpoint
- [ ] 6.9 Финальное тестирование
- [ ] 6.10 GitHub Actions для автодеплоя

**Критерий завершения:** Сайт полностью готов к продакшену

---

## 📝 ФАЙЛ PROGRESS.md (шаблон)

```markdown
# 📊 Прогресс разработки VanLife News Aggregator

**Последнее обновление:** YYYY-MM-DD HH:MM

## Текущая фаза: PHASE 1 — Фундамент

### Статус задач

#### PHASE 1: Фундамент
| # | Задача | Статус | Дата | Примечания |
|---|--------|--------|------|------------|
| 1.1 | Инициализация проекта | ⬜ | - | - |
| 1.2 | База данных | ⬜ | - | - |
| ... | ... | ... | ... | ... |

#### Легенда статусов:
- ⬜ Не начато
- 🔄 В работе  
- ✅ Завершено
- ⏸️ Приостановлено
- ❌ Отменено

### История изменений

#### [YYYY-MM-DD]
- Что сделано
- Какие проблемы возникли
- Как решили

### Известные проблемы
1. ...

### Следующие шаги
1. ...
```

---

## ⚡ ИНСТРУКЦИИ ПО РАБОТЕ С ПРОМТОМ

### Начало новой сессии
```
1. Прочитай PROGRESS.md — что уже сделано?
2. Прочитай README.md — актуален ли он?
3. Определи текущую задачу
4. Уточни, если что-то непонятно
5. Приступай к разработке
6. После завершения:
   - Обнови PROGRESS.md
   - Обнови README.md (если были изменения)
   - Сделай коммит с осмысленным описанием
```

### Правила коммитов
```
feat: добавлен NewsFetcher для сбора RSS
fix: исправлена ошибка парсинга даты
docs: обновлён README
docs: add installation instructions
docs: update API documentation
refactor: оптимизирован запрос к БД
style: форматирование кода
test: добавлены тесты для Router
chore: обновлены зависимости
```

### Обязательные коммиты документации

После каждого `feat:` или `fix:` коммита должен следовать `docs:` коммит:

```bash
# Пример правильной последовательности:
git commit -m "feat: add GoogleNewsUrlDecoder service"
git commit -m "docs: add URL decoder documentation to README"

# Или объединённый коммит:
git commit -m "feat: add GoogleNewsUrlDecoder with documentation"
```

### Приоритеты
1. **Работающий код** важнее идеального
2. **Читаемость** важнее краткости
3. **Простота** важнее универсальности (на первых этапах)
4. **Документирование** — обязательно

---

## 📚 ОБЯЗАТЕЛЬНАЯ АКТУАЛИЗАЦИЯ README.md

### ⚠️ КРИТИЧЕСКОЕ ПРАВИЛО

**README.md должен ВСЕГДА отражать текущее состояние проекта.**

После КАЖДОГО изменения в коде, ИИ обязан проверить и обновить README.md:
- Добавлена новая функция → описать в README
- Изменена настройка → обновить раздел конфигурации
- Добавлена зависимость → обновить раздел установки
- Изменена структура БД → обновить описание таблиц
- Добавлен cron-скрипт → добавить в раздел автоматизации
- Исправлен баг → обновить changelog

### Структура README.md (шаблон)

```markdown
# 🚐 VanLife News Aggregator

> Агрегатор новостей о vanlife и автодомах со всего мира

## 📋 Содержание

- [Возможности](#-возможности)
- [Требования](#-требования)
- [Установка](#-установка)
- [Конфигурация](#-конфигурация)
- [Использование](#-использование)
- [API](#-api)
- [Cron-задачи](#-cron-задачи)
- [Структура проекта](#-структура-проекта)
- [База данных](#-база-данных)
- [Разработка](#-разработка)
- [Changelog](#-changelog)

---

## ✨ Возможности

<!-- ОБНОВЛЯТЬ при добавлении новых фич -->

- [x] Сбор новостей из Google News RSS (30+ языков)
- [x] Декодирование Google News URL в оригинальные ссылки
- [ ] ИИ-перевод на русский (OpenAI)
- [ ] Автоматическая категоризация
- [ ] Кластеризация похожих новостей

---

## 📦 Требования

<!-- ОБНОВЛЯТЬ при изменении зависимостей -->

- PHP 8.2+
- MySQL 8.0+
- Composer 2.x
- cURL, DOM, JSON extensions

---

## 🚀 Установка

<!-- ОБНОВЛЯТЬ при изменении процесса установки -->

### 1. Клонирование
\`\`\`bash
git clone https://github.com/YOUR_USERNAME/vanlife-news.git
cd vanlife-news
\`\`\`

### 2. Зависимости
\`\`\`bash
composer install --no-dev
\`\`\`

### 3. Окружение
\`\`\`bash
cp .env.example .env
nano .env
\`\`\`

### 4. База данных
\`\`\`bash
mysql -u vanlife -p vanlife_news < sql/schema.sql
mysql -u vanlife -p vanlife_news < sql/seeds/sources.sql
\`\`\`

### 5. Cron
\`\`\`cron
0 6 * * * php /path/to/scripts/fetch_news.php
0 7 * * * php /path/to/scripts/process_news.php
\`\`\`

---

## ⚙️ Конфигурация

<!-- ОБНОВЛЯТЬ при добавлении/изменении настроек -->

### Переменные окружения (.env)

| Переменная | Описание | Обязательно |
|------------|----------|-------------|
| `DB_HOST` | Хост БД | ✅ |
| `DB_NAME` | Имя БД | ✅ |
| `DB_USER` | Пользователь | ✅ |
| `DB_PASS` | Пароль | ✅ |
| `OPENAI_API_KEY` | API ключ | ✅ |
| `APP_URL` | URL сайта | ✅ |
| `APP_DEBUG` | Режим отладки | ❌ |

---

## 📖 Использование

<!-- ОБНОВЛЯТЬ при добавлении команд -->

### CLI-команды
\`\`\`bash
php scripts/fetch_news.php      # Сбор новостей
php scripts/process_news.php    # ИИ-обработка
php scripts/cluster_news.php    # Кластеризация
\`\`\`

---

## 🔌 API

<!-- ОБНОВЛЯТЬ при добавлении endpoints -->

| Метод | URL | Описание |
|-------|-----|----------|
| GET | `/api/news` | Список новостей |
| GET | `/api/news/{id}` | Одна новость |
| GET | `/api/clusters` | Кластеры |
| GET | `/api/filters` | Данные фильтров |

---

## ⏰ Cron-задачи

<!-- ОБНОВЛЯТЬ при добавлении скриптов -->

| Скрипт | Расписание | Описание |
|--------|------------|----------|
| `fetch_news.php` | `0 6 * * *` | Сбор RSS |
| `process_news.php` | `0 7 * * *` | ИИ-обработка |

---

## 📁 Структура проекта

<!-- ОБНОВЛЯТЬ при изменении структуры -->

\`\`\`
vanlife-news/
├── public/          # Document root
├── src/             # PHP-код
├── templates/       # Шаблоны
├── config/          # Конфиги
├── scripts/         # CLI-скрипты
├── sql/             # SQL-файлы
└── logs/            # Логи
\`\`\`

---

## 🗄️ База данных

<!-- ОБНОВЛЯТЬ при изменении схемы -->

| Таблица | Описание |
|---------|----------|
| `articles` | Новости |
| `sources` | RSS-источники |
| `clusters` | Кластеры |
| `translations` | Переводы |
| `categories` | Категории |

---

## 📝 Changelog

<!-- ОБНОВЛЯТЬ при каждом релизе -->

### [Unreleased]
- В разработке...

### [0.1.0] - YYYY-MM-DD
- 🎉 Первый релиз
\`\`\`

### Чек-лист обновления README

| Тип изменения | Секции для обновления |
|---------------|----------------------|
| Новая фича | Возможности, Использование, Changelog |
| Новый API endpoint | API, Changelog |
| Новая настройка .env | Конфигурация |
| Новый cron-скрипт | Cron-задачи, Установка |
| Новая CLI-команда | Использование |
| Новая зависимость | Требования, Установка |
| Изменение структуры | Структура проекта |
| Новая таблица БД | База данных, Установка |
| Баг-фикс | Changelog |

### Формат Changelog

```markdown
### [X.Y.Z] - YYYY-MM-DD

#### Added
- ✨ Новая функция

#### Changed
- 🔄 Изменение

#### Fixed
- 🐛 Исправление

#### Removed
- 🗑️ Удалено
```

### Автоматическая проверка

**В конце КАЖДОЙ сессии разработки:**

1. ✅ Проверить — соответствует ли README коду?
2. ✅ Обновить — все затронутые секции
3. ✅ Добавить — запись в Changelog
4. ✅ Коммит — `docs: update README`

```bash
git add README.md
git commit -m "docs: add API endpoint /api/filters"
```

---

## 🚀 КОМАНДА ДЛЯ СТАРТА

После создания репозитория, ИИ должен:

1. Создать структуру папок
2. Создать `schema.sql`
3. Создать `.env.example`
4. Создать базовые конфиги
5. Создать `PROGRESS.md` (трекер задач)
6. Создать `README.md` (полная документация по шаблону выше)
7. Создать `CHANGELOG.md` (история изменений)
8. Сделать первый коммит: `feat: initial project structure`
9. Приступить к PHASE 1.1

### ⚠️ Напоминание о README

**После КАЖДОГО этапа (1.1, 1.2, 1.3...):**
- Обнови соответствующие секции README.md
- Добавь запись в CHANGELOG.md
- Коммит: `docs: update README for task X.Y`

---

**Готов к разработке! Начинай с проверки/создания PROGRESS.md, README.md и задачи 1.1**
