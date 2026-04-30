<?php
include("daticonnessione.php"); //includere i dati connessione php
include("navbar.html");
?>

<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Svelati – Reti territoriali per l'orientamento</title>

  <link rel="stylesheet" href="stile.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
</head>

<body>

<?php


$logo_svelati = "logo_un_po_piu_grande.jpg"; // fallback locale

$sql = "SELECT f.path_foto FROM Progetti p JOIN Foto f ON f.ID_foto = p.id_foto WHERE p.n_ordine = 1 AND p.data_eliminazione IS NULLAND f.data_eliminazione IS NULL LIMIT 1";

$result = $conn->query($sql);
if ($result && $row = $result->fetch_assoc()) {
    $logo_svelati = "https://" . $row["path_foto"];
}
?>

<div id="heroCarousel" class="carousel slide" data-bs-ride="carousel">
  <div class="carousel-inner">

    <div class="carousel-item active">
      <div class="hero-slide">
        <img src="slide1.jpg" alt="Studenti a scuola">
        <div class="hero-caption">
          <h2>A scuola per crescere insieme…</h2>
        </div>
      </div>
    </div>

    <div class="carousel-item">
      <div class="hero-slide">
        <img src="slide2.jpg" alt="Orientamento scolastico">
        <div class="hero-caption">
          <h2>Scegli il tuo futuro con consapevolezza…</h2>
        </div>
      </div>
    </div>

    <div class="carousel-item">
      <div class="hero-slide">
        <img src="slide3.jpg" alt="Reti territoriali">
        <div class="hero-caption">
          <h2>Reti territoriali per l'orientamento…</h2>
        </div>
      </div>
    </div>

  </div>

  <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
    <span class="visually-hidden">Precedente</span>
  </button>

  <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
    <span class="carousel-control-next-icon" aria-hidden="true"></span>
    <span class="visually-hidden">Successivo</span>
  </button>
</div>



<section class="section-progetto">
  <h3>Progetto per l'orientamento</h3>

  <div class="progetto-inner">

    <div class="progetto-logo">
      <img src="<?php echo htmlspecialchars($logo_svelati); ?>" alt="Logo Svelati">
    </div>

    <div class="progetto-text">
      <div class="progetto-title">SVELATI</div><br>
      <div class="progetto-subtitle">Piattaforma 3elleorienta</div>

      <div class="progetto-buttons">
        <a href="orientati.php" class="btn-orientati">Orientati</a>
        <a href="eventi.php"    class="btn-eventi">Eventi</a>
      </div>
    </div>

  </div>
</section>

<section class="section-ambiti">
  <h3>I nostri ambiti</h3>

  <div class="ambiti-grid">

    <div class="ambito-card">
      <i class="bi bi-mortarboard"></i>
      <h4>Scuola &amp; Formazione</h4>
    </div>

    <div class="ambito-card">
      <i class="bi bi-briefcase"></i>
      <h4>Lavoro &amp; Professioni</h4>
    </div>

    <div class="ambito-card">
      <i class="bi bi-people"></i>
      <h4>Territorio &amp; Reti</h4>
    </div>

    <div class="ambito-card">
      <i class="bi bi-lightbulb"></i>
      <h4>Innovazione</h4>
    </div>

  </div>
</section>


<?php include("footer.html"); ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>
</html>
