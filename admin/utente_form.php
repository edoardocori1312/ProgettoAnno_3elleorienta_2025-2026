<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/layout.php';
require_once __DIR__ . '/gestione/gestione_utenti.php';
richiedi_admin();

$conn = db();

$idModifica   = (int)($_GET['id'] ?? 0);
$modoModifica = $idModifica > 0;

if ($modoModifica) {
    $utente = leggiUtente($conn, $idModifica);
    if (!$utente) {
        imposta_flash('errore', 'Utente non trovato.');
        $conn->close();
        header('Location: utenti.php');
        exit;
    }
} else {
    $utente = null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $azione = $_POST['azione'] ?? '';
    if ($azione === 'inserisci') {
        $esito = creaUtente($conn, $_POST);
    } elseif ($azione === 'aggiorna') {
        $esito = aggiornaUtente($conn, (int)($_POST['id_utente'] ?? 0), $_POST);
    } else {
        $esito = ['tipo' => 'errore', 'msg' => 'Azione non valida.'];
    }
    imposta_flash($esito['tipo'], $esito['msg']);
    $conn->close();
    if ($esito['tipo'] === 'errore') {
        header('Location: utente_form.php' . ($modoModifica ? '?id=' . $idModifica : ''));
    } else {
        header('Location: utenti.php');
    }
    exit;
}

$scuole = leggiScuoleUtenti($conn);
$conn->close();

$titolo = $modoModifica ? 'Modifica utente' : 'Aggiungi utente';
render_head_admin($titolo);
render_sidebar_admin('utenti.php');
render_topbar_admin($titolo);
?>

<div class="content-grid">
    <div class="grid-full">
        <div class="card-panel">

            <form method="POST" action="utente_form.php">
                <input type="hidden" name="azione" value="<?= $modoModifica ? 'aggiorna' : 'inserisci' ?>">
                <?php if ($modoModifica): ?>
                <input type="hidden" name="id_utente" value="<?= $idModifica ?>">
                <?php endif; ?>

                <div class="row g-3">

                    <div class="col-md-4">
                        <label class="form-label">Username *</label>
                        <input type="text" name="username" class="form-control" required maxlength="32"
                               value="<?= htmlspecialchars($utente['username'] ?? '') ?>">
                    </div>

                    <div class="col-md-5">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" class="form-control" required maxlength="254"
                               value="<?= htmlspecialchars($utente['email'] ?? '') ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">
                            Password <?= $modoModifica ? '(lascia vuoto per non cambiarla)' : '*' ?>
                        </label>
                        <input type="password" name="password" class="form-control"
                               <?= $modoModifica ? '' : 'required' ?> minlength="6" autocomplete="new-password">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Tipo *</label>
                        <select name="tipo" id="tipo" class="form-select" required>
                            <option value="SCOLASTICO" <?= ($utente['tipo'] ?? '') === 'SCOLASTICO' ? 'selected' : '' ?>>
                                SCOLASTICO
                            </option>
                            <option value="ADMIN" <?= ($utente['tipo'] ?? '') === 'ADMIN' ? 'selected' : '' ?>>
                                ADMIN
                            </option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Stato *</label>
                        <select name="stato" class="form-select" required>
                            <option value="ATTIVO"   <?= ($utente['stato'] ?? 'ATTIVO') === 'ATTIVO'   ? 'selected' : '' ?>>ATTIVO</option>
                            <option value="BLOCCATO" <?= ($utente['stato'] ?? '')       === 'BLOCCATO' ? 'selected' : '' ?>>BLOCCATO</option>
                        </select>
                    </div>

                    <div class="col-md-5" id="riga-scuola">
                        <label class="form-label">Scuola *</label>
                        <select name="cod_scuola" id="cod_scuola" class="form-select">
                            <option value="">Seleziona...</option>
                            <?php foreach ($scuole as $s): ?>
                            <option value="<?= htmlspecialchars($s['COD_meccanografico']) ?>"
                                <?= ($utente['cod_scuola'] ?? '') === $s['COD_meccanografico'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s['nome']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                </div>

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-<?= $modoModifica ? 'check-lg' : 'plus-lg' ?> me-1"></i>
                        <?= $modoModifica ? 'Aggiorna' : 'Aggiungi utente' ?>
                    </button>
                    <a href="utenti.php" class="btn btn-outline-secondary">Annulla</a>
                </div>

            </form>

        </div>
    </div>
</div>

<script>
const tipoSelect   = document.getElementById('tipo');
const rigaScuola   = document.getElementById('riga-scuola');
const selectScuola = document.getElementById('cod_scuola');

function aggiornaCampiTipo() {
    const isScol = tipoSelect.value === 'SCOLASTICO';
    rigaScuola.style.display = isScol ? '' : 'none';
    selectScuola.required = isScol;
}

tipoSelect.addEventListener('change', aggiornaCampiTipo);
aggiornaCampiTipo();
</script>

<?php chiudi_pagina(); ?>
