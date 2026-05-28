<?php
// La sessione è già aperta da index.php tramite controlloSessione.php
$emailUtente  = $_SESSION['emailUtente']  ?? '';
$username     = $_SESSION['usernameUtente'] ?? '';
$idUtente     = $_SESSION['idUtente']     ?? '';
$ruoloUtente  = $_SESSION['ruoloUtente']  ?? '';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-semibold mb-0">
        <i class="bi bi-gear-fill me-2 text-secondary"></i>Impostazioni account
    </h5>
</div>

<div class="content-grid">

    <!-- Dati account -->
    <div class="grid-8">
        <div class="card-panel">
            <p class="form-section-title"><i class="bi bi-person me-1"></i> Dati utente</p>
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($username) ?>" disabled>
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" value="<?= htmlspecialchars($emailUtente) ?>" disabled>
            </div>
            <div class="mb-0">
                <label class="form-label">Ruolo</label>
                <input type="text" class="form-control"
                       value="<?= htmlspecialchars($ruoloUtente) ?>" disabled>
            </div>
        </div>
    </div>

    <!-- Cambio password -->
    <div class="grid-4">
        <div class="card-panel">
            <p class="form-section-title"><i class="bi bi-lock me-1"></i> Sicurezza</p>
            <form method="POST" action="index.php?page=impostazioni">
                <input type="hidden" name="page" value="impostazioni">
                <div class="mb-3">
                    <label class="form-label">Nuova password</label>
                    <input type="password" name="new_password" class="form-control" placeholder="••••••••">
                </div>
                <div class="mb-3">
                    <label class="form-label">Conferma password</label>
                    <input type="password" name="confirm_password" class="form-control" placeholder="••••••••">
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-floppy me-1"></i> Salva password
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
