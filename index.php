<?php include 'includes/header.php'; ?>

<section class="home-hero mb-4">
    <div class="home-hero-grid">
        <div>
            <div class="page-kicker"><?= e(t('app.subtitle')) ?></div>
            <h1><?= e(t('home.title')) ?></h1>
            <p><?= e(t('home.subtitle')) ?></p>

            <a href="<?= url('login.php') ?>" class="btn btn-primary px-4 py-2">
                <?= e(t('home.login')) ?>
            </a>
        </div>

        <div class="home-hero-panel">
            <div class="hero-panel-line">
                <span><?= e(t('home.panel.registry')) ?></span>
                <strong><?= e(t('home.panel.active')) ?></strong>
            </div>
            <div class="hero-panel-line">
                <span><?= e(t('home.panel.scan')) ?></span>
                <strong><?= e(t('home.panel.ready')) ?></strong>
            </div>
            <div class="hero-panel-line">
                <span><?= e(t('home.panel.requests')) ?></span>
                <strong><?= e(t('home.panel.work')) ?></strong>
            </div>
        </div>
    </div>
</section>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="muted-label">01</div>
                <h4><?= e(t('home.equipment.title')) ?></h4>
                <p class="mb-0"><?= e(t('home.equipment.text')) ?></p>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="muted-label">02</div>
                <h4><?= e(t('home.requests.title')) ?></h4>
                <p class="mb-0"><?= e(t('home.requests.text')) ?></p>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="muted-label">03</div>
                <h4><?= e(t('home.qr.title')) ?></h4>
                <p class="mb-0"><?= e(t('home.qr.text')) ?></p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
