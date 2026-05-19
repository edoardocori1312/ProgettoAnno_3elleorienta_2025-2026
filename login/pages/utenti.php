<?php
// Avvia la sessione per poter usare variabili di sessione
session_start();
$sezione = $_GET["section"] ?? "scuola";

// Include file per controllo accesso (es: login obbligatorio)
include("backend/controlloSessione.php");

// Include file con dati di connessione al database
include("/home/uawit4pc/domains/3elleorienta.sviluppo.host/public_html/connessione/db.php"); 

// Connessione al database MySQL
$conn = mysqli_connect($HOSTDB, $USERDB, $PASSDB, $NOMEDB);

// Controllo connessione
if (!$conn) {
    // Se fallisce, salvo errore in sessione
    $_SESSION["errore"] = "errore connessione al database";

    // Reindirizzo alla login
    header('Location:backend/login.php');
    exit();
}

// Query per prendere tutti gli utenti dal database
$sql = "SELECT ID_utente, username, email, tipo, stato FROM Utenti";

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

if (isset($_GET["parolaChiave"]) && $_GET["parolaChiave"] != "") {
    $ricerca = strtolower($_GET["parolaChiave"]);
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
    $stmt = $conn->prepare("DELETE FROM Utenti WHERE ID_utente = ?");

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

// ===================== DISATTIVA UTENTE =====================
// Controllo se è stato inviato un POST di eliminazione
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["usDisattivato"])) {

    $id = $_POST["usDisattivato"];

    // Prendo lo stato corrente dell'utente
    $sel = $conn->prepare("SELECT stato FROM Utenti WHERE ID_utente = ?");
    $sel->bind_param("s", $id);
    
    if ($sel->execute()) 
    {
        $res = $sel->get_result();
        $riga = $res->fetch_assoc();
    } 
    else 
    {
        $riga = null;
    }

    $sel->close();

    if (!$riga) 
    {
        $_SESSION["msg"] = "Utente non trovato";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    // Decido nuovo stato (se BLOCCATO => ATTIVO, altrimenti => BLOCCATO)
    $nuovoStato = ($riga['stato'] === 'BLOCCATO') ? 'ATTIVO' : 'BLOCCATO';
    $msgSuccess = ($nuovoStato === 'ATTIVO') ? "Utente sbloccato correttamente" : "Utente bloccato correttamente";

    // Aggiorno lo stato
    $upd = $conn->prepare("UPDATE Utenti SET stato = ? WHERE ID_utente = ?");
    $upd->bind_param("ss", $nuovoStato, $id);

    if ($upd->execute()) {
        $_SESSION["msg"] = $msgSuccess;
    } else {
        $_SESSION["msg"] = "Errore modifica stato utente";
    }
    $upd->close();

    // Redirect per evitare ri-invio form
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - 3elleorienta</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../style/style.css">
    <link rel="stylesheet" href="../style/Progettistyle.css">
</head>
<body>

<input type="checkbox" id="sidebar-toggle">

<aside class="sidebar">
    <div class="logo">
        <span class="logo-text">
            <img src="../../pictures/logo.png" alt="logo" width="40" height="40" style="object-fit: contain; vertical-align: middle;"> 
            3elleorienta
        </span>
        <label class="menu-toggle-label" for="sidebar-toggle" title="Apri/Chiudi menu">☰</label>
    </div>

    <nav class="nav-group mt-2">
        <div class="nav-label">SCUOLA</div>
        <a href="index_scuole_backend.php" class="nav-link" data-page="Scuola">
            <i class="bi bi-backpack-fill"></i>
            <span class="link-text">Scuola</span>
        </a>
        <a href="zona.php" class="nav-link" data-page="Zona">
            <i class="bi bi-geo-fill"></i>
            <span class="link-text">Zona</span>
        </a>

        <div class="nav-label mt-2">AVVENIMENTI</div>
        <a href="eventi.php" class="nav-link" data-page="Eventi">
            <i class="bi bi-calendar-fill"></i>
            <span class="link-text">Eventi</span>
        </a>
        <a href="progetti.php" class="nav-link" data-page="Progetti">
            <i class="bi bi-lightbulb-fill"></i>
            <span class="link-text">Progetti</span>
        </a>
        <a href="link.php" class="nav-link" data-page="Link Utili">
            <i class="bi bi-link-45deg"></i>
            <span class="link-text">Link Utili</span>
        </a>

        <div class="nav-label mt-2">UTENTI</div>
        <a href="utenti.php" class="nav-link" data-page="Gestione Utenti">
            <i class="bi bi-people-fill"></i>
            <span class="link-text">Gestione Utenti</span>
        </a>

        <div class="nav-label mt-2">ALTRO</div>
        <a href="impostazioni.php" class="nav-link" data-page="Impostazioni">
            <i class="bi bi-tools"></i>
            <span class="link-text">Impostazioni</span>
        </a>
    </nav>
</aside>

<label class="sidebar-overlay" for="sidebar-toggle" aria-label="Chiudi menu"></label>

<div class="main-wrapper">
    <header class="top-bar">
        <div class="d-flex align-items-center gap-3">
            <label class="hamburger-label" for="sidebar-toggle" aria-label="Apri menu">
                <i class="bi bi-list"></i>
            </label>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item text-secondary">Dashboard</li>
                    <li class="breadcrumb-item active" aria-current="page" id="breadcrumb-current">Gestione utenti</li>
                </ol>
            </nav>
        </div>

        <div class="user-info d-flex align-items-center gap-2">
            <i class="bi bi-person-circle"></i>
            <span class="text-secondary">
                
                <!-- se admin -->
                <?php if($_SESSION["ruoloUtente"] == "ADMIN"){ ?>
                    admin
                <?php } else { ?>
                    scolastico
                <?php } ?>
                
            |</span>
            <a href="backend/logout.php" class="text-danger text-decoration-none" style="font-size:0.88rem;">Logout</a>
        </div>
    </header>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <button class="btn btn-primary shadow-sm d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#modalEventi">
            <i class="bi bi-plus-circle"></i> Aggiungi utente
        </button>
    </div>
    <div class="card shadow-sm mb-3 border-0">


    <div class="card-body py-3">
        <form method="GET" action="utenti.php">
            <input type="hidden" name="azione" value="utenti">

            <div class="row g-2 align-items-center">

                <div class="col-md-10 col-12">
                    <div class="input-group">
                        <span class="input-group-text bg-white">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" name="parolaChiave" class="form-control" placeholder="Cerca L'utente..." autocomplete="off">
                    </button>
                    </div>
                </div>

                <div class="col-md-2 col-12 d-grid">
                    <button type="submit" class="btn btn-success fw-semibold">
                        <i class="bi bi-search me-1"></i> Cerca
                    </button>
                </div>
            </div>
        </form>
    </div>
    <table class="table table-hover align-middle mb-0">
        <thead class = "table-light">
            <tr>     
                <th>Nome</th>
                <th>Email</th>
                <th>Ruolo</th>
                <th>Stato</th>
                <th>Azioni</th>
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
                        <td>

                            <!-- bottone visualizza -->
                            <button class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-eye">attiva/disattiva</i>
                            </button>

                            <!-- bottone modifica -->
                            <button class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil">Modifica</i>
                            </button>

                            <!-- form eliminazione -->
                            <form method="POST" style="display:inline;"
                                onsubmit="return confirm('Sei sicuro di voler eliminare questo utente?')">

                                <input type="hidden" name="usEliminato"
                                    value="<?= htmlspecialchars($u['ID_utente']); ?>">

                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash">Elimina</i>
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
<div class="modal fade" id="modalEventi" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="projectModalTitleEventi">Nuovo utente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
            </div>
            <div class="modal-body">
                <form id="projectForm" action="index.php" method="POST" enctype="multipart/form-data"> <!-- "enctype" permette l'upload di file (in questo caso foto)-->
                        
                            <!--Form inserimento -->


                            <!--Esempio:
                
                        <input type="hidden" id="event_id" name="event_id">
                        
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">Titolo Evento</label>
                                <input type="text" id="evento_titolo" name="titolo" class="form-control" required placeholder="Es: Orientamento Classi Terze">
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label fw-semibold">Descrizione Breve</label>
                                <input type="text" id="evento_desc_breve" name="desc_breve" class="form-control" required placeholder="Descrivi in poche parole il progetto">
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label fw-semibold">Descrizione</label>
                                <textarea type = "text" id="evento_descrizione" name="descrizione" class="form-control" rows="4" required placeholder="Descrivi il progetto..."></textarea>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Target</label>
                                <select id="target" name="target" class="form-select" required>
                                    <option value="scolastico">Scolastico</option>
                                    <option value="territoriale">Territoriale</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" id="visibile" name="visibile" value="1">
                                    <label class="form-check-label fw-semibold" for="visibile">
                                        Visibile
                                    </label>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" id="bozza" name="bozza" value="1">
                                    <label class="form-check-label fw-semibold" for="bozza">
                                        Bozza
                                    </label>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Ora Inizio</label>
                                <input type="datetime-local" id="ora_inizio" name="ora_inizio" class="form-control" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Ora Fine</label>
                                <input type="datetime-local" id="ora_fine" name="ora_fine" class="form-control" required>
                            </div>

                            <div class="col-md-8">
                                <label class="form-label fw-semibold">Via</label>
                                <input type="text" id="via" name="via" class="form-control" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Numero Civico</label>
                                <input type="number" id="n_civico" name="n_civico" class="form-control" required>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label fw-semibold">Foto (Carica file)</label>
                                <input type="file" class="form-control" id="proj_foto" name="foto" accept="image/*">
                            </div>
                        </div>



                         -->
                    </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annulla</button>
                <button type="submit" form="projectForm" class="btn btn-primary px-4" value = "aggiungi" name = "azione">Salva</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>