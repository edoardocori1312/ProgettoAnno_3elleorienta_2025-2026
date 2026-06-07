<?php

    // apertura connessione
    if (session_status() === PHP_SESSION_NONE)
    {
        session_start();
    }
    
    // prende i dati della sessione
    $emailUtente = $_SESSION["emailUtente"];
    $username = $_SESSION["usernameUtente"];
    $idUtente = $_SESSION["idUtente"];
    $ruoloUtente = $_SESSION["ruoloUtente"];


    include("../connessione/db.php");
	include("../pictures/gestFoto.php");


    // crea la connessione
    $conn = new mysqli($HOSTDB, $USERDB, $PASSDB, $NOMEDB);
	$conn->set_charset("utf8mb4");
    if($conn->connect_error)
    {
        die("Connessione non stabilita");
    }


    // Controlla se la richiesta è di tipo POST e se contiene un campo "azione"
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['azione']))
    {
        //se l'azione è uguale a elimina imposta progetto come eliminato impostando data_eliminazione = NOW()
        if ($_POST['azione'] === 'elimina')
        {
            // converte l'ID del progetto a intero
            $idProgetto = intval($_POST['idProgetto']);
            // non cancella davvero dal database, ma lo nasconde nella visualizzazione
            $queryElimina = "UPDATE Progetti SET data_eliminazione = NOW() WHERE ID_progetto = ?";
            $stmtElimina = $conn->prepare($queryElimina);
            if ($stmtElimina)
            {
                $stmtElimina->bind_param("i", $idProgetto);
                $stmtElimina->execute();
            }
        }
        // se l'azione è uguale a modifica, modifica un progetto esistente
        elseif ($_POST['azione'] === 'salva_modifica')
        {
            // recupera i dati della modifica dal form
            $idDaModificare = intval($_POST['idDaModificare']);
            $titolo = trim($_POST['titolo']);
            $descrizione = trim($_POST['descrizione']);
            $nOrdine = intval($_POST['n_ordine']);

            // controlla se l'id della foto è vuoto o meno
            if (!empty($_POST['id_foto_corrente']))
            {
                $id_foto = $_POST['id_foto_corrente'];
            }
            else
            {
                $id_foto = null;
            }

            // se è stata caricata una nuova foto, sostituisce l'id foto precedente
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK)
            {
                try
                {
                    // chiama la funzione uploadFoto per gestire il caricamento e ritorna il nuovo id della foto 
                    $id_foto = uploadFoto($conn, $_FILES['foto']);
                }
                catch (Exception $e)
                {
                    die("Errore upload foto: " . $e->getMessage());
                }
            }

            $queryModifica = "UPDATE Progetti SET titolo=?, descrizione=?, n_ordine=?, id_foto=? WHERE ID_progetto=?";
            $stmtMod = $conn->prepare($queryModifica);
            if ($stmtMod)
            {
                $stmtMod->bind_param("ssiii", $titolo, $descrizione, $nOrdine, $id_foto, $idDaModificare);
                $stmtMod->execute();
            }
            
            // riporta alla pagina progetti
            echo "<script>window.location.href = 'index.php?page=progetti';</script>";
            exit();
        }

        elseif($_POST['azione']==="aggiungi") 
        {
            $titolo      = trim($_POST["titolo"]);
            $descrizione = trim($_POST["descrizione"]);
            $n_ordine    = (int)$_POST["n_ordine"];

            if (!empty($_POST['id_foto_corrente']))
            {
                $id_foto = $_POST['id_foto_corrente'];
            }
            else
            {
                $id_foto = null;
            }

            if (isset($_FILES['foto']))
            {
                try
                {
                    $id_foto = uploadFoto($conn, $_FILES['foto']);
                }
                catch (Exception $e)
                {
                    die("Errore upload foto: " . $e->getMessage());
                }
            }

            $query = "INSERT INTO Progetti (titolo, descrizione, n_ordine, id_foto) VALUES (?, ?, ?, ?)";
            $stmt  = $conn->prepare($query);

            if ($stmt === false)
            {
                die("Errore nello statement: " . $conn->error);
            }

            $stmt->bind_param("ssii", $titolo, $descrizione, $n_ordine, $id_foto);

            if ($stmt->execute())
            {
                echo "<script>window.location.href = 'index.php?page=progetti';</script>";
            } 
        }

        // ripristina un progetto eliminato impostando data_eliminazione = NULL
        elseif ($_POST['azione'] === 'ripristina')
        {
            $idProgetto = intval($_POST['idProgetto']);
            // query per ripristinare annullando la data di eliminazione
            $queryRipristina = "UPDATE Progetti SET data_eliminazione = NULL WHERE ID_progetto = ?";
            $stmtRipristina = $conn->prepare($queryRipristina);
            if ($stmtRipristina)
            {
                $stmtRipristina->bind_param("i", $idProgetto);
                $stmtRipristina->execute();
            }
        }
       
    }



    $filtroTitolo = "";
    // controllo se è stato inserito un filtro nella barra di ricerca
    if(isset($_GET['filtro_titolo']) && !empty(trim($_GET['filtro_titolo']))) 
    {
        $filtroTitolo = trim($_GET['filtro_titolo']);
    }

    //esegue la query corrispondente al filtro di ricerca
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
                  
        // Prepara lo statement (senza parametri)
        $stmt = $conn->prepare($query);
        
        if($stmt === false) 
        {
            die("Errore nello statement");
        }
    }

    //creazione tabella
    $tabella = "";
    if($stmt->execute())
    {
        $risultato = $stmt->get_result();
        
        if($risultato->num_rows > 0)
        {

            while($riga = $risultato->fetch_object())
            {
            
                if ($riga->ID_progetto !== null)
                {
                    $idProgetto = $riga->ID_progetto;
                }
                else
                {
                    $idProgetto = "";
                }

                if ($riga->titolo !== null)
                {
                    $titolo = $riga->titolo;
                }
                else
                {
                    $titolo = "";
                }

                if ($riga->n_ordine !== null)
                {
                    $nOrdine = $riga->n_ordine;
                }
                else
                {
                    $nOrdine = "";
                }

                if ($riga->data_eliminazione !== null)
                {
                    $dataEliminazione = $riga->data_eliminazione;
                }
                else
                {
                    $dataEliminazione = "";
                }

                if ($riga->id_foto !== null)
                {
                    $idFoto = $riga->id_foto;
                }
                else
                {
                    $idFoto = "";
                }
                //se il progetto è stato eliminato cambia la classe per lo stile della riga
                if (!empty($dataEliminazione))
                {
                    $classeRiga = "class='text-muted table-light' style='opacity: 0.6;'";
                }
                else
                {

                    $classeRiga = "";
                }

                $tabella .= "<tr {$classeRiga}>";
                $tabella .= "<td class='px-3'>". htmlspecialchars($idProgetto) ."</td>";
                $tabella .= "<td>". htmlspecialchars($titolo) ."</td>";
                $tabella .= "<td class='text-center'>". htmlspecialchars($nOrdine) ."</td>";
                
                // se il progetto è eliminato, mostra la data, altrimenti mostra un badge verde "attivo"
                if (!empty($dataEliminazione))
                {
                    $tabella .= "<td>". htmlspecialchars($dataEliminazione) ."</td>";
                }
                else
                {
                    $tabella .= "<td><span class='badge bg-success text-white fw-semibold'>Attivo</span></td>";
                }
                
                $tabella .= "<td>". htmlspecialchars($idFoto) ."</td>";
                
                $tabella .= "<td class='text-end'>";
                
                // la descrizione viene presa solo quando necessaria per il pulsante di modifica
                if ($riga->descrizione !== null)
                {
                    $descrizione = $riga->descrizione;
                }
                else
                {
                    $descrizione = "";
                }
                
                // ENT_QUOTES protegge sia gli apici singoli che le virgolette doppie
                $titoloEsc = htmlspecialchars($titolo, ENT_QUOTES);
                $descrizioneEsc = htmlspecialchars($descrizione, ENT_QUOTES);

                $tabella .= "<td class='text-end'>";
                
                // apre la modale
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
                
                // se il progetto è attivo, mostra il pulsante "elimina", invece se è già eliminato, mostra il pulsante "ripristina"   
                if (empty($dataEliminazione))
                {
                    $tabella .= "<form method='POST' action='index.php' style='display:inline;' onsubmit=\"return confirm('Sei sicuro di voler nascondere questo progetto?');\">
									<input type='hidden' name='page' value='progetti'>
                                    <input type='hidden' name='idProgetto' value='{$idProgetto}'>
                                    <input type='hidden' name='azione' value='elimina'>
                                    <button type='submit' class='btn btn-sm btn-outline-danger'>
                                        <i class='bi bi-trash'></i> Elimina
                                    </button>
                                </form>";
                } 
                else
                {
                    $tabella .= "<form method='POST' action='index.php' style='display:inline;'>
									<input type='hidden' name='page' value='progetti'>
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
            // nessun progetto trovato con il filtro applicato
            $tabella = "<tr><td colspan='7' class='text-center py-4 text-muted'>Nessun progetto trovato.</td></tr>";
        }
    }
    else
    {
        die("Operazione fallita");
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
    <link rel="stylesheet" href="styles/style.css">
    <link rel="stylesheet" href="styles/Progettistyle.css">
</head>
<body>

    <div class="card shadow-sm mb-3 border-0">
    <div class="card-body py-3">
        <form method="GET" action="">
			<input type='hidden' name='page' value='progetti'>
            <input type="hidden" name="azione" value="filtra">

            <div class="row g-2 align-items-center">
                <div class="col-md-10 col-12">
                    <div class="input-group">
                        <span class="input-group-text bg-white">
                            <i class="bi bi-search"></i>
                        </span>
                        <!-- input per inserire il filtro di ricerca -->
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
					<input type='hidden' name='page' value='progetti'>
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

<!-- modale per modifica progetti -->
<div class="modal fade" id="modalModificaProgetto" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold">Modifica Progetto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
            </div>
            <div class="modal-body">
                <form id="formModifica" action="index.php" method="POST" enctype="multipart/form-data">
					<input type='hidden' name='page' value='progetti'>
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
    var modalModifica = document.getElementById('modalModificaProgetto');
    
    if(modalModifica) {
        modalModifica.addEventListener('show.bs.modal', function (event) {
            //bottone che ha scatenato l'evento
            var button = event.relatedTarget;
            //legge i dati dal bottone modifica            
            var id = button.getAttribute('data-id');
            var titolo = button.getAttribute('data-titolo');
            var descrizione = button.getAttribute('data-descrizione');
            var ordine = button.getAttribute('data-ordine');
            var idfoto = button.getAttribute('data-idfoto');
            
            // popola i campi della modale
            modalModifica.querySelector('#mod_idDaModificare').value = id;
            modalModifica.querySelector('#mod_titolo').value = titolo;
            modalModifica.querySelector('#mod_descrizione').value = descrizione;
            modalModifica.querySelector('#mod_ordine').value = ordine;
            modalModifica.querySelector('#mod_id_foto_corrente').value = idfoto;
            
            // mostra il badge della foto corrente
            var badgeContainer = modalModifica.querySelector('#mod_badge_foto');
            if(idfoto && idfoto.trim() !== "") {
                badgeContainer.innerHTML = '<span class="badge bg-info text-dark mt-1"><i class="bi bi-image me-1"></i> ID Foto Attuale: ' + idfoto + '</span>';
            } else {
                badgeContainer.innerHTML = ''; 
            }
        });
    }
</script>
</body>
</html>
