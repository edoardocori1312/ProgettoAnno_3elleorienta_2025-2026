<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/layout.php';

$conn = db();

$links = $conn->query(
    'SELECT l.ID_link, l.titolo, l.descrizione, l.indirizzo, f.path_foto
     FROM   Links l
     LEFT JOIN Foto f ON l.id_foto = f.ID_foto
     WHERE  l.data_eliminazione IS NULL
     ORDER  BY l.n_ordine ASC'
)->fetch_all(MYSQLI_ASSOC);

$conn->close();

render_head_pubblica('Link Utili');
render_navbar_pubblica('linkutili.php');
render_hero_banner('Link Utili', 'Risorse utili per l\'orientamento');
?>

<main class="py-5">
    <div class="container">
        <?php if (empty($links)): ?>
        <p class="text-muted">Nessun link presente.</p>
        <?php else: ?>
        <div class="row row-cols-1 row-cols-md-3 g-4 align-items-start">
            <?php foreach ($links as $i => $l): ?>
            <div class="col">
                <div class="card shadow-sm h-100">
                    <?php if ($l['path_foto']): ?>
                    <a href="<?= htmlspecialchars($l['indirizzo']) ?>" target="_blank" rel="noopener">
                        <img src="../<?= htmlspecialchars($l['path_foto']) ?>"
                             class="card-img-top" alt="<?= htmlspecialchars($l['titolo']) ?>">
                    </a>
                    <?php endif; ?>
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title mb-2"><?= htmlspecialchars($l['titolo']) ?></h5>
                        <button class="btn btn-outline-secondary btn-sm mb-2 w-100 d-flex justify-content-between align-items-center"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#desc-<?= $i ?>">
                            <span>Espandi</span>
                            <i class="bi bi-chevron-down toggle-icon"></i>
                        </button>
                        <div class="collapse" id="desc-<?= $i ?>">
                            <p class="card-text mb-2"><?= htmlspecialchars($l['descrizione']) ?></p>
                        </div>
                        <a href="<?= htmlspecialchars($l['indirizzo']) ?>" target="_blank" rel="noopener"
                           class="btn btn-primary w-100 mt-auto">Vai al link</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</main>

<?php render_footer(); ?>
<script>
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
<?php chiudi_pagina_pubblica(); ?>
