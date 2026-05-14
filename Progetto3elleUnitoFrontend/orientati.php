<?php
session_start();
include("daticonnessione.php");

// --- Filtri ---
$filtro_ambito = isset($_GET['ambito']) ? (int)$_GET['ambito'] : 0;
$filtro_zona   = isset($_GET['zona'])   ? (int)$_GET['zona']   : 0;
$filtro_testo  = isset($_GET['q'])      ? trim($_GET['q'])      : '';

// --- Dropdown ambiti ---
$ambiti = [];
$res = $conn->query("SELECT ID_ambito, nome FROM ambiti ORDER BY ID_ambito");
if ($res) { while ($r = $res->fetch_object()) { $ambiti[] = $r; } }

// --- Dropdown zone ---
$zone = [];
$res = $conn->query("SELECT ID_zona, nome FROM zone ORDER BY nome");
if ($res) { while ($r = $res->fetch_object()) { $zone[] = $r; } }

// --- Link per scuola dal DB ---
$extra_links = [];
$res_links = $conn->query("SELECT cod_scuola, titolo, url_link, icon FROM links WHERE data_eliminazione IS NULL ORDER BY n_ordine ASC");
if ($res_links) {
    while ($r = $res_links->fetch_object()) {
        $extra_links[$r->cod_scuola][] = [
            'label' => $r->titolo,
            'url'   => $r->url_link,
            'icon'  => $r->icon,
        ];
    }
}

// -------------------------------------------------------
// Query scuole
// -------------------------------------------------------
$where_parts = ["1=1"];
$bind_types  = "";
$bind_params = [];

$ambito_sql = "";
if ($filtro_ambito > 0) {
    $ambito_sql   = "AND EXISTS (
        SELECT 1 FROM scuole_ambiti sa
        WHERE sa.cod_scuola = s.COD_meccanografico
          AND sa.id_ambito = ?
    )";
    $bind_types  .= "i";
    $bind_params[] = $filtro_ambito;
}

if ($filtro_zona > 0) {
    $where_parts[] = "c.id_zona = ?";
    $bind_types   .= "i";
    $bind_params[] = $filtro_zona;
}

if ($filtro_testo !== '') {
    $where_parts[] = "s.nome LIKE ?";
    $bind_types   .= "s";
    $bind_params[] = '%' . $filtro_testo . '%';
}

$where_sql = implode(" AND ", $where_parts);

$sql = "
    SELECT DISTINCT
        s.COD_meccanografico,
        s.nome,
        s.descrizione,
        s.sito,
        s.via,
        s.n_civico,
        c.nome  AS citta,
        c.cap,
        c.id_zona,
        p.nome  AS provincia
    FROM scuole s
    LEFT JOIN citta c    ON c.ID_citta = s.id_citta
    LEFT JOIN province p ON p.sigla = c.sigla_provincia
    WHERE $where_sql
    $ambito_sql
    ORDER BY s.nome
";

