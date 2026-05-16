<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/layout.php';
require_once __DIR__ . '/gestione/gestione_scuole.php';
richiedi_login();

$conn      = db();
$isAdmin   = is_admin();
$codUtente = utente_cod_scuola();

$codModifica = trim($_GET['cod'] ?? '');
$modoModifica = $codModifica !== '';

// Solo ADMIN può aggiungere nuove scuole
if (!$modoModifica && !$isAdmin) {
    imposta_flash('errore', 'Solo gli amministratori possono aggiungere nuove scuole.');
    $conn->close();
    header('Location: scuole.php');
    exit;
}

// SCOLASTICO può modificare solo la propria scuola
if ($modoModifica && !$isAdmin && $codUtente !== $codModifica) {
    imposta_flash('errore', 'Non hai i permessi per modificare questa scuola.');
    $conn->close();
    header('Location: scuole.php');
    exit;
}

// POST: salva
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $azione = $_POST['azione'] ?? '';
    $file   = $_FILES['foto'] ?? ['error' => UPLOAD_ERR_NO_FILE, 'size' => 0];

    if ($azione === 'inserisci') {
        richiedi_admin();
        $esito = creaScuola($conn, $_POST, $file);
    } elseif ($azione === 'aggiorna') {
        $codPost = $_POST['cod_meccanografico'] ?? '';
        if (!$isAdmin && $codUtente !== $codPost) {
            imposta_flash('errore', 'Non hai i permessi.');
            $conn->close();
            header('Location: scuole.php');
            exit;
        }
        $esito = aggiornaScuola($conn, $codPost, $_POST, $file);
    } else {
        $esito = ['tipo' => 'errore', 'msg' => 'Azione non valida.'];
    }

    imposta_flash($esito['tipo'], $esito['msg']);
    $conn->close();
    if ($esito['tipo'] === 'errore') {
        $redirCod = $_POST['cod_meccanografico'] ?? '';
        header('Location: scuola_form.php' . ($redirCod !== '' ? '?cod=' . urlencode($redirCod) : ''));
    } else {
        header('Location: scuole.php');
    }
    exit;
}

// GET: prepara dati
$scuola   = $modoModifica ? leggiScuola($conn, $codModifica) : null;
$citta    = leggiCitta($conn);
$ambiti   = leggiAmbiti($conn);
$indirizzi = leggiIndirizzi($conn);
$ambitiSelezionati    = $modoModifica ? leggiAmbitiScuola($conn, $codModifica) : [];
$indirizziSelezionati = $modoModifica ? leggiIndirizziScuola($conn, $codModifica) : [];
$conn->close();

if ($modoModifica && !$scuola) {
    imposta_flash('errore', 'Scuola non trovata.');
    header('Location: scuole.php');
    exit;
}

$titolo = $modoModifica ? 'Modifica scuola' : 'Aggiungi scuola';
render_head_admin($titolo);
render_sidebar_admin('scuole.php');
render_topbar_admin($titolo);
?>

