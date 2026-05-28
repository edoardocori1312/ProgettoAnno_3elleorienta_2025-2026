<?php
    session_start();
    include("../connessione/db.php");
    $conn = new mysqli($HOSTDB, $USERDB, $PASSDB, $NOMEDB);
    
    // --- Filtro zona ---
    $filtro_zona = isset($_GET['zona']) ? (int)$_GET['zona'] : 0;

    // --- Dropdown zone ---
    $zone = [];
    $res = $conn->query("SELECT ID_zona, nome FROM Zone ORDER BY nome");
    while ($r = $res->fetch_object()) { $zone[] = $r; }

    // --- Ambiti ---
    $colori = array("FFC799","ADCFD9","BFCF91","D2A4CC","9BC2BE","F5E393","6A768C","B7B7B7","D57F8C","90706E");
    $ambiti_id          = [];
    $ambiti_nome        = [];
    $ambiti_descrizione = [];
    $res = $conn->query("SELECT * FROM Ambiti ORDER BY ID_ambito");
    $i = 0;
    while ($riga = $res->fetch_object()) {
        $ambiti_id[$i]          = $riga->ID_ambito;
        $ambiti_nome[$i]        = $riga->nome;
        $ambiti_descrizione[$i] = $riga->descrizione;
        $i++;
    }

    // --- Scuole per ambito (con filtro zona opzionale) ---
    $Scuole_id_ambiti = [];
    $Scuole_nome      = [];
    $Scuole_sito      = [];
    $Scuole_via       = [];
    $Scuole_n_civico  = [];
    $Scuole_cod       = [];

    $sql_scuole = "
        SELECT sa.id_ambito, s.nome, s.sito, s.via, s.n_civico, s.COD_meccanografico
        FROM Scuole_Ambiti sa
        JOIN Scuole s ON sa.cod_scuola = s.COD_meccanografico
        LEFT JOIN Citta c ON c.ID_citta = s.id_citta
        WHERE (? = 0 OR c.id_zona = ?)
    ";

    $j = 0;
    if ($stmt = $conn->prepare($sql_scuole)) {
        $stmt->bind_param("ii", $filtro_zona, $filtro_zona);
        if ($stmt->execute()) {
            $res = $stmt->get_result();
            while ($riga = $res->fetch_object()) {
                $Scuole_id_ambiti[$j] = $riga->id_ambito;
                $Scuole_nome[$j]      = $riga->nome;
                $Scuole_sito[$j]      = $riga->sito;
                $Scuole_via[$j]       = $riga->via;
                $Scuole_n_civico[$j]  = $riga->n_civico;
                $Scuole_cod[$j]       = $riga->COD_meccanografico;
                $j++;
            }
        }
    }

    // --- Pre-costruisci mappa ambito → scuole per efficienza ---
    $scuole_per_ambito = [];
    for ($t = 0; $t < $j; $t++) {
        $scuole_per_ambito[$Scuole_id_ambiti[$t]][] = [
            'nome'      => $Scuole_nome[$t],
            'sito'      => $Scuole_sito[$t],
            'via'       => $Scuole_via[$t],
            'n_civico'  => $Scuole_n_civico[$t],
            'cod'       => $Scuole_cod[$t],
        ];
    }
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ambiti</title>
    <link rel="stylesheet" href="stile.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="stile_ambiti2.css">
    <link rel="stylesheet" href="stileOrientati.css">
</head>
<body>
    <?php include("../navbar.html"); ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>

<!-- HEADER -->
<div class="header">
    <h1>Ambiti</h1>
    <p class="paragraph">Esplora le aree disciplinari</p>
</div>

<!-- FILTER BAR (stessa struttura di orientati.php) -->
<div class="filter-bar">
    <div class="container">
        <form method="GET" action="" class="d-flex flex-wrap gap-2 align-items-center">
            <select name="zona" class="form-select filter-zona">
                <option value="0">Tutte le zone</option>
                <?php foreach ($zone as $z): ?>
                    <option value="<?= $z->ID_zona ?>" <?= $filtro_zona == $z->ID_zona ? 'selected' : '' ?>>
                        <?= htmlspecialchars($z->nome) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-cerca">
                <i class="bi bi-search me-1"></i> Cerca
            </button>
            <?php if ($filtro_zona): ?>
                <a href="ambiti.php" class="btn-reset">
                    <i class="bi bi-x-circle"></i> Reimposta
                </a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- CONTENUTO -->
<div class="container">

<?php for ($a = 0; $a < count($ambiti_nome); $a++):
    $id       = $ambiti_id[$a];
    $scuole_a = $scuole_per_ambito[$id] ?? [];

    // Se c'è un filtro zona attivo e l'ambito non ha scuole in quella zona, nascondi la card
    if ($filtro_zona > 0 && empty($scuole_a)) continue;
?>
    <div class="card" style="background:#<?php echo($colori[$a])?>">
        <div class="info" >
            <div>
                <div class="titolo">
                    <?= htmlspecialchars($ambiti_nome[$a]) ?>
                </div>
                <div class="descrizione">
                    <?= htmlspecialchars($ambiti_descrizione[$a]) ?>
                </div>
                <div class="descrizione">
                    <?php if (!empty($scuole_a)): ?>
                        <?php foreach ($scuole_a as $sc):
                            $cod = htmlspecialchars($sc['cod']);
                        ?>
                            <a style="background:#<?php echo($colori[$a])?>" href="orientati.php?open=<?= $cod ?>#<?= $cod ?>">
                                <?= htmlspecialchars($sc['nome']) ?>
                            </a><br>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <em>Nessuna scuola disponibile.</em>
                    <?php endif; ?>
                </div>
            </div>
            <div class="image">
                <img src="../pictures/ambiti/<?php echo($a+1)?>.png" alt="Immagine ambito">
            </div>
        </div>
    </div>
<?php endfor; ?>

<?php if ($filtro_zona > 0 && array_sum(array_map('count', $scuole_per_ambito)) === 0): ?>
    <div class="empty-state">
        <i class="bi bi-search"></i>
        <p>Nessun ambito con scuole nella zona selezionata.</p>
    </div>
<?php endif; ?>

</div>
    <?php include("footer.html"); ?>
</body>
</html>