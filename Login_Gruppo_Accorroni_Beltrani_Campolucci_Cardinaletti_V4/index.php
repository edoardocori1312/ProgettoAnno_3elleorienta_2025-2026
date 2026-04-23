<?php
// Avvia la sessione per poter usare variabili di sessione
session_start();
$sezione = $_GET["section"] ?? "scuola";

// Include file per controllo accesso (es: login obbligatorio)
include("controlloSessione.php");

// Include file con dati di connessione al database
include("datiConnessione.php");

// Connessione al database MySQL
$conn = mysqli_connect($HOSTDB, $USERDB, $PASSDB, $NOMEDB);

// Controllo connessione
if (!$conn) {
    // Se fallisce, salvo errore in sessione
    $_SESSION["errore"] = "errore connessione al database";

    // Reindirizzo alla login
    header('Location:login.php');
    exit();
}

// Query per prendere tutti gli utenti dal database
$sql = "SELECT ID_utente, username, email, tipo, stato FROM utenti";

// Preparo la query (anche se qui non ci sono parametri, quindi è opzionale)
$stmt = $conn->prepare($sql);

// Array dove salvo gli utenti
$utenti = [];

// Se la query viene eseguita correttamente
if ($stmt && $stmt->execute()) {

    // Prendo il risultato
    $res = $stmt->get_result();

    // Ciclo tutte le righe
    while ($riga = $res->fetch_assoc()) {
        // Aggiungo ogni utente all'array
        $utenti[] = $riga;
    }
}
// ===================== RICERCA UTENTI =====================
$ricerca = "";

if (isset($_GET["search"]) && $_GET["search"] != "") {
    $ricerca = strtolower($_GET["search"]);
}

// filtro utenti senza toccare la query
$utentiFiltrati = $utenti;

if ($ricerca != "") {
    $utentiFiltrati = array_filter($utenti, function ($u) use ($ricerca) {
        return (
            strpos(strtolower($u["username"]), $ricerca) !== false ||
            strpos(strtolower($u["email"]), $ricerca) !== false ||
            strpos(strtolower($u["tipo"]), $ricerca) !== false
        );
    });
}

// ===================== ELIMINAZIONE UTENTE =====================

