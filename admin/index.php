<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/layout.php';
richiedi_login();

$conn = db();

// Conta record per ogni dominio (con scoping SCOLASTICO dove applicabile)
$codScuola = utente_cod_scuola();

if (is_admin()) {
    $nScuole   = $conn->query("SELECT COUNT(*) FROM Scuole")->fetch_row()[0];
    $nZone     = $conn->query("SELECT COUNT(*) FROM Zone")->fetch_row()[0];
    $nEventi   = $conn->query("SELECT COUNT(*) FROM Eventi WHERE data_eliminazione IS NULL")->fetch_row()[0];
    $nProgetti = $conn->query("SELECT COUNT(*) FROM Progetti WHERE data_eliminazione IS NULL")->fetch_row()[0];
    $nLinks    = $conn->query("SELECT COUNT(*) FROM Links WHERE data_eliminazione IS NULL")->fetch_row()[0];
    $nUtenti   = $conn->query("SELECT COUNT(*) FROM Utenti")->fetch_row()[0];
} else {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM Scuole WHERE COD_meccanografico = ?");
    $stmt->bind_param('s', $codScuola);
    $stmt->execute();
    $nScuole = $stmt->get_result()->fetch_row()[0];
    $stmt->close();

    $stmt = $conn->prepare("SELECT COUNT(*) FROM Eventi WHERE data_eliminazione IS NULL AND cod_scuola = ?");
    $stmt->bind_param('s', $codScuola);
    $stmt->execute();
    $nEventi = $stmt->get_result()->fetch_row()[0];
    $stmt->close();

    $nZone = $nProgetti = $nLinks = $nUtenti = null;
}

$conn->close();

render_head_admin('Dashboard');
render_sidebar_admin('index.php');
render_topbar_admin('Dashboard');
?>

<div class="content-grid">

    <div class="grid-full">
        <div class="card-panel">
            <h6 class="fw-bold mb-1">Benvenuto, <?= htmlspecialchars($_SESSION['username']) ?></h6>
            <p class="text-secondary mb-0" style="font-size:.88rem;">
                Ruolo: <strong><?= htmlspecialchars($_SESSION['ruolo']) ?></strong>
                <?php if (is_scolastico()): ?>
                    &nbsp;—&nbsp;Scuola: <strong><?= htmlspecialchars($codScuola) ?></strong>
                <?php endif; ?>
            </p>
        </div>
    </div>

    <?php
    $statistiche = [
        ['Scuole',   'bi-backpack-fill',   'scuole.php',   $nScuole,   'primary'],
        ['Zone',     'bi-geo-fill',        'zone.php',     $nZone,     'secondary'],
        ['Eventi',   'bi-calendar-fill',   'eventi.php',   $nEventi,   'success'],
        ['Progetti', 'bi-lightbulb-fill',  'progetti.php', $nProgetti, 'warning'],
        ['Link',     'bi-link-45deg',      'links.php',    $nLinks,    'info'],
        ['Utenti',   'bi-people-fill',     'utenti.php',   $nUtenti,   'danger'],
    ];
    foreach ($statistiche as [$label, $icon, $href, $count, $colore]):
        if ($count === null) continue;
    ?>
    <div class="grid-third">
        <a href="<?= $href ?>" class="text-decoration-none">
            <div class="card-panel d-flex align-items-center gap-3">
                <div class="rounded-circle bg-<?= $colore ?> bg-opacity-10 d-flex align-items-center justify-content-center"
                     style="width:48px;height:48px;flex-shrink:0;">
                    <i class="bi <?= $icon ?> text-<?= $colore ?>" style="font-size:1.3rem;"></i>
                </div>
                <div>
                    <div class="fw-bold" style="font-size:1.4rem;"><?= $count ?></div>
                    <div class="text-secondary" style="font-size:.82rem;"><?= $label ?></div>
                </div>
            </div>
        </a>
    </div>
    <?php endforeach; ?>

</div>

<?php chiudi_pagina(); ?>
