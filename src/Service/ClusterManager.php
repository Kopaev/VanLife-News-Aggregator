<?php

namespace App\Service;

use App\Core\Config;
use App\Repository\ArticleRepository;
use App\Repository\ClusterRepository;
use RuntimeException;

class ClusterManager
{
    private int $batchSize;
    private int $candidateLimit;
    private int $attachLimit;
    private int $candidateWindowHours;

    public function __construct(
        private readonly ClusteringService $clusteringService,
        private readonly ArticleRepository $articleRepository,
        private readonly ClusterRepository $clusterRepository,
        private readonly ClusterMainSelector $mainSelector,
        private readonly LoggerService $logger,
        private readonly Config $config
    ) {
        $settings = $this->config->get('clustering.algorithm', []);

        if (!is_array($settings)) {
            throw new RuntimeException('Clustering algorithm config must be an array.');
        }

        $this->batchSize = (int)($settings['batch_size'] ?? 20);
        $this->candidateLimit = (int)($settings['candidates_limit'] ?? 80);
        $this->attachLimit = (int)($settings['attach_limit'] ?? 5);
        $this->candidateWindowHours = (int)$this->config->get('clustering.candidate_window_hours', 120);
    }

    public function clusterUnassigned(?int $limit = null): int
    {
        $batch = $limit ?? $this->batchSize;
        $articles = $this->articleRepository->getArticlesForClustering($batch);
        $processed = 0;

        foreach ($articles as $article) {
            try {
                $this->clusterArticle($article);
                $processed++;
            } catch (\Throwable $e) {
                $this->logger->error('ClusterManager', 'Failed to cluster article', [
                    'article_id' => $article['id'] ?? null,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $processed;
    }

    private function clusterArticle(array $article): void
    {
        $candidates = $this->articleRepository->getCandidatesForClustering(
            (int)$article['id'],
            $this->candidateWindowHours,
            $this->candidateLimit
        );

        $similar = $this->clusteringService->findSimilar($article, $candidates);

        if (empty($similar)) {
            $clusterId = $this->clusterRepository->createFromArticles($article);
            $this->articleRepository->assignToCluster([(int)$article['id']], $clusterId);
            $this->finalizeCluster($clusterId);
            $this->logger->info('ClusterManager', 'Created new cluster for isolated article', [
                'article_id' => $article['id'],
                'cluster_id' => $clusterId,
            ]);

            return;
        }

        $targetClusterId = $this->selectTargetClusterId($similar, $candidates);
        $unclusteredMatches = $this->collectUnclusteredMatches($similar, $candidates);

        if ($targetClusterId === null) {
            $clusterId = $this->clusterRepository->createFromArticles($article, $unclusteredMatches);
            $articleIds = array_merge([(int)$article['id']], $this->extractIds($unclusteredMatches));
            $this->articleRepository->assignToCluster($articleIds, $clusterId);
            $this->finalizeCluster($clusterId);

            $this->logger->info('ClusterManager', 'Created new cluster with similar articles', [
                'cluster_id' => $clusterId,
                'article_id' => $article['id'],
                'merged_article_ids' => $this->extractIds($unclusteredMatches),
            ]);

            return;
        }

        $attachIds = $this->extractIds($unclusteredMatches);
        $articleIds = array_merge([(int)$article['id']], $attachIds);
        $this->articleRepository->assignToCluster($articleIds, $targetClusterId);
        $this->finalizeCluster($targetClusterId);

        $this->logger->info('ClusterManager', 'Attached article to existing cluster', [
            'cluster_id' => $targetClusterId,
            'article_id' => $article['id'],
            'auto_attached' => $attachIds,
        ]);
    }

    private function selectTargetClusterId(array $similar, array $candidates): ?int
    {
        $scoresByCluster = [];
        $clusterByArticle = [];

        foreach ($candidates as $candidate) {
            $clusterByArticle[(int)$candidate['id']] = $candidate['cluster_id'] ?? null;
        }

        foreach ($similar as $match) {
            $clusterId = $clusterByArticle[(int)$match['id']] ?? null;
            if ($clusterId === null) {
                continue;
            }

            $scoresByCluster[(int)$clusterId][] = (float)$match['score'];
        }

        if (empty($scoresByCluster)) {
            return null;
        }

        $bestClusterId = null;
        $bestScore = 0.0;

        foreach ($scoresByCluster as $clusterId => $scores) {
            $avg = array_sum($scores) / max(count($scores), 1);
            if ($avg > $bestScore) {
                $bestScore = $avg;
                $bestClusterId = (int)$clusterId;
            }
        }

        return $bestClusterId;
    }

    private function collectUnclusteredMatches(array $similar, array $candidates): array
    {
        $result = [];
        $candidatesById = [];

        foreach ($candidates as $candidate) {
            $candidatesById[(int)$candidate['id']] = $candidate;
        }

        foreach ($similar as $match) {
            $candidate = $candidatesById[(int)$match['id']] ?? null;
            if ($candidate === null) {
                continue;
            }

            if ($candidate['cluster_id'] !== null) {
                continue;
            }

            $result[] = $candidate;

            if (count($result) >= $this->attachLimit) {
                break;
            }
        }

        return $result;
    }

    private function extractIds(array $articles): array
    {
        return array_values(array_map(static fn(array $article): int => (int)$article['id'], $articles));
    }

    private function finalizeCluster(int $clusterId): void
    {
        $this->clusterRepository->recalculateStats($clusterId);

        $mainArticleId = $this->mainSelector->selectMainArticleId(
            $this->articleRepository->getClusterArticles($clusterId)
        );

        if ($mainArticleId === null) {
            return;
        }

        $this->clusterRepository->setMainArticle($clusterId, $mainArticleId);

        $this->logger->info('ClusterManager', 'Selected main article for cluster', [
            'cluster_id' => $clusterId,
            'main_article_id' => $mainArticleId,
        ]);
    }
}
