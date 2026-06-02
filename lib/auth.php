<?php
require_once __DIR__ . '/../config/db.php';

function avvia_sessione(): void {
    if (session_status() === PHP_SESSION_NONE) {
        // Cookie di sessione hardened: HttpOnly (no accesso JS), SameSite=Lax (anti-CSRF),
        // Secure solo se siamo in HTTPS (così non rompe il dev Docker su HTTP).
        session_set_cookie_params([
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => !empty($_SERVER['HTTPS']),
        ]);
        session_start();
    }
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
}

function prova_login(string $email, string $password): bool {
    $conn = db();
    $stmt = $conn->prepare(
        "SELECT ID_utente, username, hash_password, tipo, stato, cod_scuola
         FROM Utenti WHERE email = ?"
    );
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $riga = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $conn->close();

    if (!$riga) return false;
    if ($riga['stato'] !== 'ATTIVO') return false;
    if (!password_verify($password, $riga['hash_password'])) return false;

    avvia_sessione();
    // Anti session-fixation: nuovo ID di sessione al cambio di privilegio (login).
    session_regenerate_id(true);
    $_SESSION['uid']        = (int)$riga['ID_utente'];
    $_SESSION['username']   = $riga['username'];
    $_SESSION['ruolo']      = $riga['tipo'];
    $_SESSION['cod_scuola'] = $riga['cod_scuola'];
    return true;
}

function richiedi_login(): void {
    avvia_sessione();
    if (!isset($_SESSION['uid'])) {
        header('Location: login.php');
        exit;
    }
}

function richiedi_admin(): void {
    richiedi_login();
    if ($_SESSION['ruolo'] !== 'ADMIN') {
        header('Location: index.php');
        exit;
    }
}

function logout(): void {
    avvia_sessione();
    session_unset();
    session_destroy();
}

function is_admin(): bool {
    return isset($_SESSION['ruolo']) && $_SESSION['ruolo'] === 'ADMIN';
}

function is_scolastico(): bool {
    return isset($_SESSION['ruolo']) && $_SESSION['ruolo'] === 'SCOLASTICO';
}

function utente_cod_scuola(): ?string {
    return $_SESSION['cod_scuola'] ?? null;
}

function imposta_flash(string $tipo, string $msg): void {
    avvia_sessione();
    $_SESSION['flash'] = ['tipo' => $tipo, 'msg' => $msg];
}

function prendi_flash(): ?array {
    avvia_sessione();
    if (!isset($_SESSION['flash'])) return null;
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

// ── CSRF ─────────────────────────────────────────────────────────────────────
// Restituisce il token di sessione, da inserire come campo hidden in ogni form POST.
function csrf_token(): string {
    avvia_sessione();
    return $_SESSION['csrf'] ?? '';
}

// Verifica il token inviato nel POST; in caso di mismatch interrompe con 403.
// Va chiamata in cima a ogni handler POST che modifica stato.
function verifica_csrf(): void {
    avvia_sessione();
    $inviato = $_POST['csrf'] ?? '';
    if (!is_string($inviato) || !hash_equals($_SESSION['csrf'] ?? '', $inviato)) {
        http_response_code(403);
        exit('Richiesta non valida (CSRF).');
    }
}
