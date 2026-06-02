<?php
// ── Costanti Bootstrap CDN (versione unica per tutto il progetto) ────────────
const BS_CSS  = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css';
const BS_ICON = 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css';
const BS_JS   = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js';

// ── Costanti Leaflet CDN (versione unica per le pagine con mappa) ────────────
const LEAFLET_CSS = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
const LEAFLET_JS  = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';

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
        'index.php'     => 'Home',
        'progetti.php'  => 'Progetti',
        'ambiti.php'    => 'Ambiti',
        'orientati.php' => 'Orientati',
        'eventi.php'    => 'Eventi',
        'linkutili.php' => 'Link Utili',
    ]; ?>
<header class="site-header">
  <nav class="navbar navbar-expand-lg bg-body-tertiary">
    <div class="container-fluid">
      <a class="navbar-brand" href="index.php">
        <img src="assets/img/logo.png" alt="Logo 3elleorienta" class="navbar-logo">
      </a>
      <button class="navbar-toggler" type="button"
              data-bs-toggle="collapse" data-bs-target="#navPubblica">
        <i class="bi bi-caret-down-square"></i>
      </button>
      <div class="collapse navbar-collapse" id="navPubblica">
        <ul class="navbar-nav">
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
<footer class="footer">
  <div class="footer__inner">
    <div class="footer__brand">
      <p class="footer__description">
        Reti territoriali per l'orientamento.<br>
        Supportiamo studenti e famiglie nel percorso di scelta formativa e professionale.
      </p>
    </div>
    <div class="footer__col">
      <h4 class="footer__col-title">Contatti</h4>
      <ul class="footer__links">
        <li>Jesi, Marche</li>
        <li><a href="mailto:info@svelati.it">info@svelati.it</a></li>
        <li><a href="tel:+390000000000">+39 000 000 0000</a></li>
      </ul>
    </div>
    <div class="footer__cta">
      <a href="mailto:info@svelati.it" class="footer__btn footer__btn--secondary">Contattaci</a>
    </div>
  </div>
  <div class="footer__bottom">
    <p class="footer__copyright">© 2026 Svelati. Tutti i diritti riservati.</p>
    <div class="footer__legal">
      <a href="#">Privacy Policy</a>
      <a href="#">Cookie Policy</a>
    </div>
  </div>
</footer>
<?php }

function render_hero_banner(string $titolo, string $sub = ''): void { ?>
<section class="page-banner">
  <div class="container">
    <h1><?= htmlspecialchars($titolo) ?></h1>
    <?php if ($sub !== ''): ?>
    <p><?= htmlspecialchars($sub) ?></p>
    <?php endif; ?>
  </div>
</section>
<?php }

