<?php
/**
 * VanLife News Aggregator - Entry Point
 *
 * All requests are routed through this file.
 */

declare(strict_types=1);

// Define base path
define('BASE_PATH', dirname(__DIR__));

// Error reporting for development
if (file_exists(BASE_PATH . '/.env')) {
    $envContent = file_get_contents(BASE_PATH . '/.env');
    if (str_contains($envContent, 'APP_DEBUG=true')) {
        error_reporting(E_ALL);
        ini_set('display_errors', '1');
    }
}

// Autoloader
$autoloader = BASE_PATH . '/vendor/autoload.php';
if (file_exists($autoloader)) {
    require_once $autoloader;
} else {
    // Simple PSR-4 autoloader fallback
    spl_autoload_register(function ($class) {
        $prefix = 'App\\';
        $baseDir = BASE_PATH . '/src/';

        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }

        $relativeClass = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

        if (file_exists($file)) {
            require $file;
        }
    });
}

// Load configuration
$config = require BASE_PATH . '/config/config.php';

// Set timezone
date_default_timezone_set($config['app']['timezone']);

// TODO: Initialize application (Phase 1.3)
// For now, show a placeholder

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VanLife News Aggregator</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
        }
        .container {
            text-align: center;
            padding: 2rem;
        }
        .logo {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            background: linear-gradient(90deg, #00d2ff, #3a7bd5);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .subtitle {
            font-size: 1.2rem;
            color: #8892b0;
            margin-bottom: 2rem;
        }
        .status {
            background: rgba(255,255,255,0.1);
            border-radius: 1rem;
            padding: 1.5rem 2rem;
            display: inline-block;
        }
        .status-badge {
            display: inline-block;
            background: #ffc107;
            color: #000;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .status p {
            color: #ccd6f6;
        }
        .features {
            margin-top: 2rem;
            text-align: left;
            display: inline-block;
        }
        .features li {
            color: #8892b0;
            margin: 0.5rem 0;
            list-style: none;
        }
        .features li::before {
            content: '✓ ';
            color: #64ffda;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">🚐</div>
        <h1>VanLife News Aggregator</h1>
        <p class="subtitle">Новости о vanlife и автодомах со всего мира</p>

        <div class="status">
            <span class="status-badge">В разработке</span>
            <p>Сайт находится в стадии разработки</p>
        </div>

        <ul class="features">
            <li>Новости из 20+ стран</li>
            <li>Автоматический перевод на русский</li>
            <li>Умная категоризация</li>
            <li>Группировка похожих новостей</li>
        </ul>
    </div>
</body>
</html>
