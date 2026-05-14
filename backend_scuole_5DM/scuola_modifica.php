<?php
session_start();
require_once 'dati_connessione.php';

$conn = new mysqli($HOSTDB, $USERDB, $PASSDB, $NOMEDB);
$conn->set_charset("utf8");
if ($conn->connect_error) die("Errore connessione: " . $conn->connect_error);

// Ruolo e scuola dell'utente loggato
$ruolo_utente      = $_SESSION['ruoloUtente']     ?? '';
$cod_scuola_utente = $_SESSION['codScuolaUtente'] ?? null;
$is_scolastico     = ($ruolo_utente === 'SCOLASTICO');

if (empty($_GET['cod'])) { header("Location: index_scuole_backend.php"); exit; }
$cod = $_GET['cod'];

// Lo SCOLASTICO può modificare solo la propria scuola
if ($is_scolastico && $cod !== $cod_scuola_utente) {
    $_SESSION['flash_msg']  = "Non hai i permessi per modificare questa scuola.";
    $_SESSION['flash_type'] = "danger";
    header("Location: index_scuole_backend.php");
    exit;
}

// SALVATAGGIO MODIFICA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'modifica') {

    // Doppio controllo: lo SCOLASTICO non può modificare scuole altrui neanche via POST diretto
    if ($is_scolastico && $cod !== $cod_scuola_utente) {
        $_SESSION['flash_msg']  = "Non hai i permessi per modificare questa scuola.";
        $_SESSION['flash_type'] = "danger";
        header("Location: index_scuole_backend.php");
        exit;
    }
    $nome  = trim($_POST['nome']);
    $desc  = trim($_POST['descrizione']);
    $sito  = trim($_POST['sito']);
    $via   = trim($_POST['via']);
    $civ   = (int)$_POST['n_civico'];
    $citta = (int)$_POST['id_citta'];
    $lat   = (float)$_POST['lat'];
    $lng   = (float)$_POST['lng'];

    // Gestione foto: aggiornamento opzionale
    $id_foto_nuovo = null;
    $foto_caricata = isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK && $_FILES['foto']['size'] > 0;

    if ($foto_caricata) {
        require_once 'gestFoto.php';
        try {
            $id_foto_nuovo = uploadFoto($conn, $_FILES['foto'], $nome);
        } catch (Exception $e) {
            $errore = "Errore upload foto: " . htmlspecialchars($e->getMessage());
        }
    }

    if (empty($errore)) {
        if ($id_foto_nuovo !== null) {
            // Aggiorna anche la foto
            $sql  = "UPDATE scuole SET nome=?, descrizione=?, sito=?, via=?, n_civico=?, id_citta=?,
                     coordinate=ST_PointFromText(CONCAT('POINT(', ?, ' ', ?, ')')), id_foto=?
                     WHERE COD_meccanografico=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssiiddis", $nome, $desc, $sito, $via, $civ, $citta, $lng, $lat, $id_foto_nuovo, $cod);
        } else {
            // Mantieni la foto esistente
            $sql  = "UPDATE scuole SET nome=?, descrizione=?, sito=?, via=?, n_civico=?, id_citta=?,
                     coordinate=ST_PointFromText(CONCAT('POINT(', ?, ' ', ?, ')'))
                     WHERE COD_meccanografico=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssiidds", $nome, $desc, $sito, $via, $civ, $citta, $lng, $lat, $cod);
        }

        if ($stmt->execute()) {
            // Se c'era una foto precedente e ne è stata caricata una nuova, marca la vecchia come eliminata
            if ($id_foto_nuovo !== null && !empty($_POST['id_foto_precedente'])) {
                require_once 'gestFoto.php';
                try { delFoto($conn, (int)$_POST['id_foto_precedente']); } catch (Exception $e2) {}
            }
            $stmt->close();
            $conn->close();
            $_SESSION['flash_msg']  = "Scuola <strong>" . htmlspecialchars($nome) . "</strong> aggiornata con successo.";
            $_SESSION['flash_type'] = "success";
            header("Location: index_scuole_backend.php"); exit;
        } else {
            // Se l'update fallisce e avevamo caricato una nuova foto, annullala
            if ($id_foto_nuovo !== null) {
                require_once 'gestFoto.php';
                try { delFoto($conn, $id_foto_nuovo); } catch (Exception $e2) {}
            }
            $errore = "Errore aggiornamento: " . $conn->error;
        }
        $stmt->close();
    }
}

