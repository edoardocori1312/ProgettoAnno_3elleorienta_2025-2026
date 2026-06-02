<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/layout.php';
require_once __DIR__ . '/gestione/gestione_zone.php';
richiedi_admin();

$conn = db();

// PRG: gestisci POST e redirect
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifica_csrf();
    if (isset($_POST['inserisci'])) {
        $esito = creaZona($conn, $_POST['nome'] ?? '');
        imposta_flash($esito['tipo'], $esito['msg']);
        $conn->close();
        header('Location: zone.php');
        exit;
    }
    if (isset($_POST['modifica'])) {
        $esito = aggiornaZona($conn, (int)($_POST['id'] ?? 0), $_POST['nome'] ?? '');
        imposta_flash($esito['tipo'], $esito['msg']);
        $conn->close();
        header('Location: zone.php');
        exit;
    }
    if (isset($_POST['id_zona'])) {
        $esito = eliminaZona($conn, (int)$_POST['id_zona']);
        imposta_flash($esito['tipo'], $esito['msg']);
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

<?php render_modal_elimina('zone.php', 'id_zona', "L'operazione non è reversibile se non ci sono città associate."); ?>

<!-- Modale aggiungi zona -->
<div class="modal fade" id="modalAggiungi" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title"><i class="bi bi-geo-fill me-2"></i>Aggiungi zona</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="zone.php">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <div class="modal-body pt-2">
                    <label for="nome" class="form-label">Nome zona</label>
                    <input type="text" id="nome" name="nome" class="form-control" required minlength="3">
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

<!-- Modale modifica zona -->
<div class="modal fade" id="modalModifica" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title"><i class="bi bi-pencil-fill me-2"></i>Modifica zona</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="zone.php">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="id" id="modal-modifica-id" value="">
                <div class="modal-body pt-2">
                    <label for="modal-modifica-nome" class="form-label">Nome zona</label>
                    <input type="text" id="modal-modifica-nome" name="nome" class="form-control" required minlength="3">
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" name="modifica" class="btn btn-primary btn-sm">
                        <i class="bi bi-check-lg me-1"></i>Salva
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
                    ?>
                        <tr>
                            <td><?= $id ?></td>
                            <td><?= $nome ?></td>
                            <td class="text-center">
                                <button class="btn btn-outline-primary btn-sm"
                                        onclick="apriModaleModifica(<?= $id ?>, <?= htmlspecialchars(json_encode($row['nome']), ENT_QUOTES, 'UTF-8') ?>)">
                                    <i class="bi bi-pencil-fill"></i>
                                </button>
                                <button class="btn btn-outline-danger btn-sm"
                                        onclick="apriElimina(<?= $id ?>, <?= htmlspecialchars(json_encode($row['nome']), ENT_QUOTES, 'UTF-8') ?>)">
                                    <i class="bi bi-trash-fill"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function apriModaleModifica(id, nome) {
    document.getElementById('modal-modifica-id').value = id;
    document.getElementById('modal-modifica-nome').value = nome;
    new bootstrap.Modal(document.getElementById('modalModifica')).show();
}
</script>

<?php chiudi_pagina(); ?>
