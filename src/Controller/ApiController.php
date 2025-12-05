<?php

namespace App\Controller;

use App\Core\Response;
use App\Repository\ArticleRepository;
use App\Repository\ClusterRepository;
use App\Core\Database;

class ApiController
{
    private ArticleRepository $articleRepository;
    private ClusterRepository $clusterRepository;
    private Database $database;

    public function __construct(
        ArticleRepository $articleRepository,
        ClusterRepository $clusterRepository,
        Database $database
    ) {
        $this->articleRepository = $articleRepository;
        $this->clusterRepository = $clusterRepository;
        $this->database = $database;
    }

    /**
     * GET /api/filters
     * Returns available filter options
     */
    public function filters(): Response
    {
        // Get all active categories with article counts
        $categories = $this->database->fetchAll(
            'SELECT c.slug, c.name_ru AS name, c.icon, c.color,
                    COUNT(a.id) AS count
             FROM categories c
             LEFT JOIN articles a ON a.category_slug = c.slug AND a.status IN ("published", "moderation")
             WHERE c.is_active = 1
             GROUP BY c.slug
             ORDER BY c.priority DESC, c.name_ru ASC'
        );

        // Get all countries with article counts
        $countries = $this->database->fetchAll(
            'SELECT country.code, country.name_ru AS name, country.flag_emoji AS flag,
                    COUNT(a.id) AS count
             FROM countries country
             LEFT JOIN articles a ON a.country_code = country.code AND a.status IN ("published", "moderation")
             WHERE country.is_active = 1
             GROUP BY country.code
             HAVING count > 0
             ORDER BY count DESC, country.name_ru ASC'
        );

        // Get all original languages with article counts
        $languages = $this->database->fetchAll(
            'SELECT a.original_language AS code,
                    l.name_ru AS name,
                    l.name_native,
                    COUNT(a.id) AS count
             FROM articles a
             LEFT JOIN languages l ON l.code = a.original_language
             WHERE a.status IN ("published", "moderation")
             GROUP BY a.original_language
             ORDER BY count DESC'
        );

        return Response::json([
            'categories' => $categories,
            'countries' => $countries,
            'languages' => $languages,
        ]);
    }

    /**
     * GET /api/news?category=...&country=...&language=...&period=...&page=1&limit=20
     * Returns filtered news articles
     */
    public function news(): Response
    {
        // Parse query parameters
        $category = $_GET['category'] ?? null;
        $country = $_GET['country'] ?? null;
        $language = $_GET['language'] ?? null;
        $period = $_GET['period'] ?? null; // today, week, month, all
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(100, max(1, (int)($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        // Get filtered articles
        $articles = $this->articleRepository->getFilteredArticles(
            category: $category,
            country: $country,
            language: $language,
            period: $period,
            limit: $limit,
            offset: $offset
        );

        // Get total count for pagination
        $total = $this->articleRepository->getFilteredCount(
            category: $category,
            country: $country,
            language: $language,
            period: $period
        );

        return Response::json([
            'articles' => $articles,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => (int)ceil($total / $limit),
            ],
            'filters' => [
                'category' => $category,
                'country' => $country,
                'language' => $language,
                'period' => $period,
            ],
        ]);
    }

    /**
     * GET /api/clusters?category=...&country=...&page=1&limit=20
     * Returns filtered clusters
     */
    public function clusters(): Response
    {
        $category = $_GET['category'] ?? null;
        $country = $_GET['country'] ?? null;
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(100, max(1, (int)($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        // Get filtered clusters
        $clusters = $this->clusterRepository->getFilteredClusters(
            category: $category,
            country: $country,
            limit: $limit,
            offset: $offset
        );

        // Get total count
        $total = $this->clusterRepository->getFilteredCount(
            category: $category,
            country: $country
        );

        return Response::json([
            'clusters' => $clusters,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => (int)ceil($total / $limit),
            ],
            'filters' => [
                'category' => $category,
                'country' => $country,
            ],
        ]);
    }
}
