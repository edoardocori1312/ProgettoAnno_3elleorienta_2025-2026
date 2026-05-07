<?php
    // Inizia la sessione se non è già avviata, per recuperare l'ID utente
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $emailUtente = $_SESSION["emailUtente"];
    $username = $_SESSION["usernameUtente"];
    $idUtente = $_SESSION["idUtente"];
    $ruoloUtente = $_SESSION["ruoloUtente"];



    require("../config/db.php");
$conn = new mysqli($HOSTDB, $USERDB, $PASSDB, $NOMEDB);

if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}

// Leggi e cancella il messaggio flash dalla sessione
$flash = null;
if (isset($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
}

$erroreInserimento = '';

// Inserimento
if (isset($_POST['inserisci'])) {
    $zona = trim($_POST['zona']);
    if (!empty($zona)) {
        $stmtCheck = $conn->prepare("SELECT COUNT(*) FROM Zone WHERE LOWER(nome) = LOWER(?)");
        $stmtCheck->bind_param("s", $zona);
        $stmtCheck->execute();
        $stmtCheck->bind_result($contaDuplicati);
        $stmtCheck->fetch();
        $stmtCheck->close();

        if ($contaDuplicati > 0) {
            $erroreInserimento = "La zona \"" . htmlspecialchars($zona) . "\" esiste già.";
        } else {
            $stmt = $conn->prepare("INSERT INTO Zone (nome) VALUES (?)");
            $stmt->bind_param("s", $zona);
            $stmt->execute();
            $stmt->close();
            $_SESSION['flash'] = ['tipo' => 'successo', 'msg' => 'Zona "' . htmlspecialchars($zona) . '" aggiunta con successo.'];
            header("Location: zone.php");
            exit();
        }
    }
}

// Eliminazione 
if (isset($_GET['elimina'])) {
    $id = intval($_GET['elimina']);

    // Controlla se la zona è usata in una città
    $stmtCheck = $conn->prepare("SELECT COUNT(*) FROM Citta WHERE id_zona = ?");
    $stmtCheck->bind_param("i", $id);
    $stmtCheck->execute();
    $stmtCheck->bind_result($inUso);
    $stmtCheck->fetch();
    $stmtCheck->close();

    if ($inUso > 0) {
        // Imposta un messaggio flash di errore se la zona è associata a una città
        $_SESSION['flash'] = ['tipo' => 'errore', 'msg' => 'Impossibile eliminare: la zona è associata a una o più città.'];
    } else {
        // Query per eliminare la zona dal database
        $stmt = $conn->prepare("DELETE FROM Zone WHERE ID_zona = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        // Imposta un messaggio flash di successo
        $_SESSION['flash'] = ['tipo' => 'successo', 'msg' => 'Zona eliminata con successo.'];
    }
    header("Location: zone.php");
    exit();
}

// Modifica
if (isset($_POST['modifica']) && isset($_POST['id'])) {
    $id   = intval($_POST['id']);
    $nome = trim($_POST['nome']);
    if (!empty($nome)) {
        // Query che controlla se esiste già una zona con lo stesso nome (ignorando il caso) ma con un ID diverso
        $stmtCheck = $conn->prepare("SELECT COUNT(*) FROM Zone WHERE LOWER(nome) = LOWER(?) AND ID_zona != ?");
        $stmtCheck->bind_param("si", $nome, $id);
        $stmtCheck->execute();
        $stmtCheck->bind_result($contaDuplicati);
        $stmtCheck->fetch();
        $stmtCheck->close();

        if ($contaDuplicati > 0) {
            $_SESSION['flash'] = ['tipo' => 'errore', 'msg' => 'La zona "' . htmlspecialchars($nome) . '" esiste già.'];
            header("Location: zone.php?modifica={$id}");
        } else {
            // Query per aggiornare il nome della zona nel database
            $stmt = $conn->prepare("UPDATE Zone SET nome = ? WHERE ID_zona = ?");
            $stmt->bind_param("si", $nome, $id);
            $stmt->execute();
            $stmt->close();
            $_SESSION['flash'] = ['tipo' => 'successo', 'msg' => 'Zona modificata con successo.'];
            header("Location: zone.php");
        }
    } else {
        header("Location: zone.php?modifica={$id}");
    }
    exit();
}




$tabella ="";

// Query per selezionare tutte le zone dal database, ordinandole per ID in modo crescente (default)
$result = $conn->query("SELECT ID_zona, nome FROM Zone ORDER BY ID_zona ASC");

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $id     = (int)$row['ID_zona'];
        $nome   = htmlspecialchars($row['nome']);
        $nomeJs = addslashes($row['nome']);
        // Se l'ID della zona corrisponde a quello in modifica, mostra il form di modifica inline, altrimenti mostra la riga normale
        if (isset($_GET['modifica']) && (int)$_GET['modifica'] === $id) {
            $tabella.= "
            <form id='form-mod-{$id}' method='POST' style='display:none'>
                <input type='hidden' name='id' value='{$id}'>
                <input type='hidden' name='modifica' value='1'>
            </form>
            <tr>
                <td>{$id}</td>
                <td>
                    <input type='text' name='nome' value='{$nome}' class='input-zona-inline'
                            form='form-mod-{$id}' required>
                </td>
                <td>
                    <button type='submit' form='form-mod-{$id}' class='btn-salva'>Salva</button>
                    <a href='zone.php' class='btn-annulla-link'>Annulla</a>
                </td>
            </tr>";
        } else {
            $tabella.= "
            <tr>
                <td>{$id}</td>
                <td>{$nome}</td>
                <td>
                    <a href='?modifica={$id}' class='btn-modifica'>Modifica</a>
                    <a href='#' class='btn-elimina'
                        onclick='apriModalElimina({$id}, \"{$nomeJs}\"); return false;'>Elimina</a>
                </td>
            </tr>";
        }
    }
} else {
    $tabella.= "<tr><td colspan='3' class='td-loading'>Nessuna zona trovata</td></tr>";
}

