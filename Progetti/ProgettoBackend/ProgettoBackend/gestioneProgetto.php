<?php
    session_start();
    include("daticonnessione.php");

    $conn = new mysqli($HOSTDB, $USERDB, $PASSDB, $NAMEDB, $PORTDB);

    if($conn->connect_error)
    {
        die("Connessione non stabilita");
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
                  WHERE p.data_eliminazione IS NULL AND p.titolo LIKE ?
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
                  WHERE p.data_eliminazione IS NULL
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
            $tabella = "<table border='1'>";
            $tabella .= "<tr>";
            $tabella .= "<th>ID_progetto</th>";
            $tabella .= "<th>titolo</th>";
            $tabella .= "<th>descrizione</th>";
            $tabella .= "<th>n_ordine</th>";
            $tabella .= "<th>data_eliminazione</th>";
            $tabella .= "<th>id_foto</th>";
            $tabella .= "<th>Modifica</th>";
            $tabella .= "<th>Elimina</th>";
            $tabella .= "</tr>";

            while($riga = $risultato->fetch_object())
            {
              
                
                $idProgetto = "";
                if ($riga->ID_progetto !== null) 
                {
                    $idProgetto = $riga->ID_progetto;
                }

                $titolo = "";
                if ($riga->titolo !== null)
                { 
                    $titolo = $riga->titolo; 
                }

                $descrizione = "";
                if ($riga->descrizione !== null) 
                {
                    $descrizione = $riga->descrizione; 
                }

                $nOrdine = "";
                if ($riga->n_ordine !== null) 
                {
                    $nOrdine = $riga->n_ordine;
                }

                $dataEliminazione = "";
                if ($riga->data_eliminazione !== null) 
                {
                    $dataEliminazione = $riga->data_eliminazione; 
                }

                $idFoto = "";
                if ($riga->id_foto !== null)
                { 
                    $idFoto = $riga->id_foto;
                }



                $tabella .= "<tr>";
                $tabella .= "<td>". htmlspecialchars($idProgetto) ."</td>";
                $tabella .= "<td>". htmlspecialchars($titolo) ."</td>";
                $tabella .= "<td>". htmlspecialchars($descrizione) ."</td>";
                $tabella .= "<td>". htmlspecialchars($nOrdine) ."</td>";
                $tabella .= "<td>". htmlspecialchars($dataEliminazione) ."</td>";
                $tabella .= "<td>". htmlspecialchars($idFoto) ."</td>";
                
                $tabella .= "<td><a href='modificaProgetto.php?id={$idProgetto}'>Modifica</a></td>";   
                

                $tabella .= "<td><a href='eliminaProgetto.php?id={$idProgetto}' onclick=\"return confirm('Sei sicuro di voler eliminare definitivamente questo progetto?');\" </a></td>"; 
                $tabella .= "</tr>";
            }

            $tabella .= "</table>";
        }
        else 
        {
            $tabella = "<p>Nessun progetto trovato.</p>";
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
    <title>Gestione Progetti</title>
</head>
<body>

<form action="gestioneProgetto.php" method="GET">
    <label for="filtro">Cerca per titolo: </label>
    <input type="text" id="filtro" name="filtro_titolo" value="<?php echo htmlspecialchars($filtroTitolo); ?>"><br>
    <input type="submit" value="Filtra"><br>
    <?php if($filtroTitolo !== ""): ?>
        <a href="gestioneProgetto.php"><button type="button">Mostra tutti</button></a><br>
    <?php endif; ?>
</form>
<br>

<a href="inserisciProgetto.php">Inserisci Progetto</a><br><br>

<?php
    echo $tabella;
?>

</body>
</html>