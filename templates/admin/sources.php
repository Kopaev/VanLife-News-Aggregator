<?php require __DIR__ . '/layout/header.php'; ?>

<div class="sources">
    <div class="page-header">
        <h1>Источники RSS</h1>
        <span class="badge"><?= count($sources) ?> источников</span>
    </div>

    <?php if (empty($sources)): ?>
        <div class="empty-state">
            <p>Источники не настроены</p>
        </div>
    <?php else: ?>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Источник</th>
                        <th>Язык</th>
                        <th>Страна</th>
                        <th>Статей</th>
                        <th>Последний сбор</th>
                        <th>Статус</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sources as $source): ?>
                        <tr class="<?= $source['is_enabled'] ? '' : 'disabled-row' ?>">
                            <td>
                                <div class="source-name">
                                    <strong><?= htmlspecialchars($source['name']) ?></strong>
                                    <?php if ($source['query']): ?>
                                        <small class="source-query"><?= htmlspecialchars($source['query']) ?></small>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($source['language_name'] ?? $source['language_code']) ?></td>
                            <td>
                                <?php if ($source['country_flag']): ?>
                                    <?= $source['country_flag'] ?>
                                <?php endif; ?>
                                <?= htmlspecialchars($source['country_name'] ?? $source['country_code'] ?? '-') ?>
                            </td>
                            <td><?= number_format($source['articles_count']) ?></td>
                            <td>
                                <?php if ($source['last_fetched_at']): ?>
                                    <?= date('d.m.Y H:i', strtotime($source['last_fetched_at'])) ?>
                                <?php else: ?>
                                    <span class="text-muted">Никогда</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($source['last_error']): ?>
                                    <span class="status-badge status-error" title="<?= htmlspecialchars($source['last_error']) ?>">
                                        Ошибка
                                    </span>
                                <?php elseif ($source['is_enabled']): ?>
                                    <span class="status-badge status-active">Активен</span>
                                <?php else: ?>
                                    <span class="status-badge status-disabled">Отключён</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" action="/admin/source/<?= $source['id'] ?>/toggle" class="inline-form">
                                    <button type="submit" class="btn btn-sm <?= $source['is_enabled'] ? 'btn-warning' : 'btn-success' ?>">
                                        <?= $source['is_enabled'] ? 'Отключить' : 'Включить' ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/layout/footer.php'; ?>
