<?php

namespace App\Controller;

use App\Core\Response;
use App\Repository\ArticleRepository;
use App\Repository\ClusterRepository;

class ClusterController
{
    public function __construct(
        private readonly ClusterRepository $clusterRepository,
        private readonly ArticleRepository $articleRepository
    ) {
    }

    public function index(): Response
    {
        $clusters = $this->clusterRepository->getLatestClusters();

        return Response::view('pages/clusters', [
            'clusters' => $clusters,
        ]);
    }

    public function show(string $slug): Response
    {
        $cluster = $this->clusterRepository->findBySlug($slug);

        if ($cluster === null) {
            return Response::view('pages/404', [], 404);
        }

        $articles = $this->articleRepository->getClusterArticles((int)$cluster['id']);

        return Response::view('pages/cluster', [
            'cluster' => $cluster,
            'articles' => $articles,
        ]);
    }
}
