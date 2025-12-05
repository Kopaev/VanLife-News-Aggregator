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

    <div class="articles">
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

                    <h2><?php echo htmlspecialchars($article['display_title'] ?? $article['original_title']); ?></h2>

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
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
