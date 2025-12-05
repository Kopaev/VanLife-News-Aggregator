<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Database;
use App\Core\Response;
use App\Repository\ArticleRepository;
use App\Repository\SourceRepository;
use App\Service\AuthService;

/**
 * Admin panel controller
 */
class AdminController
{
    private AuthService $authService;
    private ArticleRepository $articleRepository;
    private SourceRepository $sourceRepository;
    private Database $database;

    public function __construct(
        AuthService $authService,
        ArticleRepository $articleRepository,
        SourceRepository $sourceRepository,
        Database $database
    ) {
        $this->authService = $authService;
        $this->articleRepository = $articleRepository;
        $this->sourceRepository = $sourceRepository;
        $this->database = $database;
    }

    /**
     * Show login form
     */
    public function loginForm(): Response
    {
        if ($this->authService->isAuthenticated()) {
            return Response::redirect('/admin');
        }

        return Response::view('admin/login', [
            'error' => null,
        ]);
    }

    /**
     * Process login
     */
    public function login(): Response
    {
        if ($this->authService->isAuthenticated()) {
            return Response::redirect('/admin');
        }

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            return Response::view('admin/login', [
                'error' => 'Заполните все поля',
            ]);
        }

        if ($this->authService->login($username, $password)) {
            return Response::redirect('/admin');
        }

