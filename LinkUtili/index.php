<?php
//Start della sessione
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
//includo i  dati di connessione
include_once("/home/uawit4pc/domains/3elleorienta.sviluppo.host/public_html/connessione/db.php");
//includo il file per gestione foto

//Connessione al database
$conn = mysqli_connect($HOSTDB, $USERDB, $PASSDB, $NOMEDB);

//Recupero tutti i link con la loro foto tramite JOIN (solo link che hanno una foto)
$res = $conn->query("
    SELECT l.*, f.path_foto
    FROM Links l
    INNER JOIN Foto f ON l.id_foto = f.ID_foto AND f.data_eliminazione IS NULL
");
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Link Utili - 3elleorienta</title>
    <link rel="stylesheet" href="style/stile.css">
    <link rel="stylesheet" href="../stile.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

<?php include_once("navbar.html"); //Includo la NavBar del sito ?>

<main class="py-5">
    <div class="container">
        <div class="row row-cols-1 row-cols-md-3 g-4 align-items-start">
            <?php
            //Variabile  per il ciclo
            $i = 0;
            //Ciclo su tutti i record del risultato della query
            while ($a = $res->fetch_assoc()):
                //ID univoco per il collapse di ogni card
                $collapseId = "desc-" . $i;
                
                //Sanitizzazione output per sicurezza
                $url   = htmlspecialchars($a['url_link']);
                $title = htmlspecialchars($a['titolo']);
                $desc  = htmlspecialchars($a['descrizione']);
                $img   = htmlspecialchars($a['path_foto']);
            ?>
            <div class="col">
                <div class="card shadow-sm">
                    <!-- Immagine cliccabile che porta al link -->
                    <a href="<?= $url ?>" target="_blank">
                        <img src="<?= $img ?>" class="card-img-top" alt="<?= $title ?>">
                    </a>
                    
                    <div class="card-body">
                        <h5 class="card-title mb-2"><?= $title ?></h5>
                        <!-- Bottone toggle per descrizione collassabile -->
                        <button class="btn btn-outline-secondary btn-sm mb-2 w-100 d-flex justify-content-between align-items-center"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#<?= $collapseId ?>">
                            <span>Espandi</span>
                            <i class="bi bi-chevron-down toggle-icon"></i>
                        </button>
                         <!-- Descrizione collassabile -->
                        <div class="collapse" id="<?= $collapseId ?>">
                            <p class="card-text mb-2"><?= $desc ?></p>
                        </div>
                        
                        <a href="<?= $url ?>" target="_blank" class="btn btn-primary w-100 mt-2">Vai al link</a>
                    </div>
                </div>
            </div>
            <?php
            //Incrementa il valore di $i di 1 (i = i + 1) per il prossimo ciclo
            $i++;
            //fine del ciclo 
            endwhile;
            //Chiusura della connessione
            $conn->close();
            ?>
        </div>
    </div>
</main>
<?php include_once("../footer.html"); //Includo il footer del sito ?>
<!-- Questo script carica i componenti JavaScript di Bootstrap necessari per far funzionare le animazioni di apertura/chiusura --->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script>
//Seleziona tutti gli elementi HTML che hanno l'attributo 'data-bs-toggle="collapse"'.
//Cicla su ognuno di essi usando il metodo .forEach().
document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(btn =>
{
    //Leggendo l'ID memorizzato nell'attributo 'data-bs-target' del pulsante.
    const target = document.querySelector(btn.dataset.bsTarget);
    //Cerca all'interno del pulsante un elemento <span> che contiene il testo descrittivo.
    const icon = btn.querySelector('.toggle-icon');
    //Cerca all'interno del pulsante un elemento <span> che contiene il testo descrittivo.
    const span = btn.querySelector('span');
    //Sostituisce la classe dell'icona da "freccia in giù" a "freccia in su".
    target.addEventListener('show.bs.collapse', () => 
    {
        //Sostituisce la classe dell'icona da "freccia in giù" a "freccia in su".
        icon.classList.replace('bi-chevron-down', 'bi-chevron-up');
        //Cambia il testo contenuto nello span in "Comprimi".    
        span.textContent = 'Comprimi';

    });
    //Aggiunge un "listener" che si attiva quando l'elemento target sta per essere nascosto ('hide.bs.collapse').        
    target.addEventListener('hide.bs.collapse', () => 
    {
        //Sostituisce la classe dell'icona da "freccia in su" a "freccia in giù".
        icon.classList.replace('bi-chevron-up', 'bi-chevron-down');
        //Cambia il testo contenuto nello span in "Espandi".    
        span.textContent = 'Espandi';
    });
});
</script>
</body>
</html>