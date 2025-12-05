#!/usr/bin/env php
<?php

/**
 * Sitemap generator script
 *
 * Generates sitemap.xml with all published articles and clusters.
 *
 * Usage:
 *   php scripts/generate_sitemap.php [--output=/path/to/sitemap.xml]
 */

declare(strict_types=1);

// Autoload
require_once dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\Database;
use App\Repository\ArticleRepository;
use App\Repository\ClusterRepository;

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
$options = getopt('', ['output::']);
$outputPath = $options['output'] ?? dirname(__DIR__) . '/public/sitemap.xml';
$siteUrl = getenv('APP_URL') ?: 'https://news.vanlife.bez.coffee';

echo "=== Sitemap Generator ===\n";
echo "Output: {$outputPath}\n";
echo "Site URL: {$siteUrl}\n\n";

try {
    // Initialize database
    $db = new Database(
        getenv('DB_HOST') ?: 'localhost',
        getenv('DB_NAME') ?: 'vanlife_news',
        getenv('DB_USER') ?: 'root',
        getenv('DB_PASS') ?: ''
    );

    $articleRepo = new ArticleRepository($db);
    $clusterRepo = new ClusterRepository($db);

    // Start XML
    $xml = new XMLWriter();
    $xml->openMemory();
    $xml->setIndent(true);
    $xml->setIndentString('  ');

    $xml->startDocument('1.0', 'UTF-8');
    $xml->startElement('urlset');
    $xml->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
    $xml->writeAttribute('xmlns:news', 'http://www.google.com/schemas/sitemap-news/0.9');

    // Add home page
    $xml->startElement('url');
    $xml->writeElement('loc', $siteUrl . '/');
    $xml->writeElement('changefreq', 'hourly');
    $xml->writeElement('priority', '1.0');
    $xml->endElement();

    // Add clusters list page
    $xml->startElement('url');
    $xml->writeElement('loc', $siteUrl . '/clusters');
    $xml->writeElement('changefreq', 'daily');
    $xml->writeElement('priority', '0.8');
    $xml->endElement();

    $articleCount = 0;
    $clusterCount = 0;

    // Add published articles
    $articles = $articleRepo->getPublishedArticlesForSitemap();
    foreach ($articles as $article) {
        if (empty($article['slug'])) {
            continue;
        }

        $xml->startElement('url');
        $xml->writeElement('loc', $siteUrl . '/news/' . $article['slug']);

        $lastmod = $article['updated_at'] ?? $article['published_at'];
        if ($lastmod) {
            $xml->writeElement('lastmod', date('c', strtotime($lastmod)));
        }

        $xml->writeElement('changefreq', 'weekly');
        $xml->writeElement('priority', '0.6');
        $xml->endElement();

        $articleCount++;
    }

    // Add clusters
    $clusters = $clusterRepo->getPublishedClustersForSitemap();
    foreach ($clusters as $cluster) {
        if (empty($cluster['slug'])) {
            continue;
        }

        $xml->startElement('url');
        $xml->writeElement('loc', $siteUrl . '/clusters/' . $cluster['slug']);

        $lastmod = $cluster['last_updated_at'] ?? $cluster['created_at'];
        if ($lastmod) {
            $xml->writeElement('lastmod', date('c', strtotime($lastmod)));
        }

        $xml->writeElement('changefreq', 'daily');
        $xml->writeElement('priority', '0.7');
        $xml->endElement();

        $clusterCount++;
    }

    $xml->endElement(); // urlset
    $xml->endDocument();

    // Write to file
    $content = $xml->outputMemory();
    file_put_contents($outputPath, $content);

    echo "=== Summary ===\n";
    echo "Articles: {$articleCount}\n";
    echo "Clusters: {$clusterCount}\n";
    echo "Total URLs: " . ($articleCount + $clusterCount + 2) . "\n";
    echo "\nSitemap generated successfully!\n";

} catch (\Exception $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
