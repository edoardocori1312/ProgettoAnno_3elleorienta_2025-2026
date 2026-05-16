<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/layout.php';

$conn    = db();
$idAmbito = (int)($_GET['id'] ?? 0);

// Sempre carica la lista ambiti per la sidebar/overview
$ambiti = $conn->query(
    'SELECT a.ID_ambito, a.nome, COUNT(sa.cod_scuola) AS n_scuole
     FROM   Ambiti a
     LEFT JOIN Scuole_Ambiti sa ON a.ID_ambito = sa.id_ambito
     GROUP  BY a.ID_ambito, a.nome
     ORDER  BY a.nome ASC'
)->fetch_all(MYSQLI_ASSOC);

$ambitoCorrente = null;
$scuole         = [];

if ($idAmbito > 0) {
    foreach ($ambiti as $a) {
        if ((int)$a['ID_ambito'] === $idAmbito) { $ambitoCorrente = $a; break; }
    }

    if ($ambitoCorrente) {
        $stmt = $conn->prepare(
            'SELECT s.COD_meccanografico, s.nome, s.descrizione, s.sito, c.nome AS nome_citta, f.path_foto,
                    GROUP_CONCAT(i.nome ORDER BY si.n_ordine SEPARATOR \', \') AS indirizzi
             FROM   Scuole s
             LEFT JOIN Citta c ON s.id_citta = c.ID_citta
             LEFT JOIN Foto  f ON s.id_foto  = f.ID_foto AND f.data_eliminazione IS NULL
             LEFT JOIN Scuole_Indirizzi si ON s.COD_meccanografico = si.cod_scuola
             LEFT JOIN Indirizzi_studio i  ON si.id_indirizzo = i.ID_indirizzo
             JOIN  Scuole_Ambiti sa ON s.COD_meccanografico = sa.cod_scuola
             WHERE sa.id_ambito = ?
             GROUP  BY s.COD_meccanografico
             ORDER  BY s.nome ASC'
        );
        $stmt->bind_param('i', $idAmbito);
        $stmt->execute();
        $scuole = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}

$conn->close();

$titoloPagina = $ambitoCorrente ? 'Ambito: ' . $ambitoCorrente['nome'] : 'Ambiti';
render_head_pubblica($titoloPagina);
render_navbar_pubblica('ambiti.php');
?>

<section class="hero" style="padding:40px 0;">
    <div class="container">
        <h1 style="font-size:1.8rem;"><?= $ambitoCorrente ? htmlspecialchars($ambitoCorrente['nome']) : 'Ambiti formativi' ?></h1>
    </div>
</section>

<div class="container py-5">
    <div class="row">

        <!-- Lista ambiti (sidebar sinistra) -->
        <div class="col-md-3">
            <h5 class="fw-semibold mb-3">Ambiti</h5>
            <div class="list-group">
                <a href="ambiti.php" class="list-group-item list-group-item-action <?= $idAmbito === 0 ? 'active' : '' ?>">
                    Tutti
                </a>
                <?php foreach ($ambiti as $a): ?>
                <a href="ambiti.php?id=<?= $a['ID_ambito'] ?>"
                   class="list-group-item list-group-item-action d-flex justify-content-between
                          <?= (int)$a['ID_ambito'] === $idAmbito ? 'active' : '' ?>">
                    <span><?= htmlspecialchars($a['nome']) ?></span>
                    <span class="badge bg-secondary rounded-pill"><?= (int)$a['n_scuole'] ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Contenuto principale -->
        <div class="col-md-9">
            <?php if ($idAmbito === 0): ?>
            <!-- Panoramica ambiti -->
            <h2 class="sez-title">Panoramica</h2>
            <div class="row row-cols-1 row-cols-md-2 g-4">
                <?php foreach ($ambiti as $a): ?>
                <div class="col">
                    <a href="ambiti.php?id=<?= $a['ID_ambito'] ?>" class="text-decoration-none">
                        <div class="card border-0 shadow-sm p-3 h-100">
                            <div class="fw-semibold mb-1"><?= htmlspecialchars($a['nome']) ?></div>
                            <div class="text-muted" style="font-size:.85rem;">
                                <?= (int)$a['n_scuole'] ?> scuol<?= $a['n_scuole'] == 1 ? 'a' : 'e' ?>
                            </div>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>

            <?php elseif (empty($scuole)): ?>
            <p class="text-muted">Nessuna scuola in questo ambito.</p>

            <?php else: ?>
            <h2 class="sez-title">Scuole in questo ambito</h2>
            <div class="accordion" id="accordionScuole">
                <?php foreach ($scuole as $i => $s): ?>
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button <?= $i > 0 ? 'collapsed' : '' ?>"
                                type="button" data-bs-toggle="collapse"
                                data-bs-target="#scuola-<?= $i ?>">
                            <div>
                                <span class="fw-semibold"><?= htmlspecialchars($s['nome']) ?></span>
                                <span class="text-muted ms-2" style="font-size:.83rem;">
                                    <?= htmlspecialchars($s['nome_citta'] ?? '') ?>
                                </span>
                            </div>
                        </button>
                    </h2>
                    <div id="scuola-<?= $i ?>" class="accordion-collapse collapse <?= $i === 0 ? 'show' : '' ?>">
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
                                    <?php if ($s['indirizzi']): ?>
                                    <p class="mb-1" style="font-size:.85rem;">
                                        <strong>Indirizzi:</strong> <?= htmlspecialchars($s['indirizzi']) ?>
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

    </div>
</div>

<?php render_footer(); ?>
<?php chiudi_pagina_pubblica(); ?>
