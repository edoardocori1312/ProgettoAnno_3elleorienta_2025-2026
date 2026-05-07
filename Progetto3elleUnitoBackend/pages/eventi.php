<?php
    // Inizia la sessione se non è già avviata, per recuperare l'ID utente
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Includi il file con le funzioni di gestione
    @include('backend/gestione_eventi.php');


    // Simulo il recupero dell'ID utente dalla sessione (Sostituisci con la tua variabile di sessione reale)
    $emailUtente = $_SESSION["emailUtente"];
    $username = $_SESSION["usernameUtente"];
    $idUtente = $_SESSION["idUtente"];
    $ruoloUtente = $_SESSION["ruoloUtente"];
    $cod_scuola = $_SESSION["cod_scuola"];





    $tabellaEventi = "";
    $tabellaEventiFiltra = "";


    if($_POST && $_POST["azione"] == "elimina")
    {
        $idEvento = $_POST["idEvento"]; 
        eliminaEvento($idEvento, $idUtente); 
    }
    else if($_POST && $_POST["azione"] == "aggiungi")
    {
        $titolo = $_POST["titolo"]; 
        $desc_breve = $_POST["desc_breve"]; 
        $descrizione = $_POST["descrizione"]; 
        $target = $_POST["target"]; 
        $via = $_POST["via"]; 
        $n_civico = $_POST["n_civico"]; 
        $foto = 1;
        $idCitta = 2;  
        if(isset($_POST["visibile"]))
        {
            $visibile = true; 
        }
        else
        {
            $visibile = false; 
        }
        if(isset($_POST["prenotabile"]))
        {
            $prenotabile = true; 
        }
        else
        {
            $prenotabile = false; 
        }
        if(!empty($_POST["data_inizio"]))
        {
            $dt = new DateTime($_POST["data_inizio"]); 
            $data_inizio = $dt->format('Y-m-d H:i:s');
        }
        if(!empty($_POST["data_fine"]))
        {
            $dtf = new DateTime($_POST["data_fine"]); 
            $data_fine = $dtf->format('Y-m-d H:i:s');
        }
        if($target == "scolastico")
        {
            $risultato = insertEventoScolastico($titolo, $descrizione, "2026-05-06 17:24:55", "2026-05-06 18:24:55", $visibile, $prenotabile, $desc_breve, null, $cod_scuola, null); 
        }
        else
        {
            
        }
    }
    else if($_POST && $_POST["azione"] == "ripristina")
    {
        $idEvento = $_POST["idEvento"]; 
        ripristinaEvento($idEvento, $idUtente); 
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
        //da finire
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
                    <li class="breadcrumb-item active" aria-current="page" id="breadcrumb-current">Eventi</li>
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
        <form method="POST" action="">
            <input type="hidden" name="azione" value="filtra">

            <div class="row g-2 align-items-center">

                <div class="col-md-10 col-12">
                    <div class="input-group">
                        <span class="input-group-text bg-white">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" name="parolaChiave" class="form-control" placeholder="Cerca L'Evento..." autocomplete="off">
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
        <button class="btn btn-primary shadow-sm d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#modalEventi">
            <i class="bi bi-plus-circle"></i> Aggiungi Evento
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
<div class="modal fade" id="modalEventi" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="projectModalTitleEventi">Nuovo Evento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
            </div>
            <div class="modal-body">
                <form id="projectForm" action="" method="POST" enctype="multipart/form-data"> <!-- "enctype" permette l'upload di file (in questo caso foto)-->
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
                                    <input class="form-check-input" type="checkbox" id="visibile" name="visibile">
                                    <label class="form-check-label fw-semibold" for="visibile">
                                        Visibile
                                    </label>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" id="prenotabile" name="prenotabile">
                                    <label class="form-check-label fw-semibold" for="prenotabile">
                                        Prenotabile
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
                        <div class="modal-footer">
                            <input type='hidden' name='azione' value='aggiungi'>
                            <button type="submit" form="projectForm" class="btn btn-primary px-4">Salva</button>
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annulla</button>
                        </div>
                    </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>