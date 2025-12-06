<?php require_once __DIR__ . '/../layout/header.php'; ?>

<?php
// Helper functions (can be moved to a separate file)
$formatDate = static function (?string $datetime): string {
    if (!$datetime) return 'дата не указана';
    try {
        $date = new DateTime($datetime);
        // IntlDateFormatter could be used for locale-specific format
        return $date->format('d M Y');
    } catch (Exception $e) {
        return 'неверная дата';
    }
};

$get_source_name = static function($url) {
    $host = parse_url($url, PHP_URL_HOST);
    if (str_starts_with($host, 'www.')) {
        return substr($host, 4);
    }
    return $host;
};

// Placeholder data for stats and filters, assuming it comes from controller
$total_news = $total_news ?? count($articles ?? []);
$total_countries = count($countries ?? []);
$total_categories = count($categories ?? []);
$last_update_time = $last_update_time ?? date('Y-m-d H:i:s');
?>

<div class="page-container">

    <!-- Main Header with Gradient -->
    <header class="main-header">
        <div class="header-content">
            <h1 class="header-title">Новости Ванлайфа</h1>
            <p class="header-subtitle">Последнее обновление: <?php echo htmlspecialchars($formatDate($last_update_time)); ?></p>
            <div class="stats-bar">
                <div class="stat-item">
                    <span class="stat-value"><?php echo $total_news; ?></span>
                    <span class="stat-label">Новостей</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value"><?php echo $total_countries; ?></span>
                    <span class="stat-label">Стран</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value"><?php echo $total_categories; ?></span>
                    <span class="stat-label">Категорий</span>
                </div>
            </div>
        </div>
    </header>

    <!-- Filter Panel -->
    <div class="filter-panel">
        <form class="filter-form" id="news-filters">
            <div class="filter-group">
                <input type="search" name="search" placeholder="Поиск по заголовку...">
            </div>
            <div class="filter-group">
                <select name="country">
                    <option value="">Все страны</option>
                    <?php foreach ($countries as $country): ?>
                        <option value="<?php echo htmlspecialchars($country['code']); ?>">
                            <?php echo htmlspecialchars($country['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <select name="category">
                    <option value="">Все категории</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat['slug']); ?>">
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <select name="language">
                    <option value="">Все языки</option>
                     <?php foreach ($languages as $lang): ?>
                        <option value="<?php echo htmlspecialchars($lang['code']); ?>">
                            <?php echo htmlspecialchars($lang['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <select name="sort">
                    <option value="newest">Сначала новые</option>
                    <option value="oldest">Сначала старые</option>
                    <option value="relevance">По релевантности</option>
                </select>
            </div>
            <div class="filter-group">
                <button type="button" class="clear-filters-btn">Очистить</button>
            </div>
        </form>
    </div>

    <!-- Main Content Grid -->
    <div class="main-content-grid">
        <main class="news-column" id="news-container">
            <?php if (empty($articles)): ?>
                <p>Пока нет опубликованных новостей.</p>
            <?php else: ?>
                <?php foreach ($articles as $article): ?>
                    <?php
                        $placeholder = '/images/placeholders/placeholder.svg';
                        $imageUrl = !empty($article['image_url']) ? htmlspecialchars($article['image_url']) : $placeholder;
                    ?>
                    <article class="news-card">
                        <div class="news-card-image-wrapper">
                            <img src="<?php echo $imageUrl; ?>" alt="<?php echo htmlspecialchars($article['display_title'] ?? $article['original_title']); ?>" class="news-card-image" loading="lazy">
                        </div>
                        <div class="news-card-content">
                            <div class="news-card-meta">
                                <span class="meta-item country-meta">
                                    <span class="icon"><?php echo htmlspecialchars($article['country_flag'] ?? '🌍'); ?></span>
                                    <?php echo htmlspecialchars($article['country_name'] ?? 'Мир'); ?>
                                </span>
                                <span class="meta-separator">|</span>
                                <span class="meta-item date-meta">
                                    <span class="icon">📅</span>
                                    <?php echo htmlspecialchars($formatDate($article['published_at'] ?? null)); ?>
                                </span>
                            </div>

                            <?php if (!empty($article['category_name'])): ?>
                                <span class="category-badge">
                                    <?php echo htmlspecialchars($article['category_name']); ?>
                                </span>
                            <?php endif; ?>

                            <h2 class="news-card-title">
                                <a href="<?php echo htmlspecialchars($article['original_url']); ?>" target="_blank" rel="noopener noreferrer">
                                    <?php echo htmlspecialchars($article['display_title'] ?? $article['original_title']); ?>
                                </a>
                            </h2>

                            <p class="news-card-summary">
                                <?php echo htmlspecialchars($article['display_summary'] ?? 'Краткое описание появится после обработки AI.'); ?>
                            </p>

                            <div class="news-card-footer">
                                <?php if (!empty($article['slug'])): ?>
                                    <a href="/news/<?php echo htmlspecialchars($article['slug']); ?>" class="source-link">
                                        <span class="icon">🔗</span>
                                        <span><?php echo htmlspecialchars($get_source_name($article['original_url'])); ?></span>
                                        <span class="arrow">&rarr;</span>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </main>
        <aside class="sidebar-column">
            <div class="sidebar-widget">
                <h3 class="sidebar-title">Ближайшие события</h3>
                <div class="sidebar-content">
                    <p>Раздел в разработке. Здесь будут отображаться анонсы ближайших фестивалей, выставок и других событий в мире ванлайфа.</p>
                </div>
            </div>
        </aside>
    </div>
</div>

<script src="/js/filters.js"></script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
ость и теги от AI.</p>

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

    <div id="news-container" class="news-grid">
        <?php if (empty($articles)): ?>
            <p>Пока нет опубликованных новостей.</p>
        <?php else: ?>
            <?php foreach ($articles as $article): ?>
                <?php
                $tags = $decodeTags($article['tags'] ?? null);
                $language = strtoupper((string)($article['original_language'] ?? ''));
                $placeholder = '/images/placeholders/placeholder.svg';
                $imageUrl = !empty($article['image_url']) ? htmlspecialchars($article['image_url']) : $placeholder;
                ?>
                <div class="news-card">
                    <div class="news-card-image">
                        <img src="<?php echo $imageUrl; ?>" alt="<?php echo htmlspecialchars($article['display_title'] ?? $article['original_title']); ?>" loading="lazy">
                    </div>
                    <div class="news-card-content">
                        <div class="news-card-header">
                            <?php if (!empty($article['country_name'])): ?>
                                <span class="badge country-badge">
                                    <?php echo htmlspecialchars(trim(($article['country_flag'] ?? '') . ' ' . $article['country_name'])); ?>
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($article['category_name'])): ?>
                                <span class="badge category-badge" <?php if (!empty($article['category_color'])): ?>style="background-color: <?php echo htmlspecialchars($article['category_color']); ?>"<?php endif; ?>>
                                    <?php echo htmlspecialchars(trim(($article['category_icon'] ?? '') . ' ' . $article['category_name'])); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <h3 class="news-card-title">
                            <a href="<?php echo htmlspecialchars($article['original_url']); ?>" target="_blank" rel="noopener noreferrer">
                                <?php echo htmlspecialchars($article['display_title'] ?? $article['original_title']); ?>
                            </a>
                        </h3>

                        <p class="news-card-summary">
                            <?php echo htmlspecialchars($article['display_summary'] ?? 'Описание появится после обработки.'); ?>
                        </p>

                        <div class="news-card-footer">
                            <span class="news-card-date"><?php echo htmlspecialchars($formatDate($article['published_at'] ?? null)); ?></span>
                            <?php if (!empty($article['slug'])): ?>
                                <a href="/news/<?php echo htmlspecialchars($article['slug']); ?>" class="read-more-link">AI Cаммари &rarr;</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div id="pagination-container"></div>
</div>

<script src="/js/filters.js"></script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
