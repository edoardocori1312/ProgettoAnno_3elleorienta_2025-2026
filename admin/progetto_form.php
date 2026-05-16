<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/layout.php';
require_once __DIR__ . '/gestione/gestione_progetti.php';
richiedi_admin();

$conn = db();

$idModifica   = (int)($_GET['id'] ?? 0);
$modoModifica = $idModifica > 0;

if ($modoModifica) {
    $progetto = leggiProgetto($conn, $idModifica);
    if (!$progetto) {
        imposta_flash('errore', 'Progetto non trovato.');
        $conn->close();
        header('Location: progetti.php');
        exit;
    }
} else {
    $progetto = null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $azione = $_POST['azione'] ?? '';
    $file   = $_FILES['foto'] ?? ['error' => UPLOAD_ERR_NO_FILE, 'size' => 0];

    if ($azione === 'inserisci') {
        $esito = creaProgetto($conn, $_POST, $file);
    } elseif ($azione === 'aggiorna') {
        $esito = aggiornaProgetto($conn, (int)($_POST['id_progetto'] ?? 0), $_POST, $file);
    } else {
        $esito = ['tipo' => 'errore', 'msg' => 'Azione non valida.'];
    }

    imposta_flash($esito['tipo'], $esito['msg']);
    $conn->close();
    if ($esito['tipo'] === 'errore') {
        header('Location: progetto_form.php' . ($modoModifica ? '?id=' . $idModifica : ''));
    } else {
        header('Location: progetti.php');
    }
    exit;
}

$prossimoOrdine = $modoModifica ? null : prossimoOrdineProgetti($conn);
$conn->close();

$titolo = $modoModifica ? 'Modifica progetto' : 'Aggiungi progetto';
render_head_admin($titolo);
render_sidebar_admin('progetti.php');
render_topbar_admin($titolo);
?>

<div class="content-grid">
    <div class="grid-full">
        <div class="card-panel">

            <form method="POST" action="progetto_form.php" enctype="multipart/form-data">
                <input type="hidden" name="azione" value="<?= $modoModifica ? 'aggiorna' : 'inserisci' ?>">
                <?php if ($modoModifica): ?>
                <input type="hidden" name="id_progetto" value="<?= $idModifica ?>">
                <?php endif; ?>

                <div class="row g-3">

                    <div class="col-md-9">
                        <label class="form-label">Titolo *</label>
                        <input type="text" name="titolo" class="form-control" required maxlength="50"
                               value="<?= htmlspecialchars($progetto['titolo'] ?? '') ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">N° ordine *</label>
                        <input type="number" name="n_ordine" class="form-control" required min="1"
                               value="<?= htmlspecialchars($progetto['n_ordine'] ?? $prossimoOrdine) ?>">
                    </div>

                    <div class="col-12">
                        <label class="form-label">Descrizione *</label>
                        <textarea name="descrizione" class="form-control" rows="5" required
                        ><?= htmlspecialchars($progetto['descrizione'] ?? '') ?></textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">
                            Foto <?= $modoModifica ? '(lascia vuoto per mantenere l\'attuale)' : '' ?>
                        </label>
                        <input type="file" name="foto" class="form-control" accept="image/jpeg,image/png">
                        <?php if ($modoModifica && ($progetto['path_foto'] ?? '')): ?>
                        <div class="mt-2">
                            <img src="../<?= htmlspecialchars($progetto['path_foto']) ?>"
                                 alt="Foto attuale" style="height:80px;object-fit:cover;border-radius:4px;">
                        </div>
                        <?php endif; ?>
                    </div>

                </div>

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-<?= $modoModifica ? 'check-lg' : 'plus-lg' ?> me-1"></i>
                        <?= $modoModifica ? 'Aggiorna' : 'Aggiungi progetto' ?>
                    </button>
                    <a href="progetti.php" class="btn btn-outline-secondary">Annulla</a>
                </div>

            </form>

        </div>
    </div>
</div>

<?php chiudi_pagina(); ?>
