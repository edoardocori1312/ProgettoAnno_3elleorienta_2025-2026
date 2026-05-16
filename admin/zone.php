<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/layout.php';
require_once __DIR__ . '/gestione/gestione_zone.php';
richiedi_admin();

$conn = db();

// PRG: gestisci POST e redirect
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['inserisci'])) {
        imposta_flash(...array_values(creaZona($conn, $_POST['zona'] ?? '')));
        $conn->close();
        header('Location: zone.php');
        exit;
    }
    if (isset($_POST['modifica'])) {
        $esito = aggiornaZona($conn, (int)($_POST['id'] ?? 0), $_POST['nome'] ?? '');
        imposta_flash($esito['tipo'], $esito['msg']);
        $conn->close();
        $dest = $esito['tipo'] === 'errore' ? 'zone.php?modifica=' . (int)$_POST['id'] : 'zone.php';
        header('Location: ' . $dest);
        exit;
    }
    if (isset($_POST['id_zona'])) {
        imposta_flash(...array_values(rimuoviZona($conn, (int)$_POST['id_zona'])));
        $conn->close();
        header('Location: zone.php');
        exit;
    }
}

$zone  = leggiZone($conn);
$conn->close();
$flash = prendi_flash();

render_head_admin('Gestione Zone');
render_sidebar_admin('zone.php');
render_topbar_admin('Zone');
?>

<?php render_flash($flash); ?>

<!-- Modale conferma eliminazione -->
<div class="modal fade" id="modalElimina" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title text-danger">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Conferma eliminazione
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-2">
                <p id="modal-elimina-msg" class="mb-1" style="font-size:.9rem;"></p>
                <p class="text-danger mb-0" style="font-size:.82rem;">
                    <i class="bi bi-info-circle me-1"></i>
                    L'operazione non è reversibile se non ci sono città associate.
                </p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Annulla</button>
                <form id="form-elimina" method="POST" action="zone.php" style="display:inline">
                    <input type="hidden" name="id_zona" id="modal-id-zona" value="">
                    <button type="submit" class="btn btn-danger btn-sm">
                        <i class="bi bi-trash-fill me-1"></i>Elimina
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modale aggiungi zona -->
<div class="modal fade" id="modalAggiungi" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title"><i class="bi bi-geo-fill me-2"></i>Aggiungi zona</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="zone.php">
                <div class="modal-body pt-2">
                    <label for="zona" class="form-label">Nome zona</label>
                    <input type="text" id="zona" name="zona" class="form-control" required minlength="3">
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" name="inserisci" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-lg me-1"></i>Aggiungi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="content-grid">
    <div class="grid-full">
        <div class="card-panel">
            <p class="section-title">
                <i class="bi bi-geo-fill me-1"></i> Lista zone
                <span class="float-end fw-normal" style="text-transform:none;letter-spacing:0;">
                    <button class="btn btn-primary btn-sm"
                            data-bs-toggle="modal" data-bs-target="#modalAggiungi">
                        <i class="bi bi-plus-lg me-1"></i> Aggiungi zona
                    </button>
                </span>
            </p>

            <?php if (empty($zone)): ?>
            <div class="text-center text-secondary py-4">
                <i class="bi bi-inbox fs-3 d-block mb-2"></i>Nessuna zona trovata.
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-zone mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:70px;">ID</th>
                            <th>Nome</th>
                            <th class="text-center" style="width:140px;">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($zone as $row):
                        $id     = (int)$row['ID_zona'];
                        $nome   = htmlspecialchars($row['nome']);
                                        $mostraModifica = isset($_GET['modifica']) && (int)$_GET['modifica'] === $id;
                    ?>
                    <?php if ($mostraModifica): ?>
                        <tr class="table-warning">
                            <td><?= $id ?></td>
                            <td>
                                <form method="POST" action="zone.php" class="d-flex gap-2 align-items-center">
                                    <input type="hidden" name="id" value="<?= $id ?>">
                                    <input type="text" name="nome" class="form-control form-control-sm"
                                           value="<?= $nome ?>" required minlength="3" style="max-width:240px;">
                                    <button type="submit" name="modifica" class="btn btn-success btn-sm">
                                        <i class="bi bi-check-lg"></i>
                                    </button>
                                    <a href="zone.php" class="btn btn-outline-secondary btn-sm">
                                        <i class="bi bi-x-lg"></i>
                                    </a>
                                </form>
                            </td>
                            <td></td>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <td><?= $id ?></td>
                            <td><?= $nome ?></td>
                            <td class="text-center">
                                <a href="zone.php?modifica=<?= $id ?>" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-pencil-fill"></i>
                                </a>
                                <button class="btn btn-outline-danger btn-sm"
                                        onclick="apriModaleElimina(<?= $id ?>, <?= htmlspecialchars(json_encode($row['nome']), ENT_QUOTES, 'UTF-8') ?>)">
                                    <i class="bi bi-trash-fill"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function apriModaleElimina(id, nome) {
    document.getElementById('modal-elimina-msg').textContent = 'Eliminare la zona "' + nome + '"?';
    document.getElementById('modal-id-zona').value = id;
    new bootstrap.Modal(document.getElementById('modalElimina')).show();
}
</script>

<?php chiudi_pagina(); ?>
