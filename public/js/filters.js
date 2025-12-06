/**
 * VanLife News Aggregator - Filters Module
 * Handles client-side filtering without page reloads
 */

class NewsFilters {
    constructor() {
        this.currentFilters = {
            category: null,
            country: null,
            language: null,
            period: null,
            sort: 'newest',
            search: '',
            page: 1
        };

        this.isLoading = false;
        this.filtersData = null;

        this.init();
    }

    async init() {
        // Load filter options from API
        await this.loadFiltersData();

        // Parse URL parameters
        this.parseUrlParams();

        // Bind event listeners
        this.bindEvents();

        // Apply initial active states
        this.updateActiveFilters();
    }

    async loadFiltersData() {
        try {
            const response = await fetch('/api/filters');
            if (!response.ok) throw new Error('Failed to load filters');
            this.filtersData = await response.json();
        } catch (error) {
            console.error('Error loading filters:', error);
        }
    }

    parseUrlParams() {
        const params = new URLSearchParams(window.location.search);
        this.currentFilters.category = params.get('category') || null;
        this.currentFilters.country = params.get('country') || null;
        this.currentFilters.language = params.get('language') || null;
        this.currentFilters.period = params.get('period') || null;
        this.currentFilters.sort = params.get('sort') || 'newest';
        this.currentFilters.search = params.get('search') || '';
        this.currentFilters.page = parseInt(params.get('page') || '1', 10);
    }

