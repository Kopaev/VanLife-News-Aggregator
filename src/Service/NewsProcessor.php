<?php

namespace App\Service;

use App\AI\OpenAIProvider;
use App\Core\Config;
use App\Repository\ArticleRepository;
use DateTimeImmutable;
use RuntimeException;

class NewsProcessor
{
    private const PROMPT_TEMPLATE = <<<PROMPT
Ты — эксперт по vanlife/автодомам. Проанализируй новость и верни JSON.

НОВОСТЬ:
Заголовок: {title}
Описание: {summary}
Источник: {source}
Язык: {language}

ЗАДАЧА:
1. Оцени релевантность для vanlife-аудитории (0-100)
2. Определи категорию
3. Определи страну, о которой новость (если есть)
4. Сгенерируй 3-5 тегов
5. Проверь на "опасный" контент

КАТЕГОРИИ:
- law — законы и правила
- ban — запреты и штрафы
- opening — открытия кемпингов/стоянок
- closing — закрытия
- incident — происшествия
- festival — фестивали
- expo — выставки
- industry — индустрия
- review — обзоры
- other — прочее

ФОРМАТ ОТВЕТА (только JSON, без markdown):
{
  "relevance_score": 85,
  "category": "ban",
  "country_code": "DE",
  "tags": ["germany", "parking-ban", "munich"],
  "is_dangerous": false,
  "danger_reason": null
}
PROMPT;

    private array $moderationRules;
    private array $categories;
    private int $titleLimit;
    private int $summaryLimit;
    private int $maxTokens;

    public function __construct(
        private readonly OpenAIProvider $aiProvider,
        private readonly LoggerService $logger,
        private readonly ArticleRepository $articleRepository,
        private readonly Config $config
    ) {
        $this->moderationRules = require dirname(__DIR__, 2) . '/config/moderation.php';
        $this->categories = require dirname(__DIR__, 2) . '/config/categories.php';
        $this->titleLimit = (int)$this->config->get('prompts.relevance.title_limit', 240);
        $this->summaryLimit = (int)$this->config->get('prompts.relevance.summary_limit', 1200);
        $this->maxTokens = (int)$this->config->get('prompts.relevance.max_tokens', 300);
    }

    public function processRelevance(int $limit = 10): int
    {
        $articles = $this->articleRepository->getArticlesForProcessing($limit);
        $processed = 0;

        foreach ($articles as $article) {
            try {
                $this->processArticle($article);
                $processed++;
            } catch (\Throwable $e) {
                $this->logger->error('NewsProcessor', 'Failed to process article', [
                    'article_id' => $article['id'] ?? null,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $processed;
    }

    private function processArticle(array $article): void
    {
        $processedAt = (new DateTimeImmutable('now'))->format('Y-m-d H:i:s');

        if ($this->isAutoReject($article)) {
            $this->articleRepository->updateProcessing(
                (int)$article['id'],
                0,
                'rejected',
                'auto_reject_keyword',
                $processedAt,
                null,
                $this->normalizeCountry($article['country_code'] ?? null),
                null
            );

            $this->logger->info('NewsProcessor', 'Article auto-rejected by keyword', [
                'article_id' => $article['id'],
            ]);

            return;
        }

        $payload = $this->buildPromptPayload($article);
        $response = $this->aiProvider->chat(
            [
                [
                    'role' => 'user',
                    'content' => strtr(self::PROMPT_TEMPLATE, $payload),
                ],
            ],
            [
                'response_format' => ['type' => 'json_object'],
                'max_tokens' => $this->maxTokens,
            ]
        );

        $result = $this->parseResponse($response->content);
        $score = $this->normalizeScore($result['relevance_score'] ?? null);
        $isDangerous = (bool)($result['is_dangerous'] ?? false);
        $dangerReason = $result['danger_reason'] ?? null;
        $category = $this->normalizeCategory($result['category'] ?? null);
        $tags = $this->normalizeTags($result['tags'] ?? null);
        $countryCode = $this->normalizeCountry($result['country_code'] ?? $article['country_code'] ?? null);

        [$status, $reason] = $this->determineStatus($score, $isDangerous, $dangerReason);

        $this->articleRepository->updateProcessing(
            (int)$article['id'],
            $score,
            $status,
            $reason,
            $processedAt,
            $category,
            $countryCode,
            $tags
        );

        $this->logger->info('NewsProcessor', 'Article processed for relevance', [
            'article_id' => $article['id'],
            'score' => $score,
            'status' => $status,
            'category' => $category,
            'country' => $countryCode,
            'tags' => $tags,
        ]);
    }

    private function buildPromptPayload(array $article): array
    {
        return [
            '{title}' => TextSanitizer::limit((string)($article['original_title'] ?? ''), $this->titleLimit),
            '{summary}' => TextSanitizer::limit((string)($article['original_summary'] ?? ''), $this->summaryLimit),
            '{source}' => (string)($article['source_name'] ?? ''),
            '{language}' => (string)($article['original_language'] ?? 'unknown'),
        ];
    }

    private function parseResponse(string $content): array
    {
        $decoded = json_decode($content, true);

        if (!is_array($decoded)) {
            throw new RuntimeException('Failed to decode OpenAI response as JSON.');
        }

        return $decoded;
    }

    private function determineStatus(int $score, bool $isDangerous, ?string $dangerReason): array
    {
        $minScore = (int)($this->moderationRules['min_relevance_score'] ?? 30);
        $autoPublishScore = (int)($this->moderationRules['auto_publish_score'] ?? 70);

        if ($isDangerous) {
            return ['moderation', $dangerReason ?: 'dangerous_content'];
        }

        if ($score >= $autoPublishScore) {
            return ['published', null];
        }

        if ($score >= $minScore) {
            return ['moderation', null];
        }

        return ['rejected', 'low_relevance'];
    }

    private function normalizeScore(mixed $rawScore): int
    {
        if (!is_numeric($rawScore)) {
            return 0;
        }

        return max(0, min(100, (int)$rawScore));
    }

    private function normalizeCategory(mixed $category): ?string
    {
        if (!is_string($category)) {
            return null;
        }

        $slug = strtolower(trim($category));

        if (isset($this->categories[$slug])) {
            return $slug;
        }

        return isset($this->categories['other']) ? 'other' : null;
    }

    private function normalizeCountry(mixed $countryCode): ?string
    {
        if (!is_string($countryCode)) {
            return null;
        }

        $code = strtoupper(trim($countryCode));

        return (strlen($code) === 2) ? $code : null;
    }

    private function normalizeTags(mixed $rawTags): ?array
    {
        if (!is_array($rawTags)) {
            return null;
        }

        $tags = [];

        foreach ($rawTags as $tag) {
            if (!is_string($tag)) {
                continue;
            }

            $normalized = trim($tag);

            if ($normalized !== '') {
                $tags[] = $normalized;
            }
        }

        return $tags ?: null;
    }

    private function isAutoReject(array $article): bool
    {
        $rules = $this->moderationRules['auto_reject'] ?? [];
        if (empty($rules)) {
            return false;
        }

        $haystack = mb_strtolower(((string)($article['original_title'] ?? '')) . ' ' . ((string)($article['original_summary'] ?? '')));

        foreach ($rules as $keyword) {
            if (str_contains($haystack, mb_strtolower($keyword))) {
                return true;
            }
        }

        return false;
    }
}
