<?php require __DIR__ . '/layout/header.php'; ?>

<div class="moderation">
    <div class="page-header">
        <h1>Модерация</h1>
        <span class="badge"><?= number_format($totalCount) ?> статей</span>
    </div>

    <?php if (empty($articles)): ?>
        <div class="empty-state">
            <p>Нет статей на модерации</p>
            <a href="/admin" class="btn btn-secondary">Вернуться в Dashboard</a>
        </div>
    <?php else: ?>
        <div class="articles-list">
            <?php foreach ($articles as $article): ?>
                <div class="article-card moderation-card">
                    <div class="article-header">
                        <div class="article-meta">
                            <?php if ($article['country_flag']): ?>
                                <span class="country"><?= $article['country_flag'] ?> <?= htmlspecialchars($article['country_name'] ?? $article['country_code']) ?></span>
                            <?php endif; ?>
                            <?php if ($article['category_name']): ?>
                                <span class="category" style="background-color: <?= $article['category_color'] ?? '#666' ?>">
                                    <?= $article['category_icon'] ?? '' ?> <?= htmlspecialchars($article['category_name']) ?>
                                </span>
                            <?php endif; ?>
                            <span class="date"><?= date('d.m.Y H:i', strtotime($article['published_at'])) ?></span>
                            <?php if ($article['ai_relevance_score']): ?>
                                <span class="relevance" title="Релевантность">
                                    <?= $article['ai_relevance_score'] ?>%
                                </span>
                            <?php endif; ?>
                        </div>
                        <span class="source"><?= htmlspecialchars($article['source_name'] ?? 'Unknown') ?></span>
                    </div>

                    <h3 class="article-title">
                        <?= htmlspecialchars($article['display_title']) ?>
                    </h3>

                    <?php if ($article['display_title'] !== $article['original_title']): ?>
                        <p class="original-title">
                            <small>Оригинал: <?= htmlspecialchars($article['original_title']) ?></small>
                        </p>
                    <?php endif; ?>

                    <?php if ($article['display_summary']): ?>
                        <p class="article-summary">
                            <?= htmlspecialchars(mb_substr($article['display_summary'], 0, 300)) ?>
                            <?= mb_strlen($article['display_summary']) > 300 ? '...' : '' ?>
                        </p>
                    <?php endif; ?>

                    <?php if ($article['moderation_reason']): ?>
                        <div class="moderation-reason">
                            <strong>Причина модерации:</strong> <?= htmlspecialchars($article['moderation_reason']) ?>
                        </div>
                    <?php endif; ?>

                    <div class="article-actions">
                        <a href="<?= htmlspecialchars($article['original_url']) ?>" target="_blank" class="btn btn-sm btn-outline">
                            Оригинал &nearr;
                        </a>

                        <form method="POST" action="/admin/article/<?= $article['id'] ?>/approve" class="inline-form">
                            <input type="hidden" name="redirect" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
                            <button type="submit" class="btn btn-sm btn-success">Одобрить</button>
                        </form>

                        <form method="POST" action="/admin/article/<?= $article['id'] ?>/reject" class="inline-form">
                            <input type="hidden" name="redirect" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
                            <input type="hidden" name="reason" value="Отклонено модератором">
                            <button type="submit" class="btn btn-sm btn-danger">Отклонить</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($currentPage > 1): ?>
                    <a href="?page=<?= $currentPage - 1 ?>" class="btn btn-sm btn-outline">&larr; Назад</a>
                <?php endif; ?>

                <span class="pagination-info">
                    Страница <?= $currentPage ?> из <?= $totalPages ?>
                </span>

                <?php if ($currentPage < $totalPages): ?>
                    <a href="?page=<?= $currentPage + 1 ?>" class="btn btn-sm btn-outline">Вперёд &rarr;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/layout/footer.php'; ?>
