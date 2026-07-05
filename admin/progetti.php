<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/layout.php';
require_once __DIR__ . '/gestione/gestione_progetti.php';
richiedi_admin();

$conn = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifica_csrf();
    if (isset($_POST['elimina_id'])) {
        $esito = eliminaProgetto($conn, (int)$_POST['elimina_id']);
        imposta_flash($esito['tipo'], $esito['msg']);
        $conn->close();
        header('Location: progetti.php');
        exit;
    }
    if (isset($_POST['ripristina_id'])) {
        $esito = ripristinaProgetto($conn, (int)$_POST['ripristina_id']);
        imposta_flash($esito['tipo'], $esito['msg']);
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

<?php render_modal_elimina('progetti.php', 'elimina_id'); ?>

<div class="content-grid">
    <div class="grid-full">
        <div class="card-panel">

            <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                <?php render_tab_attivi_eliminati('progetti.php', $tab); ?>
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
                        <td style="font-size:.8rem;"><?= $p['data_eliminazione'] ? date('d/m/Y', strtotime($p['data_eliminazione'])) : '' ?></td>
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
                            <?php else: render_bottone_ripristina('progetti.php', $id); endif; ?>
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
