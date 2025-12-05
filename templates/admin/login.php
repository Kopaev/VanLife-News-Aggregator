<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход — VanLife News Admin</title>
    <link rel="stylesheet" href="/css/admin.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-logo">
            <a href="/">VanLife News</a>
            <span>Admin Panel</span>
        </div>

        <form class="login-form" method="POST" action="/admin/login">
            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label for="username">Логин</label>
                <input type="text" id="username" name="username" required autofocus
                       placeholder="Введите логин">
            </div>

            <div class="form-group">
                <label for="password">Пароль</label>
                <input type="password" id="password" name="password" required
                       placeholder="Введите пароль">
            </div>

            <button type="submit" class="btn btn-primary btn-block">
                Войти
            </button>
        </form>

        <div class="login-footer">
            <a href="/">&larr; Вернуться на сайт</a>
        </div>
    </div>
</body>
</html>
