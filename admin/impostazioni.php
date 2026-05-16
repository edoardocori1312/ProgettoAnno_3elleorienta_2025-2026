<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/layout.php';
require_once __DIR__ . '/gestione/gestione_impostazioni.php';
richiedi_login();

$conn = db();
$uid  = $_SESSION['uid'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $esito = cambiaPassword(
        $conn,
        $uid,
        $_POST['password_attuale'] ?? '',
        $_POST['password_nuova']   ?? '',
        $_POST['password_conferma'] ?? ''
    );
    imposta_flash($esito['tipo'], $esito['msg']);
    $conn->close();
    header('Location: impostazioni.php');
    exit;
}

$profilo = leggiProfiloUtente($conn, $uid);
$conn->close();
$flash = prendi_flash();

render_head_admin('Impostazioni');
render_sidebar_admin('impostazioni.php');
render_topbar_admin('Impostazioni');
?>

<?php render_flash($flash); ?>

<div class="content-grid">

    <!-- Profilo (sola lettura) -->
    <div class="grid-4">
        <div class="card-panel">
            <p class="section-title"><i class="bi bi-person-circle me-1"></i> Il mio profilo</p>
            <?php if ($profilo): ?>
            <dl class="row mb-0" style="font-size:.88rem;">
                <dt class="col-sm-4">Username</dt>
                <dd class="col-sm-8"><?= htmlspecialchars($profilo['username']) ?></dd>
                <dt class="col-sm-4">Email</dt>
                <dd class="col-sm-8"><?= htmlspecialchars($profilo['email']) ?></dd>
                <dt class="col-sm-4">Ruolo</dt>
                <dd class="col-sm-8">
                    <?php if ($profilo['tipo'] === 'ADMIN'): ?>
                    <span class="badge bg-danger">ADMIN</span>
                    <?php else: ?>
                    <span class="badge bg-secondary">SCOLASTICO</span>
                    <?php endif; ?>
                </dd>
                <?php if ($profilo['nome_scuola']): ?>
                <dt class="col-sm-4">Scuola</dt>
                <dd class="col-sm-8"><?= htmlspecialchars($profilo['nome_scuola']) ?></dd>
                <?php endif; ?>
            </dl>
            <?php endif; ?>
        </div>
    </div>

    <!-- Cambio password -->
    <div class="grid-8">
        <div class="card-panel">
            <p class="section-title"><i class="bi bi-key-fill me-1"></i> Cambia password</p>
            <form method="POST" action="impostazioni.php">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Password attuale *</label>
                        <input type="password" name="password_attuale" class="form-control" required
                               autocomplete="current-password">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nuova password * <small class="text-secondary">(min. 6 caratteri)</small></label>
                        <input type="password" name="password_nuova" class="form-control" required
                               minlength="6" autocomplete="new-password">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Conferma nuova password *</label>
                        <input type="password" name="password_conferma" class="form-control" required
                               minlength="6" autocomplete="new-password">
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>Aggiorna password
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<?php chiudi_pagina(); ?>
