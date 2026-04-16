<?php

    session_start();

    include("daticonnessione.php");
    $conn = new mysqli($HOSTDB, $USERDB, $PASSDB, $NAMEDB, $PORTDB);

    if($conn->connect_error)
    {
        die("Errore connessione: " . $conn->connect_error);
    }


    if(isset($_POST['titolo']) && isset($_POST['descrizione']) && isset($_POST['n_ordine']))
    {
        $titolo=$_POST['titolo'];
        $descrizione=$_POST['descrizione'];
        $n_ordine=$_POST['n_ordine'];

        if(empty($_POST['id_foto']))
        {
            $id_foto = null;
        }
        else
        {
            $id_foto = $_POST['id_foto'];
        }

       

        $query="INSERT INTO Progetti (titolo, descrizione, n_ordine, id_foto) VALUES (?, ?, ?, ?)";

        $stmt=$conn->prepare($query);

        if($stmt===false)
        {
            die("Query fallita");
        }

        $stmt->bind_param("ssii", $titolo, $descrizione, $n_ordine, $id_foto);

        if($stmt->execute())
        {
            echo "Operazione effettuata con successo";
        }
        else
        {
            die("Statement fallito");
        }


    }

?>














<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inserisci progetto</title>
</head>
<body>
    <form action="inserisciProgetto.php" method="POST">
        
        <label for="titolo">Titolo: </label>
        <input type="text" name="titolo"><br>

        <label for="Descrizione">Descrizione: </label>
        <input type="text" name="descrizione"><br>

        <label for="n_ordine">n_ordine: </label>
        <input type="text" name="n_ordine">

        <input type="hidden" name="Data_Eliminazione"><br>

        <label for="id_foto">id_foto: </label>
        <input type="text" name="id_foto"><br>

        <input type="submit" value="Inserisci Progetto">
    </form>

    <a href="gestioneProgetto.php">Gestione Progetti</a>
</body>
</html>