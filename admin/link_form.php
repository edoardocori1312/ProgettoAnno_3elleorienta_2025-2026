<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/layout.php';
require_once __DIR__ . '/gestione/gestione_links.php';
richiedi_admin();

$conn = db();

$idModifica   = (int)($_GET['id'] ?? 0);
$modoModifica = $idModifica > 0;

if ($modoModifica) {
    $link = leggiLink($conn, $idModifica);
    if (!$link) {
        imposta_flash('errore', 'Link non trovato.');
        $conn->close();
        header('Location: links.php');
        exit;
    }
} else {
    $link = null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $azione = $_POST['azione'] ?? '';
    $file   = $_FILES['foto'] ?? ['error' => UPLOAD_ERR_NO_FILE, 'size' => 0];

    if ($azione === 'inserisci') {
        $esito = creaLink($conn, $_POST, $file);
    } elseif ($azione === 'aggiorna') {
        $esito = aggiornaLink($conn, (int)($_POST['id_link'] ?? 0), $_POST, $file);
    } else {
        $esito = ['tipo' => 'errore', 'msg' => 'Azione non valida.'];
    }

    imposta_flash($esito['tipo'], $esito['msg']);
    $conn->close();
    if ($esito['tipo'] === 'errore') {
        header('Location: link_form.php' . ($modoModifica ? '?id=' . $idModifica : ''));
    } else {
        header('Location: links.php');
    }
    exit;
}

$prossimoOrdine = $modoModifica ? null : prossimoOrdineLinks($conn);
$conn->close();

$titolo = $modoModifica ? 'Modifica link' : 'Aggiungi link';
render_head_admin($titolo);
render_sidebar_admin('links.php');
render_topbar_admin($titolo);
?>

<div class="content-grid">
    <div class="grid-full">
        <div class="card-panel">

            <form method="POST" action="link_form.php" enctype="multipart/form-data">
                <input type="hidden" name="azione" value="<?= $modoModifica ? 'aggiorna' : 'inserisci' ?>">
                <?php if ($modoModifica): ?>
                <input type="hidden" name="id_link" value="<?= $idModifica ?>">
                <?php endif; ?>

                <div class="row g-3">

                    <div class="col-md-7">
                        <label class="form-label">Titolo *</label>
                        <input type="text" name="titolo" class="form-control" required maxlength="50"
                               value="<?= htmlspecialchars($link['titolo'] ?? '') ?>">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">N° ordine *</label>
                        <input type="number" name="n_ordine" class="form-control" required min="1"
                               value="<?= htmlspecialchars($link['n_ordine'] ?? $prossimoOrdine) ?>">
                    </div>

                    <div class="col-md-9">
                        <label class="form-label">URL *</label>
                        <input type="url" name="indirizzo" class="form-control" required maxlength="500"
                               placeholder="https://"
                               value="<?= htmlspecialchars($link['indirizzo'] ?? '') ?>">
                    </div>

                    <div class="col-12">
                        <label class="form-label">Descrizione *</label>
                        <textarea name="descrizione" class="form-control" rows="4" required
                        ><?= htmlspecialchars($link['descrizione'] ?? '') ?></textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">
                            Foto <?= $modoModifica ? '(lascia vuoto per mantenere l\'attuale)' : '' ?>
                        </label>
                        <input type="file" name="foto" class="form-control" accept="image/jpeg,image/png">
                        <?php if ($modoModifica && ($link['path_foto'] ?? '')): ?>
                        <div class="mt-2">
                            <img src="../<?= htmlspecialchars($link['path_foto']) ?>"
                                 alt="Foto attuale" style="height:80px;object-fit:cover;border-radius:4px;">
                        </div>
                        <?php endif; ?>
                    </div>

                </div>

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-<?= $modoModifica ? 'check-lg' : 'plus-lg' ?> me-1"></i>
                        <?= $modoModifica ? 'Aggiorna' : 'Aggiungi link' ?>
                    </button>
                    <a href="links.php" class="btn btn-outline-secondary">Annulla</a>
                </div>

            </form>

        </div>
    </div>
</div>

<?php chiudi_pagina(); ?>
