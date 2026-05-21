<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $emailUtente = $_SESSION["emailUtente"];
    $username = $_SESSION["usernameUtente"];
    $idUtente = $_SESSION["idUtente"];
    $ruoloUtente = $_SESSION["ruoloUtente"];


    include("../config/db.php");

    $conn = new mysqli($HOSTDB, $USERDB, $PASSDB, $NOMEDB);

    if($conn->connect_error)
    {
        die("Connessione non stabilita");
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['azione'])) {
        if ($_POST['azione'] === 'elimina') {
            $idProgetto = intval($_POST['idProgetto']);
            $queryElimina = "UPDATE Progetti SET data_eliminazione = NOW() WHERE ID_progetto = ?";
            $stmtElimina = $conn->prepare($queryElimina);
            if ($stmtElimina) {
                $stmtElimina->bind_param("i", $idProgetto);
                $stmtElimina->execute();
                $stmtElimina->close();
            }
        }
        elseif ($_POST['azione'] === 'salva_modifica') {
            // Assicurati di includere il file per la gestione foto se non è già incluso
            include_once("gestFoto.php"); 

            $idDaModificare = intval($_POST['idDaModificare']);
            $titolo = trim($_POST['titolo']);
            $descrizione = trim($_POST['descrizione']);
            $nOrdine = intval($_POST['n_ordine']);
            $id_foto = !empty($_POST['id_foto_corrente']) ? $_POST['id_foto_corrente'] : null;

            // Se è stata caricata una nuova foto
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                try {
                    $id_foto = uploadFoto($conn, $_FILES['foto']); // Usa la tua funzione
                } catch (Exception $e) {
                    die("Errore upload foto: " . $e->getMessage());
                }
            }

            $queryModifica = "UPDATE Progetti SET titolo=?, descrizione=?, n_ordine=?, id_foto=? WHERE ID_progetto=?";
            $stmtMod = $conn->prepare($queryModifica);
            if ($stmtMod) {
                $stmtMod->bind_param("ssiii", $titolo, $descrizione, $nOrdine, $id_foto, $idDaModificare);
                $stmtMod->execute();
                $stmtMod->close();
            }
            
            header("Location: progetti.php");
            exit();
        }
        elseif($_POST['azione']==="aggiungi") 
        {
            $titolo      = trim($_POST["titolo"]);
            $descrizione = trim($_POST["descrizione"]);
            $n_ordine    = (int)$_POST["n_ordine"];
            $foto        = null; // placeholder

            $query = "INSERT INTO progetti (titolo, descrizione, n_ordine, id_foto) VALUES (?, ?, ?, ?)";
            $stmt  = $conn->prepare($query);

            if ($stmt === false) {
                die("Errore nello statement: " . $conn->error);
            }

            $stmt->bind_param("ssii", $titolo, $descrizione, $n_ordine, $foto);

            if ($stmt->execute()) {
                $stmt->close();
                $conn->close();
                header("Location: progetti.php");
            } 
        }
        elseif ($_POST['azione'] === 'ripristina') {
            $idProgetto = intval($_POST['idProgetto']);
            $queryRipristina = "UPDATE Progetti SET data_eliminazione = NULL WHERE ID_progetto = ?";
            $stmtRipristina = $conn->prepare($queryRipristina);
            if ($stmtRipristina) {
                $stmtRipristina->bind_param("i", $idProgetto);
                $stmtRipristina->execute();
                $stmtRipristina->close();
            }
        }
        elseif ($_POST['azione'] === 'modifica') {
        }
    }

    $filtroTitolo = "";
    if(isset($_GET['filtro_titolo']) && !empty(trim($_GET['filtro_titolo']))) 
    {
        $filtroTitolo = trim($_GET['filtro_titolo']);
    }

    if($filtroTitolo !== "") 
    {
        $query = "SELECT p.*, f.path_foto 
                  FROM Progetti p
                  LEFT JOIN Foto f ON p.id_foto = f.ID_foto
                  WHERE p.titolo LIKE ?
                  ORDER BY p.n_ordine ASC";
                  
        $stmt = $conn->prepare($query);
        
        if($stmt === false) 
        {
            die("Errore nello statement");
        }
        
        $parametroRicerca = "%" . $filtroTitolo . "%";
        $stmt->bind_param("s", $parametroRicerca);
    } 
    else 
    {
        $query = "SELECT p.*, f.path_foto 
                  FROM Progetti p
                  LEFT JOIN Foto f ON p.id_foto = f.ID_foto
                  ORDER BY p.n_ordine ASC";
                  
        $stmt = $conn->prepare($query);
        
        if($stmt === false) 
        {
            die("Errore nello statement");
        }
    }


    $tabella = "";
    if($stmt->execute())
    {
        $risultato = $stmt->get_result();
        
        if($risultato->num_rows > 0)
        {
          while($riga = $risultato->fetch_object())
            {
                $idProgetto = ($riga->ID_progetto !== null) ? $riga->ID_progetto : "";
                $titolo = ($riga->titolo !== null) ? $riga->titolo : "";
                $nOrdine = ($riga->n_ordine !== null) ? $riga->n_ordine : "";
                $dataEliminazione = ($riga->data_eliminazione !== null) ? $riga->data_eliminazione : "";
                $idFoto = ($riga->id_foto !== null) ? $riga->id_foto : "";

                // 1. Controlla se il progetto è eliminato e imposta una classe per scurire/sbiadire la riga
                $classeRiga = !empty($dataEliminazione) ? "class='text-muted table-light' style='opacity: 0.6;'" : "";

                $tabella .= "<tr {$classeRiga}>";
                $tabella .= "<td class='px-3'>". htmlspecialchars($idProgetto) ."</td>";
                $tabella .= "<td>". htmlspecialchars($titolo) ."</td>";
                $tabella .= "<td class='text-center'>". htmlspecialchars($nOrdine) ."</td>";
                
                // 2. Gestione colonna "Data eliminazione" con il badge "Attivo" Bootstrap
                if (!empty($dataEliminazione)) {
                    $tabella .= "<td>". htmlspecialchars($dataEliminazione) ."</td>";
                } else {
                    $tabella .= "<td><span class='badge bg-success text-white fw-semibold'>Attivo</span></td>";
                }
                
                $tabella .= "<td>". htmlspecialchars($idFoto) ."</td>";
                
                $tabella .= "<td class='text-end'>";
                
               // Assicurati di recuperare anche la descrizione
                $descrizione = ($riga->descrizione !== null) ? $riga->descrizione : "";
                
                // Sfuggiamo i caratteri speciali per evitare che rompano l'HTML del bottone
                $titoloEsc = htmlspecialchars($titolo, ENT_QUOTES);
                $descrizioneEsc = htmlspecialchars($descrizione, ENT_QUOTES);

                // ... [codice della tabella] ...
                
                $tabella .= "<td class='text-end'>";
                
                // NUOVO PULSANTE MODIFICA (apre la modale e le passa i dati via data-attribute)
                $tabella .= "<button type='button' class='btn btn-sm btn-outline-primary me-1' 
                                data-bs-toggle='modal' 
                                data-bs-target='#modalModificaProgetto'
                                data-id='{$idProgetto}'
                                data-titolo='{$titoloEsc}'
                                data-descrizione='{$descrizioneEsc}'
                                data-ordine='{$nOrdine}'
                                data-idfoto='{$idFoto}'>
                                <i class='bi bi-pencil'></i> Modifica
                            </button>";   
                
                // ... [codice dei pulsanti Elimina/Ripristina esistenti] ...
                
                if (empty($dataEliminazione)) {
                    $tabella .= "<form method='POST' action='progetti.php' style='display:inline;' onsubmit=\"return confirm('Sei sicuro di voler nascondere questo progetto?');\">
                                    <input type='hidden' name='idProgetto' value='{$idProgetto}'>
                                    <input type='hidden' name='azione' value='elimina'>
                                    <button type='submit' class='btn btn-sm btn-outline-danger'>
                                        <i class='bi bi-trash'></i> Elimina
                                    </button>
                                </form>";
                } 
                else {
                    $tabella .= "<form method='POST' action='progetti.php' style='display:inline;'>
                                    <input type='hidden' name='idProgetto' value='{$idProgetto}'>
                                    <input type='hidden' name='azione' value='ripristina'>
                                    <button type='submit' class='btn btn-sm btn-outline-warning'>
                                        <i class='bi bi-arrow-counterclockwise'></i> Ripristina
                                    </button>
                                </form>";
                }
                
                $tabella .= "</td>";
                $tabella .= "</tr>";
            }
        }
        else 
        {
            $tabella = "<tr><td colspan='7' class='text-center py-4 text-muted'>Nessun progetto trovato.</td></tr>";
        }
    }
    else
    {
        die("Operazione fallita");
    }

    $stmt->close();
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
                    <li class="breadcrumb-item active" aria-current="page" id="breadcrumb-current">Progetti</li>
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

    <div class="card shadow-sm mb-3 border-0">
    <div class="card-body py-3">
        <form method="GET" action="">
            <input type="hidden" name="azione" value="filtra">

            <div class="row g-2 align-items-center">
                <div class="col-md-10 col-12">
                    <div class="input-group">
                        <span class="input-group-text bg-white">
                            <i class="bi bi-search"></i>
                        </span>
                        <input value="<?php echo htmlspecialchars($filtroTitolo); ?>" type="text" name="filtro_titolo" class="form-control" placeholder="Cerca Il Progetto..." autocomplete="off">
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
</div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <button class="btn btn-primary shadow-sm d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#modalProgetti">
            <i class="bi bi-plus-circle"></i> Aggiungi progetto
        </button>
    </div>
    
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th class="px-3">ID Progetto</th>
                <th>Titolo</th>
                <th class="text-center">N.ordine</th>
                <th>Data eliminazione</th>
                <th>Foto</th>
                <th class="text-end">Azioni</th>
            </tr>
        </thead>
        <tbody>
            <?php echo $tabella; ?>
        </tbody>
    </table>
