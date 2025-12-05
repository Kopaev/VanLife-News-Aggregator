<?php

namespace App\Repository;

use App\Core\Database;
use App\Model\Cluster;
use App\Service\TextSanitizer;
use RuntimeException;

class ClusterRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    public function createFromArticles(array $primaryArticle, array $relatedArticles = []): int
    {
        $cluster = new Cluster();
        $cluster->title_ru = $this->resolveTitle($primaryArticle);
        $cluster->summary_ru = $this->resolveSummary($primaryArticle);
        $cluster->category_slug = $primaryArticle['category_slug'] ?? null;
        $cluster->countries = $this->collectCountries($primaryArticle, $relatedArticles);
        $cluster->articles_count = count($relatedArticles) + 1;
        $cluster->first_published_at = $this->resolveFirstPublishedAt($primaryArticle, $relatedArticles);
        $cluster->last_updated_at = $this->resolveLastUpdatedAt($primaryArticle, $relatedArticles);
        $cluster->main_article_id = $primaryArticle['id'] ?? null;
        $cluster->slug = $this->generateUniqueSlug($cluster->title_ru);

        $this->db->execute(
            'INSERT INTO clusters (title_ru, slug, summary_ru, main_article_id, category_slug, articles_count, countries, first_published_at, last_updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $cluster->title_ru,
                $cluster->slug,
                $cluster->summary_ru,
                $cluster->main_article_id,
                $cluster->category_slug,
                $cluster->articles_count,
                json_encode($cluster->countries, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                $cluster->first_published_at,
                $cluster->last_updated_at,
            ]
        );

        return $this->db->lastInsertId();
    }

    public function recalculateStats(int $clusterId): void
    {
        $stats = $this->db->fetch(
            'SELECT COUNT(*) AS total,
                    MIN(published_at) AS first_published_at,
                    MAX(published_at) AS last_updated_at
             FROM articles
             WHERE cluster_id = ?',
            [$clusterId]
        );

        if ($stats === null) {
            throw new RuntimeException('Failed to recalculate cluster stats: cluster not found or has no articles.');
        }

        $countries = $this->db->fetchAll(
            'SELECT DISTINCT country_code FROM articles WHERE cluster_id = ? AND country_code IS NOT NULL',
            [$clusterId]
        );
        $countryCodes = array_values(array_filter(array_column($countries, 'country_code')));

        $categoryRow = $this->db->fetch(
            'SELECT category_slug, COUNT(*) AS cnt
             FROM articles
             WHERE cluster_id = ? AND category_slug IS NOT NULL
             GROUP BY category_slug
             ORDER BY cnt DESC
             LIMIT 1',
            [$clusterId]
        );
        $categorySlug = $categoryRow['category_slug'] ?? null;

        $this->db->execute(
            'UPDATE clusters
             SET articles_count = ?,
                 first_published_at = ?,
                 last_updated_at = ?,
                 countries = ?,
                 category_slug = COALESCE(?, category_slug)
             WHERE id = ?',
            [
                (int)($stats['total'] ?? 0),
                $stats['first_published_at'],
                $stats['last_updated_at'],
                json_encode($countryCodes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                $categorySlug,
                $clusterId,
            ]
        );
    }

    public function setMainArticle(int $clusterId, int $articleId): void
    {
        $this->db->execute(
            'UPDATE clusters SET main_article_id = ? WHERE id = ?',
            [$articleId, $clusterId]
        );
    }

    public function getLatestClusters(int $limit = 12): array
    {
        $clusters = $this->db->fetchAll(
            'SELECT c.*,
                    cat.name_ru AS category_name,
                    cat.icon AS category_icon,
                    cat.color AS category_color,
                    ma.slug AS main_article_slug,
                    ma.status AS main_article_status,
                    COALESCE(ma.title_ru, ma.original_title) AS main_display_title,
                    COALESCE(ma.summary_ru, ma.original_summary) AS main_display_summary,
                    ma.image_url AS main_image_url
             FROM clusters c
             LEFT JOIN categories cat ON cat.slug = c.category_slug
             LEFT JOIN articles ma ON ma.id = c.main_article_id
             WHERE c.is_active = 1
             ORDER BY c.last_updated_at DESC
             LIMIT ?',
            [$limit]
        );

        return $this->hydrateCountries($clusters);
    }

    public function findBySlug(string $slug): ?array
    {
        $cluster = $this->db->fetch(
            'SELECT c.*,
                    cat.name_ru AS category_name,
                    cat.icon AS category_icon,
                    cat.color AS category_color,
                    ma.slug AS main_article_slug,
                    ma.status AS main_article_status,
                    COALESCE(ma.title_ru, ma.original_title) AS main_display_title,
                    COALESCE(ma.summary_ru, ma.original_summary) AS main_display_summary,
                    ma.image_url AS main_image_url
             FROM clusters c
             LEFT JOIN categories cat ON cat.slug = c.category_slug
             LEFT JOIN articles ma ON ma.id = c.main_article_id
             WHERE c.slug = ?
               AND c.is_active = 1
             LIMIT 1',
            [$slug]
        );

        if ($cluster === null) {
            return null;
        }

        $hydrated = $this->hydrateCountries([$cluster]);

        return $hydrated[0] ?? $cluster;
    }

    private function hydrateCountries(array $clusters): array
    {
        if (empty($clusters)) {
            return $clusters;
        }

        $allCodes = [];

        foreach ($clusters as &$cluster) {
            $cluster['country_codes'] = $this->decodeCountries($cluster['countries'] ?? null);

            foreach ($cluster['country_codes'] as $code) {
                if (!in_array($code, $allCodes, true)) {
                    $allCodes[] = $code;
                }
            }

            if (empty($cluster['main_display_title'])) {
                $cluster['main_display_title'] = $cluster['title_ru'];
            }

            if (empty($cluster['main_display_summary'])) {
                $cluster['main_display_summary'] = $cluster['summary_ru'] ?? null;
            }
        }
        unset($cluster);

        if (empty($allCodes)) {
            return $clusters;
        }

        $countryMap = $this->loadCountriesMap($allCodes);

        foreach ($clusters as &$cluster) {
            $cluster['countries_meta'] = array_values(array_filter(
                array_map(
                    static fn(string $code) => $countryMap[$code] ?? null,
                    $cluster['country_codes']
                )
            ));
        }
        unset($cluster);

        return $clusters;
    }

    private function decodeCountries(mixed $value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);

            if (is_array($decoded)) {
                return array_values(array_filter(array_map('strtoupper', $decoded)));
            }
        }

        if (is_array($value)) {
            return array_values(array_filter(array_map('strtoupper', $value)));
        }

        return [];
    }

    private function loadCountriesMap(array $codes): array
    {
        $codes = array_values(array_unique(array_filter($codes)));

        if (empty($codes)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($codes), '?'));
        $rows = $this->db->fetchAll(
            "SELECT code, name_ru, flag_emoji FROM countries WHERE code IN ({$placeholders})",
            $codes
        );

        $map = [];
        foreach ($rows as $row) {
            if (!empty($row['code'])) {
                $map[strtoupper($row['code'])] = $row;
            }
        }

        return $map;
    }

    private function resolveTitle(array $article): string
    {
        $title = $article['title_ru'] ?? $article['original_title'] ?? '';
        $sanitized = TextSanitizer::sanitize((string)$title);

        return $sanitized !== '' ? $sanitized : 'Без названия';
    }

    private function resolveSummary(array $article): ?string
    {
        $summary = $article['summary_ru'] ?? $article['original_summary'] ?? null;

        if ($summary === null) {
            return null;
        }

        $sanitized = TextSanitizer::sanitize((string)$summary);

        return $sanitized !== '' ? $sanitized : null;
    }

    private function collectCountries(array $primary, array $related): array
    {
        $countries = [];

        $push = static function (?string $code) use (&$countries): void {
            $normalized = $code ? strtoupper($code) : null;
            if ($normalized !== null && $normalized !== '' && !in_array($normalized, $countries, true)) {
                $countries[] = $normalized;
            }
        };

        $push($primary['country_code'] ?? null);

        foreach ($related as $article) {
            $push($article['country_code'] ?? null);
        }

        return $countries;
    }

    private function resolveFirstPublishedAt(array $primary, array $related): string
    {
        $dates = [$primary['published_at'] ?? null];

        foreach ($related as $article) {
            $dates[] = $article['published_at'] ?? null;
        }

        $dates = array_filter($dates);

        return $dates ? min($dates) : date('Y-m-d H:i:s');
    }

    private function resolveLastUpdatedAt(array $primary, array $related): string
    {
        $dates = [$primary['published_at'] ?? null];

        foreach ($related as $article) {
            $dates[] = $article['published_at'] ?? null;
        }

        $dates = array_filter($dates);

        return $dates ? max($dates) : date('Y-m-d H:i:s');
    }

    private function generateUniqueSlug(string $title): string
    {
        $base = $this->slugify($title);
        $slug = $base;
        $counter = 1;

        while ($this->slugExists($slug)) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    private function slugExists(string $slug): bool
    {
        $row = $this->db->fetch('SELECT id FROM clusters WHERE slug = ? LIMIT 1', [$slug]);

        return $row !== null;
    }

    private function slugify(string $text): string
    {
        $text = TextSanitizer::sanitize($text);
        $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text) ?: $text;
        $text = strtolower((string)$text);
        $text = preg_replace('/[^a-z0-9]+/i', '-', $text) ?? '';
        $text = trim((string)$text, '-');

        if ($text === '') {
            $text = 'cluster';
        }

        return $text;
    }

    /**
     * Get filtered clusters with pagination
     */
    public function getFilteredClusters(
        ?string $category = null,
        ?string $country = null,
        int $limit = 20,
        int $offset = 0
    ): array {
        [$whereClause, $params] = $this->buildFilterConditions($category, $country);
        $params[] = $limit;
        $params[] = $offset;

        $clusters = $this->db->fetchAll(
            "SELECT c.*,
                    cat.name_ru AS category_name,
                    cat.icon AS category_icon,
                    cat.color AS category_color,
                    ma.slug AS main_article_slug,
                    ma.status AS main_article_status,
                    COALESCE(ma.title_ru, ma.original_title) AS main_display_title,
                    COALESCE(ma.summary_ru, ma.original_summary) AS main_display_summary,
                    ma.image_url AS main_image_url
             FROM clusters c
             LEFT JOIN categories cat ON cat.slug = c.category_slug
             LEFT JOIN articles ma ON ma.id = c.main_article_id
             WHERE {$whereClause}
             ORDER BY c.last_updated_at DESC
             LIMIT ? OFFSET ?",
            $params
        );

        return $this->hydrateCountries($clusters);
    }

    /**
     * Get count of filtered clusters
     */
    public function getFilteredCount(
        ?string $category = null,
        ?string $country = null
    ): int {
        [$whereClause, $params] = $this->buildFilterConditions($category, $country);

        return (int)$this->db->fetchOne(
            "SELECT COUNT(*) FROM clusters c WHERE {$whereClause}",
            $params
        );
    }

    /**
     * Build WHERE clause and parameters for filtering
     */
    private function buildFilterConditions(?string $category, ?string $country): array
    {
        $conditions = ['c.is_active = 1'];
        $params = [];

        // Filter by category
        if ($category) {
            $conditions[] = 'c.category_slug = ?';
            $params[] = $category;
        }

        // Filter by country - check if country code is in the JSON array
        if ($country) {
            $conditions[] = 'JSON_CONTAINS(c.countries, ?)';
            $params[] = json_encode($country);
        }

        return [implode(' AND ', $conditions), $params];
    }
}
