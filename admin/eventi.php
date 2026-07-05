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
    verifica_csrf();
    if (isset($_POST['elimina_id'])) {
        $idEv = (int)$_POST['elimina_id'];
        // SCOLASTICO può eliminare solo i propri eventi: il controllo è dentro eliminaEvento tramite cod_scuola
        $ev = leggiEvento($conn, $idEv);
        if (!$isAdmin && (!$ev || $ev['cod_scuola'] !== $codUtente)) {
            imposta_flash('errore', 'Non hai i permessi.');
        } else {
            $esito = eliminaEvento($conn, $idEv);
            imposta_flash($esito['tipo'], $esito['msg']);
        }
        $conn->close();
        header('Location: eventi.php');
        exit;
    }
    if (isset($_POST['ripristina_id'])) {
        richiedi_admin();
        $esito = ripristinaEvento($conn, (int)$_POST['ripristina_id']);
        imposta_flash($esito['tipo'], $esito['msg']);
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

<?php render_modal_elimina('eventi.php', 'elimina_id'); ?>

<div class="content-grid">
    <div class="grid-full">
        <div class="card-panel">

            <!-- Barra azioni -->
            <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                <?php if ($isAdmin) render_tab_attivi_eliminati('eventi.php', $tab); ?>
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
                                <?php if ($isAdmin) render_bottone_ripristina('eventi.php', $id); ?>
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
