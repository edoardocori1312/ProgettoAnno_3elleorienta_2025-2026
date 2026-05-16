<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/layout.php';
require_once __DIR__ . '/gestione/gestione_scuole.php';
richiedi_login();

$conn      = db();
$isAdmin   = is_admin();
$codUtente = utente_cod_scuola();

// PRG: elimina scuola (ADMIN only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['elimina_cod'])) {
    richiedi_admin();
    imposta_flash(...array_values(eliminaScuola($conn, $_POST['elimina_cod'])));
    $conn->close();
    header('Location: scuole.php');
    exit;
}

$filtroCitta = $_GET['citta'] ?? '';
$filtroNome  = trim($_GET['nome'] ?? '');
$scuole = leggiScuole($conn, $isAdmin, $codUtente, $filtroCitta, $filtroNome);
$citta  = leggiCitta($conn);
$conn->close();
$flash = prendi_flash();

render_head_admin('Gestione Scuole');
render_sidebar_admin('scuole.php');
render_topbar_admin('Scuole');
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
                <p class="text-danger mb-0" style="font-size:.82rem;">
                    <i class="bi bi-info-circle me-1"></i>
                    Verranno rimossi anche ambiti, indirizzi associati e il riferimento negli eventi.
                </p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Annulla</button>
                <form method="POST" action="scuole.php" style="display:inline">
                    <input type="hidden" name="elimina_cod" id="modal-elimina-cod" value="">
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
            <!-- Barra ricerca + aggiungi -->
            <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                <form method="GET" action="scuole.php" class="d-flex gap-2 flex-grow-1 flex-wrap">
                    <input type="text" name="nome" class="form-control form-control-sm"
                           placeholder="Cerca per nome..." value="<?= htmlspecialchars($filtroNome) ?>"
                           style="max-width:220px;">
                    <select name="citta" class="form-select form-select-sm" style="max-width:180px;">
                        <option value="">Tutte le città</option>
                        <?php foreach ($citta as $c): ?>
                        <option value="<?= $c['ID_citta'] ?>"
                            <?= $filtroCitta == $c['ID_citta'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['nome']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-search"></i>
                    </button>
                    <?php if ($filtroNome || $filtroCitta): ?>
                    <a href="scuole.php" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-x-lg"></i>
                    </a>
                    <?php endif; ?>
                </form>
                <?php if ($isAdmin): ?>
                <a href="scuola_form.php" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-lg me-1"></i>Aggiungi scuola
                </a>
                <?php endif; ?>
            </div>

            <?php if (empty($scuole)): ?>
            <div class="text-center text-secondary py-4">
                <i class="bi bi-inbox fs-3 d-block mb-2"></i>Nessuna scuola trovata.
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0" style="font-size:.88rem;">
                    <thead class="table-light">
                        <tr>
                            <th>Codice</th>
                            <th>Nome</th>
                            <th>Città</th>
                            <th>Sito</th>
                            <th class="text-center" style="width:120px;">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($scuole as $s):
                        $cod  = htmlspecialchars($s['COD_meccanografico']);
                        $nome = htmlspecialchars($s['nome']);
                        $puoModificare = $isAdmin || ($codUtente === $s['COD_meccanografico']);
                    ?>
                    <tr>
                        <td><code><?= $cod ?></code></td>
                        <td><?= $nome ?></td>
                        <td><?= htmlspecialchars($s['nome_citta'] ?? '') ?></td>
                        <td>
                            <?php if ($s['sito']): ?>
                            <a href="<?= htmlspecialchars($s['sito']) ?>" target="_blank" rel="noopener"
                               class="text-decoration-none" style="font-size:.82rem;">
                                <i class="bi bi-box-arrow-up-right"></i>
                            </a>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if ($puoModificare): ?>
                            <a href="scuola_form.php?cod=<?= urlencode($s['COD_meccanografico']) ?>"
                               class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-pencil-fill"></i>
                            </a>
                            <?php endif; ?>
                            <?php if ($isAdmin): ?>
                            <button class="btn btn-outline-danger btn-sm"
                                    onclick="apriElimina(<?= htmlspecialchars(json_encode($s['COD_meccanografico']), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode($s['nome']), ENT_QUOTES, 'UTF-8') ?>)">
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

<script>
function apriElimina(cod, nome) {
    document.getElementById('modal-msg').textContent = 'Eliminare la scuola "' + nome + '" (' + cod + ')?';
    document.getElementById('modal-elimina-cod').value = cod;
    new bootstrap.Modal(document.getElementById('modalElimina')).show();
}
</script>

<?php chiudi_pagina(); ?>
