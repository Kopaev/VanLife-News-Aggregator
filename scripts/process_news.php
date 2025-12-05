<?php
// scripts/process_news.php

declare(strict_types=1);

// Set base path
define('BASE_PATH', dirname(__DIR__));

// Autoloader
require_once BASE_PATH . '/vendor/autoload.php';

use App\AI\OpenAIProvider;
use App\Core\Config;
use App\Core\Database;
use App\Repository\ArticleRepository;
use App\Repository\TranslationRepository;
use App\Service\LoggerService;
use App\Service\ModerationService;
use App\Service\NewsProcessor;
use App\Service\TranslationService;

// Config
$config = Config::loadDefault(BASE_PATH);

// Skip if disabled
if (!$config->get('cron.process_enabled', true)) {
    echo "Processing cron is disabled via config." . PHP_EOL;
    exit(0);
}

// Logger
$logger = new LoggerService($config);

// Database
try {
    $database = new Database($config);
} catch (PDOException $e) {
    $logger->error('DB Connection', 'Failed to connect to the database in process cron script.', ['error' => $e->getMessage()]);
    exit(1);
}

// Dependencies
$articleRepository = new ArticleRepository($database);
$translationRepository = new TranslationRepository($database);

try {
    $aiProvider = new OpenAIProvider($config, $logger);
} catch (Throwable $e) {
    $logger->error('OpenAIProvider', 'Failed to initialize OpenAI provider.', ['error' => $e->getMessage()]);
    exit(1);
}

$newsProcessor = new NewsProcessor($aiProvider, $logger, $articleRepository);
$translationService = new TranslationService($aiProvider, $logger, $articleRepository, $translationRepository);
$moderationService = new ModerationService($articleRepository, $logger);

$relevanceBatch = (int)$config->get('processing.relevance_batch', 10);
$translationBatch = (int)$config->get('processing.translation_batch', 10);
$moderationBatch = (int)$config->get('processing.moderation_batch', 20);

try {
    echo "Starting AI relevance processing..." . PHP_EOL;
    $processed = $newsProcessor->processRelevance($relevanceBatch);
    echo "AI processed: {$processed}" . PHP_EOL;
} catch (Throwable $e) {
    $logger->error('Cron', 'Failed during relevance processing.', ['error' => $e->getMessage()]);
}

echo "Starting translations to Russian..." . PHP_EOL;
try {
    $translated = $translationService->translatePending($translationBatch);
    echo "Translated: {$translated}" . PHP_EOL;
} catch (Throwable $e) {
    $logger->error('Cron', 'Failed during translation stage.', ['error' => $e->getMessage()]);
}

echo "Starting moderation review..." . PHP_EOL;
try {
    $moderated = $moderationService->moderatePending($moderationBatch);
    echo "Moderated: {$moderated}" . PHP_EOL;
} catch (Throwable $e) {
    $logger->error('Cron', 'Failed during moderation stage.', ['error' => $e->getMessage()]);
}

echo "Processing pipeline completed." . PHP_EOL;
$logger->info('Cron', 'Processing pipeline completed', [
    'relevance' => $processed ?? 0,
    'translations' => $translated ?? 0,
    'moderated' => $moderated ?? 0,
]);

exit(0);
