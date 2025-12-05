<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — VanLife News</title>
    <link rel="stylesheet" href="/css/admin.css">
</head>
<body class="admin-page">
    <header class="admin-header">
        <div class="admin-header-inner">
            <a href="/admin" class="admin-logo">VanLife News Admin</a>
            <nav class="admin-nav">
                <a href="/admin" class="nav-link<?= $_SERVER['REQUEST_URI'] === '/admin' ? ' active' : '' ?>">Dashboard</a>
                <a href="/admin/moderation" class="nav-link<?= str_starts_with($_SERVER['REQUEST_URI'], '/admin/moderation') ? ' active' : '' ?>">Модерация</a>
                <a href="/admin/sources" class="nav-link<?= str_starts_with($_SERVER['REQUEST_URI'], '/admin/sources') ? ' active' : '' ?>">Источники</a>
                <a href="/admin/logs" class="nav-link<?= str_starts_with($_SERVER['REQUEST_URI'], '/admin/logs') ? ' active' : '' ?>">Логи</a>
            </nav>
            <div class="admin-user">
                <span class="user-name"><?= htmlspecialchars($admin['username'] ?? 'Admin') ?></span>
                <form method="POST" action="/admin/logout" style="display: inline;">
                    <button type="submit" class="btn btn-sm btn-outline">Выйти</button>
                </form>
            </div>
        </div>
    </header>
    <main class="admin-main">
        <div class="container">
