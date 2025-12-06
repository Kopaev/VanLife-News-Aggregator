<?php
// Helper functions
$formatDate = static function (?string $datetime): string {
    if (!$datetime) return 'дата не указана';
    try {
        $date = new DateTime($datetime);
        return $date->format('d.m.Y H:i');
    } catch (Exception $e) {
        return 'неверная дата';
    }
};

$get_source_name = static function($url) {
    if(!$url) return 'Неизвестно';
    $host = parse_url($url, PHP_URL_HOST);
    if (str_starts_with($host, 'www.')) {
        return substr($host, 4);
    }
    return $host;
};

// Data from Controller
$articles = $articles ?? [];
$clusters = $clusters ?? [];
$categories = $categories ?? [];
$countries = $countries ?? [];
$languages = $languages ?? [];
$currentFilters = $currentFilters ?? [];
?>

<div class="page-container">
    <!-- Main Header -->
    <header class="main-header">
        <div class="header-content">
            <div class="header-top-row">
                <div class="logo-group">
                    <h1 class="header-title">Новости Ванлайфа</h1>
                    <p class="header-subtitle">Путешествия и Кемпинг</p>
                </div>
                <div class="header-controls">
                    <button id="lang-switcher" class="icon-button" title="Переключить язык">🇷🇺</button>
                    <button id="theme-switcher" class="icon-button" title="Переключить тему">
                        <span class="theme-icon-light">☀️</span>
                        <span class="theme-icon-dark">🌙</span>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <?php if (!empty($featuredArticle)): ?>
    <section class="hero-section">
        <div class="hero-card">
            <div class="hero-image-wrapper">
                <img src="<?php echo !empty($featuredArticle['image_url']) ? htmlspecialchars($featuredArticle['image_url']) : '/images/placeholders/placeholder.svg'; ?>" alt="<?php echo htmlspecialchars($featuredArticle['display_title']); ?>" class="hero-image">
                <span class="category-badge hero-badge"><?php echo htmlspecialchars($featuredArticle['category_name'] ?? 'Главное'); ?></span>
            </div>
            <div class="hero-content">
                <div class="hero-meta">
                    <span class="meta-item country-meta">
                        <?php echo htmlspecialchars($featuredArticle['country_flag'] ?? '🌍'); ?> <?php echo htmlspecialchars($featuredArticle['country_name'] ?? 'Мир'); ?>
                    </span>
                    <span class="meta-item date-meta">
                        <?php echo $formatDate($featuredArticle['published_at']); ?>
                    </span>
                </div>
                <h2 class="hero-title">
                    <a href="/news/<?php echo htmlspecialchars($featuredArticle['slug']); ?>" class="hero-link">
                        <?php echo htmlspecialchars($featuredArticle['display_title']); ?>
                    </a>
                </h2>
                <p class="hero-summary">
                    <?php echo htmlspecialchars($featuredArticle['display_summary'] ?? ''); ?>
                </p>
                <div class="hero-footer">
                    <a href="/news/<?php echo htmlspecialchars($featuredArticle['slug']); ?>" class="button button-primary">Читать далее</a>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Filter Panel -->
    <div class="filter-panel">
        <form class="filter-form" id="news-filters">
             <div class="filter-group custom-select-wrapper" id="country-filter-wrapper">
                <div class="custom-select-trigger">
                    <span class="custom-select-value">
                        <span class="icon">🌍</span> Все страны
                    </span>
                    <span class="custom-select-arrow">&#9662;</span>
                </div>
                <div class="custom-select-options">
                    <div class="custom-select-option" data-value="all">
                        <span class="icon">🌍</span> Все страны
                    </div>
                    <?php foreach ($countries as $country): ?>
                    <div class="custom-select-option" data-value="<?php echo htmlspecialchars($country['code']); ?>">
                        <span class="icon"><?php echo htmlspecialchars($country['flag']); ?></span>
                        <?php echo htmlspecialchars($country['name']); ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" name="country" id="country-filter-input">
            </div>

            <div class="filter-group">
                <select name="category" title="Категория">
                    <option value="">Все категории</option>
                     <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo htmlspecialchars($cat['slug']); ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <select name="language" title="Язык">
                    <option value="">Все языки</option>
                    <?php foreach ($languages as $lang): ?>
                    <option value="<?php echo htmlspecialchars($lang['code']); ?>"><?php echo htmlspecialchars($lang['name'] ?? $lang['code']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <select name="sort" title="Сортировка">
                    <option value="newest">Сначала новые</option>
                    <option value="oldest">Сначала старые</option>
                </select>
            </div>
            <div class="filter-group">
                <button type="reset" class="clear-filters-btn">Очистить</button>
            </div>
        </form>
    </div>

    <!-- Main Content Grid -->
    <div class="main-content-grid">
        <main class="news-column" id="news-container">
            <?php if (empty($articles) && empty($featuredArticle)): ?>
                <div class="no-results-card">
                    <p>Новости по вашим фильтрам не найдены.</p>
                    <p>Попробуйте изменить или сбросить фильтры.</p>
                </div>
            <?php else: ?>
                <?php foreach ($articles as $article): ?>
                    <article class="news-card">
                        <div class="news-card-image-wrapper">
                             <img src="<?php echo !empty($article['image_url']) ? htmlspecialchars($article['image_url']) : '/images/placeholders/placeholder.svg'; ?>" alt="<?php echo htmlspecialchars($article['display_title']); ?>" class="news-card-image">
                             <span class="category-badge"><?php echo htmlspecialchars($article['category_name'] ?? 'Без категории'); ?></span>
                        </div>
                        <div class="news-card-content">
                            <div class="news-card-meta">
                                <span class="meta-item country-meta">
                                    <?php echo htmlspecialchars($article['country_flag'] ?? '🌍'); ?> <?php echo htmlspecialchars($article['country_name'] ?? 'Мир'); ?>
                                </span>
                                <span class="meta-item date-meta">
                                    <?php echo $formatDate($article['published_at']); ?>
                                </span>
                            </div>

                            <h2 class="news-card-title">
                                <a href="/news/<?php echo htmlspecialchars($article['slug']); ?>" class="card-title-link">
                                    <?php echo htmlspecialchars($article['display_title']); ?>
                                </a>
                            </h2>

                            <p class="news-card-summary">
                                <?php echo htmlspecialchars($article['display_summary'] ?? 'Нет описания.'); ?>
                            </p>

                            <div class="news-card-footer">
                                <span class="meta-item lang-meta">
                                    <?php echo strtoupper(htmlspecialchars($article['original_language'] ?? '')); ?>
                                </span>
                                <a href="/news/<?php echo htmlspecialchars($article['slug']); ?>" class="footer-link read-more-link">Читать</a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </main>
        <aside class="sidebar-column">
            <?php if (!empty($clusters)): ?>
            <div class="sidebar-widget">
                <h3 class="sidebar-title">Популярные темы</h3>
                <div class="sidebar-content">
                     <ul class="clusters-list">
                        <?php foreach ($clusters as $cluster): ?>
                        <li class="cluster-item">
                            <a href="/clusters/<?php echo htmlspecialchars($cluster['slug']); ?>" class="cluster-link">
                                <span class="cluster-title"><?php echo htmlspecialchars($cluster['title_ru']); ?></span>
                                <span class="cluster-count"><?php echo $cluster['articles_count']; ?> статей</span>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <?php endif; ?>

            <div class="sidebar-widget">
                <h3 class="sidebar-title">Ближайшие события</h3>
                <div class="sidebar-content">
                     <ul class="events-list">
                        <li>
                            <span class="event-date">Август 2025</span>
                            <span class="event-name">Caravan Salon Düsseldorf</span>
                        </li>
                        <li>
                            <span class="event-date">Сентябрь 2025</span>
                            <span class="event-name">Salone del Camper</span>
                        </li>
                        <li>
                            <span class="event-date">Октябрь 2025</span>
                            <span class="event-name">Motorhome & Caravan Show</span>
                        </li>
                    </ul>
                </div>
            </div>
        </aside>
    </div>
</div>

<script src="/js/main.js"></script>
<link rel="stylesheet" href="https://rsms.me/inter/inter.css">