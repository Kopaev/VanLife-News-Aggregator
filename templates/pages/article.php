<?php require_once __DIR__ . '/../layout/header.php'; ?>

<?php
// Generate Schema.org JSON-LD
$schemaOrg = [
    '@context' => 'https://schema.org',
    '@type' => 'NewsArticle',
    'headline' => $article['display_title'] ?? $article['original_title'] ?? '',
    'description' => $article['display_summary'] ?? $article['original_summary'] ?? '',
    'datePublished' => isset($article['published_at']) ? date('c', strtotime($article['published_at'])) : null,
    'dateModified' => isset($article['updated_at']) ? date('c', strtotime($article['updated_at'])) : null,
    'author' => [
        '@type' => 'Organization',
        'name' => 'VanLife News',
        'url' => getenv('APP_URL') ?: 'https://news.vanlife.bez.coffee',
    ],
    'publisher' => [
        '@type' => 'Organization',
        'name' => 'VanLife News',
        'url' => getenv('APP_URL') ?: 'https://news.vanlife.bez.coffee',
    ],
    'mainEntityOfPage' => [
        '@type' => 'WebPage',
        '@id' => (getenv('APP_URL') ?: 'https://news.vanlife.bez.coffee') . '/news/' . ($article['slug'] ?? ''),
    ],
    'inLanguage' => 'ru',
];

// Add image if available
if (!empty($article['image_url'])) {
    $schemaOrg['image'] = $article['image_url'];
}

// Add category
if (!empty($article['category_name'])) {
    $schemaOrg['articleSection'] = $article['category_name'];
}

// Add keywords from tags
$schemaTags = [];
if (!empty($article['tags'])) {
    $tags = is_string($article['tags']) ? json_decode($article['tags'], true) : $article['tags'];
    if (is_array($tags)) {
        $schemaTags = $tags;
    }
}
if (!empty($schemaTags)) {
    $schemaOrg['keywords'] = implode(', ', $schemaTags);
}

// Remove null values
$schemaOrg = array_filter($schemaOrg, fn($v) => $v !== null);
?>

<script type="application/ld+json">
<?php echo json_encode($schemaOrg, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); ?>
</script>

<?php
$formatDate = static function (?string $datetime): string {
    if (!$datetime) {
        return '—';
    }

    $timestamp = strtotime($datetime);

    return $timestamp ? date('d.m.Y H:i', $timestamp) : $datetime;
};

$decodeTags = static function ($rawTags): array {
    if (is_string($rawTags)) {
        $decoded = json_decode($rawTags, true);
        return is_array($decoded) ? $decoded : [];
    }

    return is_array($rawTags) ? $rawTags : [];
};

$statusMap = [
    'published' => ['label' => 'Опубликована', 'class' => 'status--published'],
    'moderation' => ['label' => 'На модерации', 'class' => 'status--moderation'],
    'new' => ['label' => 'Новая', 'class' => 'status--new'],
    'rejected' => ['label' => 'Отклонена', 'class' => 'status--rejected'],
    'processing' => ['label' => 'Обрабатывается', 'class' => 'status--processing'],
    'duplicate' => ['label' => 'Дубликат', 'class' => 'status--duplicate'],
];

$tags = $decodeTags($article['tags'] ?? null);
$status = $statusMap[$article['status'] ?? 'new'] ?? ['label' => 'Статус неизвестен', 'class' => 'status--new'];
$language = strtoupper((string)($article['original_language'] ?? ''));
?>

<div class="container">
    <div class="article-full">
        <div class="article-meta-top">
            <?php if (!empty($article['category_name'])): ?>
                <span class="badge category-badge" <?php if (!empty($article['category_color'])): ?>style="background-color: <?php echo htmlspecialchars($article['category_color']); ?>"<?php endif; ?>>
                    <?php echo htmlspecialchars(trim(($article['category_icon'] ?? '') . ' ' . $article['category_name'])); ?>
                </span>
            <?php endif; ?>

            <div class="pill-group">
                <?php if (!empty($article['country_name'])): ?>
                    <span class="pill">
                        <?php echo htmlspecialchars(trim(($article['country_flag'] ?? '') . ' ' . $article['country_name'])); ?>
                    </span>
                <?php endif; ?>
                <span class="pill status <?php echo htmlspecialchars($status['class']); ?>">
                    <?php echo htmlspecialchars($status['label']); ?>
                </span>
            </div>
        </div>

        <h1><?php echo htmlspecialchars($article['display_title'] ?? $article['original_title']); ?></h1>
        <?php if (!empty($article['display_summary'])): ?>
            <p class="article-summary"><?php echo htmlspecialchars($article['display_summary']); ?></p>
        <?php endif; ?>

        <div class="article-meta article-meta-grid">
            <div>
                <span class="meta-label">Опубликовано:</span>
                <span><?php echo htmlspecialchars($formatDate($article['published_at'] ?? null)); ?></span>
            </div>
            <div>
                <span class="meta-label">Источник:</span>
                <a href="<?php echo htmlspecialchars($article['original_url']); ?>" target="_blank" rel="noopener">Открыть оригинал</a>
            </div>
            <div>
                <span class="meta-label">Язык оригинала:</span>
                <span><?php echo $language ?: '—'; ?></span>
            </div>
            <?php if (!empty($article['ai_relevance_score'])): ?>
                <div>
                    <span class="meta-label">Релевантность AI:</span>
                    <span><?php echo (int)$article['ai_relevance_score']; ?> / 100</span>
                </div>
            <?php endif; ?>
            <?php if (!empty($article['moderation_reason'])): ?>
                <div>
                    <span class="meta-label">Причина модерации:</span>
                    <span><?php echo htmlspecialchars($article['moderation_reason']); ?></span>
                </div>
            <?php endif; ?>
            <?php if (!empty($article['source_name'])): ?>
                <div>
                    <span class="meta-label">Источник (rss):</span>
                    <span><?php echo htmlspecialchars($article['source_name']); ?></span>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($article['summary_ru'])): ?>
            <section class="panel">
                <h3>Русский перевод</h3>
                <p><?php echo nl2br(htmlspecialchars($article['summary_ru'])); ?></p>
            </section>
        <?php endif; ?>

        <?php if (!empty($article['original_summary']) && ($article['summary_ru'] ?? '') !== $article['original_summary']): ?>
            <section class="panel panel-muted">
                <h3>Оригинал (<?php echo $language ?: '—'; ?>)</h3>
                <p><?php echo nl2br(htmlspecialchars($article['original_summary'])); ?></p>
            </section>
        <?php endif; ?>

        <?php if (!empty($tags)): ?>
            <div class="tag-list">
                <?php foreach ($tags as $tag): ?>
                    <span class="tag">#<?php echo htmlspecialchars($tag); ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
