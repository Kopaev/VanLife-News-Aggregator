<?php

namespace App\Controller;

use App\Core\Response;
use App\Repository\ArticleRepository;
use App\Repository\ClusterRepository;
use App\Service\SeoService;

class ClusterController
{
    private SeoService $seoService;

    public function __construct(
        private readonly ClusterRepository $clusterRepository,
        private readonly ArticleRepository $articleRepository
    ) {
        $this->seoService = new SeoService();
    }

    public function index(): Response
    {
        $clusters = $this->clusterRepository->getLatestClusters();

        // Configure SEO for clusters list
        $this->seoService->configureForClustersList();

        return Response::view('pages/clusters', [
            'clusters' => $clusters,
            'seoService' => $this->seoService,
            'seo' => $this->seoService->getData(),
        ]);
    }

    public function show(string $slug): Response
    {
        $cluster = $this->clusterRepository->findBySlug($slug);

        if ($cluster === null) {
            return Response::view('pages/404', [
                'seo' => [
                    'title' => 'Кластер не найден — VanLife News',
                    'description' => 'Запрошенный кластер не найден.',
                    'robots' => 'noindex, nofollow',
                ],
            ], 404);
        }

        $articles = $this->articleRepository->getClusterArticles((int)$cluster['id']);

        // Configure SEO for cluster page
        $this->seoService->configureForCluster($cluster);

        return Response::view('pages/cluster', [
            'cluster' => $cluster,
            'articles' => $articles,
            'seoService' => $this->seoService,
            'seo' => $this->seoService->getData(),
        ]);
    }
}
