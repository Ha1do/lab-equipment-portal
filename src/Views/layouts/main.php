<?php $user = AuthMiddleware::currentUser(); ?>
<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? APP_NAME) ?> — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
</head>
<body>
<header class="site-header">
    <div class="container header-inner">
        <a class="logo" href="<?= BASE_URL ?>/">📦 <?= APP_NAME ?></a>
        <nav class="main-nav">
            <?php if ($user): ?>
                <a href="<?= BASE_URL ?>/items">Vybavenie</a>
                <?php if ($user['role'] === 'admin'): ?>
                    <a href="<?= BASE_URL ?>/admin">Admin</a>
                <?php endif; ?>
                <a href="<?= BASE_URL ?>/profile">👤 <?= htmlspecialchars($user['name']) ?></a>
                <a href="<?= BASE_URL ?>/logout" class="btn btn-sm btn-outline">Odhlásiť sa</a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/login" class="btn btn-sm">Prihlásiť sa</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main class="site-main">
    <div class="container">
        <?php foreach (['success','error'] as $type):
            $msg = Session::getFlash($type);
            if ($msg): ?>
                <div class="alert alert-<?= $type ?>"><?= htmlspecialchars($msg) ?></div>
            <?php endif; endforeach; ?>
        <?= $content ?>
    </div>
</main>

<footer class="site-footer">
    <div class="container">
        <p>&copy; <?= date('Y') ?> <?= APP_NAME ?></p>
    </div>
</footer>
<script src="<?= BASE_URL ?>/js/app.js"></script>
</body>
</html>