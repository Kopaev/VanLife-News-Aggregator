<?php
/**
 * Filters Component
 *
 * @var array|null $categories
 * @var array|null $countries
 * @var array|null $languages
 * @var array|null $currentFilters
 */

$categories = $categories ?? [];
$countries = $countries ?? [];
$languages = $languages ?? [];
$currentFilters = $currentFilters ?? [
    'category' => null,
    'country' => null,
    'language' => null,
    'period' => null
];
?>

<div class="filters-panel">
    <div class="filters-section">
        <h3>Фильтры</h3>

        <!-- Period Filter -->
        <div class="filter-group">
            <label>Период:</label>
            <div class="filter-buttons period-filters">
                <button
                    class="filter-btn <?= empty($currentFilters['period']) ? 'active' : '' ?>"
                    data-filter-period="all">
                    Все
                </button>
                <button
                    class="filter-btn <?= $currentFilters['period'] === 'today' ? 'active' : '' ?>"
                    data-filter-period="today">
                    Сегодня
                </button>
                <button
                    class="filter-btn <?= $currentFilters['period'] === 'week' ? 'active' : '' ?>"
                    data-filter-period="week">
                    Неделя
                </button>
                <button
                    class="filter-btn <?= $currentFilters['period'] === 'month' ? 'active' : '' ?>"
                    data-filter-period="month">
                    Месяц
                </button>
            </div>
        </div>

        <!-- Category Filter -->
        <?php if (!empty($categories)): ?>
        <div class="filter-group">
            <label>Категория:</label>
            <div class="filter-buttons category-filters">
                <button
                    class="filter-btn <?= empty($currentFilters['category']) ? 'active' : '' ?>"
                    data-filter-category="all">
                    Все
                </button>
                <?php foreach ($categories as $cat): ?>
                    <?php if (!empty($cat['count'])): ?>
                    <button
                        class="filter-btn <?= $currentFilters['category'] === $cat['slug'] ? 'active' : '' ?>"
                        data-filter-category="<?= htmlspecialchars($cat['slug']) ?>"
                        style="<?= !empty($cat['color']) ? '--badge-color: ' . htmlspecialchars($cat['color']) : '' ?>">
                        <?= !empty($cat['icon']) ? $cat['icon'] . ' ' : '' ?>
                        <?= htmlspecialchars($cat['name']) ?>
                        <span class="count">(<?= $cat['count'] ?>)</span>
                    </button>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Country Filter -->
        <?php if (!empty($countries)): ?>
        <div class="filter-group">
            <label>Страна:</label>
            <div class="filter-buttons country-filters">
                <button
                    class="filter-btn <?= empty($currentFilters['country']) ? 'active' : '' ?>"
                    data-filter-country="all">
                    Все
                </button>
                <?php foreach ($countries as $country): ?>
                    <?php if (!empty($country['count'])): ?>
                    <button
                        class="filter-btn <?= $currentFilters['country'] === $country['code'] ? 'active' : '' ?>"
                        data-filter-country="<?= htmlspecialchars($country['code']) ?>">
                        <?= !empty($country['flag']) ? $country['flag'] . ' ' : '' ?>
                        <?= htmlspecialchars($country['name']) ?>
                        <span class="count">(<?= $country['count'] ?>)</span>
                    </button>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Language Filter -->
        <?php if (!empty($languages)): ?>
        <div class="filter-group">
            <label for="filter-language">Язык оригинала:</label>
            <select id="filter-language" class="filter-select">
                <option value="all" <?= empty($currentFilters['language']) ? 'selected' : '' ?>>
                    Все языки
                </option>
                <?php foreach ($languages as $lang): ?>
                    <?php if (!empty($lang['count'])): ?>
                    <option
                        value="<?= htmlspecialchars($lang['code']) ?>"
                        <?= $currentFilters['language'] === $lang['code'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($lang['name'] ?? $lang['code']) ?>
                        (<?= $lang['count'] ?>)
                    </option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>

        <!-- Clear Filters Button -->
        <div class="filter-actions">
            <button id="clear-filters" class="clear-btn" style="display: none;">
                Сбросить фильтры
            </button>
        </div>
    </div>
</div>

<style>
.filters-panel {
    background: var(--bg-secondary);
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 30px;
}

.filters-section h3 {
    margin: 0 0 20px 0;
    font-size: 1.2em;
    color: var(--text-primary);
}

.filter-group {
    margin-bottom: 20px;
}

.filter-group label {
    display: block;
    margin-bottom: 10px;
    font-weight: 500;
    color: var(--text-secondary);
}

.filter-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.filter-btn {
    padding: 8px 16px;
    border: 1px solid var(--border-color);
    background: var(--bg-primary);
    color: var(--text-primary);
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 0.9em;
}

.filter-btn:hover {
    background: var(--bg-hover);
}

.filter-btn.active {
    background: var(--accent-color, #007bff);
    color: white;
    border-color: var(--accent-color, #007bff);
}

.filter-btn .count {
    opacity: 0.7;
    font-size: 0.85em;
    margin-left: 4px;
}

.filter-select {
    width: 100%;
    padding: 10px;
    border: 1px solid var(--border-color);
    background: var(--bg-primary);
    color: var(--text-primary);
    border-radius: 4px;
    font-size: 0.95em;
}

.filter-actions {
    margin-top: 20px;
}

.clear-btn {
    padding: 10px 20px;
    background: var(--bg-secondary);
    color: var(--text-secondary);
    border: 1px solid var(--border-color);
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s;
}

.clear-btn:hover {
    background: var(--bg-hover);
}

/* Loading state */
#news-container.loading {
    opacity: 0.5;
    pointer-events: none;
}

/* Pagination */
.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
    margin-top: 30px;
    padding: 20px;
}

.page-btn,
.page-num {
    padding: 8px 12px;
    background: var(--bg-secondary);
    color: var(--text-primary);
    border: 1px solid var(--border-color);
    border-radius: 4px;
    text-decoration: none;
    transition: all 0.2s;
}

.page-btn:hover,
.page-num:hover {
    background: var(--bg-hover);
}

.page-num.active {
    background: var(--accent-color, #007bff);
    color: white;
    border-color: var(--accent-color, #007bff);
}

.ellipsis {
    padding: 8px;
    color: var(--text-secondary);
}

.no-results,
.error {
    text-align: center;
    padding: 40px 20px;
    color: var(--text-secondary);
    font-size: 1.1em;
}

.error {
    color: #dc3545;
}

/* Responsive */
@media (max-width: 768px) {
    .filter-buttons {
        flex-direction: column;
    }

    .filter-btn {
        width: 100%;
        text-align: left;
    }

    .pagination {
        flex-wrap: wrap;
    }
}
</style>
