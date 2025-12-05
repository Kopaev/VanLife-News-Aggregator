<?php

namespace App\Controller;

use App\Core\Database;
use App\Core\Response;
use App\Repository\ArticleRepository;
use App\Repository\ClusterRepository;

class HomeController
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

    public function index(): Response
    {
        $articles = $this->articleRepository->getLatestArticles();
        $clusters = $this->clusterRepository->getLatestClusters(8);
        $filtersData = $this->getFiltersData();

        return Response::view('pages/home', [
            'articles' => $articles,
            'clusters' => $clusters,
            'categories' => $filtersData['categories'],
            'countries' => $filtersData['countries'],
            'languages' => $filtersData['languages'],
            'currentFilters' => [
                'category' => $_GET['category'] ?? null,
                'country' => $_GET['country'] ?? null,
                'language' => $_GET['language'] ?? null,
                'period' => $_GET['period'] ?? null,
            ],
        ]);
    }

    private function getFiltersData(): array
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

        return [
            'categories' => $categories,
            'countries' => $countries,
            'languages' => $languages,
        ];
    }
}
