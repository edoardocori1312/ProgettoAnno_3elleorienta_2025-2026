<?php
    session_start();
    include("daticonnessione.php");
    $sql = "SELECT * FROM ambiti";
    $ambiti_id = [];
    $ambiti_nome = [];
    $ambiti_descrizione = [];
    $riga;
    $i = 0;
    if($stmt = $conn->prepare($sql))
    {
        if($stmt->execute() === true)
        {
            $res = $stmt->get_result();
            while($riga = $res->fetch_object())
            {
                $ambiti_id[$i] = $riga->ID_ambito;
                $ambiti_nome[$i] = $riga->nome;
                $ambiti_descrizione[$i] = $riga->descrizione;
                $i = $i + 1;
            }
        }
    }

    // Aggiunto COD_meccanografico alla query per costruire il link diretto alla card
    $sql_scuole = "SELECT sa.id_ambito, s.nome, s.sito, s.via, s.n_civico, s.COD_meccanografico
                   FROM scuole_ambiti sa JOIN scuole s ON sa.cod_scuola = s.COD_meccanografico";
    $Scuole_id_ambiti = [];
    $Scuole_descrizione = [];
    $Scuole_nome = [];
    $Scuole_sito = [];
    $Scuole_via = [];
    $Scuole_n_civico = [];
    $Scuole_cod = [];
    $riga;
    $i = 0;
    if($stmt = $conn->prepare($sql_scuole))
    {
        if($stmt->execute() === true)
        {
            $res = $stmt->get_result();
            while($riga = $res->fetch_object())
            {
                $Scuole_id_ambiti[$i] = $riga->id_ambito;
                $Scuole_nome[$i] = $riga->nome;
                $Scuole_sito[$i] = $riga->sito;
                $Scuole_via[$i] = $riga->via;
                $Scuole_n_civico[$i] = $riga->n_civico;
                $Scuole_cod[$i] = $riga->COD_meccanografico;
                $i = $i + 1;
            }
        }
    }
    
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ambiti</title>
    <link rel="stylesheet" href="stile/stile.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
</head>
<body>
    <?php include("stile/navbar.html"); ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>

<!-- HEADER -->
<div class="header">
    <h1>Ambiti</h1>
    <p>Esplora le aree disciplinari</p>
</div>

<!-- CONTENUTO -->
<div class="container">

<?php for ($a = 0; $a < count($ambiti_nome); $a++): ?> 
    <div class="card"> 
        <div class="info"> 
            <div> 
                <div class="titolo"> 
                    <?= htmlspecialchars($ambiti_nome[$a]) ?> 
                </div> 
                <div class="descrizione">
                    <?= htmlspecialchars($ambiti_descrizione[$a]) ?>
                </div>
                <div class="descrizione">
                    <?php for ($t = 0; $t < $i; $t++) 
                    { 
                        if ($Scuole_id_ambiti[$t] == $ambiti_id[$a]) 
                        { 
                            // Link verso orientati.php con anchor alla card specifica
                            // orientati.php apre la card grazie al parametro open= e all'anchor #card-COD
                            $cod = htmlspecialchars($Scuole_cod[$t]);
                            $nome_enc = urlencode($Scuole_nome[$t]);
                            echo "<a href='orientati.php?open={$cod}#{$cod}'>"
                               . htmlspecialchars($Scuole_nome[$t])
                               . "</a><br>";
                        } 
                    }
                    ?> 
                </div>
            </div>
            <div class="image"> 
                <img src="img/banchi.jpg" alt="Immagine ambito">
            </div> 
        </div> 
    </div>
<?php endfor; ?>

</div>

<?php include("stile/footer.html"); ?>

</body>
</html>