$conn->close();

















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
            <img src="img/logo.png" alt="logo" width="40" height="40" style="object-fit: contain; vertical-align: middle;"> 
            3elleorienta
        </span>
        <label class="menu-toggle-label" for="sidebar-toggle" title="Apri/Chiudi menu">☰</label>
    </div>

    <nav class="nav-group mt-2">
        <div class="nav-label">SCUOLA</div>
        <a href="../index.php" class="nav-link" data-page="Scuola">
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
                    <li class="breadcrumb-item active" aria-current="page" id="breadcrumb-current">Zone</li>
                </ol>
            </nav>
        </div>

        <div class="user-info d-flex align-items-center gap-2">
            <i class="bi bi-person-circle"></i>
            <span class="fw-semibold" style="font-size:0.88rem;">

                <?php echo $username; ?>

            </span>
            <span class="text-secondary">|</span>
            <a href="logout.php" class="text-danger text-decoration-none" style="font-size:0.88rem;">Logout</a>
        </div>
    </header>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <button class="btn btn-primary shadow-sm d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#modalEventi">
            <i class="bi bi-plus-circle"></i> Aggiungi zona
        </button>
    </div>
    <table class="table table-hover align-middle mb-0">
    <thead class = "table-light">
        <tr>
            
            
            <th class="px-3">ID</th>
            <th>Nome</th>
            <th class="text-end">Azioni</th>

        </tr>
    </thead>
    <tbody>

        
        
        <?php
        
            
            
        
        
        
        
        
        echo $tabella;
        
        
        
        
        
        
        
        
        
        ?>
    
    </tbody>
</table>
</div>
<div class="modal fade" id="modalEventi" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="projectModalTitleEventi">Nuova zona</h5>
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