// CARICA DATI
$stmt = $conn->prepare("SELECT s.*, ST_X(s.coordinate) AS lng, ST_Y(s.coordinate) AS lat,
                               f.path_foto
                        FROM scuole s
                        LEFT JOIN foto f ON f.ID_foto = s.id_foto AND f.data_eliminazione IS NULL
                        WHERE s.COD_meccanografico = ?");
$stmt->bind_param("s", $cod);
$stmt->execute();
$scuola = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$scuola) { header("Location: index_scuole_backend.php"); exit; }

$citta_list = $conn->query("SELECT ID_citta, nome, sigla_provincia FROM citta ORDER BY nome ASC");
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifica Scuola — 3elleorienta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<input type="checkbox" id="sidebar-toggle">

<aside class="sidebar">
    <div class="logo">
        <span class="logo-text">
            <img src="img/logo.png" alt="logo" width="40" height="40"
                 style="object-fit:contain;vertical-align:middle;"> 3elleorienta
        </span>
        <label class="menu-toggle-label" for="sidebar-toggle" title="Apri/Chiudi menu">☰</label>
    </div>
    <nav class="nav-group mt-2">
        <div class="nav-label">SCUOLA</div>
        <a href="index_scuole_backend.php" class="nav-link active"><i class="bi bi-backpack-fill"></i><span class="link-text">Scuola</span></a>
        <a href="index_scuole_backend.php" class="nav-link"><i class="bi bi-geo-fill"></i><span class="link-text">Zona</span></a>
        <div class="nav-label mt-2">AVVENIMENTI</div>
        <a href="index_scuole_backend.php" class="nav-link"><i class="bi bi-calendar-fill"></i><span class="link-text">Eventi</span></a>
        <a href="index_scuole_backend.php" class="nav-link"><i class="bi bi-lightbulb-fill"></i><span class="link-text">Progetti</span></a>
        <a href="index_scuole_backend.php" class="nav-link"><i class="bi bi-link-45deg"></i><span class="link-text">Link Utili</span></a>
        <div class="nav-label mt-2">UTENTI</div>
        <a href="index_scuole_backend.php" class="nav-link"><i class="bi bi-people-fill"></i><span class="link-text">Gestione Utenti</span></a>
        <div class="nav-label mt-2">ALTRO</div>
        <a href="index_scuole_backend.php" class="nav-link"><i class="bi bi-tools"></i><span class="link-text">Impostazioni</span></a>
    </nav>
</aside>

<label class="sidebar-overlay" for="sidebar-toggle" aria-label="Chiudi menu"></label>

