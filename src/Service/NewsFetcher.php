<?php

namespace App\Service;

use App\Model\Article;
use App\Repository\ArticleRepository;
use App\Repository\SourceRepository;
use App\Service\GoogleNewsUrlDecoder;
use App\Service\LoggerService;
use Exception;
use SimpleXMLElement;

class NewsFetcher
{
    private LoggerService $logger;
    private GoogleNewsUrlDecoder $urlDecoder;
    private SourceRepository $sourceRepository;
    private ArticleRepository $articleRepository;

    private const GOOGLE_NEWS_RSS_URL_FORMAT = 'https://news.google.com/rss/search?q=%s&hl=%s&gl=%s&ceid=%s:%s';

    public function __construct(
        LoggerService $logger,
        GoogleNewsUrlDecoder $urlDecoder,
        SourceRepository $sourceRepository,
        ArticleRepository $articleRepository
    ) {
        $this->logger = $logger;
        $this->urlDecoder = $urlDecoder;
        $this->sourceRepository = $sourceRepository;
        $this->articleRepository = $articleRepository;
    }

    public function fetchAllSources(): int
    {
        $sources = $this->sourceRepository->getEnabledSources();
        $totalSaved = 0;

        foreach ($sources as $source) {
            try {
                $savedCount = $this->fetchFromSource($source);
                $this->logger->info('NewsFetcher', "Saved $savedCount new articles from source", [
                    'source_id' => $source['id'],
                    'source_name' => $source['name'],
                ]);
                $totalSaved += $savedCount;
                $this->sourceRepository->updateSourceLastFetched($source['id']);
            } catch (Exception $e) {
                $this->logger->error('NewsFetcher', 'Failed to fetch from source', [
                    'source_id' => $source['id'],
                    'source_name' => $source['name'],
                    'error' => $e->getMessage(),
                ]);
                $this->sourceRepository->updateSourceLastError($source['id'], $e->getMessage());
            }
        }

        return $totalSaved;
    }

    /**
     * @throws Exception
     */
    public function fetchFromSource(array $source): int
    {
        $url = $this->buildGoogleNewsUrl($source);
        $xmlContent = $this->fetchRssContent($url);
        $rss = $this->parseRss($xmlContent);

        $savedCount = 0;
        foreach ($rss->channel->item as $item) {
            $googleNewsUrl = (string)$item->link;
            $originalUrl = $googleNewsUrl; // FIX: Временно игнорируем декодер

            if ($this->articleRepository->isArticleExists($originalUrl)) {
                continue;
            }

            $article = new Article();
            $article->source_id = $source['id'];
            $article->external_id = md5($originalUrl);
            $article->original_title = (string)$item->title;
            $article->original_summary = (string)$item->description;
            $article->original_url = $originalUrl;
            $article->original_language = $source['language_code'];
            $article->published_at = date('Y-m-d H:i:s', strtotime((string)$item->pubDate));
            $article->fetched_at = date('Y-m-d H:i:s');
            $article->country_code = $source['country_code'];

            $this->articleRepository->save($article);
            $savedCount++;
        }

        return $savedCount;
    }

    private function buildGoogleNewsUrl(array $source): string
    {
        return sprintf(
            self::GOOGLE_NEWS_RSS_URL_FORMAT,
            urlencode($source['query']),
            $source['language_code'],
            $source['country_code'],
            $source['country_code'],
            $source['language_code']
        );
    }

    /**
     * @throws Exception
     */
    private function fetchRssContent(string $url): string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        ]);

        $content = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception("Failed to fetch RSS feed. HTTP code: $httpCode, error: $error");
        }

        if (empty($content)) {
            throw new Exception("Fetched empty RSS feed from $url");
        }

        return $content;
    }

    /**
     * @throws Exception
     */
    private function parseRss(string $xmlContent): SimpleXMLElement
    {
        libxml_use_internal_errors(true);
        $rss = simplexml_load_string($xmlContent);
        if ($rss === false) {
            $errors = libxml_get_errors();
            $errorMessages = array_map(fn($error) => $error->message, $errors);
            throw new Exception('Failed to parse XML: ' . implode(', ', $errorMessages));
        }
        libxml_clear_errors();
        libxml_use_internal_errors(false);

        return $rss;
    }
}
