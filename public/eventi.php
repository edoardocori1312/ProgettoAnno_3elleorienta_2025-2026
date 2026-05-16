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

// Raggruppa eventi SCOLASTICI per scuola (ordine: data asc già garantito dalla query)
$eventiPerScuola = [];
foreach ($eventi as $ev) {
    if ($ev['target'] !== 'SCOLASTICO' || !$ev['cod_scuola'] || !$ev['nome_scuola']) continue;
    $cod = $ev['cod_scuola'];
    if (!array_key_exists($cod, $eventiPerScuola)) {
        $eventiPerScuola[$cod] = ['nome' => $ev['nome_scuola'], 'eventi' => []];
    }
    $eventiPerScuola[$cod]['eventi'][] = $ev;
}
uasort($eventiPerScuola, fn($a, $b) => strcmp($a['nome'], $b['nome']));

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
            <?php render_scheda_evento($ev); ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Eventi per scuola -->
    <?php if (!empty($eventiPerScuola)): ?>
    <h2 class="sez-title mt-5">Eventi per scuola</h2>
    <div class="accordion" id="accordionEventiScuole">
        <?php foreach (array_values($eventiPerScuola) as $i => $gruppo): ?>
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button <?= $i > 0 ? 'collapsed' : '' ?>"
                        type="button" data-bs-toggle="collapse"
                        data-bs-target="#scuola-ev-<?= $i ?>">
                    <span class="fw-semibold"><?= htmlspecialchars($gruppo['nome']) ?></span>
                    <span class="badge bg-secondary rounded-pill ms-2"><?= count($gruppo['eventi']) ?></span>
                </button>
            </h2>
            <div id="scuola-ev-<?= $i ?>" class="accordion-collapse collapse <?= $i === 0 ? 'show' : '' ?>">
                <div class="accordion-body">
                    <div class="row row-cols-1 row-cols-md-2 g-4">
                        <?php foreach ($gruppo['eventi'] as $ev): ?>
                        <div class="col">
                            <?php render_scheda_evento($ev, false); ?>
                        </div>
                        <?php endforeach; ?>
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