<div class="main-wrapper">
    <header class="top-bar">
        <div class="d-flex align-items-center gap-3">
            <label class="hamburger-label" for="sidebar-toggle" aria-label="Apri menu">
                <i class="bi bi-list"></i>
            </label>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item text-secondary">Dashboard</li>
                    <li class="breadcrumb-item"><a href="index_scuole_backend.php" class="text-decoration-none text-secondary">Scuola</a></li>
                    <li class="breadcrumb-item active">Modifica</li>
                </ol>
            </nav>
        </div>
        <div class="user-info d-flex align-items-center gap-2">
            <i class="bi bi-person-circle"></i>
            <span class="fw-semibold" style="font-size:0.88rem;"><?php echo htmlspecialchars($_SESSION['usernameUtente'] ?? 'Utente'); ?></span>
            <span class="text-secondary">|</span>
            <a href="logout.php" class="text-danger text-decoration-none" style="font-size:0.88rem;">Logout</a>
        </div>
    </header>

    <main class="page-content">
        <style>
            .section-title{font-size:.7rem;text-transform:uppercase;letter-spacing:1.5px;color:#7f8c8d;font-weight:600;margin-bottom:16px}
            .form-label{font-size:.82rem;font-weight:500}
            .badge-cod{font-size:.78rem;background:#eaf2fb;color:#2980b9;border-radius:4px;padding:3px 8px;font-family:monospace}
            .geo-status         { font-size:.78rem; margin-top:5px; min-height:1.3em; }
            .geo-status.ok      { color:#27ae60; }
            .geo-status.err     { color:#e67e22; }
            .geo-status.loading { color:#7f8c8d; }
            .foto-attuale       { max-width:160px; max-height:110px; object-fit:cover; border-radius:6px; border:1px solid #dee2e6; }
            .foto-preview-nuova { max-width:160px; max-height:110px; object-fit:cover; border-radius:6px; border:2px solid #198754; margin-top:6px; display:none; }
        </style>

        <?php if (!empty($errore)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= $errore ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="d-flex align-items-center gap-3 mb-4">
            <a href="index_scuole_backend.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Torna all'elenco
            </a>
            <h5 class="mb-0">
                Modifica scuola &nbsp;<span class="badge-cod"><?= htmlspecialchars($scuola['COD_meccanografico']) ?></span>
            </h5>
        </div>

        <div class="card-panel">
            <p class="section-title"><i class="bi bi-pencil-fill me-1"></i> Dati Scuola</p>
            <form method="POST" action="scuola_modifica.php?cod=<?= urlencode($cod) ?>" enctype="multipart/form-data">
                <input type="hidden" name="action" value="modifica">
                <input type="hidden" name="lat" id="lat_hidden" value="<?= htmlspecialchars($scuola['lat']) ?>">
                <input type="hidden" name="lng" id="lng_hidden" value="<?= htmlspecialchars($scuola['lng']) ?>">
                <input type="hidden" name="id_foto_precedente" value="<?= (int)($scuola['id_foto'] ?? 0) ?>">

                <div class="row g-3">

                    <div class="col-md-3">
                        <label class="form-label">Cod. Meccanografico</label>
                        <input type="text" class="form-control form-control-sm bg-light"
                               value="<?= htmlspecialchars($scuola['COD_meccanografico']) ?>" readonly>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Nome Scuola <span class="text-danger">*</span></label>
                        <input type="text" name="nome" class="form-control form-control-sm"
                               maxlength="50" required value="<?= htmlspecialchars($scuola['nome']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Sito Web</label>
                        <input type="url" name="sito" class="form-control form-control-sm"
                               value="<?= htmlspecialchars($scuola['sito']) ?>">
                    </div>

                    <div class="col-12">
                        <label class="form-label">Descrizione <span class="text-danger">*</span></label>
                        <textarea name="descrizione" class="form-control form-control-sm" rows="6"
                                  required><?= htmlspecialchars($scuola['descrizione']) ?></textarea>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Città <span class="text-danger">*</span></label>
                        <select name="id_citta" id="ins-citta" class="form-select form-select-sm" required>
                            <option value="">— Seleziona città —</option>
                            <?php while ($c = $citta_list->fetch_assoc()): ?>
                            <option value="<?= $c['ID_citta'] ?>" data-nome="<?= htmlspecialchars($c['nome']) ?>" <?= ($c['ID_citta'] == $scuola['id_citta']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['nome']) ?> (<?= htmlspecialchars($c['sigla_provincia']) ?>)
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Via <span class="text-danger">*</span></label>
                        <input type="text" name="via" id="ins-via" class="form-control form-control-sm"
                               maxlength="30" required value="<?= htmlspecialchars($scuola['via']) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">N° Civico <span class="text-danger">*</span></label>
                        <input type="number" name="n_civico" id="ins-civico" class="form-control form-control-sm"
                               min="1" required value="<?= (int)$scuola['n_civico'] ?>">
                    </div>

                    <div class="col-12">
                        <div id="geo-feedback" class="geo-status"></div>
                    </div>

                    <!-- ── SEZIONE FOTO ── -->
                    <div class="col-12">
                        <label class="form-label">Foto Scuola</label>
                        <?php if (!empty($scuola['path_foto'])): ?>
                        <div class="mb-2">
                            <div class="text-muted mb-1" style="font-size:.78rem;">
                                <i class="bi bi-image me-1"></i>Foto attuale:
                            </div>
                            <img src="<?= htmlspecialchars($scuola['path_foto']) ?>"
                                 alt="Foto attuale della scuola"
                                 class="foto-attuale">
                        </div>
                        <?php else: ?>
                        <div class="text-muted mb-2" style="font-size:.82rem;">
                            <i class="bi bi-image me-1"></i>Nessuna foto presente.
                        </div>
                        <?php endif; ?>
                        <input type="file" name="foto" id="mod-foto" class="form-control form-control-sm"
                               accept="image/jpeg,image/png">
                        <div class="form-text">
                            Carica una nuova foto per sostituire quella attuale. Formati: JPG, PNG. Max 2 MB.
                            Se non selezioni nulla, la foto attuale verrà mantenuta.
                        </div>
                        <img id="mod-foto-preview" class="foto-preview-nuova" alt="Anteprima nuova foto">
                    </div>

                    <div class="col-12 pt-1 d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm px-4">
                            <i class="bi bi-floppy-fill me-1"></i> Salva Modifiche
                        </button>
                        <a href="index_scuole_backend.php" class="btn btn-outline-secondary btn-sm">Annulla</a>
                    </div>

                </div>
            </form>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    /* ── Anteprima nuova foto ── */
    var modFoto    = document.getElementById('mod-foto');
    var modPreview = document.getElementById('mod-foto-preview');
    if (modFoto) {
        modFoto.addEventListener('change', function () {
            if (this.files && this.files[0]) {
                var reader = new FileReader();
                reader.onload = function (e) {
                    modPreview.src = e.target.result;
                    modPreview.style.display = 'block';
                };
                reader.readAsDataURL(this.files[0]);
            } else {
                modPreview.style.display = 'none';
                modPreview.src = '';
            }
        });
    }

    /* ── Geocoding automatico ── */
    var insVia    = document.getElementById('ins-via');
    var insCivico = document.getElementById('ins-civico');
    var insCitta  = document.getElementById('ins-citta');
    var latHidden = document.getElementById('lat_hidden');
    var lngHidden = document.getElementById('lng_hidden');
    var feedback  = document.getElementById('geo-feedback');

    if (!insVia || !insCitta) return;

    var geoTimer = null;

    function getNomeCitta() {
        var opt = insCitta.selectedOptions[0];
        return opt ? (opt.dataset.nome || opt.text.replace(/\s*\(.*\)$/, '').trim()) : '';
    }

    function geocodifica() {
        var via    = insVia.value.trim();
        var civico = insCivico ? insCivico.value.trim() : '';
        var citta  = getNomeCitta();

        if (!via || !citta) {
            feedback.textContent = '';
            feedback.className   = 'geo-status';
            return;
        }

        var indirizzo = via + (civico ? ' ' + civico : '') + ', ' + citta + ', Italia';
        feedback.textContent = '⏳ Ricerca coordinate in corso…';
        feedback.className   = 'geo-status loading';

        var url = 'https://nominatim.openstreetmap.org/search?q='
                + encodeURIComponent(indirizzo)
                + '&format=json&limit=1&addressdetails=0';

        fetch(url, { headers: { 'Accept-Language': 'it', 'User-Agent': '3elleorienta/1.0' } })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data && data.length > 0) {
                latHidden.value = data[0].lat;
                lngHidden.value = data[0].lon;
                feedback.textContent = '✅ Coordinate trovate: '
                    + parseFloat(data[0].lat).toFixed(5)
                    + ', ' + parseFloat(data[0].lon).toFixed(5);
                feedback.className = 'geo-status ok';
            } else {
                feedback.textContent = '⚠️ Indirizzo non trovato. Verranno mantenute le coordinate precedenti.';
                feedback.className   = 'geo-status err';
            }
        })
        .catch(function () {
            feedback.textContent = '⚠️ Servizio geocoding non raggiungibile. Verranno mantenute le coordinate precedenti.';
            feedback.className   = 'geo-status err';
        });
    }

    function triggerGeo() {
        clearTimeout(geoTimer);
        geoTimer = setTimeout(geocodifica, 600);
    }

    insVia.addEventListener('input', triggerGeo);
    if (insCivico) insCivico.addEventListener('input', triggerGeo);
    insCitta.addEventListener('change', triggerGeo);
});
</script>
</body>
</html>
<?php $conn->close(); ?>
