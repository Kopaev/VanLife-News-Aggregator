<?php

namespace App\Service;

use App\AI\OpenAIProvider;
use App\Model\Translation;
use App\Repository\ArticleRepository;
use App\Repository\TranslationRepository;
use RuntimeException;

class TranslationService
{
    private const TARGET_LANGUAGE = 'ru';

    private const PROMPT_TEMPLATE = <<<PROMPT
Ты — профессиональный переводчик новостей о vanlife и автодомах.
Переведи заголовок и описание на русский язык, сохраняя факты и нейтральный тон.
Не добавляй размышлений, ссылок и markdown, оставь только текст.
Ответ верни в JSON-формате:
{
  "title_ru": "Перевод заголовка",
  "summary_ru": "Краткое содержание (1-3 предложения)"
}

Исходные данные:
- Язык оригинала: {language}
- Заголовок: {title}
- Описание: {summary}
PROMPT;

    public function __construct(
        private readonly OpenAIProvider $aiProvider,
        private readonly LoggerService $logger,
        private readonly ArticleRepository $articleRepository,
        private readonly TranslationRepository $translationRepository
    ) {
    }

    public function translatePending(int $limit = 10): int
    {
        $articles = $this->articleRepository->getArticlesForTranslation($limit);
        $translated = 0;

        foreach ($articles as $article) {
            try {
                $this->translateArticle($article);
                $translated++;
            } catch (\Throwable $e) {
                $this->logger->error('TranslationService', 'Failed to translate article', [
                    'article_id' => $article['id'] ?? null,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $translated;
    }

    private function translateArticle(array $article): void
    {
        $payload = [
            '{language}' => (string)($article['original_language'] ?? 'unknown'),
            '{title}' => $this->truncate((string)($article['original_title'] ?? ''), 400),
            '{summary}' => $this->truncate((string)($article['original_summary'] ?? ''), 1200),
        ];

        $response = $this->aiProvider->chat(
            [
                [
                    'role' => 'user',
                    'content' => strtr(self::PROMPT_TEMPLATE, $payload),
                ],
            ],
            [
                'response_format' => ['type' => 'json_object'],
                'max_tokens' => 300,
            ]
        );

        $data = $this->parseResponse($response->content);
        $titleRu = $data['title_ru'];
        $summaryRu = $data['summary_ru'] ?? null;

        $this->articleRepository->updateTranslation((int)$article['id'], $titleRu, $summaryRu);

        $translation = new Translation();
        $translation->article_id = (int)$article['id'];
        $translation->target_language = self::TARGET_LANGUAGE;
        $translation->title = $titleRu;
        $translation->summary = $summaryRu;

        $this->translationRepository->saveOrUpdate($translation);

        $this->logger->info('TranslationService', 'Article translated to Russian', [
            'article_id' => $article['id'],
            'language' => $article['original_language'] ?? null,
        ]);
    }

    private function parseResponse(string $content): array
    {
        $decoded = json_decode($content, true);

        if (!is_array($decoded)) {
            throw new RuntimeException('Failed to decode translation response as JSON.');
        }

        $title = isset($decoded['title_ru']) ? trim((string)$decoded['title_ru']) : '';
        $summary = isset($decoded['summary_ru']) ? trim((string)$decoded['summary_ru']) : null;

        if ($title === '') {
            throw new RuntimeException('Translation response missing title_ru.');
        }

        return [
            'title_ru' => $title,
            'summary_ru' => $summary === '' ? null : $summary,
        ];
    }

    private function truncate(string $text, int $maxLength): string
    {
        if (mb_strlen($text) <= $maxLength) {
            return $text;
        }

        return mb_substr($text, 0, $maxLength);
    }
}
