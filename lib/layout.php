<?php
// ── Costanti Bootstrap CDN (versione unica per tutto il progetto) ────────────
const BS_CSS  = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css';
const BS_ICON = 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css';
const BS_JS   = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js';

// ────────────────────────────────────────────────────────────────────────────
// SITO PUBBLICO
// ────────────────────────────────────────────────────────────────────────────

function render_head_pubblica(string $titolo, string $headExtra = ''): void { ?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titolo) ?> — Svelati</title>
    <link rel="stylesheet" href="<?= BS_CSS ?>">
    <link rel="stylesheet" href="<?= BS_ICON ?>">
    <link rel="stylesheet" href="assets/css/sito.css">
    <?= $headExtra ?>
</head>
<body>
<?php }

function chiudi_pagina_pubblica(): void { ?>
<script src="<?= BS_JS ?>"></script>
</body>
</html>
<?php }

function render_navbar_pubblica(string $attiva = ''): void {
    $voci = [
        'index.php'    => 'Home',
        'ambiti.php'   => 'Ambiti',
        'orientati.php'=> 'Orientati',
        'eventi.php'   => 'Eventi',
    ]; ?>
<header class="site-header">
  <nav class="navbar navbar-expand-lg">
    <div class="container-fluid px-4">
      <a class="navbar-brand" href="index.php">
        <img src="assets/img/logo.png" alt="Logo Svelati" class="navbar-logo" style="height:40px;">
      </a>
      <button class="navbar-toggler border-0" type="button"
              data-bs-toggle="collapse" data-bs-target="#navPubblica">
        <i class="bi bi-list" style="font-size:1.6rem;"></i>
      </button>
      <div class="collapse navbar-collapse" id="navPubblica">
        <ul class="navbar-nav ms-auto" id="navLinks">
          <?php foreach ($voci as $href => $label): ?>
          <li class="nav-item">
            <a class="nav-link<?= $attiva === $href ? ' active' : '' ?>"
               href="<?= $href ?>"><?= $label ?></a>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  </nav>
</header>
<?php }

function render_footer(): void { ?>
<footer class="footer mt-auto py-4 bg-dark text-white">
  <div class="container">
    <div class="row">
      <div class="col-md-6">
        <p class="mb-1 fw-semibold">Svelati — Reti territoriali per l'orientamento</p>
        <p class="text-secondary mb-0" style="font-size:.88rem;">
          Supportiamo studenti e famiglie nel percorso di scelta formativa e professionale.
        </p>
      </div>
      <div class="col-md-3">
        <p class="mb-1 fw-semibold" style="font-size:.88rem;">Contatti</p>
        <ul class="list-unstyled text-secondary mb-0" style="font-size:.85rem;">
          <li>Jesi, Marche</li>
          <li><a href="mailto:info@svelati.it" class="text-secondary">info@svelati.it</a></li>
        </ul>
      </div>
      <div class="col-md-3 text-md-end mt-3 mt-md-0">
        <p class="text-secondary mb-0" style="font-size:.82rem;">© 2026 Svelati. Tutti i diritti riservati.</p>
      </div>
    </div>
  </div>
</footer>
<?php }

// ────────────────────────────────────────────────────────────────────────────
// PANNELLO ADMIN
// ────────────────────────────────────────────────────────────────────────────

function render_head_admin(string $titolo): void { ?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titolo) ?> — Admin Svelati</title>
    <link rel="stylesheet" href="<?= BS_CSS ?>">
    <link rel="stylesheet" href="<?= BS_ICON ?>">
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
<?php }

