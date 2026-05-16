<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/layout.php';

$conn = db();

$scuole = $conn->query(
    'SELECT s.COD_meccanografico, s.nome, s.sito, c.nome AS nome_citta, f.path_foto
     FROM   Scuole s
     LEFT JOIN Citta c ON s.id_citta = c.ID_citta
     LEFT JOIN Foto  f ON s.id_foto  = f.ID_foto AND f.data_eliminazione IS NULL
     ORDER  BY s.nome ASC
     LIMIT 6'
)->fetch_all(MYSQLI_ASSOC);

$ambiti = $conn->query(
    'SELECT a.ID_ambito, a.nome, COUNT(sa.cod_scuola) AS n_scuole
     FROM   Ambiti a
     LEFT JOIN Scuole_Ambiti sa ON a.ID_ambito = sa.id_ambito
     GROUP  BY a.ID_ambito, a.nome
     ORDER  BY a.nome ASC'
)->fetch_all(MYSQLI_ASSOC);

$links = $conn->query(
    'SELECT l.titolo, l.descrizione, l.indirizzo
     FROM   Links l
     WHERE  l.data_eliminazione IS NULL
     ORDER  BY l.n_ordine ASC'
)->fetch_all(MYSQLI_ASSOC);

$conn->close();

render_head_pubblica('Home');
render_navbar_pubblica('index.php');
?>

<!-- Hero -->
<section class="hero">
    <div class="container">
        <h1>Svelati</h1>
        <p class="mt-3">La piattaforma per orientarsi nella scelta della scuola secondaria di secondo grado nelle Marche.</p>
        <a href="orientati.php" class="btn btn-light btn-lg mt-4">Esplora le scuole</a>
    </div>
</section>

<!-- Scuole -->
<section class="py-5">
    <div class="container">
        <h2 class="sez-title">Le scuole</h2>
        <?php if (empty($scuole)): ?>
        <p class="text-muted">Nessuna scuola presente.</p>
        <?php else: ?>
        <div class="row row-cols-1 row-cols-md-3 g-4">
            <?php foreach ($scuole as $s): ?>
            <div class="col">
                <div class="scheda">
                    <?php if ($s['path_foto']): ?>
                    <img src="../<?= htmlspecialchars($s['path_foto']) ?>" class="scheda-foto"
                         alt="<?= htmlspecialchars($s['nome']) ?>">
                    <?php else: ?>
                    <div class="scheda-placeholder"><i class="bi bi-building"></i></div>
                    <?php endif; ?>
                    <div class="p-3">
                        <h6 class="fw-semibold mb-1"><?= htmlspecialchars($s['nome']) ?></h6>
                        <p class="text-muted mb-2" style="font-size:.85rem;">
                            <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($s['nome_citta'] ?? '') ?>
                        </p>
                        <?php if ($s['sito']): ?>
                        <a href="<?= htmlspecialchars($s['sito']) ?>" target="_blank" rel="noopener"
                           class="text-decoration-none" style="font-size:.82rem;">
                            <i class="bi bi-box-arrow-up-right me-1"></i>Sito web
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a href="orientati.php" class="btn btn-outline-primary">Vedi tutte le scuole</a>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Ambiti -->
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="sez-title">Ambiti formativi</h2>
        <?php if (empty($ambiti)): ?>
        <p class="text-muted">Nessun ambito presente.</p>
        <?php else: ?>
        <div class="row row-cols-2 row-cols-md-4 g-3">
            <?php foreach ($ambiti as $a): ?>
            <div class="col">
                <a href="ambiti.php?id=<?= $a['ID_ambito'] ?>" class="text-decoration-none">
                    <div class="card h-100 text-center p-3 border-0 shadow-sm">
                        <div class="fw-semibold mb-1"><?= htmlspecialchars($a['nome']) ?></div>
                        <div class="text-muted" style="font-size:.82rem;"><?= (int)$a['n_scuole'] ?> scuol<?= $a['n_scuole'] == 1 ? 'a' : 'e' ?></div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Link utili -->
<?php if (!empty($links)): ?>
<section class="py-5">
    <div class="container">
        <h2 class="sez-title">Link utili</h2>
        <div class="list-group list-group-flush">
            <?php foreach ($links as $l): ?>
            <a href="<?= htmlspecialchars($l['indirizzo']) ?>" target="_blank" rel="noopener"
               class="list-group-item list-group-item-action d-flex gap-3 align-items-start">
                <i class="bi bi-link-45deg fs-5 text-primary mt-1"></i>
                <div>
                    <div class="fw-semibold"><?= htmlspecialchars($l['titolo']) ?></div>
                    <div class="text-muted" style="font-size:.85rem;"><?= htmlspecialchars($l['descrizione']) ?></div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php render_footer(); ?>
<?php chiudi_pagina_pubblica(); ?>
