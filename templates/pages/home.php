<?php require_once __DIR__ . '/../layout/header.php'; ?>

<div class="container">
    <h1>Latest News</h1>

    <div class="articles">
        <?php if (empty($articles)): ?>
            <p>No articles found.</p>
        <?php else: ?>
            <?php foreach ($articles as $article): ?>
                <div class="article-card">
                    <h2><?php echo htmlspecialchars($article['original_title']); ?></h2>
                    <p><?php echo htmlspecialchars($article['original_summary']); ?></p>
                    <div class="article-meta">
                        <span>Published: <?php echo $article['published_at']; ?></span>
                        <a href="<?php echo htmlspecialchars($article['original_url']); ?>" target="_blank">Read more</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