function render_sidebar_admin(string $attiva = ''): void {
    $voceScuola = [
        'scuole.php' => ['bi-backpack-fill', 'Scuole'],
        'zone.php'   => ['bi-geo-fill',      'Zone'],
    ];
    $voceAvvenimenti = [
        'eventi.php'   => ['bi-calendar-fill',  'Eventi'],
        'progetti.php' => ['bi-lightbulb-fill', 'Progetti'],
        'links.php'    => ['bi-link-45deg',     'Link Utili'],
    ];
    $voceAltro = [
        'impostazioni.php' => ['bi-tools', 'Impostazioni'],
    ]; ?>
<input type="checkbox" id="sidebar-toggle" class="d-none">
<aside class="sidebar">
    <div class="logo">
        <span class="logo-text">
            <img src="assets/img/logo.png" alt="logo" width="36" height="36"
                 style="object-fit:contain;vertical-align:middle;margin-right:6px;">
            3elleorienta
        </span>
        <label class="menu-toggle-label" for="sidebar-toggle" title="Apri/Chiudi menu">☰</label>
    </div>

    <nav class="nav-group mt-2">
        <div class="nav-label">SCUOLA</div>
        <?php foreach ($voceScuola as $href => [$icon, $label]): ?>
        <a href="<?= $href ?>" class="nav-link<?= $attiva === $href ? ' active' : '' ?>">
            <i class="bi <?= $icon ?>"></i>
            <span class="link-text"><?= $label ?></span>
        </a>
        <?php endforeach; ?>

        <div class="nav-label mt-2">AVVENIMENTI</div>
        <?php foreach ($voceAvvenimenti as $href => [$icon, $label]): ?>
        <a href="<?= $href ?>" class="nav-link<?= $attiva === $href ? ' active' : '' ?>">
            <i class="bi <?= $icon ?>"></i>
            <span class="link-text"><?= $label ?></span>
        </a>
        <?php endforeach; ?>

        <?php if (is_admin()): ?>
        <div class="nav-label mt-2">UTENTI</div>
        <a href="utenti.php" class="nav-link<?= $attiva === 'utenti.php' ? ' active' : '' ?>">
            <i class="bi bi-people-fill"></i>
            <span class="link-text">Gestione Utenti</span>
        </a>
        <?php endif; ?>

        <div class="nav-label mt-2">ALTRO</div>
        <?php foreach ($voceAltro as $href => [$icon, $label]): ?>
        <a href="<?= $href ?>" class="nav-link<?= $attiva === $href ? ' active' : '' ?>">
            <i class="bi <?= $icon ?>"></i>
            <span class="link-text"><?= $label ?></span>
        </a>
        <?php endforeach; ?>
    </nav>
</aside>
<label class="sidebar-overlay" for="sidebar-toggle" aria-label="Chiudi menu"></label>
<div class="main-wrapper">
<?php }

function render_topbar_admin(string $breadcrumb = ''): void {
    $username = htmlspecialchars($_SESSION['username'] ?? '');
    $ruolo    = $_SESSION['ruolo'] ?? ''; ?>
    <header class="top-bar">
        <div class="d-flex align-items-center gap-3">
            <label class="hamburger-label" for="sidebar-toggle" aria-label="Apri menu">
                <i class="bi bi-list"></i>
            </label>
            <?php if ($breadcrumb): ?>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                    <li class="breadcrumb-item active"><?= htmlspecialchars($breadcrumb) ?></li>
                </ol>
            </nav>
            <?php endif; ?>
        </div>
        <div class="user-info d-flex align-items-center gap-2">
            <i class="bi bi-person-circle"></i>
            <span class="fw-semibold" style="font-size:.88rem;"><?= $username ?></span>
            <span class="badge <?= $ruolo === 'ADMIN' ? 'bg-danger' : 'bg-secondary' ?> ms-1"
                  style="font-size:.72rem;"><?= $ruolo ?></span>
            <span class="text-secondary">|</span>
            <a href="logout.php" class="text-danger text-decoration-none" style="font-size:.88rem;">Logout</a>
        </div>
    </header>
    <main class="page-content">
<?php }

function chiudi_pagina(): void { ?>
    </main>
</div>
<script src="<?= BS_JS ?>"></script>
</body>
</html>
<?php }

// Rende un alert flash Bootstrap se presente
function render_flash(?array $flash): void {
    if (!$flash) return;
    $tipo   = $flash['tipo'] === 'successo' ? 'success' : 'danger';
    $icon   = $flash['tipo'] === 'successo' ? 'check-circle-fill' : 'exclamation-triangle-fill';
    $msg    = htmlspecialchars($flash['msg']); ?>
    <div class="alert alert-<?= $tipo ?> alert-dismissible fade show mb-4" role="alert">
        <i class="bi bi-<?= $icon ?> me-2"></i><?= $msg ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php }
