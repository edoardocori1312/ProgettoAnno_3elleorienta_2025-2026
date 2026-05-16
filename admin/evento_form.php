<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/layout.php';
require_once __DIR__ . '/gestione/gestione_eventi.php';
richiedi_login();

$conn      = db();
$isAdmin   = is_admin();
$codUtente = utente_cod_scuola();

$idModifica  = (int)($_GET['id'] ?? 0);
$modoModifica = $idModifica > 0;

// Verifica permessi in modifica
if ($modoModifica) {
    $ev = leggiEvento($conn, $idModifica);
    if (!$ev) {
        imposta_flash('errore', 'Evento non trovato.');
        $conn->close();
        header('Location: eventi.php');
        exit;
    }
    if (!$isAdmin && $ev['cod_scuola'] !== $codUtente) {
        imposta_flash('errore', 'Non hai i permessi per modificare questo evento.');
        $conn->close();
        header('Location: eventi.php');
        exit;
    }
} else {
    $ev = null;
}

// POST: salva
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $azione = $_POST['azione'] ?? '';
    $file   = $_FILES['foto'] ?? ['error' => UPLOAD_ERR_NO_FILE, 'size' => 0];

    if ($azione === 'inserisci') {
        $esito = creaEvento($conn, $_POST, $file, $isAdmin, $codUtente);
    } elseif ($azione === 'aggiorna') {
        $idPost = (int)($_POST['id_evento'] ?? 0);
        $esito = aggiornaEvento($conn, $idPost, $_POST, $file, $isAdmin, $codUtente);
    } else {
        $esito = ['tipo' => 'errore', 'msg' => 'Azione non valida.'];
    }

    imposta_flash($esito['tipo'], $esito['msg']);
    $conn->close();
    header('Location: eventi.php');
    exit;
}

// GET: prepara dati per form
$scuole = $isAdmin ? leggiScuoleEventi($conn) : [];
$citta  = leggiCittaEventi($conn);
$conn->close();

$titolo = $modoModifica ? 'Modifica evento' : 'Aggiungi evento';
$targetCorrente = $ev['target'] ?? ($isAdmin ? 'TERRITORIALE' : 'SCOLASTICO');

render_head_admin($titolo);
render_sidebar_admin('eventi.php');
render_topbar_admin($titolo);
?>

