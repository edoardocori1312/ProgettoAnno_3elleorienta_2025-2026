<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/layout.php';

$conn = db();

$eventi = $conn->query(
    'SELECT e.ID_evento, e.titolo, e.descrizione_breve, e.target,
            e.ora_inizio, e.ora_fine, e.visibile, e.prenotabile,
            e.via_P, e.n_civico_P, e.latitudine, e.longitudine,
            e.cod_scuola, s.nome AS nome_scuola,
            e.id_citta, c.nome AS nome_citta,
            f.path_foto
     FROM   Eventi e
     LEFT JOIN Scuole s ON e.cod_scuola = s.COD_meccanografico
     LEFT JOIN Citta  c ON e.id_citta   = c.ID_citta
     LEFT JOIN Foto   f ON e.id_foto    = f.ID_foto
     WHERE  e.visibile = 1 AND e.data_eliminazione IS NULL
     ORDER  BY e.ora_inizio ASC'
)->fetch_all(MYSQLI_ASSOC);

$conn->close();

// Separa eventi TERRITORIALI con coordinate per la mappa
$eventiMappa = array_filter($eventi, fn($e) =>
    $e['target'] === 'TERRITORIALE' && $e['latitudine'] && $e['longitudine']
);

$leafletCSS = '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">';
render_head_pubblica('Eventi', $leafletCSS);
render_navbar_pubblica('eventi.php');
?>

<section class="hero" style="padding:40px 0;">
    <div class="container">
        <h1 style="font-size:1.8rem;">Eventi</h1>
        <p style="font-size:1rem;opacity:.85;">Scopri gli eventi di orientamento sul territorio.</p>
    </div>
</section>

<div class="container py-5">

    <!-- Mappa eventi territoriali -->
    <?php if (!empty($eventiMappa)): ?>
    <h2 class="sez-title">Mappa eventi territoriali</h2>
    <div id="mappa-eventi" class="mb-5"></div>
    <?php endif; ?>

    <!-- Lista eventi -->
    <?php if (empty($eventi)): ?>
    <div class="text-center text-muted py-5">
        <i class="bi bi-calendar-x fs-2 d-block mb-2"></i>Nessun evento in programma.
    </div>
    <?php else: ?>
    <h2 class="sez-title">Tutti gli eventi</h2>
    <div class="row row-cols-1 row-cols-md-2 g-4">
        <?php foreach ($eventi as $ev): ?>
        <div class="col">
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
                        <?php if ($ev['target'] === 'SCOLASTICO' && $ev['nome_scuola']): ?>
                        <span><i class="bi bi-building me-1"></i><?= htmlspecialchars($ev['nome_scuola']) ?></span>
                        <?php elseif ($ev['via_P'] && $ev['nome_citta']): ?>
                        <span>
                            <i class="bi bi-geo-alt me-1"></i>
                            <?= htmlspecialchars($ev['via_P']) ?> <?= htmlspecialchars($ev['n_civico_P']) ?>,
                            <?= htmlspecialchars($ev['nome_citta']) ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>

<?php render_footer(); ?>

<?php if (!empty($eventiMappa)): ?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const mappa = L.map('mappa-eventi').setView([43.5, 13.0], 9);
L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    maxZoom: 18
}).addTo(mappa);

const eventiMappa = <?= json_encode(array_values(array_map(fn($e) => [
    'lat'   => (float)$e['latitudine'],
    'lng'   => (float)$e['longitudine'],
    'titolo' => $e['titolo'],
    'desc'   => $e['descrizione_breve'],
    'inizio' => $e['ora_inizio'] ? date('d/m/Y H:i', strtotime($e['ora_inizio'])) : '',
    'citta'  => $e['nome_citta'] ?? '',
], $eventiMappa))) ?>;

eventiMappa.forEach(ev => {
    if (!ev.lat || !ev.lng) return;
    L.marker([ev.lat, ev.lng])
        .addTo(mappa)
        .bindPopup(
            '<strong>' + ev.titolo + '</strong><br>' +
            ev.desc + '<br>' +
            '<small>' + ev.inizio + ' · ' + ev.citta + '</small>'
        );
});
</script>
<?php endif; ?>

<?php chiudi_pagina_pubblica(); ?>
