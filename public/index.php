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

use App\Controller\ArticleController;
use App\Controller\HomeController;
use App\Core\App;
use App\Core\Config;
use App\Core\Database;
use App\Core\Response;
use App\Core\Router;
use App\Repository\ArticleRepository;

$config = Config::loadDefault(BASE_PATH);
$router = new Router();
$database = new Database($config);
$app = new App($config, $router, $database);

// --- Repositories ---
$articleRepository = new ArticleRepository($database);

// --- Controllers ---
$homeController = new HomeController($articleRepository);
$articleController = new ArticleController($articleRepository);


$router->get('/', [$homeController, 'index']);
$router->get('/news/{slug}', [$articleController, 'show']);

$router->get('/health', function () use ($config): Response {
    return Response::json([
        'status' => 'ok',
        'app' => $config->get('app.name'),
        'environment' => $config->get('app.env'),
        'timestamp' => time(),
    ]);
});

$app->run($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');