<div class="content-grid">
    <div class="grid-full">
        <div class="card-panel">

            <form method="POST" action="evento_form.php" enctype="multipart/form-data">
                <input type="hidden" name="azione" value="<?= $modoModifica ? 'aggiorna' : 'inserisci' ?>">
                <?php if ($modoModifica): ?>
                <input type="hidden" name="id_evento" value="<?= $idModifica ?>">
                <?php endif; ?>

                <div class="row g-3">

                    <!-- Target (solo ADMIN) -->
                    <?php if ($isAdmin): ?>
                    <div class="col-12">
                        <label class="form-label">Target *</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="target"
                                       id="t_terr" value="TERRITORIALE"
                                       <?= $targetCorrente === 'TERRITORIALE' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="t_terr">
                                    <i class="bi bi-geo-fill me-1"></i>Territoriale
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="target"
                                       id="t_scol" value="SCOLASTICO"
                                       <?= $targetCorrente === 'SCOLASTICO' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="t_scol">
                                    <i class="bi bi-building me-1"></i>Scolastico
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Sezione TERRITORIALE -->
                    <div id="sezione-territoriale" class="col-12">
                        <div class="row g-3">
                            <div class="col-md-5">
                                <label class="form-label">Via</label>
                                <input type="text" name="via" id="via" class="form-control"
                                       value="<?= htmlspecialchars($ev['via_P'] ?? '') ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">N° civico</label>
                                <input type="number" name="n_civico" id="n_civico" class="form-control" min="1"
                                       value="<?= htmlspecialchars($ev['n_civico_P'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Città</label>
                                <select name="id_citta" id="id_citta" class="form-select">
                                    <option value="">Seleziona...</option>
                                    <?php foreach ($citta as $c): ?>
                                    <option value="<?= $c['ID_citta'] ?>"
                                        <?= ($ev['id_citta'] ?? 0) == $c['ID_citta'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($c['nome']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Coordinate</label>
                                <div class="d-flex gap-1">
                                    <input type="text" id="lat_display" class="form-control form-control-sm"
                                           placeholder="Lat" readonly
                                           value="<?= ($ev['latitudine'] ?? '') ?: '' ?>">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btn_geo"
                                            title="Geolocalizza">
                                        <i class="bi bi-geo-alt-fill"></i>
                                    </button>
                                </div>
                                <div id="geo_stato" class="form-text"></div>
                            </div>
                            <input type="hidden" name="lat" id="lat_hidden"
                                   value="<?= htmlspecialchars($ev['latitudine'] ?? '0') ?>">
                            <input type="hidden" name="lng" id="lng_hidden"
                                   value="<?= htmlspecialchars($ev['longitudine'] ?? '0') ?>">
                        </div>
                    </div>

                    <!-- Sezione SCOLASTICO: scuola -->
                    <div id="sezione-scolastico" class="col-md-6">
                        <label class="form-label">Scuola *</label>
                        <select name="cod_scuola" class="form-select">
                            <option value="">Seleziona...</option>
                            <?php foreach ($scuole as $s): ?>
                            <option value="<?= htmlspecialchars($s['COD_meccanografico']) ?>"
                                <?= ($ev['cod_scuola'] ?? '') === $s['COD_meccanografico'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s['nome']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <?php else: ?>
                    <!-- SCOLASTICO: target fisso, scuola fissa -->
                    <input type="hidden" name="target" value="SCOLASTICO">
                    <?php endif; ?>

                    <!-- Campi comuni -->
                    <div class="col-md-8">
                        <label class="form-label">Titolo *</label>
                        <input type="text" name="titolo" class="form-control" required maxlength="50"
                               value="<?= htmlspecialchars($ev['titolo'] ?? '') ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Descrizione breve *</label>
                        <input type="text" name="descrizione_breve" class="form-control" required maxlength="100"
                               value="<?= htmlspecialchars($ev['descrizione_breve'] ?? '') ?>">
                    </div>

                    <div class="col-12">
                        <label class="form-label">Descrizione *</label>
                        <textarea name="descrizione" class="form-control" rows="4" required
                        ><?= htmlspecialchars($ev['descrizione'] ?? '') ?></textarea>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Data e ora inizio *</label>
                        <input type="datetime-local" name="ora_inizio" class="form-control" required
                               value="<?= $ev ? str_replace(' ', 'T', substr($ev['ora_inizio'], 0, 16)) : '' ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Data e ora fine *</label>
                        <input type="datetime-local" name="ora_fine" class="form-control" required
                               value="<?= $ev ? str_replace(' ', 'T', substr($ev['ora_fine'], 0, 16)) : '' ?>">
                    </div>

                    <div class="col-md-4 d-flex align-items-end gap-4 pb-1">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="visibile" id="visibile"
                                   <?= ($ev['visibile'] ?? 1) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="visibile">Visibile</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="prenotabile" id="prenotabile"
                                   <?= ($ev['prenotabile'] ?? 0) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="prenotabile">Prenotabile</label>
                        </div>
                    </div>

                    <!-- Foto -->
                    <div class="col-md-6">
                        <label class="form-label">
                            Foto <?= $modoModifica ? '(lascia vuoto per mantenere l\'attuale)' : '*' ?>
                        </label>
                        <input type="file" name="foto" class="form-control" accept="image/jpeg,image/png"
                               <?= $modoModifica ? '' : 'required' ?>>
                        <?php if ($modoModifica && ($ev['path_foto'] ?? '')): ?>
                        <div class="mt-2">
                            <img src="../<?= htmlspecialchars($ev['path_foto']) ?>"
                                 alt="Foto attuale" style="height:80px;object-fit:cover;border-radius:4px;">
                        </div>
                        <?php endif; ?>
                    </div>

                </div>

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-<?= $modoModifica ? 'check-lg' : 'plus-lg' ?> me-1"></i>
                        <?= $modoModifica ? 'Aggiorna' : 'Aggiungi evento' ?>
                    </button>
                    <a href="eventi.php" class="btn btn-outline-secondary">Annulla</a>
                </div>

            </form>

        </div>
    </div>
</div>

<?php if ($isAdmin): ?>
<script>
const radioTerr = document.getElementById('t_terr');
const radioScol = document.getElementById('t_scol');
const sezTerr   = document.getElementById('sezione-territoriale');
const sezScol   = document.getElementById('sezione-scolastico');

function aggiornaSezioni() {
    const terrActive = radioTerr.checked;
    sezTerr.style.display = terrActive ? '' : 'none';
    sezScol.style.display = terrActive ? 'none' : '';
}

radioTerr.addEventListener('change', aggiornaSezioni);
radioScol.addEventListener('change', aggiornaSezioni);
aggiornaSezioni();

// Geocoding Nominatim
let geoTimer = null;
function geocodificaNominatim() {
    const via    = document.getElementById('via').value.trim();
    const civico = document.getElementById('n_civico').value.trim();
    const citta  = document.getElementById('id_citta');
    const nomeCitta = citta.options[citta.selectedIndex]?.text ?? '';
    const stato  = document.getElementById('geo_stato');
    if (!via || !civico || citta.value === '') return;
    stato.textContent = 'Ricerca coordinate...';
    const q = encodeURIComponent(via + ' ' + civico + ', ' + nomeCitta + ', Italia');
    fetch('https://nominatim.openstreetmap.org/search?q=' + q + '&format=json&limit=1', {
        headers: { 'Accept-Language': 'it' }
    })
    .then(r => r.json())
    .then(dati => {
        if (dati.length > 0) {
            const lat = parseFloat(dati[0].lat);
            const lng = parseFloat(dati[0].lon);
            document.getElementById('lat_hidden').value  = lat;
            document.getElementById('lng_hidden').value  = lng;
            document.getElementById('lat_display').value = lat.toFixed(6);
            stato.textContent = 'Coordinate trovate.';
        } else {
            stato.textContent = 'Indirizzo non trovato.';
        }
    })
    .catch(() => stato.textContent = 'Errore nella ricerca.');
}
['via', 'n_civico', 'id_citta'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.addEventListener('change', () => {
        clearTimeout(geoTimer);
        geoTimer = setTimeout(geocodificaNominatim, 600);
    });
});
const btnGeo = document.getElementById('btn_geo');
if (btnGeo) btnGeo.addEventListener('click', geocodificaNominatim);
</script>
<?php endif; ?>

<?php chiudi_pagina(); ?>
