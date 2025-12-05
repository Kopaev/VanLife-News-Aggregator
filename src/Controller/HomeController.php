<?php

namespace App\Controller;

use App\Core\Response;
use App\Repository\ArticleRepository;

class HomeController
{
    private ArticleRepository $articleRepository;

    public function __construct(ArticleRepository $articleRepository)
    {
        $this->articleRepository = $articleRepository;
    }

    public function index(): Response
    {
        $articles = $this->articleRepository->getLatestArticles();
        
        return Response::view('pages/home', ['articles' => $articles]);
    }
}
