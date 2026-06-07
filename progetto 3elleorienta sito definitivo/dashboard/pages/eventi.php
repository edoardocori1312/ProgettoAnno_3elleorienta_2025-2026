<?php
    // Includi il file con le funzioni di gestione
    @include('pages/backend/gestione_eventi.php');
	@include('controlloSessione.php');
    $idUtente=$_SESSION['idUtente'];
    $cod_scuola=$_SESSION['cod_scuola'];

    $tabellaEventi = "";
    $tabellaEventiFiltra = "";
    $eventoDaModificare = ""; 

    $viaScuola = ""; 
    $n_civicoScuola = ""; 


    if($_POST && $_POST["azione"] == "elimina")
    {
        $idEvento = $_POST["idEvento"]; 
        eliminaEvento($idEvento, $idUtente); 
        echo "<script>window.location.href = 'index.php?page=eventi';</script>";
        exit();
    }
    else if($_POST && $_POST["azione"] == "aggiungiEventoScolastico")
    {
        if ($cod_scuola == null)
        {
            $cod_scuola = $_POST["scuole"]; 
        } 
        $titolo = $_POST["titolo"]; 
        $desc_breve = $_POST["desc_breve"]; 
        $descrizione = $_POST["descrizione"];  
        $foto = null;
        if(isset($_FILES["foto"]) && $_FILES["foto"]["error"] !== UPLOAD_ERR_NO_FILE) {
        $foto = $_FILES["foto"];
        }
        $target = "SCOLASTICO"; 
        $data_inizio = null; 
        $data_fine = null; 
        if(isset($_POST["visibile"]))
        {
            $visibile = 1; 
        }
        else
        {
            $visibile = 0; 
        }
        if(isset($_POST["prenotabile"]))
        {
            $prenotabile = 1; 
        }
        else
        {
            $prenotabile = 0; 
        }
        if(!empty($_POST["ora_inizio"]))
        {
            $dt = new DateTime($_POST["ora_inizio"]); 
            $data_inizio = $dt->format('Y-m-d H:i:s');
        }
        if(!empty($_POST["ora_fine"]))
        {
            $dtf = new DateTime($_POST["ora_fine"]); 
            $data_fine = $dtf->format('Y-m-d H:i:s');
        }
        if(controllaData($data_inizio, $data_fine))
        {
            $risultato = insertEventoScolastico($titolo, $descrizione, $data_inizio, $data_fine, $target, $visibile, $prenotabile, $desc_breve, $cod_scuola, $foto);
        }
        else
        {
            echo "<div class='alert alert-danger'>Erorre date non valide</div>";
        }
        
    }
    else if ($_POST && $_POST["azione"] == "aggiungiEventoTerritoriale")
    {
        $titolo = $_POST["titolo"]; 
        $desc_breve = $_POST["desc_breve"]; 
        $descrizione = $_POST["descrizione"];  
        $foto = null; 
        $foto = null;
        if(isset($_FILES["foto"]) && $_FILES["foto"]["error"] !== UPLOAD_ERR_NO_FILE) {
        $foto = $_FILES["foto"];
        }
        $target = "TERRITORIALE"; 
        $via_P = $_POST["via"]; 
        $n_civico_P = $_POST["n_civico"]; 
        $data_inizio = null; 
        $data_fine = null; 
        $citta = $_POST["citta"]; 
        $resIDCitta =  recuperaIDCitta($citta);
        $id_citta = 0; 
        if($resIDCitta->isSuccess() && $resIDCitta->result)
        {
             $infoCitta = $resIDCitta->result->fetch_assoc(); 
             $id_citta = $infoCitta["ID_citta"];
         } 
         $longitudine = $_POST["longitudine"]; 
         $latitudine = $_POST["latitudine"];  
         $punto = "POINT($longitudine $latitudine)";
         if(isset($_POST["visibile"]))
         {
              $visibile = 1; 
         }
          else
         {
             $visibile = 0; 
          }
         if(isset($_POST["prenotabile"]))
        {
            $prenotabile = 1; 
        }
        else
        {
               $prenotabile = 0; 
        }
        if(!empty($_POST["ora_inizio"]))
        {
            $dt = new DateTime($_POST["ora_inizio"]); 
            $data_inizio = $dt->format('Y-m-d H:i:s');
       }
        if(!empty($_POST["ora_fine"]))
        {
            $dtf = new DateTime($_POST["ora_fine"]); 
            $data_fine = $dtf->format('Y-m-d H:i:s');
       }
        if(controllaData($data_inizio, $data_fine))
        {
             $risultato = insertEventoTerritoriale($titolo, $descrizione, $data_inizio, $data_fine, $target, $visibile, $prenotabile, $via_P, $n_civico_P, $punto, $desc_breve, $id_citta, $foto);
            echo "<script>window.location.href = 'index.php?page=eventi';</script>";
             exit();
        }
        else
        {
             echo "<div class='alert alert-danger'>Erorre date non valide</div>";
        }
        
    }
    else if($_POST && $_POST["azione"] == "ripristina")
    {
        $idEvento = $_POST["idEvento"]; 
        ripristinaEvento($idEvento, $idUtente); 
        echo "<script>window.location.href = 'index.php?page=eventi';</script>";
        exit(); 
    }
    else if($_POST && $_POST["azione"] == "filtra")
    {
        $filtro = $_POST["parolaChiave"];
        $risultatoEventi = visualizzaEventi($idUtente, true, $filtro, $filtro, null, null, null, null);
        if($risultatoEventi->isSuccess() && $risultatoEventi->result && $risultatoEventi->result->num_rows > 0) {
        while ($riga = $risultatoEventi->result->fetch_assoc()) {
                $tabellaEventiFiltra .= disegnaTabella($riga);
            }
        }                  
        else {
            // Nessun record trovato o errore
            $messaggio = $risultatoEventi->errore ? "Errore: " . $risultatoEventi->errore : "Nessun evento trovato.";
            $tabellaEventiFiltra .= "<tr><td colspan='7' class='text-center py-4 text-muted'>{$messaggio}</td></tr>";
        }

    }
    else if($_POST && $_POST["azione"] == "modifica")
    {
        $idEvento = $_POST["idEvento"];
        $resCampi = compilaCampi($idEvento); 
        $targetMod = ""; 
        
        if($resCampi->isSuccess() && $resCampi->result) {
            $eventoDaModificare = $resCampi->result->fetch_assoc();
            $targetMod = $eventoDaModificare["target"]; 
            $nomeCitta = $eventoDaModificare["id_citta"]; 
            $longitudineEvento = $eventoDaModificare["longitudine"]; 
            $latitudineEvento = $eventoDaModificare["latitudine"]; 

        }

        $resNomeCitta = recuperaNomeCitta($nomeCitta);
        if($resNomeCitta->isSuccess() && $resNomeCitta->result)
        {
            $cittaMod = $resNomeCitta->result->fetch_assoc(); 
            $nomeCittaMod = $cittaMod["nome"];
        }

        $pathFoto = null; 
        $resPathFoto = pathFoto($idEvento);
        if($resPathFoto->isSuccess() && $resPathFoto->result)
        {
            $fotoMod = $resPathFoto->result->fetch_assoc(); 
            $pathFoto = $fotoMod["path_foto"]; 
        }

        
    }
    else if($_POST && $_POST["azione"] == "salva_modifica_scolastico")
    {
        $idEventoMod = $_POST["idEvento"];
        $titoloMod = $_POST["titolo"];
        $data_inizioMod = null; 
        $data_fineMod = null; 
        $descrizioneMod = $_POST["descrizione"];
		$fotoMod = null;
        if(isset($_FILES["fotoMod"]) && $_FILES["fotoMod"]["error"] !== UPLOAD_ERR_NO_FILE) {
        $fotoMod = $_FILES["fotoMod"];
        }
        if(!empty($_POST["ora_inizio"]))
        {
            $dt = new DateTime($_POST["ora_inizio"]); 
            $data_inizioMod = $dt->format('Y-m-d H:i:s');
        }
        if(!empty($_POST["ora_fine"]))
        {
            $dtf = new DateTime($_POST["ora_fine"]); 
            $data_fineMod = $dtf->format('Y-m-d H:i:s');
        }
        if(controllaData($data_inizioMod, $data_fineMod))
        {
            $visibileMod = isset($_POST["visibile"]) ? 1 : 0;
            $prenotabileMod = isset($_POST["prenotabile"]) ? 1 : 0;
            $descrizioneBreveMod = $_POST["descrizione_breve"];
            
            $esitoModifica = modificaEventoScolastico($idUtente, $idEventoMod, $titoloMod, $descrizioneMod, $data_inizioMod, $data_fineMod, $visibileMod, $prenotabileMod, $descrizioneBreveMod, $fotoMod);
            
            if($esitoModifica->result == true) {
                echo "<script>window.location.href = 'index.php?page=eventi';</script>";
                exit();
            } else {
                $messaggioErrore = $esitoModifica->errore ? "Errore: " . $esitoModifica->errore : "Errore sconosciuto durante la modifica.";
                echo "<div class='alert alert-danger'>{$messaggioErrore}</div>";
            }
        }
        else
        {
            echo "<div class='alert alert-danger'>Erore date non valide</div>";
        }
    }
    else if($_POST && $_POST["azione"] == "salva_modifica_territoriale")
    {
        $idEventoMod = $_POST["idEvento"];
        $titoloMod = $_POST["titolo"];
        $data_inizioMod = null; 
        $data_fineMod = null; 
        $descrizioneMod = $_POST["descrizione"];
		$fotoMod = null;
        if(isset($_FILES["fotoMod"]) && $_FILES["fotoMod"]["error"] !== UPLOAD_ERR_NO_FILE) {
        $fotoMod = $_FILES["fotoMod"];
        }
        if(!empty($_POST["ora_inizio"]))
        {
            $dt = new DateTime($_POST["ora_inizio"]); 
            $data_inizioMod = $dt->format('Y-m-d H:i:s');
        }
        if(!empty($_POST["ora_fine"]))
        {
            $dtf = new DateTime($_POST["ora_fine"]); 
            $data_fineMod = $dtf->format('Y-m-d H:i:s');
        }
        if(controllaData($data_inizioMod, $data_fineMod))
        {
            $visibileMod = isset($_POST["visibile"]) ? 1 : 0;
            $prenotabileMod = isset($_POST["prenotabile"]) ? 1 : 0;
            $descrizioneBreveMod = $_POST["descrizione_breve"];
            $via_P_mod = $_POST["via"]; 
            $n_civico_P_mod = $_POST["n_civico"];
            $citta = $_POST["citta"]; 
            $resIDCitta =  recuperaIDCitta($citta);
            $id_cittaMod = 0; 
            if($resIDCitta->isSuccess() && $resIDCitta->result)
            {
                $infoCitta = $resIDCitta->result->fetch_assoc(); 
                $id_cittaMod = $infoCitta["ID_citta"];
            } 
            $longitudine = $_POST["longitudine"]; 
            $latitudine = $_POST["latitudine"];  
            $puntoMod = "POINT($longitudine $latitudine)";
            $esitoModifica = modificaEventoTerritoriale($idUtente, $idEventoMod, $titoloMod, $descrizioneMod, $data_inizioMod, $data_fineMod, $visibileMod, $prenotabileMod, $via_P_mod, $n_civico_P_mod, $puntoMod, $descrizioneBreveMod, $id_cittaMod, $fotoMod);
            if($esitoModifica->result == true) {
                echo "<script>window.location.href = 'index.php?page=eventi';</script>";
                exit();
            } else {
                $messaggioErrore = $esitoModifica->errore ? "Errore: " . $esitoModifica->errore : "Errore sconosciuto durante la modifica.";
                echo "<div class='alert alert-danger'>{$messaggioErrore}</div>";
            }
        }
        else
        {
            echo "<div class='alert alert-danger'>Erorre date non valide</div>";
        }
    }
    else if ($_POST && $_POST["azione"] == "clona")    
    {
        $idEvento = $_POST["idEvento"]; 
        clonaEvento($idEvento, $idUtente); 
        echo "<script>window.location.href = 'index.php?page=eventi';</script>";
        exit(); 

    }
    else if ($_POST && $_POST["azione"] == "filtra_avanzata")
    {
        $f_titolo = !empty($_POST["f_titolo"])? $_POST["f_titolo"]: null;
        $f_tipo = !empty($_POST["filtro_tipo"])? $_POST["filtro_tipo"]: null;
        $f_citta = !empty($_POST["f_citta"])? $_POST["f_citta"]: null;
        $f_scuola = !empty($_POST["f_scuola"])? $_POST["f_scuola"]: null;
        $f_data_inizio = null;
        $f_data_fine = null;
        $f_visibile = (isset($_POST["f_visibile"]) && $_POST["f_visibile"] !== "") ? (int)$_POST["f_visibile"]: null;
        $f_prenotabile = (isset($_POST["f_prenotabile"]) && $_POST["f_prenotabile"] !== "") ? (int)$_POST["f_prenotabile"] : null;
        $f_stato = !empty($_POST["f_stato"]) ? $_POST["f_stato"] : "attivi";

        if (!empty($_POST["f_data_inizio"])) {
            $dt = new DateTime($_POST["f_data_inizio"]);
            $f_data_inizio = $dt->format('Y-m-d H:i:s');
        }
        if (!empty($_POST["f_data_fine"])) {
            $dtf = new DateTime($_POST["f_data_fine"]);
            $f_data_fine = $dtf->format('Y-m-d H:i:s');
        }

        // La città si usa come filtro solo per gli eventi territoriali
        $cittaFiltro = ($f_tipo === "TERRITORIALE") ? $f_citta : null;

        $risultatoEventi = visualizzaEventi($idUtente, true, $f_titolo, null, $f_tipo, $f_data_inizio, $f_data_fine, $cittaFiltro);

        if ($risultatoEventi->isSuccess() && $risultatoEventi->result && $risultatoEventi->result->num_rows > 0) {
            while ($riga = $risultatoEventi->result->fetch_assoc()) {
                // Filtri post-query (visibile, prenotabile, stato, scuola)
                if ($f_visibile !== null && (int)$riga['visibile']!== $f_visibile)    continue;
                if ($f_prenotabile !== null && (int)$riga['prenotabile'] !== $f_prenotabile) continue;
                if ($f_stato === "attivi"    && !empty($riga['data_eliminazione'])) continue;
                if ($f_stato === "eliminati" &&  empty($riga['data_eliminazione'])) continue;
                if ($f_scuola !== null && $f_tipo === "SCOLASTICO" && $riga['cod_scuola'] !== $f_scuola) continue;

                $tabellaEventiFiltra .= disegnaTabella($riga);
            }
            if (empty($tabellaEventiFiltra)) {
                $tabellaEventiFiltra = "<tr><td colspan='7' class='text-center py-4 text-muted'>Nessun evento corrisponde ai filtri selezionati.</td></tr>";
            }
        } else {
            $messaggio = $risultatoEventi->errore ? "Errore: " . $risultatoEventi->errore : "Nessun evento trovato.";
            $tabellaEventiFiltra .= "<tr><td colspan='7' class='text-center py-4 text-muted'>{$messaggio}</td></tr>";
        }
    }

    // Recupera tutte le scuole per la select della ricerca avanzata
    $scuole = [];
    try {
        $connScuole = new mysqli($HOSTDB, $USERDB, $PASSDB, $NOMEDB);
        $resScuole = $connScuole->query("SELECT COD_meccanografico, nome FROM Scuole ORDER BY nome ASC");
        if ($resScuole) {
            while ($s = $resScuole->fetch_assoc()) {
                $scuole[] = $s;
            }
        }
        $connScuole->close();
    } catch (Throwable $e) {
        // scuole rimane array vuoto, la select mostrerà solo l'opzione default
    }


    $risultatoEventi = visualizzaEventi($idUtente, false, null, null, null, null, null, null);
    if($risultatoEventi->isSuccess() && $risultatoEventi->result && $risultatoEventi->result->num_rows > 0) {
    while ($riga = $risultatoEventi->result->fetch_assoc()) {
            $tabellaEventi .= disegnaTabella($riga);
        }
    }                  
    else {
        // Nessun record trovato o errore
        $messaggio = $risultatoEventi->errore ? "Errore: " . $risultatoEventi->errore : "Nessun evento trovato.";
        $tabellaEventi .= "<tr><td colspan='7' class='text-center py-4 text-muted'>{$messaggio}</td></tr>";
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

    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css">
</head>
<body>

    <div class="card shadow-sm mb-3 border-0">
    <div class="card-body py-3">
        <form method="POST" action="index.php?page=eventi">
		<input type="hidden" name="page" value="eventi">
            <input type="hidden" name="azione" value="filtra">

            <div class="row g-2 align-items-center">

                <div class="col-md-10 col-12">
                    <div class="input-group">
                        <span class="input-group-text bg-white">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" name="parolaChiave" class="form-control" placeholder="Cerca L'Evento..."autocomplete="off">
                        <button type="button"
                                class="btn btn-outline-secondary"
                                data-bs-toggle="modal"
                                data-bs-target="#modalRicercaAvanzata"
                                title="Ricerca avanzata">
                            <i class="bi bi-funnel"></i>
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
</div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <button class="btn btn-primary shadow-sm d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#modalEventiScolastico">
            <i class="bi bi-plus-circle"></i> Aggiungi Evento Scolastico
        </button>
        <button class="btn btn-primary shadow-sm d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#modalEventiTerritoriale">
            <i class="bi bi-plus-circle"></i> Aggiungi Evento Territoriale
        </button>
    </div>
    <table class="table table-hover align-middle mb-0">
    <thead class = "table-light">
        <tr>
            <th class="px-3">ID</th>
            <th>Nome Scuola</th>
            <th>Titolo</th>
            <th>Descrizione</th>
            <th class="text-center">Target / Info</th>
            <th>Data eliminazione</th>
            <th class="text-end">Azioni</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        if($_POST && ($_POST["azione"] == "filtra" || $_POST["azione"] == "filtra_avanzata"))
        {
            echo $tabellaEventiFiltra; 
        }
        else
        {
            echo $tabellaEventi;
        } ?>
    </tbody>
</table>
</div>
<div class="modal fade" id="modalEventiScolastico" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="projectModalTitleEventi">Nuovo Evento Scolastico</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
            </div>
            <div class="modal-body">
                <form id="formEventoScolastico" action="index.php?page=eventi" method="POST" enctype="multipart/form-data"> <!-- "enctype" permette l'upload di file (in questo caso foto)-->
                        <input type="hidden" name="page" value="eventi">
                        <div class="row g-3">
                            <?php
                            if($cod_scuola == null) {
                            ?>
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">Scuola</label>
                                <select id="scuole" name="scuole" class="form-select">
                                    <?php foreach ($scuole as $scuola): ?>
                                        <option value="<?= htmlspecialchars($scuola['COD_meccanografico']) ?>">
                                            <?= htmlspecialchars($scuola['nome']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php } ?>

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

                            <div class="col-md-8 d-flex align-items-center gap-4 mt-3">

                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="visibile" name="visibile">
                                    <label class="form-check-label fw-semibold" for="visibile">
                                        Visibile
                                    </label>
                                </div>

                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="prenotabile" name="prenotabile">
                                    <label class="form-check-label fw-semibold" for="prenotabile">
                                        Prenotabile
                                    </label>
                                </div>

                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Ora Inizio</label>
                                    <input type="datetime-local" id="ora_inizio" name="ora_inizio" class="form-control" required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Ora Fine</label>
                                    <input type="datetime-local" id="ora_fine" name="ora_fine" class="form-control">
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">Foto (Carica file)</label>
                                <input type="file" class="form-control" id="proj_foto" name="foto" accept="image/*">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <input type='hidden' name="azione" value="aggiungiEventoScolastico">
                            <button type="submit" form="formEventoScolastico" class="btn btn-primary px-4">Salva</button>
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annulla</button>
                        </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEventiTerritoriale" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="">Nuovo Evento Territoriale</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
            </div>
            <div class="modal-body">
                <form id="formEventoTerritoriale" action="index.php?page=eventi" method="POST" enctype="multipart/form-data"> <!-- "enctype" permette l'upload di file (in questo caso foto)-->
						<input type="hidden" name="page" value="eventi">
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

                            <div class="col-md-8 d-flex align-items-center gap-4 mt-3">

                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="visibile" name="visibile">
                                    <label class="form-check-label fw-semibold" for="visibile">
                                        Visibile
                                    </label>
                                </div>

                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="prenotabile" name="prenotabile">
                                    <label class="form-check-label fw-semibold" for="prenotabile">
                                        Prenotabile
                                    </label>
                                </div>

                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Ora Inizio</label>
                                    <input type="datetime-local" id="ora_inizio" name="ora_inizio" class="form-control" required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Ora Fine</label>
                                    <input type="datetime-local" id="ora_fine" name="ora_fine" class="form-control">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Via</label>
                                    <input type="text" id="via" name="via" class="form-control" required>
                                </div>

                                <div class="col-md-2">
                                    <label class="form-label fw-semibold">N. Civico</label>
                                    <input type="text" id="n_civico" name="n_civico" class="form-control" required>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Città</label>
                                    <input type="text" id="citta" name="citta" class="form-control" required>
                                </div>
                            </div>

                            <div class = "row">
                                <div class="col-12 mt-2">
                                    <button type="button" class="btn btn-outline-primary" id="btnGeocodifica">
                                        <i class="bi bi-geo-alt"></i> Trova posizione su mappa
                                    </button>
                                </div>
                            </div>

                            <div class="col-12">
                                    <label class="form-label fw-semibold">Seleziona posizione sulla mappa</label>
                                    <div id="map" style="height: 400px; border-radius: 10px;"></div>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">Foto (Carica file)</label>
                                <input type="file" class="form-control" id="proj_foto" name="foto" accept="image/*">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <input type='hidden' name='azione' value='aggiungiEventoTerritoriale'>
                            <input type="hidden" id="latitudine" name="latitudine">
                            <input type="hidden" id="longitudine" name="longitudine">
                            <button type="submit" form="formEventoTerritoriale" class="btn btn-primary px-4">Salva</button>
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annulla</button>
                        </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalRicercaAvanzata" tabindex="-1" aria-labelledby="modalRicercaAvanzataLabel" aria-hidden="true">
    <link rel="stylesheet" href="style/styleModal.css">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow">

            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="modalRicercaAvanzataLabel">
                    <i class="bi bi-funnel me-2"></i>Ricerca Avanzata
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
            </div>

            <div class="modal-body">
                <form id="formRicercaAvanzata" action="index.php?page=eventi" method="POST">
						<input type="hidden" name="page" value="eventi">
                    <input type="hidden" name="azione" value="filtra_avanzata">

                    <!--RADIO NASCOSTI-->
                    <input type="radio" id="filtro-tipo-tutti" name="filtro_tipo" value="" checked>
                    <input type="radio" id="filtro-tipo-ter"   name="filtro_tipo" value="TERRITORIALE">
                    <input type="radio" id="filtro-tipo-sco"   name="filtro_tipo" value="SCOLASTICO">

                    <!-- Titolo -->
                    <div class="row g-3 mb-3">
                        <div class="col-12">
                            <label for="f_titolo" class="form-label fw-semibold">Titolo</label>
                            <input type="text"
                                   id="f_titolo"
                                   name="f_titolo"
                                   class="form-control"
                                   placeholder="Cerca per titolo evento...">
                        </div>
                    </div>

                    <!-- 2. Tipologia -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold d-block">Tipologia Evento</label>
                        <div class="btn-tipo-filtro btn-group w-100" role="group" aria-label="Tipologia evento">
                            <label for="filtro-tipo-tutti" class="btn btn-outline-secondary">
                                <i class="bi bi-grid me-1"></i>Tutti
                            </label>
                            <label for="filtro-tipo-ter" class="btn btn-outline-primary">
                                <i class="bi bi-geo-alt me-1"></i>Territoriale
                            </label>
                            <label for="filtro-tipo-sco" class="btn btn-outline-success">
                                <i class="bi bi-backpack me-1"></i>Scolastico
                            </label>
                        </div>
                    </div>

                    <!-- 2a. Blocco CITTÀ -->
                    <div class="blocco-citta-filtro mb-3">
                        <label for="f_citta" class="form-label fw-semibold">Città</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-geo-alt"></i></span>
                            <input type="text"
                                   id="f_citta"
                                   name="f_citta"
                                   class="form-control"
                                   placeholder="Es. Milano">
                        </div>
                        <div class="form-text">Lascia vuoto per cercare in tutte le città.</div>
                    </div>

                    <!-- 2b. Blocco SCUOLA -->
                    <div class="blocco-scuola-filtro mb-3">
                        <label for="f_scuola" class="form-label fw-semibold">Scuola</label>
                        <select id="f_scuola" name="f_scuola" class="form-select">
                            <option value="">— Tutte le scuole —</option>
                            <?php foreach ($scuole as $scuola): ?>
                                <option value="<?= htmlspecialchars($scuola['COD_meccanografico']) ?>">
                                    <?= htmlspecialchars($scuola['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Lascia "Tutte le scuole" per non filtrare per istituto.</div>
                    </div>

                    <!-- 3. Range date/ora -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="f_data_inizio" class="form-label fw-semibold">
                                <i class="bi bi-calendar-event me-1"></i>Data inizio (da)
                            </label>
                            <input type="datetime-local"
                                   id="f_data_inizio"
                                   name="f_data_inizio"
                                   class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label for="f_data_fine" class="form-label fw-semibold">
                                <i class="bi bi-calendar-check me-1"></i>Data fine (entro)
                            </label>
                            <input type="datetime-local"
                                   id="f_data_fine"
                                   name="f_data_fine"
                                   class="form-control">
                        </div>
                    </div>

                    <!-- 4. Campi aggiuntivi-->
                    <div class="row g-3 mb-1">
                        <div class="col-md-4">
                            <label for="f_visibile" class="form-label fw-semibold">
                                <i class="bi bi-eye me-1"></i>Visibilità
                            </label>
                            <select id="f_visibile" name="f_visibile" class="form-select">
                                <option value="">Tutti</option>
                                <option value="1">Solo visibili</option>
                                <option value="0">Solo nascosti</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="f_prenotabile" class="form-label fw-semibold">
                                <i class="bi bi-bookmark me-1"></i>Prenotazione
                            </label>
                            <select id="f_prenotabile" name="f_prenotabile" class="form-select">
                                <option value="">Tutti</option>
                                <option value="1">Solo prenotabili</option>
                                <option value="0">Non prenotabili</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="f_stato" class="form-label fw-semibold">
                                <i class="bi bi-archive me-1"></i>Stato record
                            </label>
                            <select id="f_stato" name="f_stato" class="form-select">
                                <option value="attivi">Solo attivi</option>
                                <option value="eliminati">Solo eliminati</option>
                                <option value="tutti">Tutti</option>
                            </select>
                        </div>
                    </div>

                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i>Annulla
                </button>
                <button type="submit" form="formRicercaAvanzata" class="btn btn-primary px-4 fw-semibold">
                    <i class="bi bi-funnel me-1"></i>Applica Filtri
                </button>
            </div>

        </div>
    </div>
</div>

<!-- MODALE DI MODIFICA (Mostrata solo se $eventoDaModificare non è null) -->
<?php if ($eventoDaModificare != null && $targetMod == "SCOLASTICO") { ?>
    <div class="modal fade show" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content shadow">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold">Modifica Evento (ID: <?php echo htmlspecialchars($eventoDaModificare['ID_evento'] ?? $eventoDaModificare['id']); ?>)</h5>
                    <a href="index.php?page=eventi" class="btn-close" aria-label="Chiudi"></a>
                </div>
                
                <div class="modal-body">
                    <form id="formEventoScolasticoModifica" action="index.php?page=eventi" method="POST" enctype="multipart/form-data">
							<input type="hidden" name="page" value="eventi">
                        <input type="hidden" name="azione" value="salva_modifica_scolastico">
                        <input type="hidden" name="idEvento" value="<?php echo htmlspecialchars($eventoDaModificare['ID_evento'] ?? $eventoDaModificare['id']); ?>">
                        
                        <div class="row g-3">
                            <!-- Titolo -->
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">Titolo Evento</label>
                                <input type="text" name="titolo" class="form-control" value="<?php echo htmlspecialchars($eventoDaModificare['titolo'] ?? ''); ?>" required>
                            </div>
                            
                            <!-- Descrizione Breve -->
                            <div class="col-12">
                                <label class="form-label fw-semibold">Descrizione Breve</label>
                                <input type="text" name="descrizione_breve" class="form-control" value="<?php echo htmlspecialchars($eventoDaModificare['descrizione_breve'] ?? ''); ?>">
                            </div>
                            
                            <!-- Descrizione Completa -->
                            <div class="col-12">
                                <label class="form-label fw-semibold">Descrizione</label>
                                <textarea name="descrizione" class="form-control" rows="4"><?php echo htmlspecialchars($eventoDaModificare['descrizione'] ?? ''); ?></textarea>
                            </div>

                            <!-- Switch Visibile/Prenotabile -->
                            <div class="col-md-8 d-flex align-items-center gap-4 mt-3">
                                <div class="form-check form-switch d-inline-block">
                                    <input class="form-check-input" type="checkbox" name="visibile" id="visibileMod" <?php echo (!empty($eventoDaModificare['visibile'])) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="visibileMod">Visibile</label>
                                </div>
                                <div class="form-check form-switch d-inline-block">
                                    <input class="form-check-input" type="checkbox" name="prenotabile" id="prenotabileMod" <?php echo (!empty($eventoDaModificare['prenotabile'])) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="prenotabileMod">Prenotabile</label>
                                </div>
                            </div>

                            <div class = "row">
                                <!-- Date e Ore -->
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Ora Inizio</label>
                                    <input type="datetime-local" name="ora_inizio" class="form-control" value="<?php echo htmlspecialchars(isset($eventoDaModificare['ora_inizio']) ? date('Y-m-d\TH:i', strtotime($eventoDaModificare['ora_inizio'])) : ''); ?>">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Ora Fine</label>
                                    <input type="datetime-local" name="ora_fine" class="form-control" value="<?php echo htmlspecialchars(isset($eventoDaModificare['ora_fine']) ? date('Y-m-d\TH:i', strtotime($eventoDaModificare['ora_fine'])) : ''); ?>">
                                </div>
                            </div>

                            <!-- GESTIONE FOTO -->
                            <div class="col-12">
                                <label class="form-label fw-semibold">Foto Evento</label>
                                <?php if($pathFoto != null): ?>
                                    <div class="mb-2">
                                        <small class="text-muted d-block mb-1">Foto attuale:</small>
                                        <img src = "<?php echo $pathFoto; ?>">
                                    </div>
                                <?php endif; ?>
                                <input type="file" name="fotoMod" class="form-control" accept="image/*">
                                <div class="form-text">Carica un file solo se desideri sostituire la foto esistente.</div>
                            </div>
                        </div>

                        <div class="modal-footer mt-4 pb-0 border-0">
                            <a href="index.php?page=eventi" class="btn btn-outline-secondary px-4">Annulla</a>
                            <button type="submit" class="btn btn-primary px-4 shadow-sm">Salva Modifiche</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php } else if($eventoDaModificare != null && $targetMod == "TERRITORIALE"){?>
              <div id ="modalModificaTerritoriale" class="modal fade show" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content shadow">
                    <div class="modal-header bg-light">
                        <h5 class="modal-title fw-bold">Modifica Evento (ID: <?php echo htmlspecialchars($eventoDaModificare['ID_evento'] ?? $eventoDaModificare['id']); ?>)</h5>
                        <a href="index.php?page=eventi" class="btn-close" aria-label="Chiudi"></a>
                    </div>
                    
                    <div class="modal-body">
                        <form id="formEventoTerritorialeModifica" action="index.php?page=eventi" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="azione" value="salva_modifica_territoriale">
                            <input type="hidden" id="latitudineMod" name="latitudine">
                            <input type="hidden" id="longitudineMod" name="longitudine">
                            <input type="hidden" name="idEvento" value="<?php echo htmlspecialchars($eventoDaModificare['ID_evento'] ?? $eventoDaModificare['id']); ?>">
                            
                            <div class="row g-3">
                                <!-- Titolo -->
                                <div class="col-md-8">
                                    <label class="form-label fw-semibold">Titolo Evento</label>
                                    <input type="text" name="titolo" class="form-control" value="<?php echo htmlspecialchars($eventoDaModificare['titolo'] ?? ''); ?>" required>
                                </div>
                                
                                <!-- Descrizione Breve -->
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Descrizione Breve</label>
                                    <input type="text" name="descrizione_breve" class="form-control" value="<?php echo htmlspecialchars($eventoDaModificare['descrizione_breve'] ?? ''); ?>">
                                </div>
                                
                                <!-- Descrizione Completa -->
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Descrizione</label>
                                    <textarea name="descrizione" class="form-control" rows="4"><?php echo htmlspecialchars($eventoDaModificare['descrizione'] ?? ''); ?></textarea>
                                </div>

                                <!-- Switch Visibile/Prenotabile -->
                                <div class="col-md-8 d-flex align-items-center gap-4 mt-3">
                                    <div class="form-check form-switch d-inline-block">
                                        <input class="form-check-input" type="checkbox" name="visibile" id="visibileMod" <?php echo (!empty($eventoDaModificare['visibile'])) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="visibileMod">Visibile</label>
                                    </div>
                                    <div class="form-check form-switch d-inline-block">
                                        <input class="form-check-input" type="checkbox" name="prenotabile" id="prenotabileMod" <?php echo (!empty($eventoDaModificare['prenotabile'])) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="prenotabileMod">Prenotabile</label>
                                    </div>
                                </div>

                                <div class = "row">
                                    <!-- Date e Ore -->
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Ora Inizio</label>
                                        <input type="datetime-local" name="ora_inizio" class="form-control" value="<?php echo htmlspecialchars(isset($eventoDaModificare['ora_inizio']) ? date('Y-m-d\TH:i', strtotime($eventoDaModificare['ora_inizio'])) : ''); ?>">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Ora Fine</label>
                                        <input type="datetime-local" name="ora_fine" class="form-control" value="<?php echo htmlspecialchars(isset($eventoDaModificare['ora_fine']) ? date('Y-m-d\TH:i', strtotime($eventoDaModificare['ora_fine'])) : ''); ?>">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Via</label>
                                        <input type="text" id="viaMod" name="via" class="form-control" value="<?php echo htmlspecialchars($eventoDaModificare['via_P'] ?? ''); ?>"required>
                                    </div>

                                    <div class="col-md-2">
                                        <label class="form-label fw-semibold">N. Civico</label>
                                        <input type="text" id="n_civicoMod" name="n_civico" class="form-control" value="<?php echo htmlspecialchars($eventoDaModificare['n_civico_P'] ?? ''); ?>"required>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold">Città</label>
                                        <input type="text" id="cittaMod" name="citta" class="form-control" value="<?php echo $nomeCittaMod; ?>"required>
                                    </div>
                                </div>

                                <div class = "row">
                                    <div class="col-12 mt-2">
                                        <button type="button" class="btn btn-outline-primary" id="btnGeocodificaMod">
                                            <i class="bi bi-geo-alt"></i> Trova posizione su mappa
                                        </button>
                                    </div>
                                </div>

                                <div class="col-12">
                                        <label class="form-label fw-semibold">Seleziona posizione sulla mappa</label>
                                        <div id="mapModifica" style="height: 400px; border-radius: 10px;"></div>
                                </div>

                                <!-- GESTIONE FOTO -->
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Foto Evento</label>
                                    <?php if($pathFoto != null): ?>
                                        <div class="mb-2">
                                            <small class="text-muted d-block mb-1">Foto attuale:</small>
                                            <img src = "<?php echo $pathFoto; ?>">
                                        </div>
                                    <?php endif; ?>
                                    <input type="file" name="fotoMod" class="form-control" accept="image/*">
                                    <div class="form-text">Carica un file solo se desideri sostituire la foto esistente.</div>
                                </div>
                            </div>

                            <div class="modal-footer mt-4 pb-0 border-0">
                                <a href="index.php?page=eventi" class="btn btn-outline-secondary px-4">Annulla</a>
                                <button type="submit" class="btn btn-primary px-4 shadow-sm">Salva Modifiche</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php }?>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<script>

    let map;
    let marker;

    const modal = document.getElementById('modalEventiTerritoriale');

    modal.addEventListener('shown.bs.modal', function () {

        // Evita doppia inizializzazione
        if(map){
            map.invalidateSize();
            return;
        }

        // Coordinate Jesi
        const jesi = [43.5214, 13.2437];

        // Crea mappa
        map = L.map('map').setView(jesi, 14);

        // Tiles OpenStreetMap
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap'
        }).addTo(map);

        // Click mappa
        map.on('click', function(e){

            const lat = e.latlng.lat;
            const lng = e.latlng.lng;

            // Salva coordinate
            document.getElementById('latitudine').value = lat;
            document.getElementById('longitudine').value = lng;

            // Rimuove marker precedente
            if(marker){
                map.removeLayer(marker);
            }

            // Nuovo marker
            marker = L.marker([lat, lng]).addTo(map);

        });

        setTimeout(() => {
            map.invalidateSize();
        }, 200);

    });

    let mapModifica;
    let markerModifica;

    document.addEventListener("DOMContentLoaded", function () {


    // Recupera il modal
    const modalModifica = document.getElementById('modalModificaTerritoriale');

    // Controlla che esista
    if (modalModifica) {

        const latEvento = <?php echo $latitudineEvento ?? 43.5214; ?>;
        const lngEvento = <?php echo $longitudineEvento ?? 13.2437; ?>;

        const posizioneEvento = [latEvento, lngEvento];

        setTimeout(() => {

            mapModifica = L.map('mapModifica').setView(posizioneEvento, 14);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(mapModifica);

            markerModifica = L.marker(posizioneEvento).addTo(mapModifica);

            document.getElementById('latitudineMod').value = latEvento;
            document.getElementById('longitudineMod').value = lngEvento;

            mapModifica.on('click', function(e){

                const lat = e.latlng.lat;
                const lng = e.latlng.lng;

                document.getElementById('latitudineMod').value = lat;
                document.getElementById('longitudineMod').value = lng;

                if(markerModifica){
                    mapModifica.removeLayer(markerModifica);
                }

                markerModifica = L.marker([lat, lng]).addTo(mapModifica);

            });

            setTimeout(() => {
                mapModifica.invalidateSize();
            }, 200);

        }, 300);
    }

});

    async function geocodificaIndirizzo() {

        const via = document.getElementById('via').value;
        const civico = document.getElementById('n_civico').value;
        const citta = document.getElementById('citta').value;

        if (!via || !civico || !citta) {
            alert("Compila via, numero civico e città");
            return;
        }

        const query = `${via} ${civico}, ${citta}, Italia`;

        const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}`;

        try {

            const response = await fetch(url);
            const data = await response.json();

            if (!data.length) {
                alert("Indirizzo non trovato");
                return;
            }

            const lat = parseFloat(data[0].lat);
            const lng = parseFloat(data[0].lon);

            document.getElementById('latitudine').value = lat;
            document.getElementById('longitudine').value = lng;

            if (map) {

                const pos = [lat, lng];

                map.setView(pos, 16);

                if (marker) {
                    map.removeLayer(marker);
                }

                marker = L.marker(pos).addTo(map);
            }

        } catch (err) {
            console.error(err);
            alert("Errore durante la geocodifica");
        }
    }


    async function geocodificaIndirizzoMod() {

        const viaMod = document.getElementById('viaMod').value;
        const civicoMod = document.getElementById('n_civicoMod').value;
        const cittaMod = document.getElementById('cittaMod').value;

        if (!viaMod || !civicoMod || !cittaMod) {
            alert("Compila via, numero civico e città");
            return;
        }

        const query = `${viaMod} ${civicoMod}, ${cittaMod}, Italia`;

        const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}`;

        try {

            const response = await fetch(url);
            const data = await response.json();

            if (!data.length) {
                alert("Indirizzo non trovato");
                return;
            }

            const lat = parseFloat(data[0].lat);
            const lng = parseFloat(data[0].lon);

            document.getElementById('latitudineMod').value = lat;
            document.getElementById('longitudineMod').value = lng;

            if (mapModifica) {

                const pos = [lat, lng];

                mapModifica.setView(pos, 16);

                if (markerModifica) {
                    mapModifica.removeLayer(markerModifica);
                }

                markerModifica = L.marker(pos).addTo(mapModifica);
            }

        } catch (err) {
            console.error(err);
            alert("Errore durante la geocodifica");
        }
    }

    document.addEventListener("DOMContentLoaded", function () {

        const btn = document.getElementById("btnGeocodifica");

        if (btn) {
            btn.addEventListener("click", geocodificaIndirizzo);
        }

    });

    document.addEventListener("DOMContentLoaded", function () {

        const btnMod = document.getElementById("btnGeocodificaMod");

        if (btnMod) {
            btnMod.addEventListener("click", geocodificaIndirizzoMod);
        }

    });

</script>
</body>
</html>