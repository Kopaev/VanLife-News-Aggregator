<?php

namespace App\Service;

/**
 * SEO Service for generating meta tags, Open Graph, and structured data
 */
class SeoService
{
    private string $siteName = 'VanLife News';
    private string $siteUrl;
    private string $defaultDescription = 'Агрегатор новостей о vanlife и автодомах со всего мира. Законы, запреты, кемпинги, фестивали и обзоры техники.';
    private string $defaultImage = '/images/og-default.jpg';
    private string $locale = 'ru_RU';
    private string $twitterHandle = '';

    /**
     * SEO data for current page
     */
    private array $data = [];

    public function __construct(?string $siteUrl = null)
    {
        $this->siteUrl = $siteUrl ?? (getenv('APP_URL') ?: 'https://news.vanlife.bez.coffee');
        $this->reset();
    }

    /**
     * Reset SEO data to defaults
     */
    public function reset(): void
    {
        $this->data = [
            'title' => $this->siteName,
            'description' => $this->defaultDescription,
            'canonical' => null,
            'robots' => 'index, follow',
            'og' => [
                'type' => 'website',
                'title' => $this->siteName,
                'description' => $this->defaultDescription,
                'image' => $this->siteUrl . $this->defaultImage,
                'url' => null,
                'site_name' => $this->siteName,
                'locale' => $this->locale,
            ],
            'twitter' => [
                'card' => 'summary_large_image',
                'title' => null,
                'description' => null,
                'image' => null,
            ],
            'article' => null,
            'schema' => null,
        ];
    }

    /**
     * Set page title
     *
     * @param string $title Page title
     * @param bool $appendSiteName Whether to append site name
     */
    public function setTitle(string $title, bool $appendSiteName = true): self
    {
        $fullTitle = $appendSiteName ? "{$title} — {$this->siteName}" : $title;
        $this->data['title'] = $this->sanitize($fullTitle, 70);
        $this->data['og']['title'] = $this->sanitize($title, 60);

        return $this;
    }

    /**
     * Set meta description
     *
     * @param string $description Description text
     */
    public function setDescription(string $description): self
    {
        $clean = $this->sanitize($description, 160);
        $this->data['description'] = $clean;
        $this->data['og']['description'] = $clean;

        return $this;
    }

    /**
     * Set canonical URL
     *
     * @param string $path Path or full URL
     */
    public function setCanonical(string $path): self
    {
        $url = str_starts_with($path, 'http') ? $path : $this->siteUrl . $path;
        $this->data['canonical'] = $url;
        $this->data['og']['url'] = $url;

        return $this;
    }

    /**
     * Set Open Graph image
     *
     * @param string $imageUrl Image URL (absolute or relative)
     */
    public function setImage(string $imageUrl): self
    {
        $url = str_starts_with($imageUrl, 'http') ? $imageUrl : $this->siteUrl . $imageUrl;
        $this->data['og']['image'] = $url;
        $this->data['twitter']['image'] = $url;

        return $this;
    }

    /**
     * Set robots directive
     *
     * @param string $robots Robots directive (e.g., "noindex, nofollow")
     */
    public function setRobots(string $robots): self
    {
        $this->data['robots'] = $robots;

        return $this;
    }

    /**
     * Set Open Graph type
     *
     * @param string $type OG type (website, article, etc.)
     */
    public function setOgType(string $type): self
    {
        $this->data['og']['type'] = $type;

        return $this;
    }

    /**
     * Configure SEO for an article page
     *
     * @param array $article Article data
     */
    public function configureForArticle(array $article): self
    {
        $title = $article['display_title'] ?? $article['title_ru'] ?? $article['original_title'] ?? '';
        $description = $article['display_summary'] ?? $article['summary_ru'] ?? $article['original_summary'] ?? '';
        $slug = $article['slug'] ?? '';
        $publishedAt = $article['published_at'] ?? null;
        $updatedAt = $article['updated_at'] ?? null;
        $imageUrl = $article['image_url'] ?? null;
        $category = $article['category_name'] ?? null;

        $this->setTitle($title);
        $this->setDescription($description);
        $this->setOgType('article');

        if ($slug) {
            $this->setCanonical("/news/{$slug}");
        }

        if ($imageUrl) {
            $this->setImage($imageUrl);
        }

        // Article-specific Open Graph tags
        $this->data['article'] = [
            'published_time' => $publishedAt ? $this->formatIso8601($publishedAt) : null,
            'modified_time' => $updatedAt ? $this->formatIso8601($updatedAt) : null,
            'section' => $category,
            'tag' => $this->extractTags($article['tags'] ?? null),
        ];

        return $this;
    }

