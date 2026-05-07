<?php
session_start();
require_once 'dati_connessione.php';

$conn = new mysqli($HOSTDB, $USERDB, $PASSDB, $NAMEDB);
$conn->set_charset("utf8mb4");
if ($conn->connect_error) die("Errore connessione: " . $conn->connect_error);

$messaggio = '';
$tipo_msg  = '';

// Recupera i valori del form per ripopolare i campi in caso di errore
$form_vals = [
    'cod'         => '',
    'nome'        => '',
    'descrizione' => '',
    'sito'        => '',
    'via'         => '',
    'n_civico'    => '',
    'id_citta'    => 0,
    'lat'         => '0',
    'lng'         => '0',
];

/* ═══════════════════════════════════════════════════
   INSERIMENTO
═══════════════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'inserisci') {

    // Recupera valori e salva per ripopolare il form in caso di errore
    $cod   = trim($_POST['cod_meccanografico'] ?? '');
    $nome  = trim($_POST['nome']              ?? '');
    $desc  = trim($_POST['descrizione']       ?? '');
    $sito  = trim($_POST['sito']              ?? '');
    $via   = trim($_POST['via']               ?? '');
    $civ   = (int)($_POST['n_civico']         ?? 0);
    $citta = (int)($_POST['id_citta']         ?? 0);
    $lat   = (float)($_POST['lat_hidden']     ?? 0);
    $lng   = (float)($_POST['lng_hidden']     ?? 0);

    $form_vals = compact('cod', 'nome', 'desc', 'sito', 'via', 'civ', 'citta', 'lat', 'lng');
    $form_vals['descrizione'] = $desc;
    $form_vals['id_citta']    = $citta;
    $form_vals['n_civico']    = $civ;

    // Validazione base lato server
    $errori = [];
    if ($cod === '')   $errori[] = "Il codice meccanografico è obbligatorio.";
    if ($nome === '')  $errori[] = "Il nome della scuola è obbligatorio.";
    if ($desc === '')  $errori[] = "La descrizione è obbligatoria.";
    if ($via === '')   $errori[] = "La via è obbligatoria.";
    if ($civ <= 0)     $errori[] = "Il numero civico deve essere maggiore di 0.";
    if ($citta <= 0)   $errori[] = "Seleziona una città.";

    // Validazione foto obbligatoria
    $foto_presente = isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK && $_FILES['foto']['size'] > 0;
    if (!$foto_presente) $errori[] = "La foto della scuola è obbligatoria.";

    if (empty($errori)) {
        /* ── Geocoding lato server (fallback) ── */
        if ($lat == 0 && $lng == 0) {
            $stmt_c = $conn->prepare("SELECT nome FROM citta WHERE ID_citta = ?");
            $stmt_c->bind_param("i", $citta);
            $stmt_c->execute();
            $row_c      = $stmt_c->get_result()->fetch_assoc();
            $stmt_c->close();
            $citta_nome = $row_c['nome'] ?? '';

            $indirizzo_enc = urlencode("$via $civ, $citta_nome, Italia");
            $geo_url = "https://nominatim.openstreetmap.org/search?q={$indirizzo_enc}&format=json&limit=1";
            $ctx = stream_context_create([
                'http' => [
                    'header'  => "User-Agent: 3elleorienta/1.0\r\nAccept-Language: it\r\n",
                    'timeout' => 6
                ]
            ]);
            $geo_resp = @file_get_contents($geo_url, false, $ctx);
            if ($geo_resp) {
                $geo_data = json_decode($geo_resp, true);
                if (!empty($geo_data[0])) {
                    $lat = (float)$geo_data[0]['lat'];
                    $lng = (float)$geo_data[0]['lon'];
                }
            }
        }

        /* ── Upload foto ── */
        $id_foto = null;
        require_once 'gestFoto.php';
        try {
            $id_foto = uploadFoto($conn, $_FILES['foto']);
        } catch (Exception $e) {
            $messaggio = "Errore upload foto: " . htmlspecialchars($e->getMessage());
            $tipo_msg  = "danger";
        }

        if ($id_foto !== null) {
            $sql  = "INSERT INTO scuole (COD_meccanografico, nome, descrizione, sito, via, n_civico, id_citta, coordinate, id_foto)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ST_PointFromText(CONCAT('POINT(', ?, ' ', ?, ')')), ?)";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                $messaggio = "Errore preparazione query: " . $conn->error;
                $tipo_msg  = "danger";
            } else {
                $stmt->bind_param("sssssidddi", $cod, $nome, $desc, $sito, $via, $civ, $citta, $lng, $lat, $id_foto);
                if ($stmt->execute()) {
                    $stmt->close();
                    $conn->close();
                    $_SESSION['flash_msg']  = "Scuola <strong>" . htmlspecialchars($nome) . "</strong> inserita con successo.";
                    $_SESSION['flash_type'] = "success";
                    header("Location: index.php");
                    exit;
                } else {
                    // Se l'inserimento scuola fallisce, annulla la foto appena caricata
                    try { delFoto($conn, $id_foto); } catch (Exception $e2) {}
                    if ($conn->errno === 1062) {
                        $messaggio = "Errore: il codice meccanografico <strong>" . htmlspecialchars($cod) . "</strong> esiste già.";
                    } else {
                        $messaggio = "Errore inserimento: " . htmlspecialchars($conn->error);
                    }
                    $tipo_msg = "danger";
                }
                $stmt->close();
            }
        }
    } else {
        $messaggio = implode('<br>', array_map('htmlspecialchars', $errori));
        $tipo_msg  = "danger";
    }
}

