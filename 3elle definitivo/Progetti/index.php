<?php
    session_start();
    include("../connessione/db.php");
	include("../connessione/SostituisciLink.php");
	$conn = new mysqli($HOSTDB, $USERDB, $PASSDB, $NOMEDB);
	$conn->set_charset("utf8mb4");

    if($conn->connect_error) {
        die("Connessione non stabilita: " . $conn->connect_error);
    }

    $query = "SELECT p.titolo, p.descrizione, f.path_foto 
              FROM Progetti p
              LEFT JOIN Foto f ON p.id_foto = f.ID_foto
              WHERE p.data_eliminazione IS NULL
              ORDER BY p.n_ordine ASC";
              
    $stmt = $conn->prepare($query);
    
    if($stmt === false) {
        die("Errore nello statement");
    }

    $htmlProgetti = "";

    if($stmt->execute()) {
        $risultato = $stmt->get_result();
        
        if($risultato->num_rows > 0) {
            while($riga = $risultato->fetch_object()) {
                $titolo = $riga->titolo ? htmlspecialchars($riga->titolo) : "Titolo Progetto";
                $descrizione = $riga->descrizione ? htmlspecialchars($riga->descrizione) : "";
				$descrizione = SostituisciLink(nl2br($descrizione));
                if ($riga->path_foto && trim($riga->path_foto) !== '')
				{
					$path_foto = htmlspecialchars($riga->path_foto);
				} 
				else
				{
					$path_foto = "../pictures/nofoto.jpg";
				}

                $htmlProgetti .= "
<article class='custom-card'>
    <div class='custom-card-content'>
        <h2 class='custom-card-title'>{$titolo}</h2>
        <div class='custom-card-img'>
            <img src='{$path_foto}' alt='{$titolo}'>
        </div>
        <div class='custom-card-desc'>{$descrizione}</div>
    </div>
</article>";
            }
        } else {
            $htmlProgetti = "<p class='text-center py-5'>Nessun progetto disponibile.</p>";
        }
    }
    $stmt->close();
    $conn->close();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Progetti | 3elleorienta</title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="stile.css?v=<?php echo filemtime('stile.css'); ?>"> 
</head>
<body>

    <?php include("navbar.html"); ?>

    <section class="hero-page">
        <h1>Progetti</h1>
        <p>Esplora le iniziative e i percorsi formativi</p>
    </section>

    <main class="lista-card-container">
        <?php echo $htmlProgetti; ?>
    </main>

    <?php include("../footer.html"); ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>