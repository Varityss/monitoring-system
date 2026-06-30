<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/functions.php';

if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
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

<div class="app-wrapper">
    <aside class="sidebar">
        <div class="sidebar-logo">
            <div class="brand-title"><?= e(t('app.name')) ?></div>
            <div class="brand-subtitle"><?= e(t('app.subtitle')) ?></div>
        </div>

        <a href="<?= url('dashboard.php') ?>"><?= e(t('nav.dashboard')) ?></a>
        <a href="<?= url('equipment/index.php') ?>"><?= e(t('nav.equipment')) ?></a>
        <a href="<?= url('requests/index.php') ?>"><?= e(t('nav.requests')) ?></a>
        <a href="<?= url('requests/return_scan.php') ?>"><?= e(t('nav.return')) ?></a>
        <a href="<?= url('users/index.php') ?>"><?= e(t('nav.users')) ?></a>
        <a href="<?= url('logout.php') ?>"><?= e(t('nav.logout')) ?></a>
    </aside>

    <main class="main-content">
        <div class="topbar">
            <div>
                <div class="topbar-title"><?= e(t('app.name')) ?></div>
                <div class="small text-muted"><?= e(t('app.subtitle')) ?></div>
            </div>

            <div class="topbar-meta">
                <?= languageSwitcher() ?>
                <span><?= e($_SESSION['user_login']) ?></span>
            </div>
        </div>

        <div class="content-area">
