<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/layout.php';
require_once __DIR__ . '/gestione/gestione_progetti.php';
richiedi_admin();

$conn = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['elimina_id'])) {
        imposta_flash(...array_values(eliminaProgetto($conn, (int)$_POST['elimina_id'])));
        $conn->close();
        header('Location: progetti.php');
        exit;
    }
    if (isset($_POST['ripristina_id'])) {
        imposta_flash(...array_values(ripristinaProgetto($conn, (int)$_POST['ripristina_id'])));
        $conn->close();
        header('Location: progetti.php?tab=eliminati');
        exit;
    }
}

$tab             = ($_GET['tab'] ?? '') === 'eliminati' ? 'eliminati' : 'attivi';
$progetti        = leggiProgetti($conn, $tab === 'eliminati');
$conn->close();
$flash = prendi_flash();

render_head_admin('Gestione Progetti');
render_sidebar_admin('progetti.php');
render_topbar_admin('Progetti');
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
                <p id="modal-msg" style="font-size:.9rem;"></p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Annulla</button>
                <form method="POST" action="progetti.php" style="display:inline">
                    <input type="hidden" name="elimina_id" id="modal-elimina-id" value="">
                    <button type="submit" class="btn btn-danger btn-sm">
                        <i class="bi bi-trash-fill me-1"></i>Elimina
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="content-grid">
    <div class="grid-full">
        <div class="card-panel">

            <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                <ul class="nav nav-pills nav-sm me-auto">
                    <li class="nav-item">
                        <a class="nav-link py-1 <?= $tab === 'attivi' ? 'active' : '' ?>"
                           href="progetti.php">Attivi</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link py-1 <?= $tab === 'eliminati' ? 'active' : '' ?>"
                           href="progetti.php?tab=eliminati">Eliminati</a>
                    </li>
                </ul>
                <?php if ($tab === 'attivi'): ?>
                <a href="progetto_form.php" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-lg me-1"></i>Aggiungi progetto
                </a>
                <?php endif; ?>
            </div>

            <?php if (empty($progetti)): ?>
            <div class="text-center text-secondary py-4">
                <i class="bi bi-folder-x fs-3 d-block mb-2"></i>Nessun progetto trovato.
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0" style="font-size:.88rem;">
                    <thead class="table-light">
                        <tr>
                            <th style="width:60px;">Ord.</th>
                            <th>Titolo</th>
                            <th>Foto</th>
                            <?php if ($tab === 'eliminati'): ?>
                            <th>Eliminato</th>
                            <?php endif; ?>
                            <th class="text-center" style="width:120px;">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($progetti as $p):
                        $id = (int)$p['ID_progetto'];
                    ?>
                    <tr <?= $tab === 'eliminati' ? 'class="text-muted"' : '' ?>>
                        <td><?= $p['n_ordine'] ?></td>
                        <td><?= htmlspecialchars($p['titolo']) ?></td>
                        <td>
                            <?php if ($p['path_foto']): ?>
                            <img src="../<?= htmlspecialchars($p['path_foto']) ?>"
                                 alt="" style="height:36px;object-fit:cover;border-radius:3px;">
                            <?php else: ?>
                            <span class="text-secondary" style="font-size:.8rem;">—</span>
                            <?php endif; ?>
                        </td>
                        <?php if ($tab === 'eliminati'): ?>
                        <td style="font-size:.8rem;"><?= $p['data_eliminazione'] ?? '' ?></td>
                        <?php endif; ?>
                        <td class="text-center">
                            <?php if ($tab === 'attivi'): ?>
                            <a href="progetto_form.php?id=<?= $id ?>"
                               class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-pencil-fill"></i>
                            </a>
                            <button class="btn btn-outline-danger btn-sm"
                                    onclick="apriElimina(<?= $id ?>, <?= htmlspecialchars(json_encode($p['titolo']), ENT_QUOTES, 'UTF-8') ?>)">
                                <i class="bi bi-trash-fill"></i>
                            </button>
                            <?php else: ?>
                            <form method="POST" action="progetti.php" style="display:inline">
                                <input type="hidden" name="ripristina_id" value="<?= $id ?>">
                                <button type="submit" class="btn btn-outline-success btn-sm" title="Ripristina">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                </button>
                            </form>
                            <?php endif; ?>
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
function apriElimina(id, titolo) {
    document.getElementById('modal-msg').textContent = 'Eliminare il progetto "' + titolo + '"?';
    document.getElementById('modal-elimina-id').value = id;
    new bootstrap.Modal(document.getElementById('modalElimina')).show();
}
</script>

<?php chiudi_pagina(); ?>
