<?php

namespace App\Controller;

use App\Core\Response;
use App\Repository\ArticleRepository;
use App\Service\SeoService;

class ArticleController
{
    private ArticleRepository $articleRepository;
    private SeoService $seoService;

    public function __construct(ArticleRepository $articleRepository)
    {
        $this->articleRepository = $articleRepository;
        $this->seoService = new SeoService();
    }

    public function show(string $slug): Response
    {
        $article = $this->articleRepository->findBySlug($slug);

        if (!$article) {
            return Response::view('pages/404', [
                'seo' => [
                    'title' => 'Страница не найдена — VanLife News',
                    'description' => 'Запрошенная страница не найдена.',
                    'robots' => 'noindex, nofollow',
                ],
            ], 404);
        }

        // Configure SEO for article page
        $this->seoService->configureForArticle($article);

        return Response::view('pages/article', [
            'article' => $article,
            'seoService' => $this->seoService,
            'seo' => $this->seoService->getData(),
        ]);
    }
}