// Controllo se è stato inviato un POST di eliminazione
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["usEliminato"])) {

    // Recupero ID utente da eliminare
    $id = $_POST["usEliminato"];

    // Query di eliminazione con prepared statement
    $stmt = $conn->prepare("DELETE FROM utenti WHERE ID_utente = ?");

    // Binding parametro (s = stringa)
    $stmt->bind_param("s", $id);

    // Esecuzione query
    if ($stmt->execute()) {
        // Messaggio successo
        $_SESSION["msg"] = "Utente eliminato correttamente";
    } else {
        // Messaggio errore
        $_SESSION["msg"] = "Errore eliminazione utente";
    }

    // Redirect per evitare ri-invio form (refresh POST)
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- Titolo pagina -->
<title>Dashboard</title>

<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Icone Bootstrap -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<!-- CSS personalizzato -->
<link rel="stylesheet" href="style.css">
</head>

<body>

<!-- checkbox per apertura/chiusura sidebar -->
<input type="checkbox" id="sidebar-toggle">

<!-- ===================== SIDEBAR ===================== -->
<aside class="sidebar">

    <div class="logo">
        <span class="logo-text">
            <img src="img/logo.png" width="40" height="40">
            3elleorienta
        </span>

        <!-- bottone hamburger sidebar -->
        <label for="sidebar-toggle">&#9776;</label>
    </div>

    <nav class="nav-group mt-2">

        <div class="nav-label">SCUOLA</div>

        <!-- link attivo di default -->
        <a href="#" class="nav-link">
            <i class="bi bi-backpack-fill"></i>
            <span class="link-text">Scuola</span>
        </a>

        <!-- MOSTRA SOLO SE ADMIN -->
        <?php if($_SESSION["ruoloUtente"] == "ADMIN"){ ?>
            <a href="#" class="nav-link">
                <i class="bi bi-geo-fill"></i>
                <span class="link-text">Zona</span>
            </a>
        <?php } ?>

        <div class="nav-label mt-2">AVVENIMENTI</div>
        
        <a href="#" class="nav-link">
            <i class="bi bi-calendar-fill"></i>
            <span class="link-text">Eventi</span>
        </a>
        <?php if($_SESSION["ruoloUtente"] == "ADMIN"){ ?>
            <a href="#" class="nav-link">
                <i class="bi bi-lightbulb-fill"></i>
                <span class="link-text">Progetti</span>
            </a>

            <a href="#" class="nav-link">
                <i class="bi bi-link-45deg"></i>
                <span class="link-text">Link Utili</span>
            </a>

            <div class="nav-label mt-2">UTENTI</div>

            <!-- link gestione utenti -->
            <a href="#" class="nav-link">
                <i class="bi bi-people-fill"></i>
                <span class="link-text">Gestione Utenti</span>
            </a>
        <?php } ?>
        <div class="nav-label mt-2">ALTRO</div>

        <a href="#" class="nav-link">
            <i class="bi bi-tools"></i>
            <span class="link-text">Impostazioni</span>
        </a>

    </nav>
</aside>

<!-- ===================== CONTENUTO PRINCIPALE ===================== -->
<div class="main-wrapper">

<!-- TOP BAR -->
<header class="top-bar">

    <div class="d-flex align-items-center gap-3">

        <!-- toggle sidebar -->
        <label for="sidebar-toggle"><i class="bi bi-list"></i></label>

        <!-- breadcrumb -->
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item">Dashboard</li>
            <li class="breadcrumb-item active" id="breadcrumb-current">Scuola</li>
        </ol>

    </div>

    <!-- INFO UTENTE -->
    <div class="user-info">

        <!-- se admin -->
        <?php if($_SESSION["ruoloUtente"] == "ADMIN"){ ?>
            admin
        <?php } else { ?>
            scolastico
        <?php } ?>

        |
        <a href="logout.php">Logout</a>
    </div>
</header>

<!-- ===================== CONTENUTO PAGINA ===================== -->
<main class="page-content" id="main-content">

    <!-- SEZIONE DI DEFAULT -->
    <div id="default-section">
        <div class="content-grid">
            <div class="grid-third">colonna sx</div>
            <div class="grid-third">colonna centrale</div>
            <div class="grid-third">colonna dx</div>
        </div>
    </div>

    <!-- ===================== GESTIONE UTENTI ===================== -->
    <div id="gestione-utenti-section" style="display:none;">

        <div class="container-fluid">
            <h4 class="mb-4">Gestione Utenti</h4>

            <form method="GET" class="mb-3">
                <input type="hidden" name="section" value="utenti">
                <div class="input-group">
                    <input type="text" name="search" class="form-control"
                        placeholder="Cerca username"
                        value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">

                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>

            <div class="card shadow-sm">
                <div class="card-body">

                    <div class="table-responsive">

                        <table class="table table-hover align-middle">

                            <!-- intestazione tabella -->
                            <thead class="table-light">
                                <tr>
                                    <th>Nome</th>
                                    <th>Email</th>
                                    <th>Ruolo</th>
                                    <th>Stato</th>
                                    <th class="text-end">Azioni</th>
                                </tr>
                            </thead>

                            <tbody>

                            <!-- se esistono utenti -->
                            <?php $utenti = $utentiFiltrati; if (!empty($utenti)) { ?>

                                <!-- ciclo utenti -->
                                <?php foreach ($utenti as $u) { ?>
                                    <tr>

                                        <!-- username -->
                                        <td><?= htmlspecialchars($u['username']); ?></td>

                                        <!-- email -->
                                        <td><?= htmlspecialchars($u['email']); ?></td>

                                        <!-- ruolo con badge -->
                                        <td>
                                            <?php 
                                            echo ($u['tipo'] == 'ADMIN')
                                            ? '<span class="badge bg-primary">Admin</span>'
                                            : '<span class="badge bg-secondary">Scolastico</span>';
                                            ?>
                                        </td>

                                        <!-- stato con badge -->
                                        <td>
                                            <?php 
                                            echo ($u['stato'] == 'ATTIVO')
                                            ? '<span class="badge bg-success">Attivo</span>'
                                            : '<span class="badge bg-warning text-dark">In attesa</span>';
                                            ?>
                                        </td>

                                        <!-- azioni -->
                                        <td class="text-end">

                                            <!-- bottone visualizza -->
                                            <button class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-eye"></i>
                                            </button>

                                            <!-- bottone modifica -->
                                            <button class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-pencil"></i>
                                            </button>

                                            <!-- form eliminazione -->
                                            <form method="POST" style="display:inline;"
                                                  onsubmit="return confirm('Sei sicuro di voler eliminare questo utente?')">

                                                <input type="hidden" name="usEliminato"
                                                       value="<?= htmlspecialchars($u['ID_utente']); ?>">

                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-trash"></i>
                                                </button>

                                            </form>

                                        </td>
                                    </tr>
                                <?php } ?>

                            <?php } else { ?>

                                <!-- se non ci sono utenti -->
                                <tr>
                                    <td colspan="5" class="text-center">Nessun utente</td>
                                </tr>

                            <?php } ?>

                            </tbody>

                        </table>

                    </div>

                </div>
            </div>
        </div>

    </div>

</main>
</div>
<script>
    const sezionePHP = "<?= $sezione ?>";
</script>

<script>
function mostraSezione(nome) {

    document.getElementById("default-section").style.display = "none";
    document.getElementById("gestione-utenti-section").style.display = "none";

    if (nome === "utenti") {
        document.getElementById("gestione-utenti-section").style.display = "block";
    } else {
        document.getElementById("default-section").style.display = "block";
    }
}

// AL CARICAMENTO PAGINA
document.addEventListener('DOMContentLoaded', function () {

    // mostra sezione giusta dopo reload
    if (sezionePHP === "utenti") {
        mostraSezione("utenti");
        document.getElementById('breadcrumb-current').textContent = "Gestione Utenti";
    } else {
        mostraSezione("scuola");
    }

    const navLinks = document.querySelectorAll('.nav-link');
    const breadcrumbCurrent = document.getElementById('breadcrumb-current');

    navLinks.forEach(link => {

        link.addEventListener('click', function (e) {
            e.preventDefault();

            navLinks.forEach(l => l.classList.remove('active'));
            this.classList.add('active');

            const text = this.querySelector('.link-text').textContent.trim();
            breadcrumbCurrent.textContent = text;

            if (text === "Gestione Utenti") {
                mostraSezione("utenti");
                history.replaceState(null, "", "?section=utenti");
            } else {
                mostraSezione("scuola");
                history.replaceState(null, "", "?section=scuola");
            }
        });
    });

});
</script>

</body>
</html>