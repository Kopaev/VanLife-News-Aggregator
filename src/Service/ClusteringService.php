<?php

namespace App\Service;

use App\Core\Config;
use DateTimeImmutable;
use RuntimeException;

class ClusteringService
{
    private float $minSimilarity;
    private int $candidateWindowHours;
    private int $timeDecayHours;
    private int $summaryLimit;
    private float $titleWeight;
    private float $summaryWeight;
    private float $tagsWeight;
    private float $metaBonusWeight;
    private array $stopwords;

    public function __construct(private readonly Config $config, private readonly LoggerService $logger)
    {
        $settings = $this->config->get('clustering', []);

        if (!is_array($settings)) {
            throw new RuntimeException('Clustering config must be an array.');
        }

        $weights = $settings['weights'] ?? [];
        $limits = $settings['limits'] ?? [];

        $this->minSimilarity = (float)($settings['min_similarity'] ?? 0.55);
        $this->candidateWindowHours = (int)($settings['candidate_window_hours'] ?? 120);
        $this->timeDecayHours = (int)($settings['time_decay_hours'] ?? 72);
        $this->summaryLimit = (int)($limits['summary_chars'] ?? 800);
        $this->titleWeight = (float)($weights['title'] ?? 0.6);
        $this->summaryWeight = (float)($weights['summary'] ?? 0.25);
        $this->tagsWeight = (float)($weights['tags'] ?? 0.1);
        $this->metaBonusWeight = (float)($weights['meta_bonus'] ?? 0.05);
        $this->stopwords = $this->mergeStopwords($settings['stopwords'] ?? []);
    }

    /**
     * @param array{id?: int} $article
     * @param array<int, array{id?: int}> $candidates
     * @return array<int, array{id?: int, score: float}>
     */
    public function findSimilar(array $article, array $candidates): array
    {
        $results = [];

        foreach ($candidates as $candidate) {
            if (($candidate['id'] ?? null) === ($article['id'] ?? null)) {
                continue;
            }

            if (!$this->isWithinTimeWindow($article, $candidate)) {
                continue;
            }

            $score = $this->computeSimilarity($article, $candidate);

            if ($score < $this->minSimilarity) {
                continue;
            }

            $results[] = [
                'id' => $candidate['id'] ?? null,
                'score' => $score,
            ];
        }

        usort($results, static fn(array $a, array $b) => $b['score'] <=> $a['score']);

        return $results;
    }

    /**
     * @param array{id?: int} $a
     * @param array{id?: int} $b
     */
    public function computeSimilarity(array $a, array $b): float
    {
        [$titleA, $titleB] = [
            (string)($a['title_ru'] ?? $a['original_title'] ?? ''),
            (string)($b['title_ru'] ?? $b['original_title'] ?? ''),
        ];

        [$summaryA, $summaryB] = [
            $this->limitSummary((string)($a['summary_ru'] ?? $a['original_summary'] ?? '')),
            $this->limitSummary((string)($b['summary_ru'] ?? $b['original_summary'] ?? '')),
        ];

        $titleScore = $this->textSimilarity($titleA, $titleB);
        $summaryScore = $this->textSimilarity($summaryA, $summaryB);
        $tagsScore = $this->tagsSimilarity($a['tags'] ?? null, $b['tags'] ?? null);
        $metaBonus = $this->metaBonus($a, $b);
        $timeDecay = $this->timeDecay($a['published_at'] ?? null, $b['published_at'] ?? null);

        $score = ($titleScore * $this->titleWeight)
            + ($summaryScore * $this->summaryWeight)
            + ($tagsScore * $this->tagsWeight)
            + ($metaBonus * $this->metaBonusWeight);

        $score = max(0.0, min(1.0, $score)) * $timeDecay;

        return round($score, 4);
    }

    private function limitSummary(string $text): string
    {
        if ($this->summaryLimit <= 0) {
            return $text;
        }

        return TextSanitizer::limit($text, $this->summaryLimit);
    }

