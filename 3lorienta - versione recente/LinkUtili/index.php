<?php
// ── Avvio della sessione PHP (necessario per gestione utenti/admin)
session_start();

// ── Inclusione del file con le credenziali del database 
include_once("../connessione/db.php");

// ── Apertura connessione al database tramite MySQLi 
$conn = new mysqli($HOSTDB, $USERDB, $PASSDB, $NOMEDB);
$conn->set_charset("utf8mb4");

// ── Se la connessione fallisce, interrompe l'esecuzione e mostra l'errore 
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}

// ── Query: recupera tutti i link attivi (non eliminati) con la foto associata 
// ── LEFT JOIN su Foto: include anche link senza foto (path_foto sarà NULL) 
// ── Condizione su f.data_eliminazione: esclude foto eliminate dal join 
// ── Ordinamento per n_ordine crescente 
$res = $conn->query("
    SELECT l.*, f.path_foto
    FROM Links l
    LEFT JOIN Foto f ON f.ID_foto = l.id_foto AND f.data_eliminazione IS NULL
    WHERE l.data_eliminazione IS NULL
    ORDER BY l.n_ordine ASC
");
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <!-- Charset e viewport per responsive design -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Titolo della pagina -->
    <title>Link Utili - 3elleOrienta</title>

    <!-- Foglio di stile personalizzato del progetto -->
    <link rel="stylesheet" href="style/stile.css">

    <!-- Bootstrap 5: framework CSS per layout e componenti UI -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css">

    <!-- Bootstrap Icons: libreria di icone vettoriali -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

<?php include_once("navbar.html"); /* Inclusione della barra di navigazione */ ?>

<!-- Sezione principale con padding verticale -->
<div class="hero-banner">
    <div class="banner-wrapper container">
        <h1>Link utili</h1>
        <p class="hero-subtitle">Esplora i link che possono aiutarti ad orientarti</p>
    </div>
</div>
<main class="py-5">
    <div class="container">

        <!-- Griglia responsive: 1 colonna su mobile, 3 su desktop, gap 4 -->
        <div class="row row-cols-1 row-cols-md-3 g-4 align-items-start">

            <?php
            $i = 0; // Contatore usato per generare ID univoci dei pannelli collapse

            // Ciclo su ogni riga restituita dalla query 
            while ($a = $res->fetch_assoc()):

                // ID univoco per il pannello collapse di questa card
                $collapseId = "desc-" . $i;

                //Sanitizzazione: htmlspecialchars 
                $url   = htmlspecialchars($a['url_link']);    // URL del link esterno
                $title = htmlspecialchars($a['titolo']);      // Titolo del link
                $desc  = htmlspecialchars($a['descrizione']); // Descrizione testuale

                // Sorgente immagine: path dal DB oppure placeholder 
                //path_foto proviene dalla LEFT JOIN; è NULL se nessuna foto è associata
                $imgSrc = !empty($a['path_foto'])
                    ? htmlspecialchars($a['path_foto'])  // URL foto caricata sul server
                    : "img/placeholder_link.png";        // Immagine di fallback locale
            ?>

            <!-- Colonna della card (Bootstrap gestisce la larghezza) -->
            <div class="col">

                <!-- Card con altezza piena e ombra leggera -->
                <div class="card h-100 shadow-sm">

                    <!-- Immagine cliccabile che apre il link in una nuova scheda ── -->
                    <a href="<?= $url ?>" target="_blank" rel="noopener noreferrer">
                        <img
                            src="<?= $imgSrc ?>"
                            class="card-img-top"
                            alt="<?= $title ?>"
                            style="object-fit:contain; height:120px; width:100%; background:#f8f9fa;">
                    </a>

                    <!-- Corpo della card: flex colonna per spingere il bottone in fondo -->
                    <div class="card-body d-flex flex-column">

                        <!-- Titolo del link -->
                        <h5 class="card-title mb-2"><?= $title ?></h5>

                        <!-- ── Bottone toggle per espandere/comprimere la descrizione ── -->
                        <button
                            class="btn btn-sm mb-2 w-100 d-flex justify-content-between align-items-center"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#<?= $collapseId ?>"
                            style="background-color:#3abcb1; color:#fff; border:none;">
                            <span>Mostra descrizione</span> <!-- Testo che cambia dinamicamente via JS -->
                            <i class="bi bi-chevron-down toggle-icon"></i> <!-- Icona freccia -->
                        </button>

                        <!--Pannello collassabile con la descrizione -->
                        <div class="collapse" id="<?= $collapseId ?>">
                            <!-- Testo descrittivo del link, già sanitizzato sopra -->
                            <p class="card-text mb-2"><?= $desc ?></p>
                        </div>

                        <a href="<?= $url ?>"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="btn w-100 mt-auto"
                           style="background-color:#3abcb1; color:#fff; border:none;">
                           Vai al link
                        </a>

                    </div><!-- fine card-body -->

                </div><!-- fine card -->

            </div><!-- fine col -->

            <?php
            $i++;       // Incrementa contatore per il prossimo ID collapse univoco
            endwhile;   // Fine ciclo while sui risultati della query

            $res->free();   // Libera la memoria occupata dal risultato della query
            $conn->close(); // Chiude la connessione al database
            ?>

        </div><!-- fine row -->
    </div><!-- fine container -->
</main>

<?php include_once("../footer.html"); /* Inclusione del footer comune */ ?>

<!-- Bootstrap JS bundle (include Popper.js): necessario per collapse e altri componenti -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

<script>
//Gestione dinamica icona e testo dei bottoni collapse 
document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(btn => {

    // Pannello collapse target di questo bottone
    const target = document.querySelector(btn.dataset.bsTarget);

    // Icona freccia dentro il bottone
    const icon = btn.querySelector('.toggle-icon');

    // Span con il testo "Espandi" / "Comprimi"
    const span = btn.querySelector('span');

    //Quando il pannello inizia ad aprirsi: freccia su + testo "Comprimi"
    target.addEventListener('show.bs.collapse', () => 
    {
        icon.classList.replace('bi-chevron-down', 'bi-chevron-up'); // Ruota icona verso l'alto
        span.textContent = 'Nascondi descrizione'; // Aggiorna etichetta bottone
    });

    // Quando il pannello inizia a chiudersi: freccia giù + testo "Espandi"
    target.addEventListener('hide.bs.collapse', () => 
    {
        icon.classList.replace('bi-chevron-up', 'bi-chevron-down'); // Riporta icona verso il basso
        span.textContent = 'Mostra descrizione'; // Riporta etichetta originale
    });
});
</script>

</body>
</html>