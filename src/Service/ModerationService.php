<?php

namespace App\Service;

use App\Repository\ArticleRepository;
use DateTimeImmutable;

class ModerationService
{
    private const ALLOWED_STATUSES = [
        'new',
        'processing',
        'published',
        'moderation',
        'rejected',
        'duplicate',
    ];

    private array $rules;

    public function __construct(
        private readonly ArticleRepository $articleRepository,
        private readonly LoggerService $logger
    ) {
        $this->rules = require dirname(__DIR__, 2) . '/config/moderation.php';
    }

    public function moderatePending(int $limit = 20): int
    {
        $articles = $this->articleRepository->getArticlesForModeration($limit);
        $processed = 0;

        foreach ($articles as $article) {
            try {
                [$status, $reason] = $this->decide($article);
                $moderatedAt = (new DateTimeImmutable('now'))->format('Y-m-d H:i:s');

                $this->articleRepository->updateModeration((int)$article['id'], $status, $reason, $moderatedAt);

                $this->logger->info('ModerationService', 'Article moderated', [
                    'article_id' => $article['id'],
                    'status' => $status,
                    'reason' => $reason,
                ]);

                $processed++;
            } catch (\Throwable $e) {
                $this->logger->error('ModerationService', 'Failed to moderate article', [
                    'article_id' => $article['id'] ?? null,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $processed;
    }

    private function decide(array $article): array
    {
        if ($this->matchesKeywords($article, $this->rules['auto_reject'] ?? [])) {
            return ['rejected', 'auto_reject_keyword'];
        }

        if ($this->matchesKeywords($article, $this->rules['require_moderation'] ?? [])) {
            return ['moderation', 'keyword_flag'];
        }

        $status = $this->normalizeStatus($article['status'] ?? null);
        $reason = $article['moderation_reason'] ?? null;

        $minScore = (int)($this->rules['min_relevance_score'] ?? 0);
        $score = $article['ai_relevance_score'] ?? null;
        if (is_numeric($score) && (int)$score < $minScore && $status === 'published') {
            $status = 'moderation';
            $reason = $reason ?: 'below_threshold';
        }

        return [$status, $reason];
    }

    private function matchesKeywords(array $article, array $keywords): bool
    {
        if (empty($keywords)) {
            return false;
        }

        $haystack = mb_strtolower(implode(' ', array_filter([
            $article['original_title'] ?? '',
            $article['original_summary'] ?? '',
            $article['title_ru'] ?? '',
            $article['summary_ru'] ?? '',
        ])));

        foreach ($keywords as $word) {
            $word = mb_strtolower((string)$word);
            if ($word !== '' && str_contains($haystack, $word)) {
                return true;
            }
        }

        return false;
    }

    private function normalizeStatus(mixed $status): string
    {
        if (!is_string($status)) {
            return 'moderation';
        }

        $normalized = strtolower($status);

        return in_array($normalized, self::ALLOWED_STATUSES, true) ? $normalized : 'moderation';
    }
}
