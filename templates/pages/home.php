<?php require_once __DIR__ . '/../layout/header.php'; ?>

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
?>

<div class="container">
    <h1>Свежие новости</h1>
    <p class="page-lead">Переведённые и отсортированные материалы о vanlife: категории, страны, релевантность и теги от AI.</p>

    <?php
    // Include filters component
    require_once __DIR__ . '/../components/filters.php';
    ?>

    <section class="cluster-section">
        <div class="section-header">
            <div>
                <p class="eyebrow">Кластеры тем</p>
                <h2 class="section-title">Подборки похожих новостей</h2>
                <p class="section-lead">AI-группировка связанных публикаций: страны, категории и главная статья в одном блоке.</p>
            </div>
            <a class="button" href="/clusters">Смотреть все кластеры</a>
        </div>

        <?php if (!empty($clusters)): ?>
            <div class="clusters-grid">
                <?php foreach ($clusters as $cluster): ?>
                    <?php
                    $countries = $cluster['countries_meta'] ?? [];
                    $categoryName = $cluster['category_name'] ?? null;
                    $articleCount = (int)($cluster['articles_count'] ?? 0);
                    ?>
                    <article class="cluster-card">
                        <div class="cluster-meta-top">
                            <?php if (!empty($categoryName)): ?>
                                <span class="badge category-badge" <?php if (!empty($cluster['category_color'])): ?>style="background-color: <?php echo htmlspecialchars($cluster['category_color']); ?>"<?php endif; ?>>
                                    <?php echo htmlspecialchars(trim(($cluster['category_icon'] ?? '') . ' ' . $categoryName)); ?>
                                </span>
                            <?php endif; ?>
                            <div class="pill-group">
                                <?php foreach ($countries as $country): ?>
                                    <span class="pill"><?php echo htmlspecialchars(trim(($country['flag_emoji'] ?? '') . ' ' . ($country['name_ru'] ?? $country['code'] ?? ''))); ?></span>
                                <?php endforeach; ?>
                                <span class="pill pill-muted"><?php echo $articleCount; ?> статей</span>
                            </div>
                        </div>

                        <a href="/clusters/<?php echo htmlspecialchars($cluster['slug']); ?>" class="cluster-title">
                            <?php echo htmlspecialchars($cluster['title_ru']); ?>
                        </a>

                        <?php if (!empty($cluster['main_display_summary'])): ?>
                            <p class="cluster-summary"><?php echo htmlspecialchars($cluster['main_display_summary']); ?></p>
                        <?php endif; ?>

                        <div class="cluster-footer">
                            <div>
                                <p class="meta-label">Обновлено</p>
                                <p class="meta-value"><?php echo htmlspecialchars($formatDate($cluster['last_updated_at'] ?? null)); ?></p>
                            </div>
                            <?php if (!empty($cluster['main_article_slug'])): ?>
                                <a class="text-link" href="/news/<?php echo htmlspecialchars($cluster['main_article_slug']); ?>">Главная статья →</a>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="muted">Кластеры появятся после первой кластеризации.</p>
        <?php endif; ?>
    </section>

    <h2 class="section-title">Все новости</h2>

    <div id="news-container" class="articles">
        <?php if (empty($articles)): ?>
            <p>Пока нет опубликованных новостей.</p>
        <?php else: ?>
            <?php foreach ($articles as $article): ?>
                <?php
                $tags = $decodeTags($article['tags'] ?? null);
                $status = $statusMap[$article['status'] ?? 'new'] ?? ['label' => 'Статус неизвестен', 'class' => 'status--new'];
                $language = strtoupper((string)($article['original_language'] ?? '')); 
                ?>
                <div class="article-card">
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

                    <?php if (!empty($article['slug'])): ?>
                        <h2><a href="/news/<?php echo htmlspecialchars($article['slug']); ?>"><?php echo htmlspecialchars($article['display_title'] ?? $article['original_title']); ?></a></h2>
                    <?php else: ?>
                        <h2><?php echo htmlspecialchars($article['display_title'] ?? $article['original_title']); ?></h2>
                    <?php endif; ?>

                    <p class="article-summary">
                        <?php echo htmlspecialchars($article['display_summary'] ?? 'Описание появится после обработки.'); ?>
                    </p>

                    <div class="article-meta">
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
                    </div>

                    <?php if (!empty($tags)): ?>
                        <div class="tag-list">
                            <?php foreach ($tags as $tag): ?>
                                <span class="tag">#<?php echo htmlspecialchars($tag); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($article['slug'])): ?>
                        <div class="article-actions">
                            <a href="/news/<?php echo htmlspecialchars($article['slug']); ?>" class="button button--small">Читать далее</a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div id="pagination-container"></div>
</div>

<script src="/js/filters.js"></script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
