<?php


    // Includi il file con le funzioni di gestione
	include('../controlloSessione.php');
    @include('backend/gestione_eventi.php');
	$idUtente = $_SESSION["idUtente"];

    $tabellaEventi = "";
    $tabellaEventiFiltra = "";
    $eventoDaModificare = ""; 

    $viaScuola = ""; 
    $n_civicoScuola = ""; 


    if($_POST && $_POST["azione"] == "elimina")
    {
        $idEvento = $_POST["idEvento"]; 
        eliminaEvento($idEvento, $idUtente); 
        header("Location: index.php?page=eventi"); 
        exit();
    }
    else if($_POST && $_POST["azione"] == "aggiungiEventoScolastico")
    {
        $titolo = $_POST["titolo"]; 
        $desc_breve = $_POST["desc_breve"]; 
        $descrizione = $_POST["descrizione"];  
        $foto = $_FILES["foto"]; 
        $target = "SCOLASTICO"; 
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
            header("Location: index.php?page=eventi"); 
            exit(); 
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
        $foto = $_FILES["foto"];  
        $target = "TERRITORIALE"; 
        $via_P = $_POST["via"]; 
        $n_civico_P = $_POST["n_civico"]; 
        if($n_civico_P <= 0)
        {
            echo "<div class='alert alert-danger'>Erorre Numero Civico non valido</div>";
        }
        else
        {
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
                header("Location: index.php?page=eventi"); 
                exit();
            }
            else
            {
                echo "<div class='alert alert-danger'>Erorre date non valide</div>";
            }
        }
        
    }
    else if($_POST && $_POST["azione"] == "ripristina")
    {
        $idEvento = $_POST["idEvento"]; 
        ripristinaEvento($idEvento, $idUtente); 
        header("Location: index.php?page=eventi"); 
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

        
    }
    else if($_POST && $_POST["azione"] == "salva_modifica_scolastico")
    {
        $idEventoMod = $_POST["idEvento"];
        $titoloMod = $_POST["titolo"];
        $data_inizioMod = ""; 
        $data_fineMod = ""; 
        $descrizioneMod = $_POST["descrizione"];
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
            
            $esitoModifica = modificaEventoScolastico($idUtente, $idEventoMod, $titoloMod, $descrizioneMod, $data_inizioMod, $data_fineMod, $visibileMod, $prenotabileMod, $descrizioneBreveMod);
            
            if($esitoModifica->result == true) {
                header("Location: index.php?page=eventi"); 
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
    else if($_POST && $_POST["azione"] == "salva_modifica_territoriale")
    {
        $idEventoMod = $_POST["idEvento"];
        $titoloMod = $_POST["titolo"];
        $data_inizioMod = ""; 
        $data_fineMod = ""; 
        $descrizioneMod = $_POST["descrizione"];
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
            if($n_civico_P_mod <= 0)
            {
                echo "<div class='alert alert-danger'>Erorre Numero Civico non valido</div>";
            } 
            else
            {
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
                $esitoModifica = modificaEventoTerritoriale($idUtente, $idEventoMod, $titoloMod, $descrizioneMod, $data_inizioMod, $data_fineMod, $visibileMod, $prenotabileMod, $via_P_mod, $n_civico_P_mod, $puntoMod, $descrizioneBreveMod, $id_cittaMod);
                if($esitoModifica->result == true) {
                    header("Location: index.php?page=eventi"); 
                    exit();
                } else {
                    $messaggioErrore = $esitoModifica->errore ? "Errore: " . $esitoModifica->errore : "Errore sconosciuto durante la modifica.";
                    echo "<div class='alert alert-danger'>{$messaggioErrore}</div>";
                }
            } 
		}
         else
            {
                echo "<div class='alert alert-danger'>Erorre date non valide</div>";
            }
        
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
    <link rel="stylesheet" href="style/style.css">
    <link rel="stylesheet" href="style/Progettistyle.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css">
</head>
<body>
    <div class="card shadow-sm mb-3 border-0">
    <div class="card-body py-3">
        <form method="POST" action="index.php?page=eventi">
            <input type="hidden" name="azione" value="filtra">

            <div class="row g-2 align-items-center">

                <div class="col-md-10 col-12">
                    <div class="input-group">
                        <span class="input-group-text bg-white">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" name="parolaChiave" class="form-control" placeholder="Cerca L'Evento..."autocomplete="off">
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
            <th>Titolo</th>
            <th>Descrizione</th>
            <th class="text-center">Target / Info</th>
            <th>Data eliminazione</th>
            <th>Foto</th>
            <th class="text-end">Azioni</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        if($_POST && $_POST["azione"] == "filtra")
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
                                    <input type="datetime-local" id="ora_fine" name="ora_fine" class="form-control" required>
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
                                    <input type="datetime-local" id="ora_fine" name="ora_fine" class="form-control" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Via</label>
                                    <input type="text" id="via" name="via" class="form-control" required>
                                </div>

                                <div class="col-md-2">
                                    <label class="form-label fw-semibold">N. Civico</label>
                                    <input type="number" id="n_civico" name="n_civico" class="form-control" required>
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
                                <?php if(!empty($eventoDaModificare['foto'])): ?>
                                    <div class="mb-2">
                                        <small class="text-muted d-block mb-1">Foto attuale:</small>
                                        <img src="img/eventi/<?php echo $eventoDaModificare['foto']; ?>" alt="Preview" class="img-thumbnail" style="height: 100px;">
                                    </div>
                                <?php endif; ?>
                                <input type="file" name="foto" class="form-control" accept="image/*">
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
                                        <input type="number" id="n_civicoMod" name="n_civico" class="form-control" value="<?php echo htmlspecialchars($eventoDaModificare['n_civico_P'] ?? ''); ?>"required>
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
                                    <?php if(!empty($eventoDaModificare['foto'])): ?>
                                        <div class="mb-2">
                                            <small class="text-muted d-block mb-1">Foto attuale:</small>
                                            <img src="img/eventi/<?php echo $eventoDaModificare['foto']; ?>" alt="Preview" class="img-thumbnail" style="height: 100px;">
                                        </div>
                                    <?php endif; ?>
                                    <input type="file" name="foto" class="form-control" accept="image/*">
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