<?php
// Start della sessione
session_start();

// Includo i dati di connessione
include_once("../connessione/db.php");

// Connessione al database
$conn = new mysqli($HOSTDB, $USERDB, $PASSDB, $NOMEDB);
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}

// Recupero i link attivi con il path della foto tramite LEFT JOIN
$res = $conn->query("
    SELECT l.*, f.path_foto
    FROM Links l
    LEFT JOIN Foto f ON f.ID_foto = l.id_foto AND f.data_eliminazione IS NULL
    WHERE l.data_eliminazione IS NULL
    ORDER BY l.n_ordine ASC
");
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Link Utili - 3elleOrienta</title>
    <link rel="stylesheet" href="../stile.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

<?php include_once("navbar.html"); ?>

<main class="py-5">
    <div class="container">

        <h1 class="mb-4">Link Utili</h1>

        <div class="row row-cols-1 row-cols-md-3 g-4 align-items-start">
            <?php
            $i = 0;
            while ($a = $res->fetch_assoc()):
                $collapseId = "desc-" . $i;

                // Sanitizzazione output
                $url   = htmlspecialchars($a['url_link']);
                $title = htmlspecialchars($a['titolo']);
                $desc  = htmlspecialchars($a['descrizione']);

                // path_foto arriva direttamente dalla JOIN (null se nessuna foto associata)
                $imgSrc = !empty($a['path_foto'])
                    ? htmlspecialchars($a['path_foto'])
                    : "img/placeholder_link.png";
            ?>
            <div class="col">
                <div class="card h-100 shadow-sm">

                    <!-- Immagine cliccabile che porta al link esterno -->
                    <a href="<?= $url ?>" target="_blank" rel="noopener noreferrer">
                        <img src="<?= $imgSrc ?>"
                             class="card-img-top"
                             alt="<?= $title ?>"
                             style="object-fit:cover; height:160px;">
                    </a>

                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title mb-2"><?= $title ?></h5>

                        <!-- Toggle descrizione collassabile -->
                        <button class="btn btn-outline-secondary btn-sm mb-2 w-100 d-flex justify-content-between align-items-center"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#<?= $collapseId ?>">
                            <span>Espandi</span>
                            <i class="bi bi-chevron-down toggle-icon"></i>
                        </button>

                        <div class="collapse" id="<?= $collapseId ?>">
                            <p class="card-text mb-2"><?= $desc ?></p>
                        </div>

                        <a href="<?= $url ?>"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="btn btn-primary w-100 mt-auto">Vai al link</a>
                    </div>

                </div>
            </div>
            <?php
            $i++;
            endwhile;

            $res->free();
            $conn->close();
            ?>
        </div>
    </div>
</main>

<?php include_once("../footer.html"); ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Gestione icona e testo dei pulsanti collapse
document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(btn => {
    const target = document.querySelector(btn.dataset.bsTarget);
    const icon   = btn.querySelector('.toggle-icon');
    const span   = btn.querySelector('span');

    target.addEventListener('show.bs.collapse', () => {
        icon.classList.replace('bi-chevron-down', 'bi-chevron-up');
        span.textContent = 'Comprimi';
    });
    target.addEventListener('hide.bs.collapse', () => {
        icon.classList.replace('bi-chevron-up', 'bi-chevron-down');
        span.textContent = 'Espandi';
    });
});
</script>

</body>
</html>