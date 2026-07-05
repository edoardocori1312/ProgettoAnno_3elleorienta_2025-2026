<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/layout.php';

$conn = db();

$eventi = query_all($conn,
    'SELECT e.ID_evento, e.titolo, e.descrizione_breve, e.target,
            e.ora_inizio, e.ora_fine, e.visibile, e.prenotabile,
            e.via_P, e.n_civico_P, e.latitudine, e.longitudine,
            e.cod_scuola, s.nome AS nome_scuola,
            e.id_citta, c.nome AS nome_citta,
            f.path_foto
     FROM   Eventi e
     LEFT JOIN Scuole s ON e.cod_scuola = s.COD_meccanografico
     LEFT JOIN Citta  c ON e.id_citta   = c.ID_citta
     LEFT JOIN Foto   f ON e.id_foto    = f.ID_foto AND f.data_eliminazione IS NULL
     WHERE  e.visibile = 1 AND e.data_eliminazione IS NULL
     ORDER  BY e.ora_inizio ASC'
);

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

$leafletCSS = '<link rel="stylesheet" href="' . LEAFLET_CSS . '">';
render_head_pubblica('Eventi', $leafletCSS);
render_navbar_pubblica('eventi.php');
?>

<?php render_hero_banner('Eventi', 'Scopri gli eventi di orientamento sul territorio.'); ?>

<div class="container py-5">

    <div class="d-flex justify-content-end mb-4">
        <a href="pdf/report_eventi.php" class="btn btn-danger" target="_blank">
            <i class="bi bi-file-earmark-pdf"></i> Scarica PDF eventi
        </a>
    </div>

    <!-- Mappa eventi territoriali -->
    <?php if (!empty($eventiMappa)): ?>
    <h2 class="sez-title">Mappa eventi territoriali</h2>

    <!-- Ricerca per luogo: mostra solo gli eventi entro il raggio scelto -->
    <div class="row g-2 align-items-center mb-3">
        <div class="col-12 col-md-5">
            <input type="text" id="cerca-luogo" class="form-control"
                   placeholder="Cerca una città o un indirizzo...">
        </div>
        <div class="col-6 col-md-2">
            <select id="raggio-km" class="form-select" title="Raggio di ricerca">
                <option value="10" selected>10 km</option>
                <option value="25">25 km</option>
                <option value="50">50 km</option>
            </select>
        </div>
        <div class="col-6 col-md-auto">
            <button type="button" id="btn-cerca-luogo" class="btn btn-primary">
                <i class="bi bi-search me-1"></i>Cerca
            </button>
            <button type="button" id="btn-reset-mappa" class="btn btn-outline-secondary">
                Mostra tutti
            </button>
        </div>
        <div class="col-12">
            <small id="stato-ricerca" class="text-muted"></small>
        </div>
    </div>

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
<script src="<?= LEAFLET_JS ?>"></script>
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

// bindPopup inserisce la stringa come HTML (innerHTML): json_encode protegge il
// contesto JS ma NON l'HTML, quindi va riescapato qui per evitare XSS stored.
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
}[c]));

// Teniamo i riferimenti ai marker per poterli filtrare con la ricerca per luogo
const marcatori = [];
eventiMappa.forEach(ev => {
    if (!ev.lat || !ev.lng) return;
    const marker = L.marker([ev.lat, ev.lng])
        .addTo(mappa)
        .bindPopup(
            '<strong>' + esc(ev.titolo) + '</strong><br>' +
            esc(ev.desc) + '<br>' +
            '<small>' + esc(ev.inizio) + ' · ' + esc(ev.citta) + '</small>'
        );
    marcatori.push({ lat: ev.lat, lng: ev.lng, marker: marker });
});

// ── Ricerca per luogo e filtro per distanza ─────────────────────────────────
const inputLuogo   = document.getElementById('cerca-luogo');
const statoRicerca = document.getElementById('stato-ricerca');
let markerRicerca  = null; // segnaposto del luogo cercato
let cerchioRaggio  = null; // cerchio che visualizza il raggio scelto

// Distanza in km tra due coordinate (formula di haversine)
function distanzaKm(lat1, lng1, lat2, lng2) {
    const R = 6371; // raggio terrestre in km
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLng = (lng2 - lng1) * Math.PI / 180;
    const a = Math.sin(dLat / 2) ** 2 +
              Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
              Math.sin(dLng / 2) ** 2;
    return 2 * R * Math.asin(Math.sqrt(a));
}

function cercaLuogo() {
    const testo = inputLuogo.value.trim();
    if (!testo) return;
    statoRicerca.textContent = 'Ricerca in corso...';
    const q = encodeURIComponent(testo + ', Italia');
    fetch('https://nominatim.openstreetmap.org/search?q=' + q + '&format=json&limit=1', {
        headers: { 'Accept-Language': 'it' }
    })
    .then(r => r.json())
    .then(dati => {
        if (dati.length === 0) {
            statoRicerca.textContent = 'Luogo non trovato.';
            return;
        }
        const raggio = parseInt(document.getElementById('raggio-km').value, 10);
        filtraPerDistanza(parseFloat(dati[0].lat), parseFloat(dati[0].lon), raggio, dati[0].display_name);
    })
    .catch(() => statoRicerca.textContent = 'Errore nella ricerca.');
}

function filtraPerDistanza(lat, lng, raggio, nomeLuogo) {
    let visibili = 0;
    marcatori.forEach(m => {
        if (distanzaKm(lat, lng, m.lat, m.lng) <= raggio) {
            m.marker.addTo(mappa);
            visibili++;
        } else {
            mappa.removeLayer(m.marker);
        }
    });

    if (markerRicerca) mappa.removeLayer(markerRicerca);
    if (cerchioRaggio) mappa.removeLayer(cerchioRaggio);
    markerRicerca = L.marker([lat, lng], { opacity: 0.7 })
        .addTo(mappa)
        .bindPopup(esc(nomeLuogo));
    cerchioRaggio = L.circle([lat, lng], {
        radius: raggio * 1000,
        color: '#1DADA0',
        fillOpacity: 0.08
    }).addTo(mappa);
    mappa.fitBounds(cerchioRaggio.getBounds());

    statoRicerca.textContent = visibili === 0
        ? 'Nessun evento entro ' + raggio + ' km. Prova ad allargare il raggio.'
        : visibili + (visibili === 1 ? ' evento trovato' : ' eventi trovati') + ' entro ' + raggio + ' km.';
}

function mostraTutti() {
    marcatori.forEach(m => m.marker.addTo(mappa));
    if (markerRicerca) { mappa.removeLayer(markerRicerca); markerRicerca = null; }
    if (cerchioRaggio) { mappa.removeLayer(cerchioRaggio); cerchioRaggio = null; }
    inputLuogo.value = '';
    statoRicerca.textContent = '';
    mappa.setView([43.5, 13.0], 9); // vista iniziale
}

document.getElementById('btn-cerca-luogo').addEventListener('click', cercaLuogo);
document.getElementById('btn-reset-mappa').addEventListener('click', mostraTutti);
inputLuogo.addEventListener('keydown', e => {
    if (e.key === 'Enter') cercaLuogo();
});
</script>
<?php endif; ?>

<?php chiudi_pagina_pubblica(); ?>
