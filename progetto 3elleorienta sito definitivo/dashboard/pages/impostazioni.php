<?php
$emailUtente = $_SESSION['emailUtente']   ?? '';
$username    = $_SESSION['usernameUtente'] ?? '';
$idUtente    = (int)($_SESSION['idUtente'] ?? 0);
$ruoloUtente = $_SESSION['ruoloUtente']   ?? '';
$flash       = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $azione = $_POST['azione'] ?? '';

    // ── Salva dati ──────────────────────────────────────────────────────────
    if ($azione === 'cambia_dati') {
        $nuovoUsername = trim($_POST['new_username'] ?? '');
        $nuovaEmail    = trim($_POST['new_email']    ?? '');

        $stmt = $conn->prepare("UPDATE Utenti SET username = ?, email = ? WHERE ID_utente = ?");
        $stmt->bind_param('ssi', $nuovoUsername, $nuovaEmail, $idUtente);
        $stmt->execute();
        $stmt->close();

        $_SESSION['usernameUtente'] = $nuovoUsername;
        $_SESSION['emailUtente']    = $nuovaEmail;
        $username    = $nuovoUsername;
        $emailUtente = $nuovaEmail;

        $flash = ['testo' => 'Dati aggiornati.', 'tipo' => 'success'];
    }

    // ── Salva password ──────────────────────────────────────────────────────
    if ($azione === 'cambia_password') {
        $newPass  = $_POST['new_password']     ?? '';
        $confPass = $_POST['confirm_password'] ?? '';

        if ($newPass !== $confPass) {
            $flash = ['testo' => 'Le password non coincidono.', 'tipo' => 'danger'];
        } else {
            $hash = hash('sha512', $newPass);
            $stmt = $conn->prepare("UPDATE Utenti SET hash_password = ? WHERE ID_utente = ?");
            $stmt->bind_param('si', $hash, $idUtente);
            $stmt->execute();
            $stmt->close();
            $flash = ['testo' => 'Password aggiornata.', 'tipo' => 'success'];
        }
    }
}
?>

<h5 class="fw-semibold mb-4">
    <i class="bi bi-gear-fill me-2 text-secondary"></i>Impostazioni account
</h5>

<?php if ($flash): ?>
<div class="alert alert-<?= $flash['tipo'] ?> alert-dismissible fade show">
    <?= htmlspecialchars($flash['testo']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="content-grid">

    <!-- Dati utente -->
    <div class="grid-8">
        <div class="card-panel">
            <p class="form-section-title"><i class="bi bi-person me-1"></i> Dati utente</p>
            <form method="POST" action="index.php?page=impostazioni">
                <input type="hidden" name="azione" value="cambia_dati">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="new_username" class="form-control"
                           value="<?= htmlspecialchars($username) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="new_email" class="form-control"
                           value="<?= htmlspecialchars($emailUtente) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Ruolo</label>
                    <input type="text" class="form-control"
                           value="<?= htmlspecialchars($ruoloUtente) ?>" disabled>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-floppy me-1"></i> Salva
                </button>
            </form>
        </div>
    </div>

    <!-- Password -->
    <div class="grid-4">
        <div class="card-panel">
            <p class="form-section-title"><i class="bi bi-lock me-1"></i> Password</p>
            <form method="POST" action="index.php?page=impostazioni">
                <input type="hidden" name="azione" value="cambia_password">
                <div class="mb-3">
                    <label class="form-label">Nuova password</label>
                    <input type="password" name="new_password" class="form-control"
                           placeholder="••••••••" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Conferma password</label>
                    <input type="password" name="confirm_password" class="form-control"
                           placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-floppy me-1"></i> Aggiorna
                </button>
            </form>
        </div>
    </div>

</div>