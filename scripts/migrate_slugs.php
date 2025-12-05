#!/usr/bin/env php
<?php

/**
 * Migration script to generate slugs for existing articles
 *
 * Run once to populate slug field for all articles that don't have one.
 * Safe to run multiple times - only updates articles with NULL or empty slugs.
 *
 * Usage:
 *   php scripts/migrate_slugs.php [--limit=100]
 */

declare(strict_types=1);

// Autoload
require_once dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\Config;
use App\Core\Database;
use App\Repository\ArticleRepository;

// Load environment
$envPath = dirname(__DIR__) . '/.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) {
            continue;
        }
        if (str_contains($line, '=')) {
            putenv($line);
            [$key, $value] = explode('=', $line, 2);
            $_ENV[$key] = $value;
        }
    }
}

// Parse arguments
$options = getopt('', ['limit::', 'dry-run']);
$limit = isset($options['limit']) ? (int)$options['limit'] : 100;
$dryRun = isset($options['dry-run']);

echo "=== Slug Migration Script ===\n";
echo "Mode: " . ($dryRun ? "DRY RUN (no changes will be made)" : "LIVE") . "\n";
echo "Batch limit: {$limit}\n\n";

try {
    // Initialize database
    $config = new Config(dirname(__DIR__) . '/config');
    $db = new Database(
        getenv('DB_HOST') ?: 'localhost',
        getenv('DB_NAME') ?: 'vanlife_news',
        getenv('DB_USER') ?: 'root',
        getenv('DB_PASS') ?: ''
    );

    $articleRepo = new ArticleRepository($db);

    // Count total articles without slugs
    $totalCount = (int)$db->fetchOne(
        'SELECT COUNT(*) FROM articles WHERE slug IS NULL OR slug = ""'
    );

    echo "Total articles without slugs: {$totalCount}\n";

    if ($totalCount === 0) {
        echo "All articles already have slugs. Nothing to do.\n";
        exit(0);
    }

    // Get articles without slugs
    $articles = $articleRepo->getArticlesWithoutSlugs($limit);
    $count = count($articles);
    $updated = 0;
    $errors = 0;

    echo "Processing {$count} articles...\n\n";

    foreach ($articles as $article) {
        $id = (int)$article['id'];
        $title = $article['title_ru'] ?? $article['original_title'] ?? '';

        if (empty($title)) {
            echo "[SKIP] Article {$id}: No title available\n";
            $errors++;
            continue;
        }

        $slug = $articleRepo->generateUniqueSlug($id, $title);

        if ($dryRun) {
            echo "[DRY] Article {$id}: Would set slug to '{$slug}'\n";
        } else {
            try {
                $articleRepo->updateSlug($id, $slug);
                echo "[OK] Article {$id}: '{$slug}'\n";
                $updated++;
            } catch (\Exception $e) {
                echo "[ERROR] Article {$id}: {$e->getMessage()}\n";
                $errors++;
            }
        }
    }

    echo "\n=== Summary ===\n";
    echo "Processed: {$count}\n";
    echo "Updated: {$updated}\n";
    echo "Errors: {$errors}\n";
    echo "Remaining: " . max(0, $totalCount - $updated) . "\n";

    if ($totalCount > $count && !$dryRun) {
        echo "\nRun again to process more articles.\n";
    }

} catch (\Exception $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
