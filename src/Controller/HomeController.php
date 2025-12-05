<?php

namespace App\Controller;

use App\Core\Response;
use App\Repository\ArticleRepository;
use App\Repository\ClusterRepository;

class HomeController
{
    private ArticleRepository $articleRepository;
    private ClusterRepository $clusterRepository;

    public function __construct(ArticleRepository $articleRepository, ClusterRepository $clusterRepository)
    {
        $this->articleRepository = $articleRepository;
        $this->clusterRepository = $clusterRepository;
    }

    public function index(): Response
    {
        $articles = $this->articleRepository->getLatestArticles();
        $clusters = $this->clusterRepository->getLatestClusters(8);

        return Response::view('pages/home', [
            'articles' => $articles,
            'clusters' => $clusters,
        ]);
    }
}
