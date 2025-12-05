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

        // Initial load
        this.applyFilters();
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
        this.currentFilters.category = params.get('category');
        this.currentFilters.country = params.get('country');
        this.currentFilters.language = params.get('language');
        this.currentFilters.period = params.get('period');
        this.currentFilters.page = parseInt(params.get('page') || '1', 10);
    }

    bindEvents() {
        // Category filter
        const categoryButtons = document.querySelectorAll('[data-filter-category]');
        categoryButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const value = btn.dataset.filterCategory;
                this.setFilter('category', value === 'all' ? null : value);
            });
        });

        // Country filter
        const countryButtons = document.querySelectorAll('[data-filter-country]');
        countryButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const value = btn.dataset.filterCountry;
                this.setFilter('country', value === 'all' ? null : value);
            });
        });

        // Language filter
        const languageSelect = document.getElementById('filter-language');
        if (languageSelect) {
            languageSelect.addEventListener('change', (e) => {
                const value = e.target.value;
                this.setFilter('language', value === 'all' ? null : value);
            });
        }

        // Period filter
        const periodButtons = document.querySelectorAll('[data-filter-period]');
        periodButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const value = btn.dataset.filterPeriod;
                this.setFilter('period', value === 'all' ? null : value);
            });
        });

        // Clear filters button
        const clearBtn = document.getElementById('clear-filters');
        if (clearBtn) {
            clearBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.clearFilters();
            });
        }

        // Pagination
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-page]')) {
                e.preventDefault();
                const page = parseInt(e.target.dataset.page, 10);
                this.setPage(page);
            }
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
            page: 1
        };
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
        if (this.currentFilters.page > 1) {
            params.set('page', this.currentFilters.page.toString());
        }

        const newUrl = params.toString()
            ? `${window.location.pathname}?${params.toString()}`
            : window.location.pathname;

        window.history.pushState({}, '', newUrl);
    }

    updateActiveFilters() {
        // Update category buttons
        document.querySelectorAll('[data-filter-category]').forEach(btn => {
            const value = btn.dataset.filterCategory;
            const isActive = (value === 'all' && !this.currentFilters.category)
                          || (value === this.currentFilters.category);
            btn.classList.toggle('active', isActive);
        });

        // Update country buttons
        document.querySelectorAll('[data-filter-country]').forEach(btn => {
            const value = btn.dataset.filterCountry;
            const isActive = (value === 'all' && !this.currentFilters.country)
                          || (value === this.currentFilters.country);
            btn.classList.toggle('active', isActive);
        });

        // Update language select
        const languageSelect = document.getElementById('filter-language');
        if (languageSelect) {
            languageSelect.value = this.currentFilters.language || 'all';
        }

        // Update period buttons
        document.querySelectorAll('[data-filter-period]').forEach(btn => {
            const value = btn.dataset.filterPeriod;
            const isActive = (value === 'all' && !this.currentFilters.period)
                          || (value === this.currentFilters.period);
            btn.classList.toggle('active', isActive);
        });

        // Show/hide clear button
        const hasActiveFilters = this.currentFilters.category
                              || this.currentFilters.country
                              || this.currentFilters.language
                              || this.currentFilters.period;
        const clearBtn = document.getElementById('clear-filters');
        if (clearBtn) {
            clearBtn.style.display = hasActiveFilters ? 'inline-block' : 'none';
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
            params.set('page', this.currentFilters.page.toString());
            params.set('limit', '20');

            const response = await fetch(`/api/news?${params.toString()}`);
            if (!response.ok) throw new Error('Failed to load news');

            const data = await response.json();

            // Render articles
            this.renderArticles(data.articles);

            // Render pagination
            this.renderPagination(data.pagination);

            // Scroll to top
            window.scrollTo({ top: 0, behavior: 'smooth' });

        } catch (error) {
            console.error('Error loading news:', error);
            this.showError('Ошибка загрузки новостей');
        } finally {
            this.isLoading = false;
            this.hideLoading();
        }
    }

    renderArticles(articles) {
        const container = document.getElementById('news-container');
        if (!container) return;

        if (articles.length === 0) {
            container.innerHTML = '<p class="no-results">Новости не найдены</p>';
            return;
        }

        container.innerHTML = articles.map(article => this.renderArticleCard(article)).join('');
    }

    renderArticleCard(article) {
        const displayTitle = article.display_title || article.original_title;
        const displaySummary = article.display_summary || article.original_summary || '';
        const categoryBadge = article.category_name
            ? `<span class="badge" style="background-color: ${article.category_color || '#666'}">${article.category_icon || ''} ${article.category_name}</span>`
            : '';
        const countryBadge = article.country_flag
            ? `<span class="country">${article.country_flag} ${article.country_name || article.country_code}</span>`
            : '';

        return `
            <article class="news-card">
                <div class="news-meta">
                    ${categoryBadge}
                    ${countryBadge}
                    <span class="status status-${article.status}">${this.getStatusText(article.status)}</span>
                </div>
                <h2><a href="/news/${article.slug}">${displayTitle}</a></h2>
                <p class="news-summary">${displaySummary.substring(0, 200)}${displaySummary.length > 200 ? '...' : ''}</p>
                <div class="news-footer">
                    <time datetime="${article.published_at}">${this.formatDate(article.published_at)}</time>
                    <span class="source">${article.source_name || ''}</span>
                </div>
            </article>
        `;
    }

    renderPagination(pagination) {
        const container = document.getElementById('pagination-container');
        if (!container) return;

        if (pagination.pages <= 1) {
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
            container.innerHTML = `<p class="error">${message}</p>`;
        }
    }

    getStatusText(status) {
        const statusMap = {
            'published': 'Опубликовано',
            'moderation': 'На модерации',
            'new': 'Новая',
            'rejected': 'Отклонено'
        };
        return statusMap[status] || status;
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffMs = now - date;
        const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
        const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));

        if (diffHours < 1) return 'Только что';
        if (diffHours < 24) return `${diffHours} ч. назад`;
        if (diffDays === 1) return 'Вчера';
        if (diffDays < 7) return `${diffDays} дн. назад`;

        return date.toLocaleDateString('ru-RU', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    }
}

// Initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.newsFilters = new NewsFilters();
    });
} else {
    window.newsFilters = new NewsFilters();
}