</div>

<div class="modal fade" id="modalProgetti" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold">Nuovo Progetto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
            </div>
            <div class="modal-body">
                <form id="projectForm" action="" method="POST" enctype="multipart/form-data">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Titolo Progetto</label>
                            <input type="text" id="project_titolo" name="titolo" class="form-control" required placeholder="Es: Orientamento Classi Terze">
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label fw-semibold">Descrizione</label>
                            <textarea id="project_descrizione" name="descrizione" class="form-control" rows="4" required placeholder="Descrivi il progetto..."></textarea>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">N. Ordine</label>
                            <input type="number" id="proj_ordine" name="n_ordine" class="form-control" required placeholder="Es: 1">
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label fw-semibold">Foto (Carica file)</label>
                            <input type="file" class="form-control" id="proj_foto" name="foto" accept="image/*">
                        </div>
                    </div>
                    <div class="modal-footer mt-3 pb-0 pe-0">
                        <input type='hidden' name='azione' value='aggiungi'>
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annulla</button>
                        <button type="submit" class="btn btn-primary px-4">Salva</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>




<div class="modal fade" id="modalModificaProgetto" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold">Modifica Progetto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
            </div>
            <div class="modal-body">
                <form id="formModifica" action="progetti.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="azione" value="salva_modifica">
                    <input type="hidden" name="idDaModificare" id="mod_idDaModificare">
                    <input type="hidden" name="id_foto_corrente" id="mod_id_foto_corrente">

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold text-secondary">Titolo Progetto</label>
                            <input type="text" id="mod_titolo" name="titolo" class="form-control" required>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label fw-semibold text-secondary">N. Ordine</label>
                            <input type="number" id="mod_ordine" name="n_ordine" class="form-control" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold text-secondary">Descrizione</label>
                            <textarea id="mod_descrizione" name="descrizione" class="form-control" rows="4" required></textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold text-secondary">Foto (Carica nuova per sostituire)</label>
                            <input type="file" class="form-control" id="mod_foto" name="foto" accept="image/*">
                            <div id="mod_badge_foto" class="form-text mt-2"></div>
                        </div>
                    </div>
                    <div class="modal-footer mt-3 pb-0 pe-0">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annulla</button>
                        <button type="submit" class="btn btn-primary px-4">Salva Modifiche</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>


