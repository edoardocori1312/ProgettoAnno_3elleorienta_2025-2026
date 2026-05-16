<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/layout.php';
require_once __DIR__ . '/gestione/gestione_eventi.php';
richiedi_login();

$conn      = db();
$isAdmin   = is_admin();
$codUtente = utente_cod_scuola();

// PRG: elimina / ripristina (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['elimina_id'])) {
        $idEv = (int)$_POST['elimina_id'];
        // SCOLASTICO può eliminare solo i propri eventi: il controllo è dentro eliminaEvento tramite cod_scuola
        $ev = leggiEvento($conn, $idEv);
        if (!$isAdmin && (!$ev || $ev['cod_scuola'] !== $codUtente)) {
            imposta_flash('errore', 'Non hai i permessi.');
        } else {
            imposta_flash(...array_values(eliminaEvento($conn, $idEv)));
        }
        $conn->close();
        header('Location: eventi.php');
        exit;
    }
    if (isset($_POST['ripristina_id'])) {
        richiedi_admin();
        imposta_flash(...array_values(ripristinaEvento($conn, (int)$_POST['ripristina_id'])));
        $conn->close();
        header('Location: eventi.php?tab=eliminati');
        exit;
    }
}

$tab             = ($_GET['tab'] ?? '') === 'eliminati' && $isAdmin ? 'eliminati' : 'attivi';
$includiEliminati = $tab === 'eliminati';
$eventi          = leggiEventi($conn, $isAdmin, $codUtente, $includiEliminati);
$conn->close();
$flash = prendi_flash();

render_head_admin('Gestione Eventi');
render_sidebar_admin('eventi.php');
render_topbar_admin('Eventi');
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
                <form method="POST" action="eventi.php" style="display:inline">
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

            <!-- Barra azioni -->
            <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                <?php if ($isAdmin): ?>
                <ul class="nav nav-pills nav-sm me-auto">
                    <li class="nav-item">
                        <a class="nav-link py-1 <?= $tab === 'attivi' ? 'active' : '' ?>"
                           href="eventi.php">Attivi</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link py-1 <?= $tab === 'eliminati' ? 'active' : '' ?>"
                           href="eventi.php?tab=eliminati">Eliminati</a>
                    </li>
                </ul>
                <?php endif; ?>
                <a href="evento_form.php" class="btn btn-primary btn-sm ms-auto">
                    <i class="bi bi-plus-lg me-1"></i>Aggiungi evento
                </a>
            </div>

            <?php if (empty($eventi)): ?>
            <div class="text-center text-secondary py-4">
                <i class="bi bi-calendar-x fs-3 d-block mb-2"></i>Nessun evento trovato.
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0" style="font-size:.88rem;">
                    <thead class="table-light">
                        <tr>
                            <th>Titolo</th>
                            <th>Target</th>
                            <th>Scuola / Luogo</th>
                            <th>Inizio</th>
                            <th>Fine</th>
                            <th class="text-center">Vis.</th>
                            <th class="text-center">Pren.</th>
                            <?php if ($tab === 'eliminati'): ?>
                            <th>Eliminato</th>
                            <?php endif; ?>
                            <th class="text-center" style="width:120px;">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($eventi as $ev):
                        $id    = (int)$ev['ID_evento'];
                        $titolo = htmlspecialchars($ev['titolo']);
                        $puoModificare = $isAdmin || ($codUtente === $ev['cod_scuola']);
                        $puoEliminare  = $puoModificare;
                        $luogo = $ev['target'] === 'SCOLASTICO'
                            ? htmlspecialchars($ev['nome_scuola'] ?? '—')
                            : htmlspecialchars($ev['nome_citta']  ?? '—');
                        $badge = $ev['target'] === 'SCOLASTICO'
                            ? '<span class="badge bg-info text-dark">Scolastico</span>'
                            : '<span class="badge bg-warning text-dark">Territoriale</span>';
                        $dataInizio = $ev['ora_inizio'] ? date('d/m/Y H:i', strtotime($ev['ora_inizio'])) : '—';
                        $dataFine   = $ev['ora_fine']   ? date('d/m/Y H:i', strtotime($ev['ora_fine']))   : '—';
                    ?>
                    <tr <?= $tab === 'eliminati' ? 'class="text-muted"' : '' ?>>
                        <td><?= $titolo ?></td>
                        <td><?= $badge ?></td>
                        <td><?= $luogo ?></td>
                        <td><?= $dataInizio ?></td>
                        <td><?= $dataFine ?></td>
                        <td class="text-center">
                            <?= $ev['visibile']   ? '<i class="bi bi-eye text-success"></i>' : '<i class="bi bi-eye-slash text-secondary"></i>' ?>
                        </td>
                        <td class="text-center">
                            <?= $ev['prenotabile'] ? '<i class="bi bi-check-lg text-success"></i>' : '<i class="bi bi-x-lg text-secondary"></i>' ?>
                        </td>
                        <?php if ($tab === 'eliminati'): ?>
                        <td style="font-size:.8rem;"><?= $ev['data_eliminazione'] ? date('d/m/Y', strtotime($ev['data_eliminazione'])) : '' ?></td>
                        <?php endif; ?>
                        <td class="text-center">
                            <?php if ($tab === 'attivi'): ?>
                                <?php if ($puoModificare): ?>
                                <a href="evento_form.php?id=<?= $id ?>"
                                   class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-pencil-fill"></i>
                                </a>
                                <?php endif; ?>
                                <?php if ($puoEliminare): ?>
                                <button class="btn btn-outline-danger btn-sm"
                                        onclick="apriElimina(<?= $id ?>, <?= htmlspecialchars(json_encode($ev['titolo']), ENT_QUOTES, 'UTF-8') ?>)">
                                    <i class="bi bi-trash-fill"></i>
                                </button>
                                <?php endif; ?>
                            <?php else: ?>
                                <?php if ($isAdmin): ?>
                                <form method="POST" action="eventi.php" style="display:inline">
                                    <input type="hidden" name="ripristina_id" value="<?= $id ?>">
                                    <button type="submit" class="btn btn-outline-success btn-sm"
                                            title="Ripristina">
                                        <i class="bi bi-arrow-counterclockwise"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
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
    document.getElementById('modal-msg').textContent = 'Eliminare l\'evento "' + titolo + '"?';
    document.getElementById('modal-elimina-id').value = id;
    new bootstrap.Modal(document.getElementById('modalElimina')).show();
}
</script>

<?php chiudi_pagina(); ?>
