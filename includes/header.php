<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

?>

<!DOCTYPE html>
<html lang="<?= e(currentLanguage()) ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(t('app.name')) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= assetUrl('assets/css/style.css') ?>">
</head>

<body>

<nav class="navbar navbar-expand-lg custom-navbar">
    <div class="container">
        <a class="navbar-brand text-white fw-bold" href="<?= url('index.php') ?>">
            <?= e(t('app.name')) ?>
        </a>

        <div class="ms-auto d-flex align-items-center gap-3">
            <?= languageSwitcher() ?>
            <a class="text-white text-decoration-none" href="<?= url('index.php') ?>">
                <?= e(t('nav.home')) ?>
            </a>

            <?php if (isset($_SESSION['user_id'])): ?>
                <a class="text-white text-decoration-none" href="<?= url('dashboard.php') ?>">
                    <?= e(t('nav.dashboard')) ?>
                </a>
                <a class="text-white text-decoration-none" href="<?= url('logout.php') ?>">
                    <?= e(t('nav.logout')) ?>
                </a>
            <?php else: ?>
                <a class="text-white text-decoration-none" href="<?= url('login.php') ?>">
                    <?= e(t('nav.login')) ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="container py-4">
