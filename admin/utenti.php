<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/layout.php';
require_once __DIR__ . '/gestione/gestione_utenti.php';
richiedi_admin();

$conn = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['elimina_id'])) {
    verifica_csrf();
    $idElimina = (int)$_POST['elimina_id'];
    if ($idElimina === (int)$_SESSION['uid']) {
        // Un admin non può eliminare il proprio account (si chiuderebbe fuori)
        imposta_flash('errore', 'Non puoi eliminare il tuo stesso account.');
    } else {
        $esito = eliminaUtente($conn, $idElimina);
        imposta_flash($esito['tipo'], $esito['msg']);
    }
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

<?php render_modal_elimina('utenti.php', 'elimina_id', "L'operazione non è reversibile."); ?>

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
                            <?php if ($id !== (int)$_SESSION['uid']): ?>
                            <button class="btn btn-outline-danger btn-sm"
                                    onclick="apriElimina(<?= $id ?>, <?= htmlspecialchars(json_encode($u['username']), ENT_QUOTES, 'UTF-8') ?>)">
                                <i class="bi bi-trash-fill"></i>
                            </button>
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

<?php chiudi_pagina(); ?>