/* ═══════════════════════════════════════════════════
   ELIMINAZIONE
═══════════════════════════════════════════════════ */
if (($_GET['action'] ?? '') === 'elimina' && !empty($_GET['cod'])) {
    $cod_del = trim($_GET['cod']);

    $check = $conn->prepare("SELECT COD_meccanografico, id_foto FROM scuole WHERE COD_meccanografico = ?");
    $check->bind_param("s", $cod_del);
    $check->execute();
    $row_check = $check->get_result()->fetch_assoc();
    $check->close();

    if (!$row_check) {
        $_SESSION['flash_msg']  = "Scuola non trovata.";
        $_SESSION['flash_type'] = "warning";
        header("Location: index.php");
        exit;
    }
    $id_foto_da_eliminare = $row_check['id_foto'];

    foreach (['scuole_ambiti', 'scuole_indirizzi'] as $tab) {
        $s = $conn->prepare("DELETE FROM $tab WHERE cod_scuola = ?");
        if ($s) { $s->bind_param("s", $cod_del); $s->execute(); $s->close(); }
    }
    $s = $conn->prepare("UPDATE eventi SET cod_scuola = NULL WHERE cod_scuola = ?");
    if ($s) { $s->bind_param("s", $cod_del); $s->execute(); $s->close(); }

    $stmt = $conn->prepare("DELETE FROM scuole WHERE COD_meccanografico = ?");
    $stmt->bind_param("s", $cod_del);
    if ($stmt->execute()) {
        $stmt->close();

        // Segna la foto come eliminata impostando data_eliminazione
        if ($id_foto_da_eliminare) {
            require_once 'gestFoto.php';
            try { delFoto($conn, $id_foto_da_eliminare); } catch (Exception $e) {}
        }
        $conn->close();
        $_SESSION['flash_msg']  = "Scuola eliminata con successo.";
        $_SESSION['flash_type'] = "success";
        header("Location: index.php");
        exit;
    } else {
        $messaggio = "Errore eliminazione: " . htmlspecialchars($conn->error);
        $tipo_msg  = "danger";
    }
    $stmt->close();
}

/* ═══════════════════════════════════════════════════
   RICERCA / LISTA
═══════════════════════════════════════════════════ */
$filtro_citta = isset($_GET['id_citta']) ? (int)$_GET['id_citta'] : 0;
$filtro_nome  = isset($_GET['cerca'])    ? trim($_GET['cerca'])    : '';