    private function textSimilarity(string $a, string $b): float
    {
        $tokensA = $this->tokenize($a);
        $tokensB = $this->tokenize($b);

        if (empty($tokensA) || empty($tokensB)) {
            return 0.0;
        }

        $intersection = array_intersect($tokensA, $tokensB);
        $union = array_unique(array_merge($tokensA, $tokensB));

        if (count($union) === 0) {
            return 0.0;
        }

        return count($intersection) / count($union);
    }

    private function tagsSimilarity(mixed $rawA, mixed $rawB): float
    {
        $tagsA = $this->normalizeTags($rawA);
        $tagsB = $this->normalizeTags($rawB);

        if (empty($tagsA) || empty($tagsB)) {
            return 0.0;
        }

        $intersection = array_intersect($tagsA, $tagsB);
        $union = array_unique(array_merge($tagsA, $tagsB));

        if (count($union) === 0) {
            return 0.0;
        }

        return count($intersection) / count($union);
    }

    private function metaBonus(array $a, array $b): float
    {
        $bonus = 0.0;

        if (($a['category_slug'] ?? null) !== null && ($a['category_slug'] ?? null) === ($b['category_slug'] ?? null)) {
            $bonus += 1;
        }

        if (($a['country_code'] ?? null) !== null && ($a['country_code'] ?? null) === ($b['country_code'] ?? null)) {
            $bonus += 1;
        }

        return $bonus > 0 ? min(1.0, $bonus / 2) : 0.0;
    }

    private function timeDecay(?string $dateA, ?string $dateB): float
    {
        if ($this->timeDecayHours <= 0 || $dateA === null || $dateB === null) {
            return 1.0;
        }

        try {
            $a = new DateTimeImmutable($dateA);
            $b = new DateTimeImmutable($dateB);
        } catch (\Throwable $e) {
            $this->logger->warning('ClusteringService', 'Failed to parse dates for decay', [
                'date_a' => $dateA,
                'date_b' => $dateB,
                'error' => $e->getMessage(),
            ]);

            return 1.0;
        }

        $diffHours = abs($a->getTimestamp() - $b->getTimestamp()) / 3600;

        if ($diffHours <= 0) {
            return 1.0;
        }

        return exp(-$diffHours / $this->timeDecayHours);
    }

    private function isWithinTimeWindow(array $article, array $candidate): bool
    {
        if ($this->candidateWindowHours <= 0) {
            return true;
        }

        $baseline = $article['published_at'] ?? null;
        $other = $candidate['published_at'] ?? null;

        if ($baseline === null || $other === null) {
            return true;
        }

        try {
            $a = new DateTimeImmutable($baseline);
            $b = new DateTimeImmutable($other);
        } catch (\Throwable $e) {
            $this->logger->warning('ClusteringService', 'Failed to parse dates for window check', [
                'baseline' => $baseline,
                'other' => $other,
                'error' => $e->getMessage(),
            ]);

            return true;
        }

        $diffHours = abs($a->getTimestamp() - $b->getTimestamp()) / 3600;

        return $diffHours <= $this->candidateWindowHours;
    }

    private function tokenize(string $text): array
    {
        $normalized = mb_strtolower(TextSanitizer::sanitize($text));
        $normalized = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $normalized) ?? '';
        $parts = preg_split('/\s+/u', $normalized, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $filtered = [];

        foreach ($parts as $token) {
            if (mb_strlen($token) < 3) {
                continue;
            }

            if (isset($this->stopwords[$token])) {
                continue;
            }

            $filtered[] = $token;
        }

        return array_values(array_unique($filtered));
    }

    private function normalizeTags(mixed $raw): array
    {
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : [$raw];
        }

        if (!is_array($raw)) {
            return [];
        }

        $tags = [];

        foreach ($raw as $tag) {
            $normalized = mb_strtolower(trim((string)$tag));
            if ($normalized !== '') {
                $tags[] = $normalized;
            }
        }

        return array_values(array_unique($tags));
    }

    private function mergeStopwords(array $stopwords): array
    {
        $merged = [];

        foreach ($stopwords as $group) {
            if (!is_array($group)) {
                continue;
            }

            foreach ($group as $word) {
                $merged[mb_strtolower((string)$word)] = true;
            }
        }

        return $merged;
    }
}
