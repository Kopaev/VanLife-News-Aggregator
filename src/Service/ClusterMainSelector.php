<?php

namespace App\Service;

use App\Core\Config;
use DateTimeImmutable;
use RuntimeException;

class ClusterMainSelector
{
    private int $freshnessHalfLifeHours;
    private float $translationBonus;
    private float $imageBonus;
    private float $publishedBonus;
    private float $viewsWeight;
    private int $viewsCap;
    private float $relevanceFloor;

    public function __construct(private readonly Config $config, private readonly LoggerService $logger)
    {
        $settings = $this->config->get('clustering.main_selection', []);

        if (!is_array($settings)) {
            throw new RuntimeException('Main selection config must be an array.');
        }

        $this->freshnessHalfLifeHours = (int)($settings['freshness_half_life_hours'] ?? 72);
        $this->translationBonus = (float)($settings['translation_bonus'] ?? 12);
        $this->imageBonus = (float)($settings['image_bonus'] ?? 6);
        $this->publishedBonus = (float)($settings['published_bonus'] ?? 8);
        $this->viewsWeight = (float)($settings['views_weight'] ?? 0.05);
        $this->viewsCap = (int)($settings['views_cap'] ?? 200);
        $this->relevanceFloor = (float)($settings['relevance_floor'] ?? 50);
    }

    /**
     * @param array<int, array{id?: int}> $articles
     */
    public function selectMainArticleId(array $articles): ?int
    {
        $bestId = null;
        $bestScore = 0.0;

        foreach ($articles as $article) {
            $score = $this->scoreArticle($article);

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestId = $article['id'] ?? null;
            }
        }

        return $bestId ? (int)$bestId : null;
    }

    private function scoreArticle(array $article): float
    {
        $base = (float)($article['ai_relevance_score'] ?? $this->relevanceFloor);
        $base += !empty($article['title_ru']) ? $this->translationBonus : 0.0;
        $base += !empty($article['image_url']) ? $this->imageBonus : 0.0;
        $base += (($article['status'] ?? null) === 'published') ? $this->publishedBonus : 0.0;

        $views = (int)($article['views_count'] ?? 0);
        $base += min($views, $this->viewsCap) * $this->viewsWeight;

        $freshness = $this->freshnessMultiplier($article['published_at'] ?? null);

        return round($base * $freshness, 4);
    }

    private function freshnessMultiplier(?string $publishedAt): float
    {
        if ($this->freshnessHalfLifeHours <= 0 || $publishedAt === null) {
            return 1.0;
        }

        try {
            $published = new DateTimeImmutable($publishedAt);
        } catch (\Throwable $e) {
            $this->logger->warning('ClusterMainSelector', 'Failed to parse published date', [
                'published_at' => $publishedAt,
                'error' => $e->getMessage(),
            ]);

            return 1.0;
        }

        $hoursAgo = max(0, (time() - $published->getTimestamp()) / 3600);

        if ($hoursAgo <= 0) {
            return 1.0;
        }

        $decay = -log(2) / max($this->freshnessHalfLifeHours, 1);

        return exp($decay * $hoursAgo);
    }
}
