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
        return $this->db->fetchAll('SELECT * FROM articles ORDER BY published_at DESC LIMIT ?', [$limit]);
    }

    public function findBySlug(string $slug): ?array
    {
        return $this->db->fetch('SELECT * FROM articles WHERE slug = ?', [$slug]);
    }
}
