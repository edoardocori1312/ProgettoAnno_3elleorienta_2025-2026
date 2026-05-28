<?php
// La sessione è già aperta da index.php tramite controlloSessione.php
// $conn è disponibile da index.php

// ── Azioni POST ───────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['azione'])) {
    $azione = $_POST['azione'];

    if ($azione === 'elimina') {
        $id  = (int)$_POST['idProgetto'];
        $sql = "UPDATE Progetti SET data_eliminazione = NOW() WHERE ID_progetto = ?";
        $s   = $conn->prepare($sql);
        $s->bind_param('i', $id);
        $s->execute();
        $s->close();
        header('Location: index.php?page=progetti');
        exit();

    } elseif ($azione === 'ripristina') {
        $id  = (int)$_POST['idProgetto'];
        $sql = "UPDATE Progetti SET data_eliminazione = NULL WHERE ID_progetto = ?";
        $s   = $conn->prepare($sql);
        $s->bind_param('i', $id);
        $s->execute();
        $s->close();
        header('Location: index.php?page=progetti');
        exit();

    } elseif ($azione === 'salva_modifica') {
        @include_once __DIR__ . '/../pictures/gestFoto.php';
        $idMod   = (int)$_POST['idDaModificare'];
        $titolo  = trim($_POST['titolo']);
        $descr   = trim($_POST['descrizione']);
        $nOrd    = (int)$_POST['n_ordine'];
        $id_foto = !empty($_POST['id_foto_corrente']) ? (int)$_POST['id_foto_corrente'] : null;

        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            try { $id_foto = uploadFoto($conn, $_FILES['foto']); }
            catch (Exception $e) { die('Errore upload foto: ' . $e->getMessage()); }
        }
        $s = $conn->prepare("UPDATE Progetti SET titolo=?, descrizione=?, n_ordine=?, id_foto=? WHERE ID_progetto=?");
        $s->bind_param('ssiii', $titolo, $descr, $nOrd, $id_foto, $idMod);
        $s->execute();
        $s->close();
        header('Location: index.php?page=progetti');
        exit();

    } elseif ($azione === 'aggiungi') {
        $titolo  = trim($_POST['titolo']);
        $descr   = trim($_POST['descrizione']);
        $nOrd    = (int)$_POST['n_ordine'];
        $s = $conn->prepare("INSERT INTO Progetti (titolo, descrizione, n_ordine) VALUES (?, ?, ?)");
        $s->bind_param('ssi', $titolo, $descr, $nOrd);
        $s->execute();
        $s->close();
        header('Location: index.php?page=progetti');
        exit();
    }
}

// ── Filtro ricerca ────────────────────────────────────────────────────────────
$filtroTitolo = trim($_GET['filtro_titolo'] ?? '');

if ($filtroTitolo !== '') {
    $sql  = "SELECT p.*, f.path_foto FROM Progetti p LEFT JOIN Foto f ON p.id_foto = f.ID_foto WHERE p.titolo LIKE ? ORDER BY p.n_ordine ASC";
    $s    = $conn->prepare($sql);
    $like = "%{$filtroTitolo}%";
    $s->bind_param('s', $like);
} else {
    $sql = "SELECT p.*, f.path_foto FROM Progetti p LEFT JOIN Foto f ON p.id_foto = f.ID_foto ORDER BY p.n_ordine ASC";
    $s   = $conn->prepare($sql);
}

