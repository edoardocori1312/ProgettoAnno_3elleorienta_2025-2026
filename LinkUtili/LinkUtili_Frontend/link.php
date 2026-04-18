<?php
session_start();
include("connessione.php");

// Connessione al database
$conn = mysqli_connect($HOSTDB, $USERDB, $PASSDB, $NAMEDB);

// Recupero tutti i link dalla tabella
$res = $conn->query("SELECT * FROM links");

// Immagini da ruotare ciclicamente sulle card
$immagini = ["sorprendo.jpg", "ScuolainChiaro.jpg", "iscrizioni.jpg", "iostudio.png", "liceo.jpg"];
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Link Utili - 3elleorienta</title>
    <link rel="stylesheet" href="style/stile.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

<?php include("navbar.html"); ?>

<main class="py-5">
    <div class="container">
        <div class="row row-cols-1 row-cols-md-3 g-4 align-items-start">

            <?php
            $id = 0;
            while ($r = $res->fetch_assoc()):
                // ID univoco per il collapse di ogni card
                $collapseId = "desc-" . $id;

                // Immagine ciclica basata sull'indice
                $img = $immagini[$id % count($immagini)];

                //Controllo che non ci siano caratteri strani
                $url   = htmlspecialchars($r['url_link']);
                $title = htmlspecialchars($r['titolo']);
                $desc  = htmlspecialchars($r['descrizione']);
            ?>
            <div class="col">
                <div class="card shadow-sm">

                    <!-- Immagine cliccabile che porta al link -->
                    <a href="<?= $url ?>" target="_blank">
                        <img src="img/<?= $img ?>" class="card-img-top" alt="<?= $title ?>">
                    </a>

                    <div class="card-body">
                        <h5 class="card-title mb-2"><?= $title ?></h5>

                        <!-- Bottone per mostrare/nascondere la descrizione -->
                        <button class="btn btn-outline-secondary btn-sm mb-2 w-100 d-flex justify-content-between align-items-center"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#<?= $collapseId ?>">
                            <span>Espandi</span>
                            <i class="bi bi-chevron-down toggle-icon"></i>
                        </button>

                        <!-- Descrizione collassabile -->
                        <div class="collapse" id="<?= $collapseId ?>">
                            <p class="card-text mb-2"><?= $desc ?></p>
                        </div>

                        <!-- Bottone principale di navigazione -->
                        <a href="<?= $url ?>" target="_blank" class="btn btn-success w-100 mt-2">Vai al link</a>
                    </div>
                </div>
            </div>
            <?php
            $id++;
            endwhile;

            // Chiusura connessione dopo l'uso
            $conn->close();
            ?>

        </div>
    </div>
</main>

<?php include("footer.html"); ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Seleziona tutti i bottoni che attivano un collapse e li itera
    document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(btn => 
    {
        // Recupera il pannello collapse associato al bottone
        const target = document.querySelector(btn.getAttribute('data-bs-target'));

        // Recupera l'icona freccia e il testo "Espandi/Comprimi" all'interno del bottone
        const icon  = btn.querySelector('.toggle-icon');
        const label = btn.querySelector('span');

        // Quando il collapse si sta APRENDO: freccia su + testo "Comprimi"
        target.addEventListener('show.bs.collapse', () => {
            icon.classList.replace('bi-chevron-down', 'bi-chevron-up');
            label.textContent = 'Comprimi';
        });

        // Quando il collapse si sta CHIUDENDO: freccia giù + testo "Espandi"
        target.addEventListener('hide.bs.collapse', () => {
            icon.classList.replace('bi-chevron-up', 'bi-chevron-down');
            label.textContent = 'Espandi';
        });
    });
</script>
</body>
</html>