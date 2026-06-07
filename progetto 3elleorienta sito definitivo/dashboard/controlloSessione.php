<?php
// Avvia la sessione se non è già attiva
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Se l'utente non è loggato, reindirizza alla pagina di login
if (!isset($_SESSION['emailUtente'])) {
    header('Location: login.php');
    exit();
}
