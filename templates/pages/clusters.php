<?php require_once __DIR__ . '/../layout/header.php'; ?>

<?php
$formatDate = static function (?string $datetime): string {
    if (!$datetime) {
        return '—';
    }

    $timestamp = strtotime($datetime);

    return $timestamp ? date('d.m.Y H:i', $timestamp) : $datetime;
};
?>

<div class="container">
    <h1>Кластеры новостей</h1>
    <p class="page-lead">Связанные материалы, сгруппированные по тематике и времени публикации. В каждом кластере — главная статья и связанные источники.</p>

    <?php if (empty($clusters)): ?>
        <p>Кластеры будут доступны после первой кластеризации.</p>
    <?php else: ?>
        <div class="clusters-grid clusters-grid--wide">
            <?php foreach ($clusters as $cluster): ?>
                <?php $countries = $cluster['countries_meta'] ?? []; ?>
                <article class="cluster-card">
                    <div class="cluster-meta-top">
                        <?php if (!empty($cluster['category_name'])): ?>
                            <span class="badge category-badge" <?php if (!empty($cluster['category_color'])): ?>style="background-color: <?php echo htmlspecialchars($cluster['category_color']); ?>"<?php endif; ?>>
                                <?php echo htmlspecialchars(trim(($cluster['category_icon'] ?? '') . ' ' . $cluster['category_name'])); ?>
                            </span>
                        <?php endif; ?>
                        <div class="pill-group">
                            <?php foreach ($countries as $country): ?>
                                <span class="pill"><?php echo htmlspecialchars(trim(($country['flag_emoji'] ?? '') . ' ' . ($country['name_ru'] ?? $country['code'] ?? ''))); ?></span>
                            <?php endforeach; ?>
                            <span class="pill pill-muted"><?php echo (int)($cluster['articles_count'] ?? 0); ?> статей</span>
                        </div>
                    </div>

                    <a class="cluster-title" href="/clusters/<?php echo htmlspecialchars($cluster['slug']); ?>">
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
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
