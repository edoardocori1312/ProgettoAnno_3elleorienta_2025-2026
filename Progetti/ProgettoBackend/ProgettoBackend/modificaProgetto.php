<?php
    session_start();
    include("daticonnessione.php");

    $conn=new mysqli($HOSTDB, $USERDB, $PASSDB, $NAMEDB, $PORTDB);

    $titolo = "";
    $descrizione = "";
    $nOrdine = "";
    $id_foto = "";
    $idDaModficare = "";

    if(isset($_GET['id']))
    {
        $idDaModficare = $_GET['id'];

    } elseif(isset($_POST['idDaModficare'])) 
    {
        $idDaModficare = $_POST['idDaModficare'];
    }

    if(isset($_POST['titolo']) && isset($_POST['descrizione']) && isset($_POST['n_ordine']))
    {
        $titolo=$_POST['titolo'];
        $descrizione=$_POST['descrizione'];
        $nOrdine=$_POST['n_ordine'];
        
        if(empty($_POST['id_foto'])) 
        {
            $id_foto = null;
        } else
        {
            $id_foto = $_POST['id_foto'];
        }

        $query="UPDATE progetti SET titolo=?, descrizione=?, n_ordine=?, id_foto=? WHERE ID_Progetto=?";
        $stmt=$conn->prepare($query);


        $stmt->bind_param("ssiii", $titolo, $descrizione, $nOrdine, $id_foto, $idDaModficare);

        if($stmt->execute())
        {
            $righeProcessate=$conn->affected_rows;
                
            if($righeProcessate>0) 
            {
                echo "Modifica avvenuta con successo!";
            } else 
            {
                echo "Nessuna modifica apportata.";
            }
        } else 
        {
            echo "Errore query: " . $stmt->error;
        }
    }
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
                header('Location: gestioneProgetto.php');
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
    <title>Modifica Progetto</title>
</head>
<body>
    <form action="modificaProgetto.php" method="POST">
        
        <input type="hidden" name="idDaModficare" value="<?php echo $idDaModficare; ?>">
        
        <label for="titolo">Titolo: </label>
        <input type="text" name="titolo" value="<?php echo $titolo;?>"><br><br>

        <label for="Descrizione">Descrizione: </label>
        <input type="text" name="descrizione" value="<?php echo $descrizione;?>"><br><br>

        <label for="n_ordine">n_ordine: </label>
        <input type="number" name="n_ordine" value="<?php echo $nOrdine;?>"><br><br>

        <label for="id_foto">id_foto: </label>
        <input type="number" name="id_foto" value="<?php echo $id_foto;?>"><br><br>

        <input type="submit" value="Salva modifica">

        <br><br>
        <a href="gestioneProgetto.php">Torna alla gestione Progetto</a>
    </form>
</body>
</html>