$tabella = '';
if ($s && $s->execute()) {
    $res = $s->get_result();
    if ($res->num_rows > 0) {
        while ($r = $res->fetch_object()) {
            $idP   = (int)$r->ID_progetto;
            $tit   = htmlspecialchars($r->titolo ?? '');
            $desc  = htmlspecialchars($r->descrizione ?? '');
            $titQ  = htmlspecialchars($r->titolo ?? '', ENT_QUOTES);
            $descQ = htmlspecialchars($r->descrizione ?? '', ENT_QUOTES);
            $nOrd  = (int)$r->n_ordine;
            $datEl = $r->data_eliminazione ?? '';
            $idF   = (int)($r->id_foto ?? 0);

            $rowClass = $datEl ? "class='text-muted' style='opacity:.6;'" : '';

            $tabella .= "<tr {$rowClass}>";
            $tabella .= "<td>{$idP}</td>";
            $tabella .= "<td class='fw-semibold'>{$tit}</td>";
            $tabella .= "<td class='text-center'>{$nOrd}</td>";
            $tabella .= $datEl
                ? "<td><span class='badge bg-danger-subtle text-danger'>{$datEl}</span></td>"
                : "<td><span class='badge bg-success'>Attivo</span></td>";
            $tabella .= "<td>" . ($idF ?: '—') . "</td>";
            $tabella .= "<td class='text-end'>";
            $tabella .= "<button type='button' class='btn btn-sm btn-outline-primary me-1'
                            data-bs-toggle='modal' data-bs-target='#modalModificaProgetto'
                            data-id='{$idP}' data-titolo='{$titQ}' data-descrizione='{$descQ}'
                            data-ordine='{$nOrd}' data-idfoto='{$idF}'>
                            <i class='bi bi-pencil'></i> Modifica
                          </button>";
            if (empty($datEl)) {
                $tabella .= "<form method='POST' action='index.php?page=progetti' style='display:inline;'
                              onsubmit=\"return confirm('Nascondere il progetto?')\">
                    <input type='hidden' name='page' value='progetti'>
                    <input type='hidden' name='idProgetto' value='{$idP}'>
                    <input type='hidden' name='azione' value='elimina'>
                    <button type='submit' class='btn btn-sm btn-outline-danger'>
                        <i class='bi bi-trash'></i> Elimina
                    </button></form>";
            } else {
                $tabella .= "<form method='POST' action='index.php?page=progetti' style='display:inline;'>
                    <input type='hidden' name='page' value='progetti'>
                    <input type='hidden' name='idProgetto' value='{$idP}'>
                    <input type='hidden' name='azione' value='ripristina'>
                    <button type='submit' class='btn btn-sm btn-outline-warning'>
                        <i class='bi bi-arrow-counterclockwise'></i> Ripristina
                    </button></form>";
            }
            $tabella .= "</td></tr>";
        }
    } else {
        $tabella = "<tr><td colspan='6' class='text-center text-secondary py-4'><i class='bi bi-inbox fs-3 d-block mb-2'></i>Nessun progetto trovato.</td></tr>";
    }
    $s->close();
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-semibold mb-0">
        <i class="bi bi-grid-1x2-fill me-2 text-secondary"></i>Progetti
    </h5>
    <button class="btn btn-primary d-flex align-items-center gap-2"
            data-bs-toggle="modal" data-bs-target="#modalProgetti">
        <i class="bi bi-plus-circle"></i> Aggiungi progetto
    </button>
</div>

<!-- Barra ricerca -->
<div class="card-panel mb-3">
    <form method="GET" action="index.php">
        <input type="hidden" name="page" value="progetti">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="input-group">
                    <span class="input-group-text bg-white">
                        <i class="bi bi-search text-secondary"></i>
                    </span>
                    <input type="text" name="filtro_titolo" class="form-control"
                           placeholder="Cerca progetto…"
                           value="<?= htmlspecialchars($filtroTitolo) ?>" autocomplete="off">
                </div>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-outline-primary">Cerca</button>
                <?php if ($filtroTitolo): ?>
                    <a href="index.php?page=progetti" class="btn btn-outline-secondary ms-1">Azzera</a>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<div class="content-grid">
    <div class="grid-full">
        <div class="card-panel">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Titolo</th>
                            <th class="text-center">N. ordine</th>
                            <th>Stato</th>
                            <th>Foto</th>
                            <th class="text-end">Azioni</th>
                        </tr>
                    </thead>
                    <tbody><?= $tabella ?></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Aggiungi Progetto -->
<div class="modal fade" id="modalProgetti" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow">
            <div class="modal-header bg-light border-0">
                <h6 class="modal-title fw-bold"><i class="bi bi-plus-circle-fill me-2"></i>Nuovo Progetto</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="index.php?page=progetti" enctype="multipart/form-data">
                <input type="hidden" name="page" value="progetti">
                <input type="hidden" name="azione" value="aggiungi">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Titolo <span class="text-danger">*</span></label>
                            <input type="text" name="titolo" class="form-control" required placeholder="Es: Orientamento">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">N. Ordine <span class="text-danger">*</span></label>
                            <input type="number" name="n_ordine" class="form-control" required min="1">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Descrizione</label>
                            <textarea name="descrizione" class="form-control" rows="4" placeholder="Descrivi il progetto…"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Foto</label>
                            <input type="file" class="form-control" name="foto" accept="image/*">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary px-4"><i class="bi bi-floppy me-1"></i>Salva</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Modifica Progetto -->
<div class="modal fade" id="modalModificaProgetto" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow">
            <div class="modal-header bg-light border-0">
                <h6 class="modal-title fw-bold"><i class="bi bi-pencil-fill me-2"></i>Modifica Progetto</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="index.php?page=progetti" enctype="multipart/form-data">
                <input type="hidden" name="page" value="progetti">
                <input type="hidden" name="azione" value="salva_modifica">
                <input type="hidden" name="idDaModificare" id="mod_idDaModificare">
                <input type="hidden" name="id_foto_corrente" id="mod_id_foto_corrente">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Titolo</label>
                            <input type="text" id="mod_titolo" name="titolo" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">N. Ordine</label>
                            <input type="number" id="mod_ordine" name="n_ordine" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Descrizione</label>
                            <textarea id="mod_descrizione" name="descrizione" class="form-control" rows="4" required></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Foto (lascia vuoto per mantenerla)</label>
                            <input type="file" class="form-control" name="foto" accept="image/*">
                            <div id="mod_badge_foto" class="form-text mt-1"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary px-4"><i class="bi bi-floppy me-1"></i>Salva modifiche</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('modalModificaProgetto')?.addEventListener('show.bs.modal', function(e) {
    const btn = e.relatedTarget;
    document.getElementById('mod_idDaModificare').value  = btn.dataset.id;
    document.getElementById('mod_titolo').value          = btn.dataset.titolo;
    document.getElementById('mod_descrizione').value     = btn.dataset.descrizione;
    document.getElementById('mod_ordine').value          = btn.dataset.ordine;
    document.getElementById('mod_id_foto_corrente').value= btn.dataset.idfoto;
    const badge = document.getElementById('mod_badge_foto');
    badge.innerHTML = btn.dataset.idfoto
        ? `<span class="badge bg-info-subtle text-info"><i class="bi bi-image me-1"></i>ID Foto attuale: ${btn.dataset.idfoto}</span>`
        : '';
});
</script>
