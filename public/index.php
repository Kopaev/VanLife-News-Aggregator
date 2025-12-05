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

use App\Controller\AdminController;
use App\Controller\ApiController;
use App\Controller\ArticleController;
use App\Controller\ClusterController;
use App\Controller\HomeController;
use App\Core\App;
use App\Core\Config;
use App\Core\Database;
use App\Core\Response;
use App\Core\Router;
use App\Repository\AdminRepository;
use App\Repository\ArticleRepository;
use App\Repository\ClusterRepository;
use App\Repository\SourceRepository;
use App\Service\AuthService;

$config = Config::loadDefault(BASE_PATH);
$router = new Router();
$database = new Database($config);
$app = new App($config, $router, $database);

// --- Repositories ---
$articleRepository = new ArticleRepository($database);
$clusterRepository = new ClusterRepository($database);
$sourceRepository = new SourceRepository($database);
$adminRepository = new AdminRepository($database);

// --- Services ---
$authService = new AuthService($adminRepository, $config);

// --- Controllers ---
$homeController = new HomeController($articleRepository, $clusterRepository, $database);
$articleController = new ArticleController($articleRepository);
$clusterController = new ClusterController($clusterRepository, $articleRepository);
$apiController = new ApiController($articleRepository, $clusterRepository, $database);
$adminController = new AdminController($authService, $articleRepository, $sourceRepository, $database);

// --- Public Routes ---
$router->get('/', [$homeController, 'index']);
$router->get('/news/{slug}', [$articleController, 'show']);
$router->get('/clusters', [$clusterController, 'index']);
$router->get('/clusters/{slug}', [$clusterController, 'show']);

// --- API Routes ---
$router->get('/api/filters', [$apiController, 'filters']);
$router->get('/api/news', [$apiController, 'news']);
$router->get('/api/clusters', [$apiController, 'clusters']);

// --- Admin Routes ---
$router->get('/admin/login', [$adminController, 'loginForm']);
$router->post('/admin/login', [$adminController, 'login']);
$router->post('/admin/logout', [$adminController, 'logout']);
$router->get('/admin', [$adminController, 'dashboard']);
$router->get('/admin/moderation', [$adminController, 'moderation']);
$router->post('/admin/article/{id}/approve', [$adminController, 'approve']);
$router->post('/admin/article/{id}/reject', [$adminController, 'reject']);
$router->get('/admin/sources', [$adminController, 'sources']);
$router->post('/admin/source/{id}/toggle', [$adminController, 'toggleSource']);
$router->get('/admin/logs', [$adminController, 'logs']);

// --- System Routes ---
$router->get('/health', function () use ($config, $database): Response {
    // Check database connection
    $dbStatus = 'ok';
    try {
        $database->fetchOne('SELECT 1');
    } catch (\Throwable) {
        $dbStatus = 'error';
    }

    return Response::json([
        'status' => $dbStatus === 'ok' ? 'ok' : 'degraded',
        'app' => $config->get('app.name'),
        'environment' => $config->get('app.env'),
        'database' => $dbStatus,
        'timestamp' => time(),
        'version' => '1.0.0',
    ]);
});

// Sitemap
$router->get('/sitemap.xml', function (): Response {
    $sitemapPath = BASE_PATH . '/public/sitemap.xml';

    if (!file_exists($sitemapPath)) {
        return Response::text('Sitemap not found. Run: php scripts/generate_sitemap.php', 404);
    }

    $content = file_get_contents($sitemapPath);
    return Response::xml($content);
});

// Robots.txt
$router->get('/robots.txt', function () use ($config): Response {
    $siteUrl = $config->get('app.url') ?: 'https://news.vanlife.bez.coffee';
    $isProduction = $config->get('app.env') === 'production';

    if ($isProduction) {
        $content = "User-agent: *\n";
        $content .= "Allow: /\n\n";
        $content .= "Disallow: /admin\n";
        $content .= "Disallow: /api/\n\n";
        $content .= "Sitemap: {$siteUrl}/sitemap.xml\n";
    } else {
        // Block indexing on non-production environments
        $content = "User-agent: *\n";
        $content .= "Disallow: /\n";
    }

    return Response::text($content);
});

$app->run($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');