<div class="content-grid">
    <div class="grid-full">
        <div class="card-panel">

            <form method="POST" action="scuola_form.php" enctype="multipart/form-data">
                <input type="hidden" name="azione" value="<?= $modoModifica ? 'aggiorna' : 'inserisci' ?>">
                <?php if ($modoModifica): ?>
                <input type="hidden" name="cod_meccanografico" value="<?= htmlspecialchars($scuola['COD_meccanografico']) ?>">
                <?php endif; ?>

                <div class="row g-3">

                    <?php if (!$modoModifica): ?>
                    <div class="col-md-4">
                        <label class="form-label">Codice meccanografico *</label>
                        <input type="text" name="cod" class="form-control" required maxlength="10"
                               placeholder="es. ANIS01100A">
                    </div>
                    <?php else: ?>
                    <div class="col-md-4">
                        <label class="form-label">Codice meccanografico</label>
                        <input type="text" class="form-control" disabled
                               value="<?= htmlspecialchars($scuola['COD_meccanografico']) ?>">
                    </div>
                    <?php endif; ?>

                    <div class="col-md-8">
                        <label class="form-label">Nome scuola *</label>
                        <input type="text" name="nome" class="form-control" required
                               value="<?= htmlspecialchars($scuola['nome'] ?? '') ?>">
                    </div>

                    <div class="col-12">
                        <label class="form-label">Descrizione *</label>
                        <textarea name="descrizione" class="form-control" rows="3" required
                        ><?= htmlspecialchars($scuola['descrizione'] ?? '') ?></textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Sito web</label>
                        <input type="url" name="sito" class="form-control"
                               placeholder="https://" value="<?= htmlspecialchars($scuola['sito'] ?? '') ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Città *</label>
                        <select name="id_citta" id="id_citta" class="form-select" required>
                            <option value="">Seleziona...</option>
                            <?php foreach ($citta as $c): ?>
                            <option value="<?= $c['ID_citta'] ?>"
                                <?= ($scuola['id_citta'] ?? 0) == $c['ID_citta'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['nome']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Via *</label>
                        <input type="text" name="via" id="via" class="form-control" required
                               value="<?= htmlspecialchars($scuola['via'] ?? '') ?>">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">N° civico *</label>
                        <input type="number" name="n_civico" id="n_civico" class="form-control" required min="1"
                               value="<?= htmlspecialchars($scuola['n_civico'] ?? '') ?>">
                    </div>

                    <!-- Coordinate (compilate dal JS Nominatim) -->
                    <input type="hidden" name="lat" id="lat_hidden"
                           value="<?= htmlspecialchars($scuola['latitudine'] ?? '0') ?>">
                    <input type="hidden" name="lng" id="lng_hidden"
                           value="<?= htmlspecialchars($scuola['longitudine'] ?? '0') ?>">

                    <div class="col-md-4">
                        <label class="form-label">Coordinate</label>
                        <div class="d-flex gap-2 align-items-center">
                            <input type="text" id="lat_display" class="form-control form-control-sm"
                                   placeholder="Latitudine" readonly
                                   value="<?= ($scuola['latitudine'] ?? '') ?: '' ?>">
                            <input type="text" id="lng_display" class="form-control form-control-sm"
                                   placeholder="Longitudine" readonly
                                   value="<?= ($scuola['longitudine'] ?? '') ?: '' ?>">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btn_geo"
                                    title="Geolocalizza">
                                <i class="bi bi-geo-alt-fill"></i>
                            </button>
                        </div>
                        <div id="geo_stato" class="form-text"></div>
                    </div>

                    <!-- Ambiti -->
                    <div class="col-md-6">
                        <label class="form-label">Ambiti</label>
                        <div class="border rounded p-2" style="max-height:130px;overflow-y:auto;">
                            <?php foreach ($ambiti as $a): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox"
                                       name="ambiti[]" value="<?= $a['ID_ambito'] ?>"
                                       id="amb_<?= $a['ID_ambito'] ?>"
                                       <?= in_array($a['ID_ambito'], $ambitiSelezionati) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="amb_<?= $a['ID_ambito'] ?>">
                                    <?= htmlspecialchars($a['nome']) ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Indirizzi di studio -->
                    <div class="col-md-6">
                        <label class="form-label">Indirizzi di studio</label>
                        <div class="border rounded p-2" style="max-height:130px;overflow-y:auto;">
                            <?php foreach ($indirizzi as $ind): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox"
                                       name="indirizzi[]" value="<?= $ind['ID_indirizzo'] ?>"
                                       id="ind_<?= $ind['ID_indirizzo'] ?>"
                                       <?= in_array($ind['ID_indirizzo'], $indirizziSelezionati) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="ind_<?= $ind['ID_indirizzo'] ?>">
                                    <?= htmlspecialchars($ind['nome']) ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Foto -->
                    <div class="col-md-6">
                        <label class="form-label">Foto <?= $modoModifica ? '(lascia vuoto per mantenere l\'attuale)' : '' ?></label>
                        <input type="file" name="foto" class="form-control" accept="image/jpeg,image/png">
                        <?php if ($modoModifica && $scuola['id_foto']): ?>
                        <div class="mt-2">
                            <img src="../<?= htmlspecialchars($scuola['path_foto'] ?? '') ?>"
                                 alt="Foto attuale" style="height:80px;object-fit:cover;border-radius:4px;">
                        </div>
                        <?php endif; ?>
                    </div>

                </div>

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-<?= $modoModifica ? 'check-lg' : 'plus-lg' ?> me-1"></i>
                        <?= $modoModifica ? 'Aggiorna' : 'Aggiungi scuola' ?>
                    </button>
                    <a href="scuole.php" class="btn btn-outline-secondary">Annulla</a>
                </div>

            </form>

        </div>
    </div>
</div>

<script>
// ── JS Nominatim geocoding ──────────────────────────────────────────────────
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
            document.getElementById('lng_display').value = lng.toFixed(6);
            stato.textContent = 'Coordinate trovate.';
        } else {
            stato.textContent = 'Indirizzo non trovato (le coordinate verranno cercate lato server).';
        }
    })
    .catch(() => stato.textContent = 'Errore nella ricerca coordinate.');
}

['via', 'n_civico', 'id_citta'].forEach(function(id) {
    document.getElementById(id).addEventListener('change', function() {
        clearTimeout(geoTimer);
        geoTimer = setTimeout(geocodificaNominatim, 600);
    });
});

document.getElementById('btn_geo').addEventListener('click', geocodificaNominatim);
</script>

<?php chiudi_pagina(); ?>
