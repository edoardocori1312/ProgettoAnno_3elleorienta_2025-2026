<?php
    session_start();
    include("daticonnessione.php");

    $conn = new mysqli($HOSTDB, $USERDB, $PASSDB, $NAMEDB, $PORTDB);

    $messaggio = ""; 

    if(isset($_GET['id']) && !empty($_GET['id']))
    {
        $idDaEliminare = $_GET['id'];
        
        $query = "DELETE FROM progetti WHERE ID_Progetto=?";
        $stmt = $conn->prepare($query);

        if($stmt === false) 
        {
            $messaggio = "Errore nella preparazione della query.";
        }
        else
        {
            $stmt->bind_param("i", $idDaEliminare);

            if($stmt->execute())
            {
                if($conn->affected_rows > 0) 
                {
                    $messaggio = "Eliminazione avvenuta con successo!";
                } else 
                {
                    $messaggio = "Nessun progetto eliminato";
                }
            } else 
            {
                $messaggio = "Errore query: " . $stmt->error;
            }
            $stmt->close();
        }
    }
    
    $conn->close();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
</head>
<body>

    <script>

        alert("<?php echo addslashes($messaggio); ?>");
        window.location.href = "gestioneProgetto.php";
    </script>

</body>
</html>