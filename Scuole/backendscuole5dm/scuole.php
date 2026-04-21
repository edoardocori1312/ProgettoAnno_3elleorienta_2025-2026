<?php
session_start();
require_once 'dati_connessione.php';

$conn = new mysqli($HOSTDB, $USERDB, $PASSDB, $NAMEDB);
$conn->set_charset("utf8");
if ($conn->connect_error) die("Errore connessione: " . $conn->connect_error);

$messaggio = '';
$tipo_msg  = '';

/* ═══════════════════════════════════════════════════
   INSERIMENTO
═══════════════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'inserisci') {
    $cod   = trim($_POST['cod_meccanografico']);
    $nome  = trim($_POST['nome']);
    $desc  = trim($_POST['descrizione']);
    $sito  = trim($_POST['sito']);
    $via   = trim($_POST['via']);
    $civ   = (int)$_POST['n_civico'];
    $citta = (int)$_POST['id_citta'];
    $lat   = (float)($_POST['lat_hidden'] ?? 0);
    $lng   = (float)($_POST['lng_hidden'] ?? 0);

    /* ── Geocoding lato server (fallback se il JS non ha valorizzato le coordinate) ── */
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

    $sql  = "INSERT INTO scuole (COD_meccanografico, nome, descrizione, sito, via, n_civico, id_citta, coordinate)
             VALUES (?, ?, ?, ?, ?, ?, ?, ST_PointFromText(CONCAT('POINT(', ?, ' ', ?, ')')))";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssiidd", $cod, $nome, $desc, $sito, $via, $civ, $citta, $lat, $lng);
    if ($stmt->execute()) {
        $stmt->close(); $conn->close();
        $_SESSION['flash_msg']  = "Scuola <strong>" . htmlspecialchars($nome) . "</strong> inserita con successo.";
        $_SESSION['flash_type'] = "success";
        header("Location: index.php"); exit;
    } else {
        $messaggio = "Errore inserimento: " . $conn->error;
        $tipo_msg  = "danger";
    }
    $stmt->close();
}

