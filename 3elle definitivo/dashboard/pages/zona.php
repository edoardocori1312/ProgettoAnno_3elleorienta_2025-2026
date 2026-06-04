<?php
// La sessione è già aperta da index.php tramite controlloSessione.php
// $conn è disponibile da index.php

require_once __DIR__ . '/backend/gestione_zone.php';

// ── Flash message ─────────────────────────────────────────────────────────────
$flash = null;
if (isset($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
}

// ── Azioni POST ───────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['inserisci'])) {
        $_SESSION['flash'] = creaZona($conn, $_POST['zona'] ?? '');
        echo "<script>window.location.href = 'index.php?page=zona';</script>";
        exit();

    } elseif (isset($_POST['modifica'])) {
        $_SESSION['flash'] = aggiornaZona($conn, (int)($_POST['id'] ?? 0), $_POST['nome'] ?? '');
        echo "<script>window.location.href = 'index.php?page=zona';</script>";
        exit();

    } elseif (isset($_POST['id_zona'])) {
        $_SESSION['flash'] = rimuoviZona($conn, (int)$_POST['id_zona']);
        echo "<script>window.location.href = 'index.php?page=zona';</script>";
        exit();
    }
}

$zone = leggiZone($conn);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-semibold mb-0">
        <i class="bi bi-geo-fill me-2 text-secondary"></i>Zone
    </h5>
    <button class="btn btn-primary d-flex align-items-center gap-2"
            data-bs-toggle="modal" data-bs-target="#modalAggiungiZona">
        <i class="bi bi-plus-lg"></i> Aggiungi zona
    </button>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= $flash['tipo'] === 'successo' ? 'success' : 'danger' ?> alert-dismissible fade show mb-4" role="alert">
    <i class="bi bi-<?= $flash['tipo'] === 'successo' ? 'check-circle-fill' : 'exclamation-triangle-fill' ?> me-2"></i>
    <?= htmlspecialchars($flash['msg']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="content-grid">
    <div class="grid-full">
        <div class="card-panel">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:70px;">ID</th>
                            <th>Nome</th>
                            <th class="text-center" style="width:160px;">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($zone)): ?>
                        <tr>
                            <td colspan="3" class="text-center text-secondary py-4">
                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                Nessuna zona trovata.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($zone as $row):
                            $id     = (int)$row['ID_zona'];
                            $nome   = htmlspecialchars($row['nome']);
                            $nomeJs = addslashes($row['nome']);
                        ?>
                            <tr>
                                <td><?= $id ?></td>
                                <td class="fw-semibold"><?= $nome ?></td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <button class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1"
                                                onclick="apriModalModifica(<?= $id ?>, '<?= $nomeJs ?>')">
                                            <i class="bi bi-pencil-fill"></i> Modifica
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1"
                                                onclick="apriModalElimina(<?= $id ?>, '<?= $nomeJs ?>')">
                                            <i class="bi bi-trash-fill"></i> Elimina
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Aggiungi Zona -->
<div class="modal fade" id="modalAggiungiZona" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow">
            <div class="modal-header bg-light border-0">
                <h6 class="modal-title fw-bold">
                    <i class="bi bi-plus-circle-fill me-2"></i>Nuova Zona
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="index.php?page=zona">
                <input type="hidden" name="page" value="zona">
                <div class="modal-body">
                    <label class="form-label">Nome zona <span class="text-danger">*</span></label>
                    <input type="text" name="zona" class="form-control" required
                           placeholder="Es: Nord, Sud, Centro…">
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" name="inserisci" class="btn btn-success px-4">
                        <i class="bi bi-floppy-fill me-1"></i>Salva
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Modifica Zona -->
<div class="modal fade" id="modalModificaZona" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow">
            <div class="modal-header bg-light border-0">
                <h6 class="modal-title fw-bold">
                    <i class="bi bi-pencil-fill me-2"></i>Modifica Zona
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="index.php?page=zona">
                <input type="hidden" name="page" value="zona">
                <input type="hidden" name="modifica" value="1">
                <input type="hidden" name="id" id="modal-modifica-id" value="">
                <div class="modal-body">
                    <label class="form-label">Nome zona <span class="text-danger">*</span></label>
                    <input type="text" name="nome" id="modal-modifica-nome" class="form-control" required>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-floppy-fill me-1"></i>Salva
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Conferma Eliminazione -->
<div class="modal fade" id="modalElimina" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow">
            <div class="modal-header bg-light border-0">
                <h6 class="modal-title text-danger fw-bold">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Conferma eliminazione
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="modal-elimina-msg" class="mb-1"></p>
                <p class="text-danger mb-0" style="font-size:.85rem;">
                    <i class="bi bi-info-circle me-1"></i>L'operazione non è reversibile.
                </p>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Annulla</button>
                <form id="form-elimina" method="POST" action="index.php?page=zona" style="display:inline;">
                    <input type="hidden" name="page" value="zona">
                    <input type="hidden" name="id_zona" id="modal-id-zona" value="">
                    <button type="submit" class="btn btn-danger btn-sm">
                        <i class="bi bi-trash-fill me-1"></i>Elimina
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function apriModalModifica(id, nome) {
    document.getElementById('modal-modifica-id').value = id;
    document.getElementById('modal-modifica-nome').value = nome;
    new bootstrap.Modal(document.getElementById('modalModificaZona')).show();
}

function apriModalElimina(id, nome) {
    document.getElementById('modal-elimina-msg').textContent = 'Eliminare la zona "' + nome + '"?';
    document.getElementById('modal-id-zona').value = id;
    new bootstrap.Modal(document.getElementById('modalElimina')).show();
}
</script>
