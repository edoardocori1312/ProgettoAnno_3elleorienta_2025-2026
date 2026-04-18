<?php
session_start();
include("connessione.php");

$conn = mysqli_connect($HOSTDB, $USERDB, $PASSDB, $NAMEDB);
$res  = $conn->query("SELECT * FROM links");

$immagini = ["sorprendo.jpg", "ScuolainChiaro.jpg", "iscrizioni.jpg", "iostudio.png", "liceo.jpg"];
$i = 0;
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
    <style>
        .card-img-top {
            cursor: pointer;
            transition: opacity 0.2s;
            width: 100%;
            height: 140px;
            object-fit: contain;
            background: #f8f9fa;
            object-position: center;
            display: block;
        }
        .card-img-top:hover {
            opacity: 0.85;
        }
    </style>
</head>
<body>

<?php include("navbar.html"); ?>

<main class="py-5">
    <div class="container">
        <div class="row row-cols-1 row-cols-md-3 g-4 align-items-start" id="cards-row">

            <?php
            $idx = 0;
            while ($r = $res->fetch_assoc()):
                $collapseId = "desc-" . $idx;
                $imgSrc = $immagini[$i++ % count($immagini)];
            ?>
            <div class="col">
                <div class="card shadow-sm">
                    <a href="<?= htmlspecialchars($r['url_link']) ?>" target="_blank" title="Vai a <?= htmlspecialchars($r['titolo']) ?>">
                        <img src="img/<?= $imgSrc ?>" class="card-img-top" alt="<?= htmlspecialchars($r['titolo']) ?>">
                    </a>
                    <div class="card-body">
                        <h5 class="card-title mb-2"><?= htmlspecialchars($r['titolo']) ?></h5>

                        <button
                            class="btn btn-outline-secondary btn-sm mb-2 w-100 d-flex justify-content-between align-items-center"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#<?= $collapseId ?>"
                            aria-expanded="false"
                            aria-controls="<?= $collapseId ?>">
                            <span>Espandi</span>
                            <i class="bi bi-chevron-down ms-1 toggle-icon"></i>
                        </button>

                        <div class="collapse collapse-description" id="<?= $collapseId ?>">
                            <p class="card-text mb-2"><?= htmlspecialchars($r['descrizione']) ?></p>
                        </div>

                        <a href="<?= htmlspecialchars($r['url_link']) ?>" target="_blank" class="btn btn-success w-100 mt-2">Vai al link</a>
                    </div>
                </div>
            </div>
            <?php
            $idx++;
            endwhile;
            ?>

        </div>
    </div>
</main>

<?php include("footer.html"); ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(function(btn) {
        var target = document.querySelector(btn.getAttribute('data-bs-target'));
        var icon = btn.querySelector('.toggle-icon');

        target.addEventListener('show.bs.collapse', function() {
            icon.classList.replace('bi-chevron-down', 'bi-chevron-up');
            btn.querySelector('span').textContent = 'Comprimi';
        });
        target.addEventListener('hide.bs.collapse', function() {
            icon.classList.replace('bi-chevron-up', 'bi-chevron-down');
            btn.querySelector('span').textContent = 'Espandi';
        });
    });
</script>
</body>
</html>