$scuole = [];
if ($stmt = $conn->prepare($sql)) {
    if (!empty($bind_params)) {
        $stmt->bind_param($bind_types, ...$bind_params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_object()) { $scuole[] = $r; }
}

// --- Per ogni scuola: ambiti, indirizzi, eventi ---
foreach ($scuole as $scuola) {
    $cod = $scuola->COD_meccanografico;

    $scuola->ambiti_list = [];
    $s2 = $conn->prepare("
        SELECT a.ID_ambito, a.nome
        FROM ambiti a
        JOIN scuole_ambiti sa ON sa.id_ambito = a.ID_ambito
        WHERE sa.cod_scuola = ?
        ORDER BY a.ID_ambito
    ");
    $s2->bind_param("s", $cod);
    $s2->execute();
    $r2 = $s2->get_result();
    while ($row = $r2->fetch_object()) { $scuola->ambiti_list[] = $row; }

    $scuola->indirizzi_list = [];
    $s3 = $conn->prepare("
        SELECT i.nome
        FROM indirizzi_studio i
        JOIN scuole_indirizzi si ON si.id_indirizzo = i.ID_indirizzo
        WHERE si.cod_scuola = ?
        ORDER BY si.n_ordine
    ");
    $s3->bind_param("s", $cod);
    $s3->execute();
    $r3 = $s3->get_result();
    while ($row = $r3->fetch_object()) { $scuola->indirizzi_list[] = $row->nome; }

    $scuola->eventi_list = [];
    $s4 = $conn->prepare("
        SELECT titolo, ora_inizio, ora_fine, descrizione_breve, prenotabile
        FROM eventi
        WHERE cod_scuola = ?
          AND data_eliminazione IS NULL
          AND visibile = 1
        ORDER BY ora_inizio ASC
    ");
    $s4->bind_param("s", $cod);
    $s4->execute();
    $r4 = $s4->get_result();
    while ($row = $r4->fetch_object()) { $scuola->eventi_list[] = $row; }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orientati – Svelati</title>
    <link rel="stylesheet" href="stile/stile.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
</head>
<body>

<?php include("stile/navbar.html"); ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>

<div class="header">
    <h1>Orientati</h1>
    <p>Esplora le scuole del territorio e trova il percorso giusto per te</p>
</div>

<div class="filter-bar">
    <div class="container">
        <form method="GET" action="" class="d-flex flex-wrap gap-2 align-items-center">
            <input
                type="text"
                name="q"
                class="form-control filter-input"
                placeholder="Cerca scuola…"
                value="<?= htmlspecialchars($filtro_testo) ?>"
            >
            <select name="zona" class="form-select filter-zona">
                <option value="0">Tutte le zone</option>
                <?php foreach ($zone as $z): ?>
                    <option value="<?= $z->ID_zona ?>" <?= $filtro_zona == $z->ID_zona ? 'selected' : '' ?>>
                        <?= htmlspecialchars($z->nome) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="ambito" class="form-select filter-ambito">
                <option value="0">Tutti gli ambiti</option>
                <?php foreach ($ambiti as $a): ?>
                    <option value="<?= $a->ID_ambito ?>" <?= $filtro_ambito == $a->ID_ambito ? 'selected' : '' ?>>
                        Ambito <?= $a->ID_ambito ?> – <?= htmlspecialchars($a->nome) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-cerca">
                <i class="bi bi-search me-1"></i> Cerca
            </button>
            <?php if ($filtro_zona || $filtro_ambito || $filtro_testo): ?>
                <a href="orientati.php" class="btn-reset">
                    <i class="bi bi-x-circle"></i> Reimposta
                </a>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="container results-area">

    <p class="results-count">
        <strong><?= count($scuole) ?></strong>
        scuol<?= count($scuole) === 1 ? 'a trovata' : 'e trovate' ?>
        <?php if ($filtro_ambito > 0): foreach ($ambiti as $a): if ($a->ID_ambito == $filtro_ambito): ?>
            — Ambito <?= $a->ID_ambito ?>: <strong><?= htmlspecialchars($a->nome) ?></strong>
        <?php endif; endforeach; endif; ?>
    </p>

    <?php if (empty($scuole)): ?>
        <div class="empty-state">
            <i class="bi bi-search"></i>
            <p>Nessuna scuola corrisponde ai criteri selezionati.</p>
        </div>
    <?php endif; ?>

    <?php foreach ($scuole as $s):
        $cod       = $s->COD_meccanografico;
        $card_id   = 'card-' . $cod;
        $iniziale  = mb_strtoupper(mb_substr($s->nome, 0, 1));
        $indirizzo = trim($s->via . ' ' . $s->n_civico . ', ' . ($s->cap ?? '') . ' ' . ($s->citta ?? '') . ' ' . ($s->provincia ?? ''));
        $mapQuery  = urlencode($s->via . ' ' . $s->n_civico . ' ' . ($s->citta ?? '') . ' ' . ($s->provincia ?? '') . ' Italia');
        $links     = $extra_links[$cod] ?? [];
    ?>
    <div class="school-card" id="<?= $card_id ?>">

        <div class="card-header-row" onclick="toggleCard('<?= $card_id ?>')">
            <div class="card-iniziale"><?= $iniziale ?></div>
            <div class="card-meta">
                <h2><?= htmlspecialchars($s->nome) ?></h2>
                <div class="card-location">
                    <i class="bi bi-geo-alt-fill"></i>
                    <?= htmlspecialchars(($s->citta ?? '') . ($s->provincia ? ' (' . $s->provincia . ')' : '')) ?>
                </div>
                <div class="ambiti-tags">
                    <?php foreach ($s->ambiti_list as $amb): ?>
                        <span class="ambito-tag"><?= htmlspecialchars($amb->nome) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <button class="chevron-btn" type="button">
                <i class="bi bi-chevron-down"></i>
            </button>
        </div>

        <div class="card-body-content">

            <div class="col-info">
                <div class="section-label">Descrizione</div>
                <p class="desc-text"><?= nl2br(htmlspecialchars($s->descrizione)) ?></p>

                <?php if (!empty($s->ambiti_list)): ?>
                    <div class="section-label section-label-spaced">Ambiti</div>
                    <ul class="ambiti-desc-list">
                        <?php foreach ($s->ambiti_list as $amb): ?>
                            <li>
                                <span class="ambito-num">Ambito <?= $amb->ID_ambito ?></span>
                                <?= htmlspecialchars($amb->nome) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <?php if (!empty($links)): ?>
                    <div class="section-label section-label-spaced">Link utili</div>
                    <div class="links-extra">
                        <?php foreach ($links as $l): ?>
                            <a href="<?= htmlspecialchars($l['url']) ?>" target="_blank" rel="noopener" class="link-extra-btn">
                                <i class="bi <?= $l['icon'] ?>"></i>
                                <?= htmlspecialchars($l['label']) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($s->indirizzi_list)): ?>
                    <div class="section-label section-label-spaced">Indirizzi scolastici</div>
                    <ul class="indirizzi-list">
                        <?php foreach ($s->indirizzi_list as $ind): ?>
                            <li><?= htmlspecialchars($ind) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <?php if (!empty($s->eventi_list)): ?>
                    <div class="section-label section-label-spaced">
                        <i class="bi bi-calendar-event me-1"></i> Open Day ed eventi
                    </div>
                    <ul class="eventi-list">
                        <?php foreach ($s->eventi_list as $ev):
                            $d_ini = new DateTime($ev->ora_inizio);
                            $d_fin = new DateTime($ev->ora_fine);
                        ?>
                            <li class="evento-item">
                                <div class="evento-titolo"><?= htmlspecialchars($ev->titolo) ?></div>
                                <div class="evento-data">
                                    <i class="bi bi-clock"></i>
                                    <?= $d_ini->format('d/m/Y') ?> ore <?= $d_ini->format('H:i') ?> – <?= $d_fin->format('H:i') ?>
                                </div>
                                <?php if ($ev->descrizione_breve): ?>
                                    <div class="evento-desc"><?= htmlspecialchars($ev->descrizione_breve) ?></div>
                                <?php endif; ?>
                                <?php if ($ev->prenotabile): ?>
                                    <span class="badge-prenotabile"><i class="bi bi-check-circle me-1"></i>Prenotabile</span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <div class="col-map">
                <div class="section-label">Dove siamo</div>
                <div class="map-wrap">
                    <iframe
                        loading="lazy"
                        src="https://www.google.com/maps?q=<?= $mapQuery ?>&output=embed"
                        allowfullscreen
                    ></iframe>
                </div>
                <p class="map-address">
                    <i class="bi bi-geo-alt"></i>
                    <?= htmlspecialchars($indirizzo) ?>
                </p>
            </div>

        </div>
    </div>
    <?php endforeach; ?>

</div>

<script>
function toggleCard(id) {
    document.getElementById(id).classList.toggle('open');
}

(function() {
    const params = new URLSearchParams(window.location.search);
    const openCod = params.get('open');
    if (openCod) {
        const cardId = 'card-' + openCod;
        const card = document.getElementById(cardId);
        if (card) {
            card.classList.add('open');
            setTimeout(function() {
                card.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 150);
        }
    }
})();
</script>

<?php include("stile/footer.html"); ?>

</body>
</html>
