<?php
    session_start();
    include("/home/uawit4pc/domains/3elleorienta.sviluppo.host/public_html/connessione/db.php");
    include("../connessione/SostituisciLink.php");

    $conn = new mysqli($HOSTDB, $USERDB, $PASSDB, $NOMEDB);

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
                $descrizione = SostituisciLink($descrizione);
                $path_foto = ($riga->path_foto && trim($riga->path_foto) !== '') ? htmlspecialchars($riga->path_foto) : "img/placeholder.png";

                $htmlProgetti .= "
                <article class='progetto-aperto'>
                    <div class='row align-items-start'>
                        <div class='col-md-4 mb-3'>
                            <img src='{$path_foto}' alt='{$titolo}' class='img-fluida'>
                        </div>
                        <div class='col-md-8'>
                            <h2 class='titolo-progetto-aperto'>{$titolo}</h2>
                            <div class='testo-progetto-aperto'>{$descrizione}</div>
                        </div>
                    </div>
                </article>
                <hr class='separatore-progetto'>";
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
    <link rel="stylesheet" href="stile.css">
    <link rel="stylesheet" href="../stile.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

    <?php include("navbar.html"); ?>

    <script>
        document.querySelectorAll('.nav-link').forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === 'paginaProgetti.php') {
                link.classList.add('active');
            }
        });
    </script>

    <main class="main-wrapper">
        <h1 class="pagina-titolo">Progetti</h1>

        <div class="lista-progetti-aperta">
            <?php echo $htmlProgetti; ?>
        </div>
    </main>

    <?php include("../footer.html"); ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>