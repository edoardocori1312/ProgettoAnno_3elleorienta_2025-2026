<?php
session_start();
include_once '../connessione/db.php';
include_once 'events.php';

$filtro_attivo = (isset($_GET['lat']) && isset($_GET['lng'])) || isset($_GET['data']) || (isset($_GET['cerca']) && $_GET['cerca'] !== '');

// Modalità fragment: restituisce solo card + paginazione (usata dal fetch in main.js)
if (isset($_GET['fragment'])) {
    header('Content-Type: text/html; charset=UTF-8');
    foreach ($events as $evt):
        $img            = $evt['image_path'];
        $date_inizio    = $evt['ora_inizio'] ? date('d/m/Y', strtotime($evt['ora_inizio'])) : '';
        $ora_inizio     = $evt['ora_inizio'] ? date('H:i',   strtotime($evt['ora_inizio'])) : '';
        $ora_fine       = $evt['ora_fine']   ? date('H:i',   strtotime($evt['ora_fine']))   : '';
        $address        = trim(($evt['address'] ?? '') . ' ' . ($evt['number'] ?? ''));
        $summary        = htmlspecialchars($evt['summary'] ?? substr($evt['description'] ?? '', 0, 140));
        $evtId          = htmlspecialchars($evt['id']);
        $distanza       = $evt['distanza_km'] ?? null;
        $prenotabile    = (int)($evt['prenotabile'] ?? 0);
        $coord_lat      = $evt['coord_lat'] ?? null;
        $coord_lng      = $evt['coord_lng'] ?? null;
        $school_name    = $evt['school_name'] ?? null;
        $school_address = trim(($evt['school_via'] ?? '') . ' ' . ($evt['school_n_civico'] ?? ''));
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
         data-description="<?= htmlspecialchars($evt['description'] ?? '') ?>"
         data-lat="<?= $coord_lat !== null ? htmlspecialchars($coord_lat) : '' ?>"
         data-lng="<?= $coord_lng !== null ? htmlspecialchars($coord_lng) : '' ?>"
         data-title="<?= htmlspecialchars($evt['title']) ?>"
         data-school-address="<?= htmlspecialchars($school_address) ?>">
    <img src="<?= htmlspecialchars($img) ?>" class="card-img-top" alt="<?= htmlspecialchars($evt['title']) ?>">
    <div class="card-body">
        <h5 id="evt-<?= $evtId ?>-title" class="card-title"><?= htmlspecialchars($evt['title']) ?></h5>
        <p class="card-text"><?= $summary ?></p>
        <div class="mt-2">
            <?php if ($date_inizio): ?><small class="text-muted d-block"><i class="bi bi-calendar"></i> <?= htmlspecialchars($date_inizio) ?></small><?php endif; ?>
            <?php if ($address): ?><small class="text-muted d-block"><i class="bi bi-map"></i> <?= htmlspecialchars($address) ?></small><?php endif; ?>
            <?php if ($ora_inizio): ?><small class="text-muted d-block"><i class="bi bi-clock"></i> <?= htmlspecialchars($ora_inizio . ($ora_fine ? ' – ' . $ora_fine : '')) ?></small><?php endif; ?>
            <?php if ($distanza !== null): ?><small class="text-muted d-block"><i class="bi bi-geo-alt"></i> <?= $distanza ?> km</small><?php endif; ?>
            <?php if ($school_name): ?><small class="text-muted d-block"><i class="bi bi-building"></i> <?= htmlspecialchars($school_name) ?></small><?php endif; ?>
            <?php if ($school_address && $school_address !== ' '): ?><small class="text-muted d-block"><i class="bi bi-signpost"></i> <?= htmlspecialchars($school_address) ?></small><?php endif; ?>
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
<?php endforeach;

if ($total_pages > 1):
    $base_params = $_GET;
    unset($base_params['page'], $base_params['fragment']);
    $base_url = '?' . http_build_query($base_params);
    $sep = empty($base_params) ? '' : '&';
?>
<nav class="pagination-nav" aria-label="Navigazione pagine eventi" style="grid-column:1/-1">
    <?php if ($page_current > 1): ?>
    <a href="<?= $base_url . $sep ?>page=<?= $page_current - 1 ?>" class="page-btn page-btn--arrow"><i class="bi bi-chevron-left"></i></a>
    <?php else: ?>
    <span class="page-btn page-btn--arrow page-btn--disabled"><i class="bi bi-chevron-left"></i></span>
    <?php endif; ?>
    <?php $window = 2;
    for ($p = 1; $p <= $total_pages; $p++):
        $show = ($p === 1 || $p === $total_pages || ($p >= $page_current - $window && $p <= $page_current + $window));
        $ellipsis_before = ($p === 2 && $page_current - $window > 2);
        $ellipsis_after  = ($p === $total_pages - 1 && $page_current + $window < $total_pages - 1);
        if ($ellipsis_before): ?><span class="page-ellipsis">…</span><?php endif;
        if ($show): ?>
        <a href="<?= $base_url . $sep ?>page=<?= $p ?>" class="page-btn<?= $p === $page_current ? ' page-btn--active' : '' ?>" <?= $p === $page_current ? 'aria-current="page"' : '' ?>><?= $p ?></a>
        <?php endif;
        if ($ellipsis_after): ?><span class="page-ellipsis">…</span><?php endif;
    endfor; ?>
    <?php if ($page_current < $total_pages): ?>
    <a href="<?= $base_url . $sep ?>page=<?= $page_current + 1 ?>" class="page-btn page-btn--arrow"><i class="bi bi-chevron-right"></i></a>
    <?php else: ?>
    <span class="page-btn page-btn--arrow page-btn--disabled"><i class="bi bi-chevron-right"></i></span>
    <?php endif; ?>
</nav>
<p class="pagination-info" style="grid-column:1/-1">Pagina <?= $page_current ?> di <?= $total_pages ?> &nbsp;·&nbsp; <?= $total_events ?> eventi totali</p>
<?php endif;
    exit;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3ElleOrienta</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
          integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="stile.css">
</head>
<body>

<?php include 'navbar.html'; ?>

<div class="hero-banner">
    <div class="banner-wrapper container">
        <h1>Eventi</h1>
        <p class="hero-subtitle">Esplora gli eventi disponibili e partecipa a quello che più ti attira</p>
    </div>
</div>

<main class="py-4" style="min-height: 80vh;">

    <!-- ── Layout 3 colonne ── -->
    <div class="three-col-layout">

        <!-- Colonna sinistra: bianca/vuota -->
        <div class="col-left"></div>

        <!-- Colonna centrale: card + paginazione -->
        <div class="col-center">

            

            <?php if ($filtro_attivo): ?>
            <div class="mb-3">
                <button id="btn_reset_filtro" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-x"></i> Mostra tutti
                </button>
            </div>
            <?php endif; ?>

            <!-- Card eventi -->
            <div id="events_section" class="cards-wrapper" aria-live="polite">
                <?php foreach ($events as $evt):
                    $img            = $evt['image_path'];
                    $date_inizio    = $evt['ora_inizio'] ? date('d/m/Y', strtotime($evt['ora_inizio'])) : '';
                    $ora_inizio     = $evt['ora_inizio'] ? date('H:i',   strtotime($evt['ora_inizio'])) : '';
                    $ora_fine       = $evt['ora_fine']   ? date('H:i',   strtotime($evt['ora_fine']))   : '';
                    $address        = trim(($evt['address'] ?? '') . ' ' . ($evt['number'] ?? ''));
                    $summary        = htmlspecialchars($evt['summary'] ?? substr($evt['description'] ?? '', 0, 140));
                    $evtId          = htmlspecialchars($evt['id']);
                    $distanza       = $evt['distanza_km'] ?? null;
                    $prenotabile    = (int)($evt['prenotabile'] ?? 0);
                    $coord_lat      = $evt['coord_lat'] ?? null;
                    $coord_lng      = $evt['coord_lng'] ?? null;
                    $school_name    = $evt['school_name'] ?? null;
                    $school_address = trim(($evt['school_via'] ?? '') . ' ' . ($evt['school_n_civico'] ?? ''));
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
                         data-description="<?= htmlspecialchars($evt['description'] ?? '') ?>"
                         data-lat="<?= $coord_lat !== null ? htmlspecialchars($coord_lat) : '' ?>"
                         data-lng="<?= $coord_lng !== null ? htmlspecialchars($coord_lng) : '' ?>"
                         data-title="<?= htmlspecialchars($evt['title']) ?>"
                         data-school-address="<?= htmlspecialchars($school_address) ?>">
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
                                <i class="bi bi-calendar"></i> <?= htmlspecialchars($date_inizio) ?>
                            </small>
                            <?php endif; ?>
                            <?php if ($address): ?>
                            <small class="text-muted d-block">
                                <i class="bi bi-map"></i> <?= htmlspecialchars($address) ?>
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
                            <?php if ($school_name): ?>
                            <small class="text-muted d-block">
                                <i class="bi bi-building"></i> <?= htmlspecialchars($school_name) ?>
                            </small>
                            <?php endif; ?>
                            <?php if ($school_address && $school_address !== ' '): ?>
                            <small class="text-muted d-block">
                                <i class="bi bi-signpost"></i> <?= htmlspecialchars($school_address) ?>
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

            <!-- Paginazione -->
            <?php if ($total_pages > 1): ?>
            <nav class="pagination-nav" aria-label="Navigazione pagine eventi">
                <?php
                $base_params = $_GET;
                unset($base_params['page']);
                $base_url = '?' . http_build_query($base_params);
                $sep = empty($base_params) ? '?' : '&';
                ?>
                <?php if ($page_current > 1): ?>
                <a href="<?= $base_url . $sep ?>page=<?= $page_current - 1 ?>" class="page-btn page-btn--arrow" aria-label="Pagina precedente">
                    <i class="bi bi-chevron-left"></i>
                </a>
                <?php else: ?>
                <span class="page-btn page-btn--arrow page-btn--disabled" aria-disabled="true"><i class="bi bi-chevron-left"></i></span>
                <?php endif; ?>

                <?php
                $window = 2;
                for ($p = 1; $p <= $total_pages; $p++):
                    $show = ($p === 1 || $p === $total_pages || ($p >= $page_current - $window && $p <= $page_current + $window));
                    $ellipsis_before = ($p === 2 && $page_current - $window > 2);
                    $ellipsis_after  = ($p === $total_pages - 1 && $page_current + $window < $total_pages - 1);
                    if ($ellipsis_before): ?><span class="page-ellipsis">…</span><?php endif;
                    if ($show): ?>
                        <a href="<?= $base_url . $sep ?>page=<?= $p ?>"
                           class="page-btn<?= $p === $page_current ? ' page-btn--active' : '' ?>"
                           <?= $p === $page_current ? 'aria-current="page"' : '' ?>><?= $p ?></a>
                    <?php endif;
                    if ($ellipsis_after): ?><span class="page-ellipsis">…</span><?php endif;
                endfor; ?>

                <?php if ($page_current < $total_pages): ?>
                <a href="<?= $base_url . $sep ?>page=<?= $page_current + 1 ?>" class="page-btn page-btn--arrow" aria-label="Pagina successiva">
                    <i class="bi bi-chevron-right"></i>
                </a>
                <?php else: ?>
                <span class="page-btn page-btn--arrow page-btn--disabled" aria-disabled="true"><i class="bi bi-chevron-right"></i></span>
                <?php endif; ?>
            </nav>
            <p class="pagination-info">
                Pagina <?= $page_current ?> di <?= $total_pages ?> &nbsp;·&nbsp; <?= $total_events ?> eventi totali
            </p>
            <?php endif; ?>

        </div><!-- /.col-center -->

        <!-- Colonna destra: filtri + mappa -->
        <div class="col-right">
            <!-- Pulsante scarica calendario -->
            <div class="text-end mb-3">
                <a href="report_pdf.php" class="btn-calendario" download>
                    <i class="bi bi-calendar-pdf"></i> Scarica il calendario annuale degli eventi
                </a>
            </div>

            <!-- Ricerca testo -->
            <div class="search-container" aria-label="Ricerca evento">
                <div class="input-group">
                    <input id="filtro_citta" type="search" class="form-control"
                           placeholder="Cerca evento…"
                           value="<?= htmlspecialchars($_GET['cerca'] ?? '') ?>"
                           autocomplete="off" aria-label="Cerca evento">
                </div>
                <ul id="suggestions" class="list-group mt-2" role="listbox" aria-label="Suggerimenti"></ul>
            </div>

            <!-- Selezione città marchigiana -->
            <div class="citta-filter-control">
                <label for="select_citta">
                    <i class="bi bi-geo-alt-fill"></i> Filtra per città
                </label>
                <select id="select_citta" class="form-select">
                    <option value="">-- Seleziona una città --</option>
                    <optgroup label="Province">
                        <option value="43.6158,13.5189">Ancona</option>
                        <option value="43.9174,12.9038">Pesaro</option>
                        <option value="43.8975,13.0188">Urbino</option>
                        <option value="43.3019,13.4530">Macerata</option>
                        <option value="42.7540,13.5764">Ascoli Piceno</option>
                        <option value="43.1597,13.7228">Fermo</option>
                    </optgroup>
                    <optgroup label="Altre città">
                        <option value="43.5228,13.2466">Jesi</option>
                        <option value="43.7305,13.2167">Senigallia</option>
                        <option value="43.4985,13.6018">Osimo</option>
                        <option value="43.5608,13.1551">Chiaravalle</option>
                        <option value="43.4714,13.5498">Loreto</option>
                        <option value="43.4453,13.6219">Porto Recanati</option>
                        <option value="43.4074,13.5493">Recanati</option>
                        <option value="43.3095,13.6584">Civitanova Marche</option>
                        <option value="43.2637,13.6940">Porto San Giorgio</option>
                        <option value="43.2105,13.7170">Porto Sant'Elpidio</option>
                        <option value="43.1550,13.7167">Sant'Elpidio a Mare</option>
                        <option value="43.1333,13.6958">Montegranaro</option>
                        <option value="43.7597,12.6344">Urbania</option>
                        <option value="43.9630,12.9547">Fano</option>
                        <option value="44.0600,12.9053">Cattolica</option>
                        <option value="43.6997,13.1752">Corinaldo</option>
                        <option value="43.5540,13.5098">Castelfidardo</option>
                        <option value="43.2688,13.4225">Tolentino</option>
                        <option value="43.1428,13.2065">Camerino</option>
                        <option value="43.0752,13.6501">Amandola</option>
                        <option value="42.8567,13.5742">Arquata del Tronto</option>
                        <option value="42.8580,13.3893">San Benedetto del Tronto</option>
                        <option value="42.9534,13.5890">Offida</option>
                        <option value="43.0293,13.6213">Monteprandone</option>
                    </optgroup>
                </select>
            </div>

            <!-- Filtro data -->
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

            <!-- Slider raggio -->
            <div class="radius-control">
                <label for="radius_slider">
                    Raggio: <span id="radius_value">30</span> km
                </label>
                <input id="radius_slider" type="range"
                       min="5" max="100" step="5" value="30"
                       aria-label="Raggio di ricerca in km">
            </div>

            <!-- Mappa -->
            <div id="map">
                <div id="coordLabel" class="coord-label hidden" aria-hidden="true"></div>
            </div>

        </div><!-- /.col-right -->

    </div><!-- /.three-col-layout -->

    <!-- Modal dettaglio card -->
    <div id="cardModal" class="card-modal" aria-hidden="true" role="dialog" aria-modal="true">
        <div class="card-modal-backdrop" data-close></div>
        <div class="card-modal-inner" role="document">
            <button id="cardModalClose" class="card-modal-close" aria-label="Chiudi" data-close>&times;</button>
            <img id="modalImg" src="" alt="" class="modal-img">
            <div class="modal-content">
                <h4 id="modalTitle"></h4>
                <p id="modalText"></p>
                <div id="modalMeta" class="mt-3">
                    <p id="modalDate"          class="text-muted mb-1"></p>
                    <p id="modalAddress"       class="text-muted mb-1"></p>
                    <p id="modalOrario"        class="text-muted mb-1"></p>
                    <p id="modalSchool"        class="text-muted mb-1"></p>
                    <p id="modalSchoolAddress" class="text-muted mb-1"></p>
                    <p id="modalPrenotabile"   class="mb-0"></p>
                </div>
            </div>
        </div>
    </div>

</main>

<?php include 'footer.html'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="main.js"></script>

</body>
</html>