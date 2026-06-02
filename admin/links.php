<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/layout.php';
require_once __DIR__ . '/gestione/gestione_links.php';
richiedi_admin();

$conn = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifica_csrf();
    if (isset($_POST['elimina_id'])) {
        $esito = eliminaLink($conn, (int)$_POST['elimina_id']);
        imposta_flash($esito['tipo'], $esito['msg']);
        $conn->close();
        header('Location: links.php');
        exit;
    }
    if (isset($_POST['ripristina_id'])) {
        $esito = ripristinaLink($conn, (int)$_POST['ripristina_id']);
        imposta_flash($esito['tipo'], $esito['msg']);
        $conn->close();
        header('Location: links.php?tab=eliminati');
        exit;
    }
}

$tab   = ($_GET['tab'] ?? '') === 'eliminati' ? 'eliminati' : 'attivi';
$links = leggiLinks($conn, $tab === 'eliminati');
$conn->close();
$flash = prendi_flash();

render_head_admin('Gestione Link Utili');
render_sidebar_admin('links.php');
render_topbar_admin('Link Utili');
?>

<?php render_flash($flash); ?>

<?php render_modal_elimina('links.php', 'elimina_id'); ?>

<div class="content-grid">
    <div class="grid-full">
        <div class="card-panel">

            <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                <ul class="nav nav-pills nav-sm me-auto">
                    <li class="nav-item">
                        <a class="nav-link py-1 <?= $tab === 'attivi' ? 'active' : '' ?>"
                           href="links.php">Attivi</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link py-1 <?= $tab === 'eliminati' ? 'active' : '' ?>"
                           href="links.php?tab=eliminati">Eliminati</a>
                    </li>
                </ul>
                <?php if ($tab === 'attivi'): ?>
                <a href="link_form.php" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-lg me-1"></i>Aggiungi link
                </a>
                <?php endif; ?>
            </div>

            <?php if (empty($links)): ?>
            <div class="text-center text-secondary py-4">
                <i class="bi bi-link-45deg fs-3 d-block mb-2"></i>Nessun link trovato.
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0" style="font-size:.88rem;">
                    <thead class="table-light">
                        <tr>
                            <th style="width:60px;">Ord.</th>
                            <th>Titolo</th>
                            <th>Indirizzo</th>
                            <?php if ($tab === 'eliminati'): ?>
                            <th>Eliminato</th>
                            <?php endif; ?>
                            <th class="text-center" style="width:120px;">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($links as $l):
                        $id = (int)$l['ID_link'];
                    ?>
                    <tr <?= $tab === 'eliminati' ? 'class="text-muted"' : '' ?>>
                        <td><?= $l['n_ordine'] ?></td>
                        <td><?= htmlspecialchars($l['titolo']) ?></td>
                        <td>
                            <?php if (url_http_valido($l['indirizzo'])): ?>
                            <a href="<?= htmlspecialchars($l['indirizzo']) ?>" target="_blank" rel="noopener"
                               class="text-decoration-none" style="font-size:.82rem;">
                                <i class="bi bi-box-arrow-up-right me-1"></i><?= htmlspecialchars($l['indirizzo']) ?>
                            </a>
                            <?php else: ?>
                            <span class="text-muted" style="font-size:.82rem;"><?= htmlspecialchars($l['indirizzo']) ?></span>
                            <?php endif; ?>
                        </td>
                        <?php if ($tab === 'eliminati'): ?>
                        <td style="font-size:.8rem;"><?= $l['data_eliminazione'] ? date('d/m/Y', strtotime($l['data_eliminazione'])) : '' ?></td>
                        <?php endif; ?>
                        <td class="text-center">
                            <?php if ($tab === 'attivi'): ?>
                            <a href="link_form.php?id=<?= $id ?>"
                               class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-pencil-fill"></i>
                            </a>
                            <button class="btn btn-outline-danger btn-sm"
                                    onclick="apriElimina(<?= $id ?>, <?= htmlspecialchars(json_encode($l['titolo']), ENT_QUOTES, 'UTF-8') ?>)">
                                <i class="bi bi-trash-fill"></i>
                            </button>
                            <?php else: ?>
                            <form method="POST" action="links.php" style="display:inline">
                                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
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

<?php chiudi_pagina(); ?>
