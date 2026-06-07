<?php
// Nota: la sessione è già avviata da login.php — non serve session_start() qui.
// Non includere controlloSessione.php: durante il login la sessione non esiste ancora.

// Include il file con i dati di connessione al database
require_once '../connessione/db.php';

// Recupera i dati inviati dal form tramite POST
$em = $_POST['email'] ?? '';

// Trasforma la password in hash SHA-512
$pw = hash('sha512', $_POST['password'] ?? '');

// Connessione al database
$conn = mysqli_connect($HOSTDB, $USERDB, $PASSDB, $NOMEDB);

if (!$conn) {
    $_SESSION['errore'] = 'Errore di connessione al database.';
    header('Location: login.php');
    exit();
}

// Query parametrizzata per sicurezza
$sql  = 'SELECT * FROM Utenti WHERE email = ?';
$stmt = $conn->prepare($sql);

if (!$stmt) {
    $_SESSION['errore'] = 'Errore interno del server.';
    header('Location: login.php');
    exit();
}

$stmt->bind_param('s', $em);
$stmt->execute();
$res  = $stmt->get_result();
$riga = $res->fetch_object();

if ($riga && $riga->hash_password === $pw) {
    // Credenziali corrette: popola la sessione
    $_SESSION['emailUtente']    = $em;
    $_SESSION['usernameUtente'] = $riga->username;
    $_SESSION['idUtente']       = $riga->ID_utente;
    $_SESSION['ruoloUtente']    = $riga->tipo;
    $_SESSION['cod_scuola']     = $riga->cod_scuola;
    $stmt->close();
    $conn->close();
    header('Location: index.php');
    exit();
} else {
    $_SESSION['errore'] = 'Credenziali non valide.';
    $stmt->close();
    $conn->close();
    header('Location: login.php');
    exit();
}
