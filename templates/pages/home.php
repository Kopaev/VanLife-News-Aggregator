<?php require_once __DIR__ . '/../layout/header.php'; ?>

<?php
// Helper functions
$formatDate = static function (?string $datetime): string {
    if (!$datetime) return 'дата не указана';
    try {
        $date = new DateTime($datetime);
        return $date->format('d') . ' ' . $date->format('M') . ' ' . $date->format('Y') . ' г.';
    } catch (Exception $e) {
        return 'неверная дата';
    }
};

$formatLastUpdate = static function (?string $datetime): string {
    if (!$datetime) return date('d.m.Y H:i');
    try {
        $date = new DateTime($datetime);
        return $date->format('d.m.Y H:i');
    } catch (Exception $e) {
        return date('d.m.Y H:i');
    }
};

$get_source_name = static function($url) {
    $host = parse_url($url, PHP_URL_HOST);
    if ($host && str_starts_with($host, 'www.')) {
        return substr($host, 4);
    }
    return $host ?: 'источник';
};

// Calculate stats
$total_news = $total_news ?? count($articles ?? []);
$total_countries = count($countries ?? []);
$total_categories = count($categories ?? []);
$last_update_time = $last_update_time ?? date('Y-m-d H:i:s');
?>

<div class="page-container">

    <!-- Hero Header with Gradient -->
    <header class="hero-header">
        <div class="hero-top-bar">
            <div class="hero-branding">
                <h1 class="hero-title"><span class="hero-logo">🚐</span> Новости Ванлайфа</h1>
                <p class="hero-subtitle">Путешествия и Кемпинги</p>
                <span class="hero-update-badge">Последнее обновление: <?php echo htmlspecialchars($formatLastUpdate($last_update_time)); ?></span>
            </div>
            <div class="hero-controls">
                <button type="button" class="icon-button theme-toggle" id="theme-toggle" aria-label="Переключить тему">
                    <span class="theme-icon-dark">🌙</span>
                    <span class="theme-icon-light">☀️</span>
                </button>
                <button type="button" class="lang-button">Русский</button>
            </div>
        </div>
        <div class="stats-row">
            <div class="stat-pill stat-purple">
                <span class="stat-number"><?php echo $total_news; ?></span>
                <span class="stat-label">Новостей</span>
            </div>
            <div class="stat-pill stat-blue">
                <span class="stat-number"><?php echo $total_countries; ?></span>
                <span class="stat-label">Стран</span>
            </div>
            <div class="stat-pill stat-violet">
                <span class="stat-number"><?php echo $total_categories; ?></span>
                <span class="stat-label">Категорий</span>
            </div>
        </div>
    </header>

    <!-- Filter Panel -->
    <div class="filter-panel">
        <form class="filter-form" id="news-filters">
            <div class="filter-group search-group">
                <input type="search" name="search" id="filter-search" placeholder="Поиск..." class="filter-input">
            </div>
            
            <div class="filter-group">
                <label for="filter-country">Страна</label>
                <select name="country" id="filter-country" class="filter-select">
                    <option value="">Все страны</option>
                    <?php foreach ($countries as $country): ?>
                        <option value="<?php echo htmlspecialchars($country['code']); ?>"
                            <?php echo ($currentFilters['country'] ?? '') === $country['code'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars(($country['flag'] ?? '') . ' ' . $country['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="filter-category">Категория</label>
                <select name="category" id="filter-category" class="filter-select">
                    <option value="">Все категории</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat['slug']); ?>"
                            <?php echo ($currentFilters['category'] ?? '') === $cat['slug'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="filter-language">Language</label>
                <select name="language" id="filter-language" class="filter-select">
                    <option value="">controls.all_languages</option>
                    <?php foreach ($languages as $lang): ?>
                        <option value="<?php echo htmlspecialchars($lang['code']); ?>"
                            <?php echo ($currentFilters['language'] ?? '') === $lang['code'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($lang['name'] ?? strtoupper($lang['code'])); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="filter-sort">Сортировка</label>
                <select name="sort" id="filter-sort" class="filter-select">
                    <option value="newest">Свежие сначала</option>
                    <option value="oldest">Старые сначала</option>
                    <option value="relevance">По релевантности</option>
                </select>
            </div>
            
            <div class="filter-group filter-actions">
                <button type="button" class="clear-filters-btn" id="clear-filters">
                    <span class="btn-icon">🔄</span> Очистить
                </button>
            </div>
        </form>
        <div class="filter-summary">
            <span class="total-count">Всего: <strong><?php echo $total_news; ?> Новостей</strong></span>
        </div>
    </div>

    <!-- Layout Toggle -->
    <div class="layout-toggle-row">
        <button type="button" class="layout-toggle-btn" id="layout-list" data-layout="list" title="Список">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><rect x="0" y="1" width="16" height="3" rx="1"/><rect x="0" y="6" width="16" height="3" rx="1"/><rect x="0" y="11" width="16" height="3" rx="1"/></svg>
        </button>
        <button type="button" class="layout-toggle-btn active" id="layout-grid" data-layout="grid" title="Сетка">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><rect x="0" y="0" width="7" height="7" rx="1"/><rect x="9" y="0" width="7" height="7" rx="1"/><rect x="0" y="9" width="7" height="7" rx="1"/><rect x="9" y="9" width="7" height="7" rx="1"/></svg>
        </button>
    </div>

    <!-- Main Content Grid -->
    <div class="main-content-grid">
        <main class="news-column layout-grid" id="news-container" data-layout="grid">
            <?php if (empty($articles)): ?>
                <div class="no-results-card">
                    <p>Пока нет опубликованных новостей.</p>
                    <p>Новости появятся после обработки RSS-лент.</p>
                </div>
            <?php else: ?>
                <?php foreach ($articles as $index => $article): ?>
                    <?php
                        $placeholder = '/images/placeholders/placeholder.svg';
                        $imageUrl = !empty($article['image_url']) ? htmlspecialchars($article['image_url']) : $placeholder;
                        $displayTitle = $article['display_title'] ?? $article['title_ru'] ?? $article['original_title'];
                        $displaySummary = $article['display_summary'] ?? $article['summary_ru'] ?? $article['original_summary'] ?? '';
                        $countryFlag = $article['country_flag'] ?? '';
                        $countryName = $article['country_name'] ?? '';
                        $langCode = strtoupper($article['original_language'] ?? '');
                        $categoryName = $article['category_name'] ?? '';
                        $categoryColor = $article['category_color'] ?? '#8B5CF6';
                    ?>
                    <article class="news-card <?php echo $index % 2 === 0 ? 'card-left' : 'card-right'; ?>">
                        <div class="news-card-image-wrapper">
                            <img src="<?php echo $imageUrl; ?>" 
                                 alt="<?php echo htmlspecialchars($displayTitle); ?>" 
                                 class="news-card-image" 
                                 loading="lazy"
                                 onerror="this.src='<?php echo $placeholder; ?>'">
                        </div>
                        <div class="news-card-content">
                            <div class="news-card-meta">
                                <?php if ($countryFlag || $countryName): ?>
                                <span class="meta-item country-meta">
                                    <span class="flag-icon"><?php echo htmlspecialchars($countryFlag); ?></span>
                                    <?php echo htmlspecialchars($countryName); ?>
                                </span>
                                <?php endif; ?>
                                <?php if ($langCode): ?>
                                <span class="meta-item lang-badge"><?php echo htmlspecialchars($langCode); ?></span>
                                <?php endif; ?>
                                <span class="meta-item date-meta">
                                    📅 <?php echo htmlspecialchars($formatDate($article['published_at'] ?? null)); ?>
                                </span>
                            </div>

                            <?php if ($categoryName): ?>
                            <span class="category-tag" style="background-color: <?php echo htmlspecialchars($categoryColor); ?>">
                                <?php echo htmlspecialchars($categoryName); ?>
                            </span>
                            <?php endif; ?>

                            <h2 class="news-card-title">
                                <?php echo htmlspecialchars($displayTitle); ?>
                            </h2>

                            <p class="news-card-summary">
                                <?php echo htmlspecialchars(mb_substr($displaySummary, 0, 300)); ?>
                                <?php echo mb_strlen($displaySummary) > 300 ? '...' : ''; ?>
                            </p>

                            <div class="news-card-footer">
                                <a href="<?php echo htmlspecialchars($article['original_url']); ?>" 
                                   target="_blank" 
                                   rel="noopener noreferrer" 
                                   class="source-link">
                                    🔗 <?php echo htmlspecialchars($get_source_name($article['original_url'])); ?>, inoreader.com ↗
                                </a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </main>

        <aside class="sidebar-column">
            <div class="sidebar-widget events-widget">
                <h3 class="sidebar-title">📅 Ближайшие события</h3>
                <div class="sidebar-content">
                    <div class="event-item">
                        <div class="event-date">19–21 окт.</div>
                        <div class="event-info">
                            <div class="event-name">European Vanlife Summit 2025</div>
                            <div class="event-location">🇵🇹 Португалия • Фестиваль</div>
                        </div>
                    </div>
                    <div class="event-item">
                        <div class="event-date">28–30 ноя.</div>
                        <div class="event-info">
                            <div class="event-name">Чэнду 2025 — выставка автодомов и кемперов на Chengdu RV Show</div>
                            <div class="event-location">🇨🇳 Китай • Выставки</div>
                        </div>
                    </div>
                </div>
                <a href="#" class="sidebar-link">Все события →</a>
            </div>
        </aside>
    </div>

    <div id="pagination-container"></div>
</div>

<script src="/js/filters.js"></script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
