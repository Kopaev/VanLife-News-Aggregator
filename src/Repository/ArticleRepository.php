<?php

namespace App\Repository;

use App\Core\Database;
use App\Helper\SlugHelper;
use App\Model\Article;
use DateTimeImmutable;

class ArticleRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function save(Article $article): int
    {
        $this->db->execute(
            'INSERT INTO articles (source_id, external_id, original_title, original_summary, original_url, original_language, published_at, fetched_at, country_code, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $article->source_id,
                $article->external_id,
                $article->original_title,
                $article->original_summary,
                $article->original_url,
                $article->original_language,
                $article->published_at,
                $article->fetched_at,
                $article->country_code,
                $article->status,
            ]
        );

        return $this->db->lastInsertId();
    }

    public function isArticleExists(string $originalUrl): bool
    {
        $hash = md5($originalUrl);
        $count = $this->db->fetchOne('SELECT COUNT(*) FROM articles WHERE external_id = ?', [$hash]);
        return $count > 0;
    }

    public function getLatestArticles(int $limit = 50): array
    {
        return $this->db->fetchAll(
            'SELECT a.*,
                    COALESCE(a.title_ru, a.original_title) AS display_title,
                    COALESCE(a.summary_ru, a.original_summary) AS display_summary,
                    c.name_ru AS category_name,
                    c.icon AS category_icon,
                    c.color AS category_color,
                    country.name_ru AS country_name,
                    country.flag_emoji AS country_flag,
                    s.name AS source_name
             FROM articles a
             LEFT JOIN categories c ON c.slug = a.category_slug
             LEFT JOIN countries country ON country.code = a.country_code
             LEFT JOIN sources s ON s.id = a.source_id
             WHERE a.status IN ("published", "moderation")
             ORDER BY a.published_at DESC
             LIMIT ?',
            [$limit]
        );
    }

    public function findBySlug(string $slug): ?array
    {
        return $this->db->fetch(
            'SELECT a.*,
                    COALESCE(a.title_ru, a.original_title) AS display_title,
                    COALESCE(a.summary_ru, a.original_summary) AS display_summary,
                    c.name_ru AS category_name,
                    c.icon AS category_icon,
                    c.color AS category_color,
                    country.name_ru AS country_name,
                    country.flag_emoji AS country_flag,
                    s.name AS source_name
             FROM articles a
             LEFT JOIN categories c ON c.slug = a.category_slug
             LEFT JOIN countries country ON country.code = a.country_code
             LEFT JOIN sources s ON s.id = a.source_id
             WHERE a.slug = ?',
            [$slug]
        );
    }

    public function getArticlesForProcessing(int $limit = 10): array
    {
        return $this->db->fetchAll(
            'SELECT a.*, s.name AS source_name FROM articles a
             LEFT JOIN sources s ON s.id = a.source_id
             WHERE a.status = "new" ORDER BY a.published_at DESC LIMIT ?',
            [$limit]
        );
    }

    public function getArticlesForTranslation(int $limit = 10): array
    {
        return $this->db->fetchAll(
            'SELECT a.* FROM articles a
             LEFT JOIN translations t ON t.article_id = a.id AND t.target_language = "ru"
             WHERE a.original_language <> "ru"
               AND a.ai_processed_at IS NOT NULL
               AND (a.title_ru IS NULL OR a.summary_ru IS NULL OR t.id IS NULL)
               AND a.status IN ("published", "moderation")
             ORDER BY a.ai_processed_at DESC, a.published_at DESC
             LIMIT ?',
            [$limit]
        );
    }

    public function getArticlesForModeration(int $limit = 20, int $offset = 0): array
    {
        return $this->db->fetchAll(
            'SELECT a.*,
                    COALESCE(a.title_ru, a.original_title) AS display_title,
                    COALESCE(a.summary_ru, a.original_summary) AS display_summary,
                    c.name_ru AS category_name,
                    c.icon AS category_icon,
                    c.color AS category_color,
                    country.name_ru AS country_name,
                    country.flag_emoji AS country_flag,
                    s.name AS source_name
             FROM articles a
             LEFT JOIN categories c ON c.slug = a.category_slug
             LEFT JOIN countries country ON country.code = a.country_code
             LEFT JOIN sources s ON s.id = a.source_id
             WHERE a.status = "moderation"
             ORDER BY a.published_at DESC
             LIMIT ? OFFSET ?',
            [$limit, $offset]
        );
    }

    /**
     * Get count of articles awaiting moderation
     */
    public function getModerationCount(): int
    {
        $result = $this->db->fetchOne(
            'SELECT COUNT(*) as count FROM articles WHERE status = "moderation"'
        );
        return (int) ($result['count'] ?? 0);
    }

    /**
     * Update article status
     */
    public function updateStatus(int $articleId, string $status): void
    {
        $this->db->execute(
            'UPDATE articles SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?',
            [$status, $articleId]
        );
    }

    /**
     * Reject article with reason
     */
    public function rejectArticle(int $articleId, string $reason): void
    {
        $this->db->execute(
            'UPDATE articles SET status = "rejected", moderation_reason = ?, moderated_at = NOW(), updated_at = CURRENT_TIMESTAMP WHERE id = ?',
            [$reason, $articleId]
        );
    }

    public function updateProcessing(
        int $articleId,
        int $score,
        string $status,
        ?string $moderationReason,
        string $processedAt,
        ?string $categorySlug,
        ?string $countryCode,
        ?array $tags
    ): void {
        $tagsJson = $tags ? json_encode($tags, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;

        $this->db->execute(
            'UPDATE articles
             SET ai_relevance_score = ?, status = ?, moderation_reason = ?, ai_processed_at = ?,
                 category_slug = ?, country_code = ?, tags = ?
             WHERE id = ?',
            [
                $score,
                $status,
                $moderationReason,
                $processedAt,
                $categorySlug,
                $countryCode,
                $tagsJson,
                $articleId,
            ]
        );
    }

    public function updateTranslation(int $articleId, string $titleRu, ?string $summaryRu): void
    {
        // Generate unique slug from Russian title
        $slug = $this->generateUniqueSlug($articleId, $titleRu);

        $this->db->execute(
            'UPDATE articles
             SET title_ru = ?, summary_ru = ?, slug = ?, updated_at = CURRENT_TIMESTAMP
             WHERE id = ?',
            [
                $titleRu,
                $summaryRu,
                $slug,
                $articleId,
            ]
        );
    }

    public function updateModeration(int $articleId, string $status, ?string $reason, string $moderatedAt): void
    {
        $this->db->execute(
            'UPDATE articles
             SET status = ?, moderation_reason = ?, moderated_at = ?, updated_at = CURRENT_TIMESTAMP
             WHERE id = ?',
            [
                $status,
                $reason,
                $moderatedAt,
                $articleId,
            ]
        );
    }

    public function getArticlesForClustering(int $limit = 20): array
    {
        return $this->db->fetchAll(
            'SELECT id, cluster_id, title_ru, original_title, summary_ru, original_summary, tags,
                    category_slug, country_code, published_at, ai_relevance_score
             FROM articles
             WHERE cluster_id IS NULL
               AND status IN ("published", "moderation")
             ORDER BY published_at DESC
             LIMIT ?',
            [$limit]
        );
    }

    public function getCandidatesForClustering(int $excludeArticleId, int $hoursWindow, int $limit = 100): array
    {
        $fromDate = (new DateTimeImmutable(sprintf('-%d hours', $hoursWindow)))->format('Y-m-d H:i:s');

        return $this->db->fetchAll(
            'SELECT id, cluster_id, title_ru, original_title, summary_ru, original_summary, tags,
                    category_slug, country_code, published_at, ai_relevance_score
             FROM articles
             WHERE id <> ?
               AND status IN ("published", "moderation")
               AND published_at >= ?
             ORDER BY published_at DESC
             LIMIT ?',
            [$excludeArticleId, $fromDate, $limit]
        );
    }

    public function getClusterArticles(int $clusterId): array
    {
        return $this->db->fetchAll(
            'SELECT a.*, 
                    COALESCE(a.title_ru, a.original_title) AS display_title,
                    COALESCE(a.summary_ru, a.original_summary) AS display_summary,
                    c.name_ru AS category_name,
                    c.icon AS category_icon,
                    c.color AS category_color,
                    country.name_ru AS country_name,
                    country.flag_emoji AS country_flag
             FROM articles a
             LEFT JOIN categories c ON c.slug = a.category_slug
             LEFT JOIN countries country ON country.code = a.country_code
             WHERE a.cluster_id = ?
               AND a.status IN ("published", "moderation")
             ORDER BY a.published_at DESC',
            [$clusterId]
        );
    }

    public function assignToCluster(array $articleIds, int $clusterId): void
    {
        $ids = array_values(array_unique(array_map('intval', $articleIds)));

        if (empty($ids)) {
            return;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $params = array_merge([$clusterId], $ids);

        $this->db->execute(
            "UPDATE articles SET cluster_id = ?, updated_at = CURRENT_TIMESTAMP WHERE id IN ({$placeholders})",
            $params
        );
    }

    /**
     * Get filtered articles with pagination
     */
    public function getFilteredArticles(
        ?string $category = null,
        ?string $country = null,
        ?string $language = null,
        ?string $period = null,
        int $limit = 20,
        int $offset = 0
    ): array {
        [$whereClause, $params] = $this->buildFilterConditions($category, $country, $language, $period);
        $params[] = $limit;
        $params[] = $offset;

        return $this->db->fetchAll(
            "SELECT a.*,
                    COALESCE(a.title_ru, a.original_title) AS display_title,
                    COALESCE(a.summary_ru, a.original_summary) AS display_summary,
                    c.name_ru AS category_name,
                    c.icon AS category_icon,
                    c.color AS category_color,
                    country.name_ru AS country_name,
                    country.flag_emoji AS country_flag,
                    s.name AS source_name
             FROM articles a
             LEFT JOIN categories c ON c.slug = a.category_slug
             LEFT JOIN countries country ON country.code = a.country_code
             LEFT JOIN sources s ON s.id = a.source_id
             WHERE {$whereClause}
             ORDER BY a.published_at DESC
             LIMIT ? OFFSET ?",
            $params
        );
    }

    /**
     * Get count of filtered articles
     */
    public function getFilteredCount(
        ?string $category = null,
        ?string $country = null,
        ?string $language = null,
        ?string $period = null
    ): int {
        [$whereClause, $params] = $this->buildFilterConditions($category, $country, $language, $period);

        return (int)$this->db->fetchOne(
            "SELECT COUNT(*) FROM articles a WHERE {$whereClause}",
            $params
        );
    }

    /**
     * Build WHERE clause and parameters for filtering
     */
    private function buildFilterConditions(
        ?string $category,
        ?string $country,
        ?string $language,
        ?string $period
    ): array {
        $conditions = ['a.status IN ("published", "moderation")'];
        $params = [];

        // Filter by category
        if ($category) {
            $conditions[] = 'a.category_slug = ?';
            $params[] = $category;
        }

        // Filter by country
        if ($country) {
            $conditions[] = 'a.country_code = ?';
            $params[] = $country;
        }

        // Filter by original language
        if ($language) {
            $conditions[] = 'a.original_language = ?';
            $params[] = $language;
        }

        // Filter by period
        if ($period) {
            $dateCondition = $this->getPeriodDateCondition($period);
            if ($dateCondition) {
                $conditions[] = $dateCondition;
            }
        }

        return [implode(' AND ', $conditions), $params];
    }

    /**
     * Get date condition for period filter
     */
    private function getPeriodDateCondition(string $period): ?string
    {
        return match ($period) {
            'today' => 'a.published_at >= CURDATE()',
            'week' => 'a.published_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)',
            'month' => 'a.published_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)',
            'year' => 'a.published_at >= DATE_SUB(NOW(), INTERVAL 365 DAY)',
            default => null,
        };
    }

    /**
     * Generate a unique slug for an article
     *
     * Uses article ID prefix to guarantee uniqueness (format: {id}-{slug})
     *
     * @param int $articleId Article ID
     * @param string $title Article title (preferably Russian translation)
     * @return string Unique slug
     */
    public function generateUniqueSlug(int $articleId, string $title): string
    {
        // Use ID-prefixed slug for guaranteed uniqueness
        $slug = SlugHelper::generateWithId($articleId, $title);

        // Double-check uniqueness (shouldn't happen with ID prefix, but safety first)
        $counter = 1;
        $baseSlug = $slug;
        while ($this->slugExists($slug, $articleId)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Check if a slug already exists (excluding given article ID)
     *
     * @param string $slug Slug to check
     * @param int|null $excludeArticleId Article ID to exclude from check
     * @return bool True if slug exists
     */
    public function slugExists(string $slug, ?int $excludeArticleId = null): bool
    {
        if ($excludeArticleId !== null) {
            $row = $this->db->fetch(
                'SELECT id FROM articles WHERE slug = ? AND id != ? LIMIT 1',
                [$slug, $excludeArticleId]
            );
        } else {
            $row = $this->db->fetch(
                'SELECT id FROM articles WHERE slug = ? LIMIT 1',
                [$slug]
            );
        }

        return $row !== null;
    }

    /**
     * Update article slug
     *
     * @param int $articleId Article ID
     * @param string $slug New slug
     */
    public function updateSlug(int $articleId, string $slug): void
    {
        $this->db->execute(
            'UPDATE articles SET slug = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?',
            [$slug, $articleId]
        );
    }

    /**
     * Find article by ID
     *
     * @param int $articleId Article ID
     * @return array|null Article data or null
     */
    public function findById(int $articleId): ?array
    {
        return $this->db->fetch(
            'SELECT a.*,
                    COALESCE(a.title_ru, a.original_title) AS display_title,
                    COALESCE(a.summary_ru, a.original_summary) AS display_summary,
                    c.name_ru AS category_name,
                    c.icon AS category_icon,
                    c.color AS category_color,
                    country.name_ru AS country_name,
                    country.flag_emoji AS country_flag,
                    s.name AS source_name
             FROM articles a
             LEFT JOIN categories c ON c.slug = a.category_slug
             LEFT JOIN countries country ON country.code = a.country_code
             LEFT JOIN sources s ON s.id = a.source_id
             WHERE a.id = ?',
            [$articleId]
        );
    }

    /**
     * Get articles without slugs for migration
     *
     * @param int $limit Maximum number of articles to return
     * @return array Articles without slugs
     */
    public function getArticlesWithoutSlugs(int $limit = 100): array
    {
        return $this->db->fetchAll(
            'SELECT id, title_ru, original_title
             FROM articles
             WHERE slug IS NULL OR slug = ""
             ORDER BY published_at DESC
             LIMIT ?',
            [$limit]
        );
    }

    /**
     * Get all published articles for sitemap
     *
     * @return array Articles with slug, updated_at
     */
    public function getPublishedArticlesForSitemap(): array
    {
        return $this->db->fetchAll(
            'SELECT slug, updated_at, published_at
             FROM articles
             WHERE status = "published"
               AND slug IS NOT NULL
               AND slug != ""
             ORDER BY published_at DESC'
        );
    }
}
