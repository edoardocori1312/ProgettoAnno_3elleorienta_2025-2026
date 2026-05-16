<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/layout.php';
require_once __DIR__ . '/gestione/gestione_utenti.php';
richiedi_admin();

$conn = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['elimina_id'])) {
    imposta_flash(...array_values(eliminaUtente($conn, (int)$_POST['elimina_id'])));
    $conn->close();
    header('Location: utenti.php');
    exit;
}

$utenti = leggiUtenti($conn);
$conn->close();
$flash = prendi_flash();

render_head_admin('Gestione Utenti');
render_sidebar_admin('utenti.php');
render_topbar_admin('Utenti');
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
                <form method="POST" action="utenti.php" style="display:inline">
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

            <div class="d-flex justify-content-end mb-3">
                <a href="utente_form.php" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-lg me-1"></i>Aggiungi utente
                </a>
            </div>

            <?php if (empty($utenti)): ?>
            <div class="text-center text-secondary py-4">
                <i class="bi bi-people fs-3 d-block mb-2"></i>Nessun utente trovato.
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0" style="font-size:.88rem;">
                    <thead class="table-light">
                        <tr>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Tipo</th>
                            <th>Stato</th>
                            <th>Scuola</th>
                            <th class="text-center" style="width:120px;">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($utenti as $u):
                        $id = (int)$u['ID_utente'];
                        $tipoBadge = $u['tipo'] === 'ADMIN'
                            ? '<span class="badge bg-danger">ADMIN</span>'
                            : '<span class="badge bg-secondary">SCOLASTICO</span>';
                        $statoBadge = $u['stato'] === 'ATTIVO'
                            ? '<span class="badge bg-success">ATTIVO</span>'
                            : '<span class="badge bg-warning text-dark">BLOCCATO</span>';
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($u['username']) ?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td><?= $tipoBadge ?></td>
                        <td><?= $statoBadge ?></td>
                        <td><?= htmlspecialchars($u['nome_scuola'] ?? '—') ?></td>
                        <td class="text-center">
                            <a href="utente_form.php?id=<?= $id ?>"
                               class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-pencil-fill"></i>
                            </a>
                            <button class="btn btn-outline-danger btn-sm"
                                    onclick="apriElimina(<?= $id ?>, <?= htmlspecialchars(json_encode($u['username']), ENT_QUOTES, 'UTF-8') ?>)">
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
function apriElimina(id, username) {
    document.getElementById('modal-msg').textContent = 'Eliminare l\'utente "' + username + '"? L\'operazione non è reversibile.';
    document.getElementById('modal-elimina-id').value = id;
    new bootstrap.Modal(document.getElementById('modalElimina')).show();
}
</script>

<?php chiudi_pagina(); ?>
