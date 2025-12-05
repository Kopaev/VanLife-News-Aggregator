<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($seo['title'] ?? 'VanLife News — агрегатор новостей'); ?></title>

    <?php if (isset($seoService) && $seoService instanceof \App\Service\SeoService): ?>
        <?php echo $seoService->renderMetaTags(); ?>
    <?php else: ?>
        <meta name="description" content="<?php echo htmlspecialchars($seo['description'] ?? 'Агрегатор новостей о vanlife и автодомах со всего мира.'); ?>">
        <meta name="robots" content="<?php echo htmlspecialchars($seo['robots'] ?? 'index, follow'); ?>">
        <?php if (!empty($seo['canonical'])): ?>
            <link rel="canonical" href="<?php echo htmlspecialchars($seo['canonical']); ?>">
        <?php endif; ?>

        <!-- Open Graph -->
        <meta property="og:type" content="<?php echo htmlspecialchars($seo['og_type'] ?? 'website'); ?>">
        <meta property="og:title" content="<?php echo htmlspecialchars($seo['og_title'] ?? $seo['title'] ?? 'VanLife News'); ?>">
        <meta property="og:description" content="<?php echo htmlspecialchars($seo['og_description'] ?? $seo['description'] ?? ''); ?>">
        <meta property="og:site_name" content="VanLife News">
        <meta property="og:locale" content="ru_RU">
        <?php if (!empty($seo['og_image'])): ?>
            <meta property="og:image" content="<?php echo htmlspecialchars($seo['og_image']); ?>">
        <?php endif; ?>
        <?php if (!empty($seo['canonical'])): ?>
            <meta property="og:url" content="<?php echo htmlspecialchars($seo['canonical']); ?>">
        <?php endif; ?>

        <!-- Twitter Card -->
        <meta name="twitter:card" content="summary_large_image">
    <?php endif; ?>

    <link rel="stylesheet" href="/css/style.css">
    <link rel="icon" type="image/svg+xml" href="/images/favicon.svg">
</head>
<body>
    <header>
        <div class="container header-inner">
            <a href="/" class="logo">VanLife News</a>
            <div class="header-actions">
                <p class="header-subtitle">Русскоязычный дайджест мира vanlife</p>
                <nav class="header-nav">
                    <a href="/" class="link">Новости</a>
                    <a href="/clusters" class="link">Кластеры</a>
                </nav>
            </div>
        </div>
    </header>
    <main>
