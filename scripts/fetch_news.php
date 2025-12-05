<?php
// scripts/fetch_news.php

declare(strict_types=1);

// Set base path
define('BASE_PATH', dirname(__DIR__));

// Autoloader
require_once BASE_PATH . '/vendor/autoload.php';

use App\Core\Config;
use App\Core\Database;
use App\Repository\ArticleRepository;
use App\Repository\SourceRepository;
use App\Service\GoogleNewsUrlDecoder;
use App\Service\LoggerService;
use App\Service\NewsFetcher;

// Config
$config = Config::loadDefault(BASE_PATH);

// Logger
$logger = new LoggerService($config);

// Database
try {
    $database = new Database($config);
} catch (PDOException $e) {
    $logger->error('DB Connection', 'Failed to connect to the database in cron script.', ['error' => $e->getMessage()]);
    exit(1);
}

// Services and Repositories
$urlDecoder = new GoogleNewsUrlDecoder($database, $logger, $config);
$sourceRepository = new SourceRepository($database);
$articleRepository = new ArticleRepository($database);
$newsFetcher = new NewsFetcher($logger, $urlDecoder, $sourceRepository, $articleRepository);

// Run the fetcher
try {
    echo "Starting news fetching process..." . PHP_EOL;
    $savedCount = $newsFetcher->fetchAllSources();
    echo "Successfully fetched and saved $savedCount new articles." . PHP_EOL;
    $logger->info('Cron', "News fetching process completed. Saved $savedCount new articles.");
} catch (Exception $e) {
    echo "An error occurred: " . $e->getMessage() . PHP_EOL;
    $logger->error('Cron', 'News fetching process failed.', ['error' => $e->getMessage()]);
    exit(1);
}

exit(0);
