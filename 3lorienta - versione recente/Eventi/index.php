<?php
/**
 * index.php  —  pagina principale 3ElleOrienta
 * Include connessione.php e events.php per i dati,
 * poi renderizza tutto il layout in un unico file.
 */
session_start();
include_once '../connessione/db.php';
include_once 'events.php';   // popola $events (con filtro lat/lng se presenti in GET)

$filtro_attivo = (isset($_GET['lat']) && isset($_GET['lng'])) || isset($_GET['data']);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3ElleOrienta</title>

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <!-- Bootstrap CSS + icone -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
          integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Foglio di stile unico del progetto -->
    <link rel="stylesheet" href="stile.css">
</head>
<body>

<?php include 'navbar.html'; ?>

<div class="hero-banner">
    <img src="https://www.3elleorienta.it/wp-content/themes/eduma/images/bg-page.jpg" alt="Banner">
    <div class="page-title-wrapper">
        <div class="banner-wrapper container">
            <h1>Eventi</h1>
        </div>
    </div>
</div>


<main class="py-4" style="min-height: 80vh;">
    <div class="container-fluid">

        <!-- Pulsante scarica calendario -->
        <div style="text-align: right; margin-bottom: 12px; padding-right: 20px;">
            <a href="calendario.php" class="btn-calendario" download>
                <i class="bi bi-calendar-pdf"></i> Scarica il calendario annuale degli eventi
            </a>
        </div>

        

        <!-- Barra di ricerca città -->
        <div class="search-container" aria-label="Ricerca luogo">
            <div class="input-group">
                <input id="filtro_citta" type="search" class="form-control"
                       placeholder="Cerca luogo… (es. Jesi)"
                       autocomplete="off" aria-label="Cerca luogo">
            </div>
            <ul id="suggestions" class="list-group mt-2" role="listbox" aria-label="Suggerimenti"></ul>
        </div>

        <!-- Banner filtro attivo -->
        <?php if ($filtro_attivo): ?>
        <div class="container mb-2" span="2">
                <button id="btn_reset_filtro" class="btn btn-sm btn-outline-secondary ms-3">
                    Mostra tutti
                </button>
            </div>
        </div>
        <?php endif; ?>

        <!-- ── Sezione eventi / card ── -->
        <section id="events_section_wrapper" class="mt-2" aria-label="Sezione Eventi">
            <div id="events_section" class="cards-wrapper" aria-live="polite">
                    <?php foreach ($events as $evt):
                        $img        = $evt['image_path'];
                        $date_inizio = $evt['ora_inizio'] ? date('d/m/Y', strtotime($evt['ora_inizio'])) : '';
                        $ora_inizio  = $evt['ora_inizio'] ? date('H:i', strtotime($evt['ora_inizio'])) : '';
                        $ora_fine    = $evt['ora_fine']    ? date('H:i', strtotime($evt['ora_fine']))    : '';
                        $address    = trim(($evt['address'] ?? '') . ' ' . ($evt['number'] ?? ''));
                        $summary    = htmlspecialchars($evt['summary'] ?? substr($evt['description'] ?? '', 0, 140));
                        $evtId      = htmlspecialchars($evt['id']);
                        $distanza    = $evt['distanza_km'] ?? null;
                        $prenotabile = (int)($evt['prenotabile'] ?? 0);
                    ?>
                    <article class="card info-card"
                             role="article"
                             aria-labelledby="evt-<?= $evtId ?>-title"
                             data-date="<?= htmlspecialchars($date_inizio) ?>"
                             data-address="<?= htmlspecialchars($address) ?>"
                             data-ora-inizio="<?= htmlspecialchars($ora_inizio) ?>"
                             data-ora-fine="<?= htmlspecialchars($ora_fine) ?>"
                             data-prenotabile="<?= $prenotabile ?>"
                             data-school="<?= htmlspecialchars($evt['school_name'] ?? '') ?>"
                             data-description="<?= htmlspecialchars($evt['description'] ?? '') ?>">
                        <img src="<?= htmlspecialchars($img) ?>"
                             class="card-img-top"
                             alt="<?= htmlspecialchars($evt['title']) ?>">
                        <div class="card-body">
                            <h5 id="evt-<?= $evtId ?>-title" class="card-title">
                                <?= htmlspecialchars($evt['title']) ?>
                            </h5>
                            <p class="card-text"><?= $summary ?></p>
                            <div class="mt-2">
                                <?php if ($date_inizio): ?>
                                <small class="text-muted d-block">
                                    <i class="bi bi-calendar"></i>
                                    <?= htmlspecialchars($date_inizio) ?>
                                </small>
                                <?php endif; ?>
                                <?php if ($address): ?>
                                <small class="text-muted d-block">
                                    <i class="bi bi-map"></i>
                                    <?= htmlspecialchars($address) ?>
                                </small>
                                <?php endif; ?>
                                <?php if ($ora_inizio): ?>
                                <small class="text-muted d-block">
                                    <i class="bi bi-clock"></i>
                                    <?= htmlspecialchars($ora_inizio . ($ora_fine ? ' – ' . $ora_fine : '')) ?>
                                </small>
                                <?php endif; ?>
                                <?php if ($distanza !== null): ?>
                                <small class="text-muted d-block">
                                    <i class="bi bi-geo-alt"></i> <?= $distanza ?> km
                                </small>
                                <?php endif; ?>
                                <small class="d-block mt-1">
                                    <?php if ($prenotabile): ?>
                                    <span class="badge bg-success"><i class="bi bi-check-circle"></i> Prenotabile</span>
                                    <?php else: ?>
                                    <span class="badge bg-secondary"><i class="bi bi-x-circle"></i> Non prenotabile</span>
                                    <?php endif; ?>
                                </small>
                            </div>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- ── Modal dettaglio card ── -->
        <div id="cardModal" class="card-modal" aria-hidden="true" role="dialog" aria-modal="true">
            <div class="card-modal-backdrop" data-close></div>
            <div class="card-modal-inner" role="document">
                <button id="cardModalClose" class="card-modal-close" aria-label="Chiudi" data-close>&times;</button>
                <img id="modalImg" src="" alt="" class="modal-img">
                <div class="modal-content">
                    <h4 id="modalTitle"></h4>
                    <p id="modalText"></p>
                    <div id="modalMeta" class="mt-3">
                        <p id="modalDate"  class="text-muted mb-1"></p>
                        <p id="modalAddress" class="text-muted mb-1"></p>
                        <p id="modalOrario" class="text-muted mb-1"></p>
                        <p id="modalPrenotabile" class="mb-0"></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Mini-mappa circolare ── -->
        <div class="map-container">
            <!-- Calendario filtro data -->
            <div class="date-filter-control">
                <label for="filtro_data">
                    <i class="bi bi-calendar3"></i> Filtra per data
                </label>
                <input id="filtro_data" type="date"
                       value="<?= htmlspecialchars($_GET['data'] ?? '') ?>"
                       aria-label="Seleziona una data per filtrare gli eventi">
                <?php if (isset($_GET['data'])): ?>
                <button id="btn_reset_data" class="btn-reset-data" title="Rimuovi filtro data">
                    <i class="bi bi-x-circle"></i> Rimuovi filtro
                </button>
                <?php endif; ?>
            </div>
            <div class="radius-control">
                <label for="radius_slider">
                     Raggio: <span id="radius_value">30</span> km
                </label>
                <input id="radius_slider" type="range"
                            min="5" max="100" step="5" value="30"
                            aria-label="Raggio di ricerca in km">
                        </div>
                    <div id="map">
                <div id="coordLabel" class="coord-label hidden" aria-hidden="true"></div>
            </div>
        </div>

    </div>
</main>


<?php include 'footer.html'; ?>


<!-- ── Script ── -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="main.js"></script>

</body>
</html>