/* ═══════════════════════════════════════════════════
   ELIMINAZIONE
═══════════════════════════════════════════════════ */
if (($_GET['action'] ?? '') === 'elimina' && !empty($_GET['cod'])) {
    $cod = $_GET['cod'];
    foreach (['scuole_ambiti', 'scuole_indirizzi'] as $tab) {
        $s = $conn->prepare("DELETE FROM $tab WHERE cod_scuola = ?");
        $s->bind_param("s", $cod); $s->execute(); $s->close();
    }
    $s = $conn->prepare("UPDATE eventi SET cod_scuola = NULL WHERE cod_scuola = ?");
    $s->bind_param("s", $cod); $s->execute(); $s->close();

    $stmt = $conn->prepare("DELETE FROM scuole WHERE COD_meccanografico = ?");
    $stmt->bind_param("s", $cod);
    if ($stmt->execute()) {
        $stmt->close(); $conn->close();
        $_SESSION['flash_msg']  = "Scuola eliminata con successo.";
        $_SESSION['flash_type'] = "success";
        header("Location: index.php"); exit;
    } else {
        $messaggio = "Errore eliminazione: " . $conn->error;
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

$citta_list = $conn->query("SELECT ID_citta, nome, sigla_provincia FROM citta ORDER BY nome ASC");
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
</style>

<?php if ($messaggio): ?>
<div class="alert alert-<?=$tipo_msg?> alert-dismissible fade show" role="alert">
    <?=$messaggio?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- ═══ BARRA DI RICERCA ═══ -->
<div class="search-bar">
    <p class="section-title mb-3"><i class="bi bi-search me-1"></i> Ricerca Scuole</p>
    <div class="row g-3 align-items-end">
        <div class="col-md-4">
            <label class="form-label">Filtra per città</label>
            <select id="f-citta" class="form-select form-select-sm">
                <option value="0">— Tutte le città —</option>
                <?php $citta_list->data_seek(0); while ($c = $citta_list->fetch_assoc()): ?>
                <option value="<?=$c['ID_citta']?>" <?=($filtro_citta==$c['ID_citta'])?'selected':''?>>
                    <?=htmlspecialchars($c['nome'])?> (<?=htmlspecialchars($c['sigla_provincia'])?>)
                </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-5">
            <label class="form-label">Cerca per nome</label>
            <input type="text" id="f-nome" class="form-control form-control-sm"
                   placeholder="es. Liceo, IIS…" value="<?=htmlspecialchars($filtro_nome)?>">
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
                    <?=$result_lista->num_rows?> risultati
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
                            <td><span class="badge-cod"><?=htmlspecialchars($row['COD_meccanografico'])?></span></td>
                            <td class="fw-semibold"><?=htmlspecialchars($row['nome'])?></td>
                            <td><?=htmlspecialchars($row['citta'])?> (<?=htmlspecialchars($row['sigla_provincia'])?>)</td>
                            <td><?=htmlspecialchars($row['via'])?>, <?=(int)$row['n_civico']?></td>
                            <td>
                                <?php if ($row['sito']): ?>
                                    <a href="<?=htmlspecialchars($row['sito'])?>" target="_blank"
                                       class="text-decoration-none" style="font-size:.82rem;">
                                        <i class="bi bi-box-arrow-up-right me-1"></i>Apri
                                    </a>
                                <?php else: echo '—'; endif; ?>
                            </td>
                            <td class="text-center">
                                <a href="scuola_modifica.php?cod=<?=urlencode($row['COD_meccanografico'])?>"
                                   class="btn btn-outline-primary btn-sm py-0 px-2 me-1" title="Modifica">
                                    <i class="bi bi-pencil-fill"></i>
                                </a>
                                <a href="scuole.php?action=elimina&cod=<?=urlencode($row['COD_meccanografico'])?>&ajax=1"
                                   class="btn btn-outline-danger btn-sm py-0 px-2" title="Elimina"
                                   onclick="return confirm('Eliminare \'<?=addslashes(htmlspecialchars($row['nome']))?>\'?\nL\'operazione non è reversibile.')">
                                    <i class="bi bi-trash-fill"></i>
                                </a>
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
            <form method="POST" action="scuole.php">
                <input type="hidden" name="action"     value="inserisci">
                <input type="hidden" name="lat_hidden" id="lat_hidden" value="0">
                <input type="hidden" name="lng_hidden" id="lng_hidden" value="0">

                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Cod. Meccanografico <span class="text-danger">*</span></label>
                        <input type="text" name="cod_meccanografico" class="form-control form-control-sm"
                               maxlength="10" placeholder="es. MNIS00100E" required>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Nome Scuola <span class="text-danger">*</span></label>
                        <input type="text" name="nome" class="form-control form-control-sm"
                               maxlength="50" placeholder="es. IIS Galileo Galilei" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Sito Web</label>
                        <input type="url" name="sito" class="form-control form-control-sm"
                               placeholder="https://www.scuola.edu.it">
                    </div>

                    <div class="col-12">
                        <label class="form-label">Descrizione <span class="text-danger">*</span></label>
                        <textarea name="descrizione" class="form-control form-control-sm" rows="4"
                                  placeholder="Descrizione della scuola…" required></textarea>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Città <span class="text-danger">*</span></label>
                        <select name="id_citta" id="ins-citta" class="form-select form-select-sm" required>
                            <option value="">— Seleziona città —</option>
                            <?php $citta_list->data_seek(0); while ($c = $citta_list->fetch_assoc()): ?>
                            <option value="<?=$c['ID_citta']?>"
                                    data-nome="<?=htmlspecialchars($c['nome'])?>">
                                <?=htmlspecialchars($c['nome'])?> (<?=htmlspecialchars($c['sigla_provincia'])?>)
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Via <span class="text-danger">*</span></label>
                        <input type="text" name="via" id="ins-via" class="form-control form-control-sm"
                               maxlength="30" placeholder="es. Via Roma" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">N° Civico <span class="text-danger">*</span></label>
                        <input type="number" name="n_civico" id="ins-civico" class="form-control form-control-sm"
                               min="1" placeholder="es. 12" required>
                    </div>

                    <div class="col-12">
                        <div id="geo-feedback" class="geo-status"></div>
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
<?php $conn->close(); ?>
