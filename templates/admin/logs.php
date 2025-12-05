<?php require __DIR__ . '/layout/header.php'; ?>

<div class="logs">
    <div class="page-header">
        <h1>Логи</h1>
        <span class="badge"><?= number_format($totalCount) ?> записей</span>
    </div>

    <!-- Filters -->
    <div class="filters-panel">
        <form method="GET" action="/admin/logs" class="filters-form">
            <div class="filter-group">
                <label>Уровень:</label>
                <select name="level" onchange="this.form.submit()">
                    <option value="">Все</option>
                    <?php foreach (['debug', 'info', 'warning', 'error', 'critical'] as $level): ?>
                        <option value="<?= $level ?>" <?= $currentLevel === $level ? 'selected' : '' ?>>
                            <?= ucfirst($level) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label>Контекст:</label>
                <select name="context" onchange="this.form.submit()">
                    <option value="">Все</option>
                    <?php foreach (['fetcher', 'processor', 'api', 'admin', 'decoder', 'clustering'] as $ctx): ?>
                        <option value="<?= $ctx ?>" <?= $currentContext === $ctx ? 'selected' : '' ?>>
                            <?= ucfirst($ctx) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if ($currentLevel || $currentContext): ?>
                <a href="/admin/logs" class="btn btn-sm btn-outline">Сбросить</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if (empty($logs)): ?>
        <div class="empty-state">
            <p>Нет записей в логах</p>
        </div>
    <?php else: ?>
        <div class="table-container">
            <table class="data-table logs-table">
                <thead>
                    <tr>
                        <th>Время</th>
                        <th>Уровень</th>
                        <th>Контекст</th>
                        <th>Сообщение</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr class="log-row log-<?= $log['level'] ?>">
                            <td class="log-time">
                                <?= date('d.m H:i:s', strtotime($log['created_at'])) ?>
                            </td>
                            <td>
                                <span class="log-level level-<?= $log['level'] ?>">
                                    <?= strtoupper($log['level']) ?>
                                </span>
                            </td>
                            <td class="log-context"><?= htmlspecialchars($log['context']) ?></td>
                            <td class="log-message">
                                <?= htmlspecialchars($log['message']) ?>
                                <?php if ($log['details']): ?>
                                    <button type="button" class="btn btn-xs btn-outline toggle-details"
                                            onclick="this.nextElementSibling.classList.toggle('show')">
                                        Детали
                                    </button>
                                    <pre class="log-details"><?= htmlspecialchars(json_encode(json_decode($log['details']), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($currentPage > 1): ?>
                    <a href="?page=<?= $currentPage - 1 ?><?= $currentLevel ? "&level=$currentLevel" : '' ?><?= $currentContext ? "&context=$currentContext" : '' ?>"
                       class="btn btn-sm btn-outline">&larr; Назад</a>
                <?php endif; ?>

                <span class="pagination-info">
                    Страница <?= $currentPage ?> из <?= $totalPages ?>
                </span>

                <?php if ($currentPage < $totalPages): ?>
                    <a href="?page=<?= $currentPage + 1 ?><?= $currentLevel ? "&level=$currentLevel" : '' ?><?= $currentContext ? "&context=$currentContext" : '' ?>"
                       class="btn btn-sm btn-outline">Вперёд &rarr;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/layout/footer.php'; ?>
