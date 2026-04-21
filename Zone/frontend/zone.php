<?php
session_start();
require("../backend/config/datiConnessione.php");
$conn = new mysqli($HOSTDB, $USERDB, $PASSDB, $NAMEDB);

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
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Zone — 3ElleOrienta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<input type="checkbox" id="sidebar-toggle">
<aside class="sidebar">
    <div class="logo">
        <span class="logo-text">
            <img src="../img/logo.png" alt="logo" class="logo-img"> 3elleorienta
        </span>
        <label class="menu-toggle-label" for="sidebar-toggle" title="Apri/Chiudi menu">☰</label>
    </div>

    <nav class="nav-group mt-2">
        <div class="nav-label">SCUOLA</div>
        <a href="scuola.php" class="nav-link">
            <i class="bi bi-backpack-fill"></i>
            <span class="link-text">Scuola</span>
        </a>
        <a href="zone.php" class="nav-link active">
            <i class="bi bi-geo-fill"></i>
            <span class="link-text">Zona</span>
        </a>

        <div class="nav-label mt-2">AVVENIMENTI</div>
        <a href="eventi.php" class="nav-link">
            <i class="bi bi-calendar-fill"></i>
            <span class="link-text">Eventi</span>
        </a>
        <a href="progetti.php" class="nav-link">
            <i class="bi bi-lightbulb-fill"></i>
            <span class="link-text">Progetti</span>
        </a>
        <a href="link.php" class="nav-link">
            <i class="bi bi-link-45deg"></i>
            <span class="link-text">Link Utili</span>
        </a>

        <div class="nav-label mt-2">UTENTI</div>
        <a href="utenti.php" class="nav-link">
            <i class="bi bi-people-fill"></i>
            <span class="link-text">Gestione Utenti</span>
        </a>

        <div class="nav-label mt-2">ALTRO</div>
        <a href="impostazioni.php" class="nav-link">
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
                    <li class="breadcrumb-item active" aria-current="page">Zone</li>
                </ol>
            </nav>
        </div>

        <div class="top-bar-user">
            <i class="bi bi-person-circle"></i>
            <span class="top-bar-username"><?php echo htmlspecialchars('admin'); ?></span>
            <span class="top-bar-sep">|</span>
            <a href="logout.php" class="top-bar-logout">Logout</a>
        </div>
    </header>

    <main class="page-content">

        <p class="page-title">Gestione Zone</p>
        <p class="page-subtitle">Aggiungi, modifica o elimina le zone dal sistema.</p>

        <div class="content-grid">

            <div class="grid-full">
                <div class="card-panel">

                    <div class="zone-header">
                        <h4 class="zone-title">
                            <i class="bi bi-geo-fill"></i> Lista Zone
                        </h4>
                    </div>

                    <?php if ($flash): ?>
                        <p class="msg-flash msg-<?php echo $flash['tipo']; ?>">
                            <?php echo $flash['msg']; ?>
                        </p>
                    <?php endif; ?>

                    <form method="POST" class="form-aggiungi">
                        <input type="text" name="zona" class="input-zona" placeholder="Nome nuova zona" required>
                        <button type="submit" name="inserisci" class="btn-dashboard">
                            <i class="bi bi-plus-lg"></i> Aggiungi
                        </button>
                    </form>

                    <div class="table-responsive-zone">
                        <table id="tabellaZone">
                            <thead>
                                <tr>
                                    <th class="td-id">ID</th>
                                    <th>Nome</th>
                                    <th class="td-azioni">Azioni</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                            // Query per selezionare tutte le zone dal database, ordinandole per ID in modo crescente (default)
                            $result = $conn->query("SELECT ID_zona, nome FROM Zone ORDER BY ID_zona ASC");

                            if ($result && $result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    $id     = (int)$row['ID_zona'];
                                    $nome   = htmlspecialchars($row['nome']);
                                    $nomeJs = addslashes($row['nome']);
                                    // Se l'ID della zona corrisponde a quello in modifica, mostra il form di modifica inline, altrimenti mostra la riga normale
                                    if (isset($_GET['modifica']) && (int)$_GET['modifica'] === $id) {
                                        echo "
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
                                        echo "
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
                                echo "<tr><td colspan='3' class='td-loading'>Nessuna zona trovata</td></tr>";
                            }

                            $conn->close();
                            ?>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>

        </div>
    </main>
</div>

<!-- Modal di conferma eliminazione -->
<div id="modal-overlay" class="modal-overlay" onclick="chiudiModal()">
    <div class="modal-box" onclick="event.stopPropagation()">
        <p class="modal-title">Conferma eliminazione</p>
        <p id="modal-msg" class="modal-msg"></p>
        <div class="modal-actions">
            <button onclick="chiudiModal()" class="btn-annulla-modal">Annulla</button>
            <a id="modal-confirm-btn" href="#" class="btn-elimina-modal">Elimina</a>
        </div>
    </div>
</div>

<script>
// Funzione per aprire il modal di conferma eliminazione, impostando il messaggio e il link di conferma dinamicamente
function apriModalElimina(id, nome) {
    document.getElementById('modal-msg').textContent = 'Eliminare la zona "' + nome + '"?';
    document.getElementById('modal-confirm-btn').href = '?elimina=' + id;
    document.getElementById('modal-overlay').style.display = 'flex';
}
// Funzione per chiudere il modal
function chiudiModal() {
    document.getElementById('modal-overlay').style.display = 'none';
}
// Aggiunge un listener per chiudere il modal quando si preme il tasto Esc della tastiera 

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') chiudiModal();
});
</script>

</body>
</html>
