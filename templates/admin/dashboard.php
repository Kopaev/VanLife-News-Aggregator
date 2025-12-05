<?php require __DIR__ . '/layout/header.php'; ?>

<div class="dashboard">
    <h1>Dashboard</h1>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">📰</div>
            <div class="stat-content">
                <span class="stat-value"><?= number_format($stats['articles']['total']) ?></span>
                <span class="stat-label">Всего статей</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">📥</div>
            <div class="stat-content">
                <span class="stat-value"><?= number_format($stats['articles']['today']) ?></span>
                <span class="stat-label">Сегодня</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">📅</div>
            <div class="stat-content">
                <span class="stat-value"><?= number_format($stats['articles']['week']) ?></span>
                <span class="stat-label">За неделю</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">🔗</div>
            <div class="stat-content">
                <span class="stat-value"><?= number_format($stats['clusters']) ?></span>
                <span class="stat-label">Кластеров</span>
            </div>
        </div>
    </div>

    <!-- Status Breakdown -->
    <div class="panel">
        <h2>Статусы статей</h2>
        <div class="status-grid">
            <?php
            $statusLabels = [
                'new' => ['label' => 'Новые', 'class' => 'status-new'],
                'processing' => ['label' => 'В обработке', 'class' => 'status-processing'],
                'moderation' => ['label' => 'На модерации', 'class' => 'status-moderation'],
                'published' => ['label' => 'Опубликованы', 'class' => 'status-published'],
                'rejected' => ['label' => 'Отклонены', 'class' => 'status-rejected'],
                'duplicate' => ['label' => 'Дубликаты', 'class' => 'status-duplicate'],
            ];
            foreach ($statusLabels as $status => $info):
                $count = $stats['articles']['by_status'][$status] ?? 0;
            ?>
                <div class="status-item <?= $info['class'] ?>">
                    <span class="status-count"><?= number_format($count) ?></span>
                    <span class="status-label"><?= $info['label'] ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Two Column Layout -->
    <div class="dashboard-columns">
        <!-- Sources -->
        <div class="panel">
            <h2>Источники</h2>
            <div class="panel-content">
                <p>
                    <strong><?= $stats['sources']['enabled'] ?></strong> активных из
                    <strong><?= $stats['sources']['total'] ?></strong> всего
                </p>
                <a href="/admin/sources" class="btn btn-secondary">Управление источниками</a>
            </div>
        </div>

        <!-- Last Operations -->
        <div class="panel">
            <h2>Последние операции</h2>
            <div class="panel-content">
                <?php if ($stats['last_fetch']): ?>
                    <div class="operation-item">
                        <span class="operation-label">Последний сбор:</span>
                        <span class="operation-value">
                            <?= date('d.m.Y H:i', strtotime($stats['last_fetch']['created_at'])) ?>
                            (<?= $stats['last_fetch']['items_created'] ?? 0 ?> новых)
                        </span>
                    </div>
                <?php endif; ?>

                <?php if ($stats['last_process']): ?>
                    <div class="operation-item">
                        <span class="operation-label">Последняя обработка:</span>
                        <span class="operation-value">
                            <?= date('d.m.Y H:i', strtotime($stats['last_process']['created_at'])) ?>
                            (<?= $stats['last_process']['items_processed'] ?? 0 ?> обработано)
                        </span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="panel">
        <h2>Быстрые действия</h2>
        <div class="quick-actions">
            <?php if (($stats['articles']['by_status']['moderation'] ?? 0) > 0): ?>
                <a href="/admin/moderation" class="btn btn-warning">
                    На модерации: <?= $stats['articles']['by_status']['moderation'] ?>
                </a>
            <?php endif; ?>
            <a href="/admin/sources" class="btn btn-secondary">Источники</a>
            <a href="/admin/logs" class="btn btn-secondary">Логи</a>
            <a href="/" class="btn btn-secondary" target="_blank">Открыть сайт</a>
        </div>
    </div>

    <!-- Recent Errors -->
    <?php if (!empty($stats['recent_errors'])): ?>
    <div class="panel panel-danger">
        <h2>Последние ошибки</h2>
        <div class="errors-list">
            <?php foreach ($stats['recent_errors'] as $error): ?>
                <div class="error-item">
                    <span class="error-time"><?= date('d.m H:i', strtotime($error['created_at'])) ?></span>
                    <span class="error-context">[<?= htmlspecialchars($error['context']) ?>]</span>
                    <span class="error-message"><?= htmlspecialchars($error['message']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <a href="/admin/logs?level=error" class="btn btn-link">Все ошибки &rarr;</a>
    </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/layout/footer.php'; ?>
