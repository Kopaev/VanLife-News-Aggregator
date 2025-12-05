<?php require_once __DIR__ . '/../layout/header.php'; ?>

<div class="container">
    <div class="article-full">
        <h1><?php echo htmlspecialchars($article['original_title']); ?></h1>
        <div class="article-meta">
            <span>Published: <?php echo $article['published_at']; ?></span>
            <a href="<?php echo htmlspecialchars($article['original_url']); ?>" target="_blank">Read original</a>
        </div>
        <div class="article-content">
            <p><?php echo nl2br(htmlspecialchars($article['original_summary'])); ?></p>
            
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
