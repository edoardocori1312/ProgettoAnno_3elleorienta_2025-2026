<?php
// La sessione è già aperta da index.php tramite controlloSessione.php
// $conn è disponibile da index.php

// ── Query utenti ──────────────────────────────────────────────────────────────
$sql  = "SELECT ID_utente, username, email, tipo, stato FROM Utenti ORDER BY username ASC";
$stmt = $conn->prepare($sql);
$utenti = [];
if ($stmt && $stmt->execute()) {
    $utenti = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// ── Ricerca client-side (filtro PHP) ─────────────────────────────────────────
$ricerca = strtolower(trim($_GET['parolaChiave'] ?? ''));
$utentiFiltrati = $ricerca
    ? array_filter($utenti, fn($u) =>
        str_contains(strtolower($u['username']), $ricerca) ||
        str_contains(strtolower($u['email']),    $ricerca) ||
        str_contains(strtolower($u['tipo']),     $ricerca))
    : $utenti;

// ── Eliminazione utente ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['usEliminato'])) {
    $id   = (int)$_POST['usEliminato'];
    $stmt = $conn->prepare("DELETE FROM Utenti WHERE ID_utente = ?");
    $stmt->bind_param('i', $id);
    $_SESSION['flash_msg']  = $stmt->execute() ? 'Utente eliminato.' : 'Errore eliminazione.';
    $_SESSION['flash_type'] = $stmt->execute() ? 'success' : 'danger';
    $stmt->close();
    header('Location: index.php?page=utenti');
    exit();
}

// ── Toggle attivo/inattivo ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['usDisattivato'])) {
    $id    = (int)$_POST['usDisattivato'];
    $sel   = $conn->prepare("SELECT stato FROM Utenti WHERE ID_utente = ?");
    $sel->bind_param('i', $id);
    $sel->execute();
    $riga  = $sel->get_result()->fetch_assoc();
    $sel->close();

    if ($riga) {
        $nuovo = ($riga['stato'] === 'ATTIVO') ? 'INATTIVO' : 'ATTIVO';
        $upd   = $conn->prepare("UPDATE Utenti SET stato = ? WHERE ID_utente = ?");
        $upd->bind_param('si', $nuovo, $id);
        $upd->execute();
        $upd->close();
        $_SESSION['flash_msg']  = "Stato utente aggiornato a {$nuovo}.";
        $_SESSION['flash_type'] = 'success';
    }
    header('Location: index.php?page=utenti');
    exit();
}

// ── Flash message ─────────────────────────────────────────────────────────────
$flash = null;
if (isset($_SESSION['flash_msg'])) {
    $flash = ['testo' => $_SESSION['flash_msg'], 'tipo' => $_SESSION['flash_type'] ?? 'info'];
    unset($_SESSION['flash_msg'], $_SESSION['flash_type']);
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-semibold mb-0">
        <i class="bi bi-people-fill me-2 text-secondary"></i>Utenti
    </h5>
    <button class="btn btn-primary d-flex align-items-center gap-2"
            data-bs-toggle="modal" data-bs-target="#modalNuovoUtente">
        <i class="bi bi-person-plus-fill"></i> Nuovo utente
    </button>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= htmlspecialchars($flash['tipo']) ?> alert-dismissible fade show" role="alert">
    <?= htmlspecialchars($flash['testo']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="content-grid">
    <div class="grid-full">
        <div class="card-panel">

            <!-- Barra ricerca -->
            <form method="GET" action="index.php" class="mb-3">
                <input type="hidden" name="page" value="utenti">
                <div class="row g-2 align-items-center">
                    <div class="col">
                        <div class="input-group">
                            <span class="input-group-text bg-white">
                                <i class="bi bi-search text-secondary"></i>
                            </span>
                            <input type="text" name="parolaChiave" class="form-control"
                                   placeholder="Cerca per nome, email o ruolo…"
                                   value="<?= htmlspecialchars($ricerca) ?>" autocomplete="off">
                        </div>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-outline-primary">Cerca</button>
                        <?php if ($ricerca): ?>
                            <a href="index.php?page=utenti" class="btn btn-outline-secondary ms-1">Azzera</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>

            <!-- Tabella utenti -->
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Ruolo</th>
                            <th>Stato</th>
                            <th class="text-center">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($utentiFiltrati)): ?>
                        <?php foreach ($utentiFiltrati as $u): ?>
                        <tr>
                            <td><?= htmlspecialchars($u['username']) ?></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td>
                                <?php if ($u['tipo'] === 'ADMIN'): ?>
                                    <span class="badge bg-primary">Admin</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Scolastico</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($u['stato'] === 'ATTIVO'): ?>
                                    <span class="badge bg-success">Attivo</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">Inattivo</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <!-- Toggle stato -->
                                    <form method="POST" action="index.php?page=utenti" style="display:inline;">
                                        <input type="hidden" name="page" value="utenti">
                                        <input type="hidden" name="usDisattivato" value="<?= (int)$u['ID_utente'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary" title="Attiva / Disattiva">
                                            <i class="bi bi-toggle-on"></i>
                                        </button>
                                    </form>
                                    <!-- Elimina -->
                                    <form method="POST" action="index.php?page=utenti" style="display:inline;"
                                          onsubmit="return confirm('Eliminare l\'utente <?= htmlspecialchars(addslashes($u['username'])) ?>?')">
                                        <input type="hidden" name="page" value="utenti">
                                        <input type="hidden" name="usEliminato" value="<?= (int)$u['ID_utente'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Elimina">
                                            <i class="bi bi-trash-fill"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-secondary py-4">
                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                Nessun utente trovato.
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>

<!-- Modal Nuovo Utente -->
<div class="modal fade" id="modalNuovoUtente" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow">
            <div class="modal-header bg-light border-0">
                <h6 class="modal-title fw-bold">
                    <i class="bi bi-person-plus-fill me-2"></i>Nuovo utente
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="index.php?page=utenti">
                <input type="hidden" name="page" value="utenti">
                <input type="hidden" name="azione" value="aggiungi">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Username <span class="text-danger">*</span></label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Ruolo</label>
                        <select name="tipo" class="form-select">
                            <option value="SCOLASTICO">Scolastico</option>
                            <option value="ADMIN">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-floppy me-1"></i>Salva
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
