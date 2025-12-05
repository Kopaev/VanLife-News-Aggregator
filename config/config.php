<?php
/**
 * VanLife News Aggregator - Main Configuration
 *
 * This file loads environment variables and provides configuration values.
 */

declare(strict_types=1);

// Load .env file if exists
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) {
            continue;
        }
        if (str_contains($line, '=')) {
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if (!isset($_ENV[$key]) && !isset($_SERVER[$key])) {
                $_ENV[$key] = $value;
                putenv("{$key}={$value}");
            }
        }
    }
}

/**
 * Get environment variable with default value
 */
function env(string $key, mixed $default = null): mixed
{
    $value = $_ENV[$key] ?? getenv($key);

    if ($value === false || $value === '') {
        return $default;
    }

    // Convert string booleans
    return match (strtolower((string)$value)) {
        'true', '(true)' => true,
        'false', '(false)' => false,
        'null', '(null)' => null,
        default => $value,
    };
}

return [
    // Application
    'app' => [
        'name' => 'VanLife News Aggregator',
        'env' => env('APP_ENV', 'production'),
        'debug' => env('APP_DEBUG', false),
        'url' => env('APP_URL', 'https://news.vanlife.bez.coffee'),
        'timezone' => env('APP_TIMEZONE', 'Europe/Moscow'),
        'locale' => env('APP_LOCALE', 'ru'),
    ],

    // Database
    'database' => [
        'host' => env('DB_HOST', 'localhost'),
        'port' => (int)env('DB_PORT', 3306),
        'name' => env('DB_NAME', 'vanlife_news'),
        'user' => env('DB_USER', 'vanlife'),
        'pass' => env('DB_PASS', ''),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
    ],

    // OpenAI
    'openai' => [
        'api_key' => env('OPENAI_API_KEY', ''),
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
        'max_tokens' => (int)env('OPENAI_MAX_TOKENS', 1000),
        'temperature' => (float)env('OPENAI_TEMPERATURE', 0.3),
        'requests_per_minute' => (int)env('OPENAI_REQUESTS_PER_MINUTE', 20),
    ],

    // Admin
    'admin' => [
        'username' => env('ADMIN_USERNAME', 'admin'),
        'password' => env('ADMIN_PASSWORD', ''),
    ],

    // Session
    'session' => [
        'lifetime' => (int)env('SESSION_LIFETIME', 86400),
        'secure' => env('SESSION_SECURE', true),
    ],

    // Cron
    'cron' => [
        'fetch_enabled' => env('FETCH_ENABLED', true),
        'process_enabled' => env('PROCESS_ENABLED', true),
        'cluster_enabled' => env('CLUSTER_ENABLED', true),
    ],

    // Rate Limiting
    'rate_limit' => [
        'google_news_delay_ms' => (int)env('GOOGLE_NEWS_DELAY_MS', 1000),
    ],

    // Logging
    'logging' => [
        'level' => env('LOG_LEVEL', 'info'),
        'retention_days' => (int)env('LOG_RETENTION_DAYS', 30),
        'path' => dirname(__DIR__) . '/logs',
    ],

    // Cache
    'cache' => [
        'enabled' => env('CACHE_ENABLED', true),
        'ttl' => (int)env('CACHE_TTL', 3600),
    ],
];