        return Response::view('admin/login', [
            'error' => 'Неверный логин или пароль',
        ]);
    }

    /**
     * Process logout
     */
    public function logout(): Response
    {
        $this->authService->logout();
        return Response::redirect('/admin/login');
    }

    /**
     * Dashboard with statistics
     */
    public function dashboard(): Response
    {
        $admin = $this->requireAuth();
        if ($admin instanceof Response) {
            return $admin;
        }

        $stats = $this->getStatistics();

        return Response::view('admin/dashboard', [
            'admin' => $admin,
            'stats' => $stats,
        ]);
    }

    /**
     * Moderation queue
     */
    public function moderation(): Response
    {
        $admin = $this->requireAuth();
        if ($admin instanceof Response) {
            return $admin;
        }

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 20;

        $articles = $this->articleRepository->getArticlesForModeration($perPage, ($page - 1) * $perPage);
        $totalCount = $this->articleRepository->getModerationCount();
        $totalPages = max(1, (int) ceil($totalCount / $perPage));

        return Response::view('admin/moderation', [
            'admin' => $admin,
            'articles' => $articles,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalCount' => $totalCount,
        ]);
    }

    /**
     * Approve article
     */
    public function approve(array $params): Response
    {
        $admin = $this->requireAuth();
        if ($admin instanceof Response) {
            return $admin;
        }

        $id = (int) ($params['id'] ?? 0);

        if ($id > 0) {
            $this->articleRepository->updateStatus($id, 'published');
        }

        $redirect = $_POST['redirect'] ?? '/admin/moderation';
        return Response::redirect($redirect);
    }

    /**
     * Reject article
     */
    public function reject(array $params): Response
    {
        $admin = $this->requireAuth();
        if ($admin instanceof Response) {
            return $admin;
        }

        $id = (int) ($params['id'] ?? 0);
        $reason = trim($_POST['reason'] ?? 'Отклонено модератором');

        if ($id > 0) {
            $this->articleRepository->rejectArticle($id, $reason);
        }

        $redirect = $_POST['redirect'] ?? '/admin/moderation';
        return Response::redirect($redirect);
    }

    /**
     * Sources management
     */
    public function sources(): Response
    {
        $admin = $this->requireAuth();
        if ($admin instanceof Response) {
            return $admin;
        }

        $sources = $this->sourceRepository->getAllSources();

        return Response::view('admin/sources', [
            'admin' => $admin,
            'sources' => $sources,
        ]);
    }

    /**
     * Toggle source enabled/disabled
     */
    public function toggleSource(array $params): Response
    {
        $admin = $this->requireAuth();
        if ($admin instanceof Response) {
            return $admin;
        }

        $id = (int) ($params['id'] ?? 0);

        if ($id > 0) {
            $this->sourceRepository->toggleEnabled($id);
        }

        return Response::redirect('/admin/sources');
    }

    /**
     * View logs
     */
    public function logs(): Response
    {
        $admin = $this->requireAuth();
        if ($admin instanceof Response) {
            return $admin;
        }

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $level = $_GET['level'] ?? null;
        $context = $_GET['context'] ?? null;
        $perPage = 50;

        $logs = $this->getLogs($perPage, ($page - 1) * $perPage, $level, $context);
        $totalCount = $this->getLogsCount($level, $context);
        $totalPages = max(1, (int) ceil($totalCount / $perPage));

        return Response::view('admin/logs', [
            'admin' => $admin,
            'logs' => $logs,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalCount' => $totalCount,
            'currentLevel' => $level,
            'currentContext' => $context,
        ]);
    }

    /**
     * Require authentication or redirect to login
     */
    private function requireAuth(): array|Response
    {
        $admin = $this->authService->getCurrentAdmin();

        if (!$admin) {
            return Response::redirect('/admin/login');
        }

        return $admin;
    }

    /**
     * Get dashboard statistics
     */
    private function getStatistics(): array
    {
        // Articles by status
        $statusCounts = $this->database->fetchAll(
            'SELECT status, COUNT(*) as count FROM articles GROUP BY status'
        );
        $byStatus = [];
        foreach ($statusCounts as $row) {
            $byStatus[$row['status']] = (int) $row['count'];
        }

        // Articles today
        $todayCount = $this->database->fetchOne(
            'SELECT COUNT(*) as count FROM articles WHERE DATE(created_at) = CURDATE()'
        );

        // Articles this week
        $weekCount = $this->database->fetchOne(
            'SELECT COUNT(*) as count FROM articles WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)'
        );

        // Sources count
        $sourcesCount = $this->database->fetchOne(
            'SELECT COUNT(*) as total, SUM(is_enabled) as enabled FROM sources'
        );

        // Clusters count
        $clustersCount = $this->database->fetchOne(
            'SELECT COUNT(*) as count FROM clusters WHERE is_active = 1'
        );

        // Recent errors
        $recentErrors = $this->database->fetchAll(
            'SELECT * FROM logs WHERE level IN ("error", "critical") ORDER BY created_at DESC LIMIT 5'
        );

        // Last fetch metrics
        $lastFetch = $this->database->fetchOne(
            'SELECT * FROM metrics WHERE type = "fetch_run" ORDER BY created_at DESC LIMIT 1'
        );

        // Last process metrics
        $lastProcess = $this->database->fetchOne(
            'SELECT * FROM metrics WHERE type = "process_run" ORDER BY created_at DESC LIMIT 1'
        );

        return [
            'articles' => [
                'total' => array_sum($byStatus),
                'by_status' => $byStatus,
                'today' => (int) ($todayCount['count'] ?? 0),
                'week' => (int) ($weekCount['count'] ?? 0),
            ],
            'sources' => [
                'total' => (int) ($sourcesCount['total'] ?? 0),
                'enabled' => (int) ($sourcesCount['enabled'] ?? 0),
            ],
            'clusters' => (int) ($clustersCount['count'] ?? 0),
            'recent_errors' => $recentErrors,
            'last_fetch' => $lastFetch,
            'last_process' => $lastProcess,
        ];
    }

    /**
     * Get logs with filtering
     */
    private function getLogs(int $limit, int $offset, ?string $level, ?string $context): array
    {
        $sql = 'SELECT * FROM logs WHERE 1=1';
        $params = [];

        if ($level) {
            $sql .= ' AND level = :level';
            $params['level'] = $level;
        }

        if ($context) {
            $sql .= ' AND context = :context';
            $params['context'] = $context;
        }

        $sql .= ' ORDER BY created_at DESC LIMIT :limit OFFSET :offset';
        $params['limit'] = $limit;
        $params['offset'] = $offset;

        return $this->database->fetchAll($sql, $params);
    }

    /**
     * Get logs count with filtering
     */
    private function getLogsCount(?string $level, ?string $context): int
    {
        $sql = 'SELECT COUNT(*) as count FROM logs WHERE 1=1';
        $params = [];

        if ($level) {
            $sql .= ' AND level = :level';
            $params['level'] = $level;
        }

        if ($context) {
            $sql .= ' AND context = :context';
            $params['context'] = $context;
        }

        $result = $this->database->fetchOne($sql, $params);
        return (int) ($result['count'] ?? 0);
    }
}
