<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/layout.php';

$conn = db();

// Dati per i filtri
$zone   = $conn->query('SELECT ID_zona, nome FROM Zone   ORDER BY nome ASC')->fetch_all(MYSQLI_ASSOC);
$ambiti = $conn->query('SELECT ID_ambito, nome FROM Ambiti ORDER BY nome ASC')->fetch_all(MYSQLI_ASSOC);

// Filtri dalla GET
$filtroNome  = trim($_GET['nome']  ?? '');
$filtroZona  = (int)($_GET['zona']  ?? 0);
$filtroAmbito = (int)($_GET['ambito'] ?? 0);

// Query dinamica con filtri
$where  = ['1=1'];
$params = [];
$types  = '';

if ($filtroNome !== '') {
    $where[]  = 's.nome LIKE ?';
    $params[] = '%' . $filtroNome . '%';
    $types   .= 's';
}
if ($filtroZona > 0) {
    $where[]  = 'c.id_zona = ?';
    $params[] = $filtroZona;
    $types   .= 'i';
}
if ($filtroAmbito > 0) {
    $where[]  = 'EXISTS (SELECT 1 FROM Scuole_Ambiti sa2 WHERE sa2.cod_scuola = s.COD_meccanografico AND sa2.id_ambito = ?)';
    $params[] = $filtroAmbito;
    $types   .= 'i';
}

$sql = 'SELECT s.COD_meccanografico, s.nome, s.descrizione, s.sito, s.via, s.n_civico,
               c.nome AS nome_citta, z.nome AS nome_zona, f.path_foto,
               GROUP_CONCAT(DISTINCT a.nome  ORDER BY a.nome  SEPARATOR \', \') AS ambiti_nomi,
               GROUP_CONCAT(         i.nome  ORDER BY si.n_ordine SEPARATOR \', \') AS indirizzi
        FROM   Scuole s
        LEFT JOIN Citta c ON s.id_citta = c.ID_citta
        LEFT JOIN Zone  z ON c.id_zona  = z.ID_zona
        LEFT JOIN Foto  f ON s.id_foto  = f.ID_foto AND f.data_eliminazione IS NULL
        LEFT JOIN Scuole_Ambiti     sa ON s.COD_meccanografico = sa.cod_scuola
        LEFT JOIN Ambiti             a ON sa.id_ambito         = a.ID_ambito
        LEFT JOIN Scuole_Indirizzi  si ON s.COD_meccanografico = si.cod_scuola
        LEFT JOIN Indirizzi_studio   i ON si.id_indirizzo      = i.ID_indirizzo
        WHERE ' . implode(' AND ', $where) . '
        GROUP  BY s.COD_meccanografico
        ORDER  BY s.nome ASC';

if ($types === '') {
    $scuole = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
} else {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $scuole = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$conn->close();

render_head_pubblica('Orientati — Esplora le scuole');
render_navbar_pubblica('orientati.php');
?>

<section class="hero" style="padding:40px 0;">
    <div class="container">
        <h1 style="font-size:1.8rem;">Esplora le scuole</h1>
    </div>
</section>

<div class="container py-5">

    <!-- Filtri -->
    <div class="filtri-barra">
        <form method="GET" action="orientati.php" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label mb-1" style="font-size:.85rem;">Nome scuola</label>
                <input type="text" name="nome" class="form-control form-control-sm"
                       placeholder="Cerca..." value="<?= htmlspecialchars($filtroNome) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label mb-1" style="font-size:.85rem;">Zona</label>
                <select name="zona" class="form-select form-select-sm">
                    <option value="">Tutte le zone</option>
                    <?php foreach ($zone as $z): ?>
                    <option value="<?= $z['ID_zona'] ?>" <?= $filtroZona == $z['ID_zona'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($z['nome']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label mb-1" style="font-size:.85rem;">Ambito</label>
                <select name="ambito" class="form-select form-select-sm">
                    <option value="">Tutti gli ambiti</option>
                    <?php foreach ($ambiti as $a): ?>
                    <option value="<?= $a['ID_ambito'] ?>" <?= $filtroAmbito == $a['ID_ambito'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($a['nome']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="bi bi-search me-1"></i>Cerca
                </button>
                <?php if ($filtroNome || $filtroZona || $filtroAmbito): ?>
                <a href="orientati.php" class="btn btn-outline-secondary btn-sm" title="Rimuovi filtri">
                    <i class="bi bi-x-lg"></i>
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Risultati -->
    <?php if (empty($scuole)): ?>
    <div class="text-center text-muted py-5">
        <i class="bi bi-search fs-2 d-block mb-2"></i>Nessuna scuola trovata con i filtri selezionati.
    </div>
    <?php else: ?>
    <p class="text-muted mb-3" style="font-size:.88rem;"><?= count($scuole) ?> scuol<?= count($scuole) == 1 ? 'a' : 'e' ?> trovat<?= count($scuole) == 1 ? 'a' : 'e' ?>.</p>
    <div class="accordion" id="accordionOrientati">
        <?php foreach ($scuole as $i => $s): ?>
        <div class="accordion-item mb-2 border rounded">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#sc-<?= $i ?>">
                    <div class="d-flex flex-column">
                        <span class="fw-semibold"><?= htmlspecialchars($s['nome']) ?></span>
                        <span class="text-muted" style="font-size:.82rem; font-weight: normal;">
                            <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($s['nome_citta'] ?? '') ?>
                            <?php if ($s['nome_zona']): ?>
                            · <?= htmlspecialchars($s['nome_zona']) ?>
                            <?php endif; ?>
                        </span>
                    </div>
                </button>
            </h2>
            <div id="sc-<?= $i ?>" class="accordion-collapse collapse">
                <div class="accordion-body">
                    <div class="row g-3">
                        <?php if ($s['path_foto']): ?>
                        <div class="col-md-3">
                            <img src="../<?= htmlspecialchars($s['path_foto']) ?>"
                                 class="img-fluid rounded" alt="">
                        </div>
                        <?php endif; ?>
                        <div class="col">
                            <p style="font-size:.9rem;"><?= htmlspecialchars($s['descrizione']) ?></p>
                            <?php if ($s['ambiti_nomi']): ?>
                            <p class="mb-1" style="font-size:.85rem;">
                                <strong>Ambiti:</strong> <?= htmlspecialchars($s['ambiti_nomi']) ?>
                            </p>
                            <?php endif; ?>
                            <?php if ($s['indirizzi']): ?>
                            <p class="mb-1" style="font-size:.85rem;">
                                <strong>Indirizzi:</strong> <?= htmlspecialchars($s['indirizzi']) ?>
                            </p>
                            <?php endif; ?>
                            <?php if ($s['via'] && $s['nome_citta']): ?>
                            <p class="mb-1 text-muted" style="font-size:.82rem;">
                                <i class="bi bi-map-fill me-1"></i>
                                <?= htmlspecialchars($s['via']) ?> <?= htmlspecialchars($s['n_civico']) ?>,
                                <?= htmlspecialchars($s['nome_citta']) ?>
                            </p>
                            <?php endif; ?>
                            <?php if ($s['sito']): ?>
                            <a href="<?= htmlspecialchars($s['sito']) ?>" target="_blank" rel="noopener"
                               class="btn btn-outline-primary btn-sm mt-2">
                                <i class="bi bi-box-arrow-up-right me-1"></i>Sito web
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>

<?php render_footer(); ?>
<?php chiudi_pagina_pubblica(); ?>
