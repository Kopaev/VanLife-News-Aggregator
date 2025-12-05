<?php

namespace App\Repository;

use App\Core\Database;
use App\Model\Translation;

class TranslationRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    public function findByArticleAndLanguage(int $articleId, string $targetLanguage): ?array
    {
        return $this->db->fetch(
            'SELECT * FROM translations WHERE article_id = ? AND target_language = ?',
            [$articleId, $targetLanguage]
        );
    }

    public function saveOrUpdate(Translation $translation): int
    {
        $this->db->execute(
            'INSERT INTO translations (article_id, target_language, title, summary, provider)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE title = VALUES(title), summary = VALUES(summary), provider = VALUES(provider)',
            [
                $translation->article_id,
                $translation->target_language,
                $translation->title,
                $translation->summary,
                $translation->provider,
            ]
        );

        return $this->db->lastInsertId();
    }
}