    /**
     * Configure SEO for a cluster page
     *
     * @param array $cluster Cluster data
     */
    public function configureForCluster(array $cluster): self
    {
        $title = $cluster['title_ru'] ?? '';
        $description = $cluster['summary_ru'] ?? $cluster['main_display_summary'] ?? '';
        $slug = $cluster['slug'] ?? '';
        $category = $cluster['category_name'] ?? null;

        $this->setTitle($title);

        if ($description) {
            $this->setDescription($description);
        } else {
            $count = (int)($cluster['articles_count'] ?? 0);
            $this->setDescription("Подборка из {$count} новостей по теме: {$title}");
        }

        if ($slug) {
            $this->setCanonical("/clusters/{$slug}");
        }

        $this->setOgType('article');

        if ($category) {
            $this->data['article'] = ['section' => $category];
        }

        return $this;
    }

    /**
     * Configure SEO for home page
     */
    public function configureForHome(): self
    {
        $this->setTitle('Свежие новости о vanlife', true);
        $this->setDescription($this->defaultDescription);
        $this->setCanonical('/');

        return $this;
    }

    /**
     * Configure SEO for clusters list page
     */
    public function configureForClustersList(): self
    {
        $this->setTitle('Кластеры новостей', true);
        $this->setDescription('Подборки похожих новостей о vanlife: AI-группировка связанных публикаций по темам, странам и категориям.');
        $this->setCanonical('/clusters');

        return $this;
    }

    /**
     * Configure SEO for category page
     *
     * @param string $categoryName Category name
     * @param string $categorySlug Category slug
     */
    public function configureForCategory(string $categoryName, string $categorySlug): self
    {
        $this->setTitle("Новости: {$categoryName}", true);
        $this->setDescription("Все новости о vanlife в категории «{$categoryName}»: законы, запреты, открытия, фестивали.");
        $this->setCanonical("/category/{$categorySlug}");

        return $this;
    }

    /**
     * Configure SEO for country page
     *
     * @param string $countryName Country name
     * @param string $countryCode Country code
     */
    public function configureForCountry(string $countryName, string $countryCode): self
    {
        $this->setTitle("Новости vanlife: {$countryName}", true);
        $this->setDescription("Актуальные новости о vanlife и автодомах в {$countryName}: законы, кемпинги, события.");
        $this->setCanonical("/country/{$countryCode}");

        return $this;
    }

    /**
     * Get all SEO data
     *
     * @return array SEO data array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Generate HTML meta tags
     *
     * @return string HTML meta tags
     */
    public function renderMetaTags(): string
    {
        $html = [];

        // Basic meta tags
        $html[] = '<meta name="description" content="' . $this->escape($this->data['description']) . '">';
        $html[] = '<meta name="robots" content="' . $this->escape($this->data['robots']) . '">';

        // Canonical URL
        if ($this->data['canonical']) {
            $html[] = '<link rel="canonical" href="' . $this->escape($this->data['canonical']) . '">';
        }

        // Open Graph tags
        foreach ($this->data['og'] as $property => $content) {
            if ($content) {
                $html[] = '<meta property="og:' . $this->escape($property) . '" content="' . $this->escape($content) . '">';
            }
        }

        // Article-specific OG tags
        if (!empty($this->data['article'])) {
            foreach ($this->data['article'] as $property => $content) {
                if ($content) {
                    if (is_array($content)) {
                        foreach ($content as $value) {
                            $html[] = '<meta property="article:' . $this->escape($property) . '" content="' . $this->escape($value) . '">';
                        }
                    } else {
                        $html[] = '<meta property="article:' . $this->escape($property) . '" content="' . $this->escape($content) . '">';
                    }
                }
            }
        }

        // Twitter Card tags
        $html[] = '<meta name="twitter:card" content="' . $this->escape($this->data['twitter']['card']) . '">';
        if ($this->twitterHandle) {
            $html[] = '<meta name="twitter:site" content="' . $this->escape($this->twitterHandle) . '">';
        }

        return implode("\n    ", $html);
    }

    /**
     * Get page title
     */
    public function getTitle(): string
    {
        return $this->data['title'];
    }

    /**
     * Sanitize text for meta tags
     */
    private function sanitize(string $text, int $maxLength = 0): string
    {
        // Remove HTML tags
        $text = strip_tags($text);
        // Decode HTML entities
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', $text) ?? '';
        $text = trim($text);

        // Truncate if needed
        if ($maxLength > 0 && mb_strlen($text) > $maxLength) {
            $text = mb_substr($text, 0, $maxLength - 3) . '...';
        }

        return $text;
    }

    /**
     * Escape HTML special characters
     */
    private function escape(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Format date to ISO 8601
     */
    private function formatIso8601(string $datetime): string
    {
        $timestamp = strtotime($datetime);
        return $timestamp ? date('c', $timestamp) : '';
    }

    /**
     * Extract tags from JSON or array
     */
    private function extractTags($tags): array
    {
        if (is_string($tags)) {
            $decoded = json_decode($tags, true);
            return is_array($decoded) ? $decoded : [];
        }

        return is_array($tags) ? $tags : [];
    }
}
