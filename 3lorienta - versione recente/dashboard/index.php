<?php
// Avvia la sessione e controlla il login
require_once 'controlloSessione.php';

// Connessione al database (path relativo al server)
require_once '../connessione/db.php';
$conn = mysqli_connect($HOSTDB, $USERDB, $PASSDB, $NOMEDB);
if (!$conn) {
    die('Errore connessione database');
}

// Pagina corrente (GET o POST)
$page = $_REQUEST['page'] ?? 'scuole';

$allowedPages = [
    'scuole',
    'zona',
    'eventi',
    'utenti',
    'progetti',
    'impostazioni',
    'scuola_modifica',
];

if (!in_array($page, $allowedPages)) {
    $page = 'scuole';
}

// Mappa pagina → titolo breadcrumb
$pageTitles = [
    'scuole'          => 'Scuole',
    'zona'            => 'Zone',
    'eventi'          => 'Eventi',
    'utenti'          => 'Utenti',
    'progetti'        => 'Progetti',
    'impostazioni'    => 'Impostazioni',
    'scuola_modifica' => 'Modifica Scuola',
];

$pageTitle = $pageTitles[$page] ?? ucfirst($page);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — 3elleorienta</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="styles/style.css">
</head>
<body>

<!-- Checkbox nascosto per il toggle sidebar (no JS) -->
<input type="checkbox" id="sidebar-toggle">

<!-- Overlay mobile: cliccando chiude la sidebar -->
<label class="sidebar-overlay" for="sidebar-toggle" aria-hidden="true"></label>

<?php include 'sidebar.php'; ?>

<div class="main-wrapper">
    <?php include 'topbar.php'; ?>

    <main class="page-content">
        <?php include "pages/{$page}.php"; ?>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
