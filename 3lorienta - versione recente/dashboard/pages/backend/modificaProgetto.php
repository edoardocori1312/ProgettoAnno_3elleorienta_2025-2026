<?php
    session_start();
    include("../db.php");
    
    // Assicurati che il percorso di gestFoto.php sia corretto per la tua alberatura
    include("gestFoto.php"); 

    $conn=new mysqli($HOSTDB, $USERDB, $PASSDB, $NOMEDB);

    $titolo = "";
    $descrizione = "";
    $nOrdine = "";
    $id_foto = "";
    $idDaModficare = "";
    
    // Variabili per gestire i messaggi di feedback grafici
    $messaggio = "";
    $tipoAlert = "";

    // Recupero l'ID da modificare (da GET se arrivo dalla lista, da POST se sto salvando)
    if(isset($_GET['id']))
    {
        $idDaModficare = $_GET['id'];
    } elseif(isset($_POST['idDaModficare'])) 
    {
        $idDaModficare = $_POST['idDaModficare'];
    }

    // GESTIONE SALVATAGGIO MODIFICHE (POST)
    if(isset($_POST['titolo']) && isset($_POST['descrizione']) && isset($_POST['n_ordine']))
    {
        $titolo=$_POST['titolo'];
        $descrizione=$_POST['descrizione'];
        $nOrdine=$_POST['n_ordine'];
        
        // Di base mantengo la foto corrente
        $id_foto = !empty($_POST['id_foto_corrente']) ? $_POST['id_foto_corrente'] : null;

        // Controllo se l'utente ha caricato una nuova foto
        if(isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) 
        {
            try {
                // Utilizzo la funzione di gestFoto.php passandogli l'array del file
                $nuovo_id_foto = uploadFoto($conn, $_FILES['foto']);
                $id_foto = $nuovo_id_foto; // Aggiorno l'ID con quello nuovo
            } catch (Exception $e) {
                $messaggio = "Errore upload foto: " . $e->getMessage();
                $tipoAlert = "danger";
            }
        }

        $query="UPDATE progetti SET titolo=?, descrizione=?, n_ordine=?, id_foto=? WHERE ID_Progetto=?";
        $stmt=$conn->prepare($query);

        $stmt->bind_param("ssiii", $titolo, $descrizione, $nOrdine, $id_foto, $idDaModficare);

        if($stmt->execute())
        {
            $righeProcessate=$conn->affected_rows;
                
            if($righeProcessate>0) 
            {
                $messaggio = "Modifica avvenuta con successo!";
                $tipoAlert = "success";
            } else 
            {
                $messaggio = "Nessuna modifica apportata (i dati inseriti sono uguali ai precedenti).";
                $tipoAlert = "info";
            }
        } else 
        {
            $messaggio = "Errore query: " . $stmt->error;
            $tipoAlert = "danger";
        }
    }
    // GESTIONE RECUPERO DATI ESISTENTI (Solo se non sto salvando e ho un ID valido)
    else if(!empty($idDaModficare)) 
    {
        $query="SELECT * FROM Progetti WHERE ID_progetto=?";
        $stmt=$conn->prepare($query);

        if($stmt===false) 
        {
            echo "statement fallito";
        }

        $stmt->bind_param("i", $idDaModficare); 

        if($stmt->execute())
        {
            $res=$stmt->get_result();

            if($res===false) 
            {
                echo "ERRORE QUERY". mysqli_error($conn);
            }

            if($res->num_rows==0) 
            {
                header('Location: ../index.php'); // Meglio rimandare alla index
                exit;
            }

            $riga=$res->fetch_object();

            $titolo=$riga->titolo;
            $descrizione=$riga->descrizione;
            $nOrdine=$riga->n_ordine;
            $id_foto=$riga->id_foto;
        }
    }
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifica Progetto - Dashboard</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <link rel="stylesheet" href="../styles/style.css">
    

</head>
<body>
    
    <div class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10">
                
                <div class="card-panel">
                    
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="mb-0 fw-bold"><i class="bi bi-pencil-square text-primary me-2"></i>Modifica Progetto</h4>
                        <a href="../index.php" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-arrow-left me-1"></i> Torna alla Dashboard
                        </a>
                    </div>

                    <hr class="mb-4 text-muted">

                    <?php if(!empty($messaggio)): ?>
                        <div class="alert alert-<?php echo $tipoAlert; ?> alert-dismissible fade show" role="alert">
                            <?php 
                                if($tipoAlert == 'success') echo '<i class="bi bi-check-circle-fill me-2"></i>';
                                else if($tipoAlert == 'danger') echo '<i class="bi bi-exclamation-triangle-fill me-2"></i>';
                                else echo '<i class="bi bi-info-circle-fill me-2"></i>';
                                
                                echo htmlspecialchars($messaggio); 
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form action="modificaProgetto.php" method="POST" enctype="multipart/form-data">
                        
                        <input type="hidden" name="idDaModficare" value="<?php echo htmlspecialchars($idDaModficare); ?>">
                        <input type="hidden" name="id_foto_corrente" value="<?php echo htmlspecialchars($id_foto); ?>">
                        
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label for="titolo" class="form-label fw-semibold text-secondary">Titolo Progetto</label>
                                <input type="text" class="form-control" id="titolo" name="titolo" value="<?php echo htmlspecialchars($titolo);?>" required>
                            </div>
                            
                            <div class="col-md-4">
                                <label for="n_ordine" class="form-label fw-semibold text-secondary">N. Ordine</label>
                                <input type="number" class="form-control" id="n_ordine" name="n_ordine" value="<?php echo htmlspecialchars($nOrdine);?>" required>
                            </div>

                            <div class="col-12">
                                <label for="descrizione" class="form-label fw-semibold text-secondary">Descrizione</label>
                                <textarea class="form-control" id="descrizione" name="descrizione" rows="4" required><?php echo htmlspecialchars($descrizione);?></textarea>
                            </div>

                            <div class="col-12">
                                <label for="foto" class="form-label fw-semibold text-secondary">Modifica Foto</label>
                                <input type="file" class="form-control" id="foto" name="foto" accept="image/jpeg, image/png">
                                <div class="form-text mt-2">
                                    <?php if(!empty($id_foto)): ?>
                                        <br><span class="badge bg-info text-dark mt-1"><i class="bi bi-image me-1"></i> ID Foto Attuale: <?php echo htmlspecialchars($id_foto); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="col-12 text-end mt-4">
                                <button type="submit" class="btn btn-primary px-4 py-2 fw-bold shadow-sm">
                                    <i class="bi bi-save me-2"></i> Salva Modifiche
                                </button>
                            </div>
                        </div>
                        
                    </form>

                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>