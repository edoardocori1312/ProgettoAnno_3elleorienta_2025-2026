<?php
  session_start();
  include("../daticonnessione.php");
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eventi – 3elleorienta</title>
    <link rel="stylesheet" href="../stile/stile.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <link rel="stylesheet" href="../stile/provaMappa.css"/>
    <style>
        /* Annulla gli override di provaMappa.css sulla navbar Bootstrap */
        .site-header {
            display: block !important;
            position: static !important;
            height: auto !important;
            max-height: none !important;
            overflow: visible !important;
            padding: 0 !important;
        }
        html, body {
            height: auto !important;
            padding-top: 0 !important;
        }
    </style>
</head>

<body>

<?php include("../stile/navbar.html"); ?>

<main style="min-height: calc(100vh - 200px);">
    <div class="container-fluid">

        <!-- Riga Principale -->
        <div class="search-container" aria-hidden="false">
            <!-- La header principale ora è in cima alla pagina; qui rimane solo il filtro/search -->
            <form method="get" action="index.php">
                <div class="input-group">
                    <!-- Cambia placeholder/width per adattare al tuo pubblico -->
                    <input id="filtro_citta" name="citta" type="search" class="form-control" placeholder="Cerca luogo... (es. Milano)" autocomplete="off" aria-label="Cerca luogo" value="<?php echo isset($_GET['citta']) ? htmlspecialchars($_GET['citta']) : ''; ?>">
                    <button class="btn btn-outline-secondary" type="submit" aria-label="Cerca">🔍</button>
                </div>
            </form>
            <!-- I suggerimenti vengono popolati dinamicamente da provaMappa.js -->
            <ul id="suggestions" class="list-group mt-2" role="listbox" aria-label="Suggerimenti"></ul>
        </div>

        <section class="cards-wrapper" aria-label="Schede informative">
            <?php
                include("events.php");
            ?>
        </section>

        <!-- Modal semplice per visualizzare dettagli card (senza dipendenze JS esterne) -->
        <!-- Nota: provaMappa.js apre/chiude il modal copiando l'immagine/titolo/test nella finestra -->
        <div id="cardModal" class="card-modal" aria-hidden="true" role="dialog" aria-modal="true">
            <div class="card-modal-backdrop" data-close></div>
            <div class="card-modal-inner" role="document">
                <button id="cardModalClose" class="card-modal-close" aria-label="Chiudi" data-close>&times;</button>
                <img id="modalImg" src="" alt="" class="modal-img">
                <div class="modal-content">
                    <h4 id="modalTitle"></h4>
                    <p id="modalText"></p>
                </div>
            </div>
        </div>

        <div id="map">
            <div id="coordLabel" class="coord-label hidden" aria-hidden="true"></div>
        </div>

    </div>
</main>

<?php include("../stile/footer.html"); ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="provaMappa.js"></script>

</body>
</html>