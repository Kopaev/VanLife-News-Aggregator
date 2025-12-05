<?php

namespace App\Controller;

use App\Core\Response;
use App\Repository\ArticleRepository;

class ArticleController
{
    private ArticleRepository $articleRepository;

    public function __construct(ArticleRepository $articleRepository)
    {
        $this->articleRepository = $articleRepository;
    }

    public function show(string $slug): Response
    {
        $article = $this->articleRepository->findBySlug($slug);

        if (!$article) {
            return Response::view('pages/404', [], 404);
        }

        return Response::view('pages/article', ['article' => $article]);
    }
}