<script>
document.addEventListener('DOMContentLoaded', function () {
    var modalModifica = document.getElementById('modalModificaProgetto');
    
    if(modalModifica) {
        modalModifica.addEventListener('show.bs.modal', function (event) {
            // Il bottone che ha scatenato l'evento
            var button = event.relatedTarget;
            
            // Estrae le info dagli attributi data-*
            var id = button.getAttribute('data-id');
            var titolo = button.getAttribute('data-titolo');
            var descrizione = button.getAttribute('data-descrizione');
            var ordine = button.getAttribute('data-ordine');
            var idfoto = button.getAttribute('data-idfoto');
            
            // Popola i campi della modale
            modalModifica.querySelector('#mod_idDaModificare').value = id;
            modalModifica.querySelector('#mod_titolo').value = titolo;
            modalModifica.querySelector('#mod_descrizione').value = descrizione;
            modalModifica.querySelector('#mod_ordine').value = ordine;
            modalModifica.querySelector('#mod_id_foto_corrente').value = idfoto;
            
            // Mostra il badge della foto corrente (se esiste)
            var badgeContainer = modalModifica.querySelector('#mod_badge_foto');
            if(idfoto && idfoto.trim() !== "") {
                badgeContainer.innerHTML = '<span class="badge bg-info text-dark mt-1"><i class="bi bi-image me-1"></i> ID Foto Attuale: ' + idfoto + '</span>';
            } else {
                badgeContainer.innerHTML = ''; // Svuota se non c'è foto
            }
        });
    }
});
</script>
</body>
</html>