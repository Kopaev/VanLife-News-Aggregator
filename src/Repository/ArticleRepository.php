<?php

namespace App\Repository;

use App\Core\Database;
use App\Model\Article;

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

    public function getArticlesForModeration(int $limit = 20): array
    {
        return $this->db->fetchAll(
            'SELECT a.*, s.name AS source_name FROM articles a
             LEFT JOIN sources s ON s.id = a.source_id
             WHERE a.ai_processed_at IS NOT NULL
               AND a.moderated_at IS NULL
               AND a.status IN ("published", "moderation")
             ORDER BY a.ai_processed_at DESC
             LIMIT ?',
            [$limit]
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
        $this->db->execute(
            'UPDATE articles
             SET title_ru = ?, summary_ru = ?, updated_at = CURRENT_TIMESTAMP
             WHERE id = ?',
            [
                $titleRu,
                $summaryRu,
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
}