$sql_lista = "SELECT s.COD_meccanografico, s.nome, s.sito, s.via, s.n_civico,
                     c.nome AS citta, c.sigla_provincia
              FROM scuole s
              JOIN citta c ON s.id_citta = c.ID_citta
              WHERE 1=1";
$params      = [];
$param_types = "";

if ($filtro_citta > 0) {
    $sql_lista  .= " AND s.id_citta = ?";
    $params[]    = $filtro_citta;
    $param_types .= "i";
}
if ($filtro_nome !== '') {
    $sql_lista  .= " AND s.nome LIKE ?";
    $params[]    = "%" . $filtro_nome . "%";
    $param_types .= "s";
}
$sql_lista .= " ORDER BY s.nome ASC";

$stmt_lista = $conn->prepare($sql_lista);
if (!empty($params)) $stmt_lista->bind_param($param_types, ...$params);
$stmt_lista->execute();
$result_lista = $stmt_lista->get_result();

$citta_rows = [];
$res_citta  = $conn->query("SELECT ID_citta, nome, sigla_provincia FROM citta ORDER BY nome ASC");
while ($c = $res_citta->fetch_assoc()) $citta_rows[] = $c;
$res_citta->free();
?>
<style>
.table-scuole th  { font-size:.78rem; text-transform:uppercase; letter-spacing:.8px; color:#7f8c8d; }
.table-scuole td  { font-size:.88rem; vertical-align:middle; }
.badge-cod        { font-size:.7rem; background:#eaf2fb; color:#2980b9; border-radius:4px; padding:2px 6px; font-family:monospace; }
.section-title    { font-size:.7rem; text-transform:uppercase; letter-spacing:1.5px; color:#7f8c8d; font-weight:600; margin-bottom:16px; }
.form-label       { font-size:.82rem; font-weight:500; }
.search-bar       { background:white; border-radius:8px; box-shadow:0 1px 6px rgba(0,0,0,.07); padding:18px 24px; margin-bottom:20px; }
.geo-status         { font-size:.78rem; margin-top:5px; min-height:1.3em; }
.geo-status.ok      { color:#27ae60; }
.geo-status.err     { color:#e67e22; }
.geo-status.loading { color:#7f8c8d; }
.foto-preview       { max-width:120px; max-height:80px; object-fit:cover; border-radius:4px; border:1px solid #dee2e6; margin-top:6px; display:none; }
</style>

<?php if ($messaggio): ?>
<div class="alert alert-<?= htmlspecialchars($tipo_msg) ?> alert-dismissible fade show" role="alert">
    <i class="bi bi-<?= $tipo_msg === 'danger' ? 'exclamation-triangle-fill' : 'check-circle-fill' ?> me-2"></i>
    <?= $messaggio ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- ═══ MODALE CONFERMA ELIMINAZIONE ═══ -->
<div class="modal fade" id="modalElimina" tabindex="-1" aria-labelledby="modalEliminaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title text-danger" id="modalEliminaLabel">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Conferma Eliminazione
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-2">
                <p class="mb-1" style="font-size:.9rem;">
                    Stai per eliminare la scuola:<br>
                    <strong id="modalNomeScuola" class="text-dark"></strong>
                </p>
                <p class="text-danger mb-0" style="font-size:.82rem;">
                    <i class="bi bi-info-circle me-1"></i>L'operazione non è reversibile.
                </p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Annulla</button>
                <a id="modalEliminaLink" href="#" class="btn btn-danger btn-sm">
                    <i class="bi bi-trash-fill me-1"></i>Elimina
                </a>
            </div>
        </div>
    </div>
</div>

<!-- ═══ BARRA DI RICERCA ═══ -->
<div class="search-bar">
    <p class="section-title mb-3"><i class="bi bi-search me-1"></i> Ricerca Scuole</p>
    <div class="row g-3 align-items-end">
        <div class="col-md-4">
            <label class="form-label">Filtra per città</label>
            <select id="f-citta" class="form-select form-select-sm">
                <option value="0">— Tutte le città —</option>
                <?php foreach ($citta_rows as $c): ?>
                <option value="<?= $c['ID_citta'] ?>" <?= ($filtro_citta == $c['ID_citta']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c['nome']) ?> (<?= htmlspecialchars($c['sigla_provincia']) ?>)
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-5">
            <label class="form-label">Cerca per nome</label>
            <input type="text" id="f-nome" class="form-control form-control-sm"
                   placeholder="es. Liceo, IIS…" value="<?= htmlspecialchars($filtro_nome) ?>">
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="button" id="btn-filtra" class="btn btn-primary btn-sm w-100">
                <i class="bi bi-funnel-fill me-1"></i> Filtra
            </button>
            <button type="button" id="btn-reset" class="btn btn-outline-secondary btn-sm" title="Azzera filtri">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    </div>
</div>

<!-- ═══ TABELLA ELENCO ═══ -->
<div class="content-grid">
    <div class="grid-full">
        <div class="card-panel">
            <p class="section-title">
                <i class="bi bi-backpack-fill me-1"></i> Elenco Scuole
                <span class="float-end fw-normal text-secondary"
                      id="contatore-risultati"
                      style="font-size:.75rem;text-transform:none;letter-spacing:0;">
                    <?= $result_lista->num_rows ?> risultati
                </span>
            </p>

            <div id="tabella-scuole-wrapper">
                <?php if ($result_lista->num_rows === 0): ?>
                    <div class="text-center text-secondary py-4">
                        <i class="bi bi-inbox fs-3 d-block mb-2"></i> Nessuna scuola trovata.
                    </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover table-scuole mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Cod. Meccanografico</th>
                                <th>Nome Scuola</th>
                                <th>Città</th>
                                <th>Indirizzo</th>
                                <th>Sito Web</th>
                                <th class="text-center">Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php while ($row = $result_lista->fetch_assoc()): ?>
                        <tr>
                            <td><span class="badge-cod"><?= htmlspecialchars($row['COD_meccanografico']) ?></span></td>
                            <td class="fw-semibold"><?= htmlspecialchars($row['nome']) ?></td>
                            <td><?= htmlspecialchars($row['citta']) ?> (<?= htmlspecialchars($row['sigla_provincia']) ?>)</td>
                            <td><?= htmlspecialchars($row['via']) ?>, <?= (int)$row['n_civico'] ?></td>
                            <td>
                                <?php if ($row['sito']): ?>
                                    <a href="<?= htmlspecialchars($row['sito']) ?>" target="_blank" rel="noopener noreferrer"
                                       class="text-decoration-none" style="font-size:.82rem;">
                                        <i class="bi bi-box-arrow-up-right me-1"></i>Apri
                                    </a>
                                <?php else: echo '—'; endif; ?>
                            </td>
                            <td class="text-center">
                                <a href="scuola_modifica.php?cod=<?= urlencode($row['COD_meccanografico']) ?>"
                                   class="btn btn-outline-primary btn-sm py-0 px-2 me-1" title="Modifica">
                                    <i class="bi bi-pencil-fill"></i>
                                </a>
                                <button type="button"
                                        class="btn btn-outline-danger btn-sm py-0 px-2 btn-elimina-scuola"
                                        title="Elimina"
                                        data-nome="<?= htmlspecialchars($row['nome'], ENT_QUOTES) ?>"
                                        data-url="scuole.php?action=elimina&cod=<?= urlencode($row['COD_meccanografico']) ?>&ajax=1">
                                    <i class="bi bi-trash-fill"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div><!-- /tabella-scuole-wrapper -->
        </div>
    </div>

    <!-- ═══ FORM INSERIMENTO ═══ -->
    <div class="grid-full">
        <div class="card-panel">
            <p class="section-title"><i class="bi bi-plus-circle-fill me-1"></i> Inserisci Nuova Scuola</p>
            <form method="POST" action="scuole.php?ajax=1" enctype="multipart/form-data">
                <input type="hidden" name="action"     value="inserisci">
                <input type="hidden" name="lat_hidden" id="lat_hidden" value="<?= htmlspecialchars($form_vals['lat']) ?>">
                <input type="hidden" name="lng_hidden" id="lng_hidden" value="<?= htmlspecialchars($form_vals['lng']) ?>">

                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Cod. Meccanografico <span class="text-danger">*</span></label>
                        <input type="text" name="cod_meccanografico" class="form-control form-control-sm"
                               maxlength="10" placeholder="es. MNIS00100E" required
                               value="<?= htmlspecialchars($form_vals['cod']) ?>">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Nome Scuola <span class="text-danger">*</span></label>
                        <input type="text" name="nome" class="form-control form-control-sm"
                               maxlength="50" placeholder="es. IIS Galileo Galilei" required
                               value="<?= htmlspecialchars($form_vals['nome']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Sito Web</label>
                        <input type="url" name="sito" class="form-control form-control-sm"
                               placeholder="https://www.scuola.edu.it"
                               value="<?= htmlspecialchars($form_vals['sito']) ?>">
                    </div>

                    <div class="col-12">
                        <label class="form-label">Descrizione <span class="text-danger">*</span></label>
                        <textarea name="descrizione" class="form-control form-control-sm" rows="4"
                                  placeholder="Descrizione della scuola…" required><?= htmlspecialchars($form_vals['descrizione']) ?></textarea>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Città <span class="text-danger">*</span></label>
                        <select name="id_citta" id="ins-citta" class="form-select form-select-sm" required>
                            <option value="">— Seleziona città —</option>
                            <?php foreach ($citta_rows as $c): ?>
                            <option value="<?= $c['ID_citta'] ?>"
                                    data-nome="<?= htmlspecialchars($c['nome']) ?>"
                                    <?= ($form_vals['id_citta'] == $c['ID_citta']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['nome']) ?> (<?= htmlspecialchars($c['sigla_provincia']) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Via <span class="text-danger">*</span></label>
                        <input type="text" name="via" id="ins-via" class="form-control form-control-sm"
                               maxlength="30" placeholder="es. Via Roma" required
                               value="<?= htmlspecialchars($form_vals['via']) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">N° Civico <span class="text-danger">*</span></label>
                        <input type="number" name="n_civico" id="ins-civico" class="form-control form-control-sm"
                               min="1" placeholder="es. 12" required
                               value="<?= $form_vals['n_civico'] ?: '' ?>">
                    </div>

                    <div class="col-12">
                        <div id="geo-feedback" class="geo-status"></div>
                    </div>

                    <!-- ── FOTO OBBLIGATORIA ── -->
                    <div class="col-12">
                        <label class="form-label">Foto Scuola <span class="text-danger">*</span></label>
                        <input type="file" name="foto" id="ins-foto" class="form-control form-control-sm"
                               accept="image/jpeg,image/png" required>
                        <div class="form-text">Formati accettati: JPG, PNG. Dimensione massima: 2 MB.</div>
                        <img id="ins-foto-preview" class="foto-preview" alt="Anteprima foto">
                    </div>

                    <div class="col-12 pt-1">
                        <button type="submit" class="btn btn-success btn-sm px-4">
                            <i class="bi bi-floppy-fill me-1"></i> Inserisci Scuola
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
/* ── Anteprima foto inserimento ── */
(function () {
    var inputFoto   = document.getElementById('ins-foto');
    var previewFoto = document.getElementById('ins-foto-preview');
    if (!inputFoto) return;
    inputFoto.addEventListener('change', function () {
        if (this.files && this.files[0]) {
            var reader = new FileReader();
            reader.onload = function (e) {
                previewFoto.src = e.target.result;
                previewFoto.style.display = 'block';
            };
            reader.readAsDataURL(this.files[0]);
        } else {
            previewFoto.style.display = 'none';
            previewFoto.src = '';
        }
    });
})();

/* ── Modale eliminazione ── */
document.addEventListener('click', function (e) {
    var btn = e.target.closest('.btn-elimina-scuola');
    if (!btn) return;
    document.getElementById('modalNomeScuola').textContent = btn.dataset.nome;
    document.getElementById('modalEliminaLink').href = btn.dataset.url;
    new bootstrap.Modal(document.getElementById('modalElimina')).show();
});
</script>

<?php
$stmt_lista->close();
$conn->close();
?>
