<?php
// scripts/cluster_news.php

declare(strict_types=1);

// Set base path
define('BASE_PATH', dirname(__DIR__));

// Autoloader
require_once BASE_PATH . '/vendor/autoload.php';

use App\Core\Config;
use App\Core\Database;
use App\Repository\ArticleRepository;
use App\Repository\ClusterRepository;
use App\Service\ClusterMainSelector;
use App\Service\ClusterManager;
use App\Service\ClusteringService;
use App\Service\LoggerService;

// Config
$config = Config::loadDefault(BASE_PATH);

// Skip if disabled
if (!$config->get('cron.cluster_enabled', true)) {
    echo "Clustering cron is disabled via config." . PHP_EOL;
    exit(0);
}

// Logger
$logger = new LoggerService($config);

// Database
try {
    $database = new Database($config);
} catch (\PDOException $e) {
    $logger->error('DB Connection', 'Failed to connect to the database in clustering cron script.', ['error' => $e->getMessage()]);
    exit(1);
}

// Dependencies
$articleRepository = new ArticleRepository($database);
$clusterRepository = new ClusterRepository($database);
$clusteringService = new ClusteringService($config, $logger);
$mainSelector = new ClusterMainSelector($config, $logger);
$clusterManager = new ClusterManager(
    $clusteringService,
    $articleRepository,
    $clusterRepository,
    $mainSelector,
    $logger,
    $config
);

$limit = null;
if (isset($argv[1]) && is_numeric($argv[1]) && (int)$argv[1] > 0) {
    $limit = (int)$argv[1];
}

echo "Starting clustering pipeline..." . PHP_EOL;

try {
    $processed = $clusterManager->clusterUnassigned($limit);
    echo "Clustered: {$processed}" . PHP_EOL;
} catch (\Throwable $e) {
    $logger->error('Cron', 'Failed during clustering pipeline.', ['error' => $e->getMessage()]);
    exit(1);
}

$logger->info('Cron', 'Clustering pipeline completed', [
    'processed' => $processed ?? 0,
    'limit' => $limit,
]);

echo "Clustering pipeline completed." . PHP_EOL;

exit(0);
