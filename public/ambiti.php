<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/layout.php';

$conn = db();

$ambiti = $conn->query(
    'SELECT a.ID_ambito, a.nome, a.descrizione,
            GROUP_CONCAT(s.nome ORDER BY s.nome SEPARATOR \'|||\') AS scuole_nomi,
            GROUP_CONCAT(s.COD_meccanografico ORDER BY s.nome SEPARATOR \'|||\') AS scuole_cod
     FROM   Ambiti a
     LEFT JOIN Scuole_Ambiti sa ON a.ID_ambito = sa.id_ambito
     LEFT JOIN Scuole s ON sa.cod_scuola = s.COD_meccanografico
     GROUP  BY a.ID_ambito, a.nome, a.descrizione
     ORDER  BY a.nome ASC'
)->fetch_all(MYSQLI_ASSOC);

$conn->close();

render_head_pubblica('Ambiti');
render_navbar_pubblica('ambiti.php');
render_hero_banner('Ambiti', 'Esplora le aree disciplinari');
?>

<div class="container py-5">
    <?php if (empty($ambiti)): ?>
    <p class="text-muted">Nessun ambito presente.</p>
    <?php else: ?>
    <?php foreach ($ambiti as $a): ?>
    <div class="ambito-row">
        <div class="ambito-row-info">
            <div class="ambito-row-titolo"><?= htmlspecialchars($a['nome']) ?></div>
            <?php if ($a['descrizione']): ?>
            <div class="ambito-row-desc"><?= htmlspecialchars($a['descrizione']) ?></div>
            <?php endif; ?>
            <?php if ($a['scuole_nomi']): ?>
            <div class="mt-2">
                <?php foreach (explode('|||', $a['scuole_nomi']) as $nome): ?>
                <a href="orientati.php?ambito=<?= (int)$a['ID_ambito'] ?>"
                   class="ambito-school-link">
                    <?= htmlspecialchars($nome) ?>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <div class="ambito-row-img">
            <i class="bi bi-mortarboard"></i>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php render_footer(); ?>
<?php chiudi_pagina_pubblica(); ?>