    bindEvents() {
        // Country filter (select)
        const countrySelect = document.getElementById('filter-country');
        if (countrySelect) {
            countrySelect.addEventListener('change', (e) => {
                const value = e.target.value;
                this.setFilter('country', value || null);
            });
        }

        // Category filter (select)
        const categorySelect = document.getElementById('filter-category');
        if (categorySelect) {
            categorySelect.addEventListener('change', (e) => {
                const value = e.target.value;
                this.setFilter('category', value || null);
            });
        }

        // Language filter (select)
        const languageSelect = document.getElementById('filter-language');
        if (languageSelect) {
            languageSelect.addEventListener('change', (e) => {
                const value = e.target.value;
                this.setFilter('language', value || null);
            });
        }

        // Sort filter (select)
        const sortSelect = document.getElementById('filter-sort');
        if (sortSelect) {
            sortSelect.addEventListener('change', (e) => {
                const value = e.target.value;
                this.setFilter('sort', value || 'newest');
            });
        }

        // Search input
        const searchInput = document.getElementById('filter-search');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.setFilter('search', e.target.value.trim() || '');
                }, 500); // Debounce search
            });
            
            // Set initial value from URL
            searchInput.value = this.currentFilters.search;
        }

        // Clear filters button
        const clearBtn = document.getElementById('clear-filters');
        if (clearBtn) {
            clearBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.clearFilters();
            });
        }

        // Legacy button-based filters (for backwards compatibility)
        document.querySelectorAll('[data-filter-category]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const value = btn.dataset.filterCategory;
                this.setFilter('category', value === 'all' ? null : value);
            });
        });

        document.querySelectorAll('[data-filter-country]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const value = btn.dataset.filterCountry;
                this.setFilter('country', value === 'all' ? null : value);
            });
        });

        document.querySelectorAll('[data-filter-period]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const value = btn.dataset.filterPeriod;
                this.setFilter('period', value === 'all' ? null : value);
            });
        });

        // Pagination
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-page]')) {
                e.preventDefault();
                const page = parseInt(e.target.dataset.page, 10);
                this.setPage(page);
            }
        });

        // Browser back/forward
        window.addEventListener('popstate', () => {
            this.parseUrlParams();
            this.updateActiveFilters();
            this.loadNews();
        });
    }

    setFilter(filterName, value) {
        this.currentFilters[filterName] = value;
        this.currentFilters.page = 1; // Reset to first page
        this.applyFilters();
    }

    setPage(page) {
        this.currentFilters.page = page;
        this.applyFilters();
    }

    clearFilters() {
        this.currentFilters = {
            category: null,
            country: null,
            language: null,
            period: null,
            sort: 'newest',
            search: '',
            page: 1
        };
        
        // Reset form elements
        const countrySelect = document.getElementById('filter-country');
        const categorySelect = document.getElementById('filter-category');
        const languageSelect = document.getElementById('filter-language');
        const sortSelect = document.getElementById('filter-sort');
        const searchInput = document.getElementById('filter-search');
        
        if (countrySelect) countrySelect.value = '';
        if (categorySelect) categorySelect.value = '';
        if (languageSelect) languageSelect.value = '';
        if (sortSelect) sortSelect.value = 'newest';
        if (searchInput) searchInput.value = '';
        
        this.applyFilters();
    }

    async applyFilters() {
        if (this.isLoading) return;

        // Update URL
        this.updateUrl();

        // Update UI state
        this.updateActiveFilters();

        // Load filtered news
        await this.loadNews();
    }

    updateUrl() {
        const params = new URLSearchParams();

        if (this.currentFilters.category) {
            params.set('category', this.currentFilters.category);
        }
        if (this.currentFilters.country) {
            params.set('country', this.currentFilters.country);
        }
        if (this.currentFilters.language) {
            params.set('language', this.currentFilters.language);
        }
        if (this.currentFilters.period) {
            params.set('period', this.currentFilters.period);
        }
        if (this.currentFilters.sort && this.currentFilters.sort !== 'newest') {
            params.set('sort', this.currentFilters.sort);
        }
        if (this.currentFilters.search) {
            params.set('search', this.currentFilters.search);
        }
        if (this.currentFilters.page > 1) {
            params.set('page', this.currentFilters.page.toString());
        }

        const newUrl = params.toString()
            ? `${window.location.pathname}?${params.toString()}`
            : window.location.pathname;

        window.history.pushState({}, '', newUrl);
    }

    updateActiveFilters() {
        // Update select elements
        const countrySelect = document.getElementById('filter-country');
        const categorySelect = document.getElementById('filter-category');
        const languageSelect = document.getElementById('filter-language');
        const sortSelect = document.getElementById('filter-sort');
        
        if (countrySelect) {
            countrySelect.value = this.currentFilters.country || '';
        }
        if (categorySelect) {
            categorySelect.value = this.currentFilters.category || '';
        }
        if (languageSelect) {
            languageSelect.value = this.currentFilters.language || '';
        }
        if (sortSelect) {
            sortSelect.value = this.currentFilters.sort || 'newest';
        }

        // Update legacy button states
        document.querySelectorAll('[data-filter-category]').forEach(btn => {
            const value = btn.dataset.filterCategory;
            const isActive = (value === 'all' && !this.currentFilters.category)
                          || (value === this.currentFilters.category);
            btn.classList.toggle('active', isActive);
        });

        document.querySelectorAll('[data-filter-country]').forEach(btn => {
            const value = btn.dataset.filterCountry;
            const isActive = (value === 'all' && !this.currentFilters.country)
                          || (value === this.currentFilters.country);
            btn.classList.toggle('active', isActive);
        });

        document.querySelectorAll('[data-filter-period]').forEach(btn => {
            const value = btn.dataset.filterPeriod;
            const isActive = (value === 'all' && !this.currentFilters.period)
                          || (value === this.currentFilters.period);
            btn.classList.toggle('active', isActive);
        });

        // Update total count if available
        this.updateTotalCount();
    }

    updateTotalCount(total) {
        const countElement = document.querySelector('.total-count strong');
        if (countElement && typeof total === 'number') {
            countElement.textContent = `${total} Новостей`;
        }
    }

    async loadNews() {
        this.isLoading = true;
        this.showLoading();

        try {
            const params = new URLSearchParams();
            if (this.currentFilters.category) params.set('category', this.currentFilters.category);
            if (this.currentFilters.country) params.set('country', this.currentFilters.country);
            if (this.currentFilters.language) params.set('language', this.currentFilters.language);
            if (this.currentFilters.period) params.set('period', this.currentFilters.period);
            if (this.currentFilters.sort) params.set('sort', this.currentFilters.sort);
            if (this.currentFilters.search) params.set('search', this.currentFilters.search);
            params.set('page', this.currentFilters.page.toString());
            params.set('limit', '20');

            const response = await fetch(`/api/news?${params.toString()}`);
            if (!response.ok) throw new Error('Failed to load news');

            const data = await response.json();

            // Render articles
            this.renderArticles(data.articles);

            // Render pagination
            this.renderPagination(data.pagination);

            // Update total count
            this.updateTotalCount(data.pagination?.total);

            // Scroll to top
            window.scrollTo({ top: 0, behavior: 'smooth' });

        } catch (error) {
            console.error('Error loading news:', error);
            this.showError('Ошибка загрузки новостей. Попробуйте обновить страницу.');
        } finally {
            this.isLoading = false;
            this.hideLoading();
        }
    }

    renderArticles(articles) {
        const container = document.getElementById('news-container');
        if (!container) return;

        if (!articles || articles.length === 0) {
            container.innerHTML = `
                <div class="no-results-card">
                    <p>Новости не найдены</p>
                    <p>Попробуйте изменить параметры фильтрации</p>
                </div>
            `;
            return;
        }

        container.innerHTML = articles.map((article, index) => this.renderArticleCard(article, index)).join('');
    }

    renderArticleCard(article, index) {
        const displayTitle = article.display_title || article.title_ru || article.original_title || 'Без заголовка';
        const displaySummary = article.display_summary || article.summary_ru || article.original_summary || '';
        const truncatedSummary = displaySummary.length > 300 
            ? displaySummary.substring(0, 300) + '...' 
            : displaySummary;
        
        const categoryColor = article.category_color || '#8B5CF6';
        const categoryName = article.category_name || '';
        const countryFlag = article.country_flag || '';
        const countryName = article.country_name || '';
        const langCode = (article.original_language || '').toUpperCase();
        const imageUrl = article.image_url || '/images/placeholders/placeholder.svg';
        const sourceName = this.getSourceName(article.original_url || '');
        const cardClass = index % 2 === 0 ? 'card-left' : 'card-right';

        return `
            <article class="news-card ${cardClass}">
                <div class="news-card-image-wrapper">
                    <img src="${imageUrl}" 
                         alt="${this.escapeHtml(displayTitle)}" 
                         class="news-card-image" 
                         loading="lazy"
                         onerror="this.src='/images/placeholders/placeholder.svg'">
                </div>
                <div class="news-card-content">
                    <div class="news-card-meta">
                        ${countryFlag || countryName ? `
                        <span class="meta-item country-meta">
                            <span class="flag-icon">${this.escapeHtml(countryFlag)}</span>
                            ${this.escapeHtml(countryName)}
                        </span>
                        ` : ''}
                        ${langCode ? `<span class="meta-item lang-badge">${this.escapeHtml(langCode)}</span>` : ''}
                        <span class="meta-item date-meta">
                            📅 ${this.formatDate(article.published_at)}
                        </span>
                    </div>

                    ${categoryName ? `
                    <span class="category-tag" style="background-color: ${categoryColor}">
                        ${this.escapeHtml(categoryName)}
                    </span>
                    ` : ''}

                    <h2 class="news-card-title">
                        ${this.escapeHtml(displayTitle)}
                    </h2>

                    <p class="news-card-summary">
                        ${this.escapeHtml(truncatedSummary)}
                    </p>

                    <div class="news-card-footer">
                        <a href="${this.escapeHtml(article.original_url || '#')}" 
                           target="_blank" 
                           rel="noopener noreferrer" 
                           class="source-link">
                            🔗 ${this.escapeHtml(sourceName)}, inoreader.com ↗
                        </a>
                    </div>
                </div>
            </article>
        `;
    }

    getSourceName(url) {
        try {
            const host = new URL(url).hostname;
            return host.startsWith('www.') ? host.substring(4) : host;
        } catch {
            return 'источник';
        }
    }

    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    renderPagination(pagination) {
        const container = document.getElementById('pagination-container');
        if (!container) return;

        if (!pagination || pagination.pages <= 1) {
            container.innerHTML = '';
            return;
        }

        let html = '<div class="pagination">';

        // Previous button
        if (pagination.page > 1) {
            html += `<a href="#" data-page="${pagination.page - 1}" class="page-btn">← Назад</a>`;
        }

        // Page numbers
        const startPage = Math.max(1, pagination.page - 2);
        const endPage = Math.min(pagination.pages, pagination.page + 2);

        if (startPage > 1) {
            html += `<a href="#" data-page="1" class="page-num">1</a>`;
            if (startPage > 2) html += '<span class="ellipsis">...</span>';
        }

        for (let i = startPage; i <= endPage; i++) {
            const isActive = i === pagination.page;
            html += `<a href="#" data-page="${i}" class="page-num ${isActive ? 'active' : ''}">${i}</a>`;
        }

        if (endPage < pagination.pages) {
            if (endPage < pagination.pages - 1) html += '<span class="ellipsis">...</span>';
            html += `<a href="#" data-page="${pagination.pages}" class="page-num">${pagination.pages}</a>`;
        }

        // Next button
        if (pagination.page < pagination.pages) {
            html += `<a href="#" data-page="${pagination.page + 1}" class="page-btn">Вперёд →</a>`;
        }

        html += '</div>';
        container.innerHTML = html;
    }

    showLoading() {
        const container = document.getElementById('news-container');
        if (container) {
            container.classList.add('loading');
        }
    }

    hideLoading() {
        const container = document.getElementById('news-container');
        if (container) {
            container.classList.remove('loading');
        }
    }

    showError(message) {
        const container = document.getElementById('news-container');
        if (container) {
            container.innerHTML = `
                <div class="no-results-card">
                    <p>${this.escapeHtml(message)}</p>
                </div>
            `;
        }
    }

    formatDate(dateString) {
        if (!dateString) return 'дата не указана';
        
        try {
            const date = new Date(dateString);
            const now = new Date();
            const diffMs = now - date;
            const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
            const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));

            if (diffHours < 1) return 'Только что';
            if (diffHours < 24) return `${diffHours} ч. назад`;
            if (diffDays === 1) return 'Вчера';
            if (diffDays < 7) return `${diffDays} дн. назад`;

            const months = ['янв.', 'фев.', 'мар.', 'апр.', 'мая', 'июн.', 
                           'июл.', 'авг.', 'сен.', 'окт.', 'ноя.', 'дек.'];
            return `${date.getDate()} ${months[date.getMonth()]} ${date.getFullYear()} г.`;
        } catch {
            return 'неверная дата';
        }
    }
}

// Theme toggle functionality
function initThemeToggle() {
    const toggle = document.getElementById('theme-toggle');
    if (!toggle) return;

    // Get saved theme or default to system preference
    const savedTheme = localStorage.getItem('theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const initialTheme = savedTheme || (prefersDark ? 'dark' : 'light');
    
    document.documentElement.setAttribute('data-theme', initialTheme);

    toggle.addEventListener('click', () => {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
    });
}

// Initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.newsFilters = new NewsFilters();
        initThemeToggle();
    });
} else {
    window.newsFilters = new NewsFilters();
    initThemeToggle();
}