// ────────────────────────────────────────────────────────────────────────────
function render_scheda_evento(array $ev, bool $mostraLuogo = true): void { ?>
    <div class="scheda h-100">
        <?php if ($ev['path_foto']): ?>
        <img src="../<?= htmlspecialchars($ev['path_foto']) ?>" class="scheda-foto"
             alt="<?= htmlspecialchars($ev['titolo']) ?>">
        <?php else: ?>
        <div class="scheda-placeholder"><i class="bi bi-calendar-event"></i></div>
        <?php endif; ?>
        <div class="p-3">
            <div class="d-flex gap-2 mb-2">
                <?php if ($ev['target'] === 'TERRITORIALE'): ?>
                <span class="badge badge-terr">Territoriale</span>
                <?php else: ?>
                <span class="badge badge-scol">Scolastico</span>
                <?php endif; ?>
                <?php if ($ev['prenotabile']): ?>
                <span class="badge bg-success">Prenotabile</span>
                <?php endif; ?>
            </div>
            <h6 class="fw-semibold"><?= htmlspecialchars($ev['titolo']) ?></h6>
            <p class="text-muted mb-2" style="font-size:.85rem;">
                <?= htmlspecialchars($ev['descrizione_breve']) ?>
            </p>
            <div class="d-flex flex-wrap gap-3" style="font-size:.82rem;color:var(--muted);">
                <span>
                    <i class="bi bi-calendar3 me-1"></i>
                    <?= date('d/m/Y H:i', strtotime($ev['ora_inizio'])) ?>
                    → <?= date('d/m/Y H:i', strtotime($ev['ora_fine'])) ?>
                </span>
                <?php if ($mostraLuogo): ?>
                <?php if ($ev['target'] === 'SCOLASTICO' && $ev['nome_scuola']): ?>
                <span><i class="bi bi-building me-1"></i><?= htmlspecialchars($ev['nome_scuola']) ?></span>
                <?php elseif ($ev['via_P'] && $ev['nome_citta']): ?>
                <span>
                    <i class="bi bi-geo-alt me-1"></i>
                    <?= htmlspecialchars($ev['via_P']) ?> <?= htmlspecialchars($ev['n_civico_P']) ?>,
                    <?= htmlspecialchars($ev['nome_citta']) ?>
                </span>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php }
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
    // Scuole ed Eventi sono visibili anche allo SCOLASTICO; Zone, Progetti e
    // Link Utili sono solo ADMIN (i relativi controller usano richiedi_admin()).
    $voceScuola = ['scuole.php' => ['bi-backpack-fill', 'Scuole']];
    if (is_admin()) {
        $voceScuola['zone.php'] = ['bi-geo-fill', 'Zone'];
    }
    $voceAvvenimenti = ['eventi.php' => ['bi-calendar-fill', 'Eventi']];
    if (is_admin()) {
        $voceAvvenimenti['progetti.php'] = ['bi-lightbulb-fill', 'Progetti'];
        $voceAvvenimenti['links.php']    = ['bi-link-45deg',     'Link Utili'];
    }
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

/**
 * Modale di conferma eliminazione condivisa (prima duplicata in 6 pagine admin).
 * Emette il markup Bootstrap + la funzione JS apriElimina(id, label).
 *
 * @param string $action       URL del form POST (es. 'eventi.php')
 * @param string $campoHidden  nome del campo hidden con l'id (es. 'elimina_id', 'elimina_cod', 'id_zona')
 * @param string $msgExtra     eventuale avviso aggiuntivo (es. cascade su scuole)
 */
function render_modal_elimina(string $action, string $campoHidden = 'elimina_id', string $msgExtra = ''): void {
    $csrf = function_exists('csrf_token') ? csrf_token() : ''; ?>
<div class="modal fade" id="modalElimina" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title text-danger">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Conferma eliminazione
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-2">
                <p id="modal-msg" class="mb-1" style="font-size:.9rem;"></p>
                <?php if ($msgExtra !== ''): ?>
                <p class="text-danger mb-0" style="font-size:.82rem;">
                    <i class="bi bi-info-circle me-1"></i><?= htmlspecialchars($msgExtra) ?>
                </p>
                <?php endif; ?>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Annulla</button>
                <form method="POST" action="<?= htmlspecialchars($action) ?>" style="display:inline">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                    <input type="hidden" name="<?= htmlspecialchars($campoHidden) ?>" id="modal-elimina-id" value="">
                    <button type="submit" class="btn btn-danger btn-sm">
                        <i class="bi bi-trash-fill me-1"></i>Elimina
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
function apriElimina(id, label) {
    document.getElementById('modal-msg').textContent = 'Eliminare «' + label + '»?';
    document.getElementById('modal-elimina-id').value = id;
    new bootstrap.Modal(document.getElementById('modalElimina')).show();
}
</script>
<?php }

// True solo se l'URL ha schema http/https (blocca javascript:, data:, ecc.).
// Usato sia per validare al salvataggio sia come guardia in output.
function url_http_valido(string $url): bool {
    $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
    return in_array($scheme, ['http', 'https'], true);
}

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
