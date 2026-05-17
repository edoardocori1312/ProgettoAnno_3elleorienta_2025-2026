<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/layout.php';

$conn = db();

$progetti = $conn->query(
    'SELECT p.ID_progetto, p.titolo, p.descrizione, f.path_foto
     FROM   Progetti p
     LEFT JOIN Foto f ON p.id_foto = f.ID_foto
     WHERE  p.data_eliminazione IS NULL
     ORDER  BY p.n_ordine ASC'
)->fetch_all(MYSQLI_ASSOC);

$conn->close();

render_head_pubblica('Progetti');
render_navbar_pubblica('progetti.php');
render_hero_banner('Progetti', 'Reti Territoriali per l\'Orientamento');
?>

<div class="container py-5">
    <?php if (empty($progetti)): ?>
    <p class="text-muted">Nessun progetto presente.</p>
    <?php else: ?>
    <div class="lista-progetti-aperta">
        <?php foreach ($progetti as $i => $p): ?>
        <?php if ($i > 0): ?><hr class="separatore-progetto"><?php endif; ?>
        <article class="progetto-aperto">
            <div class="row align-items-start">
                <div class="col-md-4 mb-3">
                    <?php if ($p['path_foto']): ?>
                    <img src="../<?= htmlspecialchars($p['path_foto']) ?>"
                         alt="<?= htmlspecialchars($p['titolo']) ?>"
                         class="img-fluida">
                    <?php else: ?>
                    <div class="progetto-pub-placeholder">
                        <i class="bi bi-lightbulb"></i>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-8">
                    <h2 class="titolo-progetto-aperto"><?= htmlspecialchars($p['titolo']) ?></h2>
                    <div class="testo-progetto-aperto"><?= htmlspecialchars($p['descrizione']) ?></div>
                </div>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php render_footer(); ?>
<?php chiudi_pagina_pubblica(); ?>
