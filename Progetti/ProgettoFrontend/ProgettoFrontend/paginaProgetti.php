<?php

    session_start();
    include("daticonnessione.php");


    $conn = new mysqli($HOSTDB, $USERDB, $PASSDB, $NAMEDB, $PORTDB);

    if($conn->connect_error)
    {
        die("Connessione non stabilita: " . $conn->connect_error);
    }

    $query = "SELECT p.titolo, p.descrizione, f.path_foto 
              FROM Progetti p
              LEFT JOIN Foto f ON p.id_foto = f.ID_foto
              WHERE p.data_eliminazione IS NULL
              ORDER BY p.n_ordine ASC";
              
    $stmt = $conn->prepare($query);
    
    if($stmt === false)
    {
        die("Errore nello statement");
    }

    $nuovoProgetto = "";

    
    if($stmt->execute())
    {
        $risultato = $stmt->get_result();
        
        if($risultato->num_rows > 0)
        {
            while($riga = $risultato->fetch_object())
            {
                // Gestione del titolo 
                if ($riga->titolo !== null)
                {
                    $titolo = htmlspecialchars($riga->titolo);
                }
                else
                {
                    $titolo = "Titolo non disponibile";
                }
                
                // Gestione della descrizione 
                if ($riga->descrizione !== null)
                {
                    $descrizione = htmlspecialchars($riga->descrizione);
                }
                else
                {
                    $descrizione = "Nessuna descrizione presente.";
                }
                
                // Gestione della foto 
                if ($riga->path_foto !== null && trim($riga->path_foto) !== '')
                {
                    $path_foto = htmlspecialchars($riga->path_foto);
                }
                else
                {
                    
                    $path_foto = ""; 
                }

                $nuovoProgetto .= "
                <div>
                    <img src='{$path_foto}' alt='Foto del progetto: {$titolo}' class='card-img'>
                    <h2>{$titolo}</h2>
                    <p>{$descrizione}</p>
                </div>";
            }
        }
        else 
        {
            $nuovoProgetto = "<p>Nessun progetto disponibile al momento.</p>";
        }
    }
    else
    {
        $nuovoProgetto = "<p>Si è verificato un errore durante il caricamento dei progetti.</p>";
    }

    $stmt->close();
    $conn->close();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Progetti</title>
</head>
<body>

    <h1>Progetti</h1>

    <?php 
        echo $nuovoProgetto; 
    ?>

</body>
</html>