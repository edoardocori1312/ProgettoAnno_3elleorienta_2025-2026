<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/layout.php';

$conn = db();
$prj = $conn->query(
    'SELECT f.path_foto FROM Progetti p
     LEFT JOIN Foto f ON p.id_foto = f.ID_foto
     WHERE p.data_eliminazione IS NULL AND f.path_foto IS NOT NULL
     ORDER BY p.n_ordine ASC LIMIT 1'
)->fetch_assoc();
$conn->close();

render_head_pubblica('Home');
render_navbar_pubblica('index.php');
?>

<!-- Carousel hero -->
<div id="heroCarousel" class="carousel slide" data-bs-ride="carousel">
  <div class="carousel-inner">
    <div class="carousel-item active">
      <div class="hero-slide">
        <img src="assets/img/slide1.jpg" alt="Studenti a scuola">
        <div class="hero-caption"><h2>A scuola per crescere insieme…</h2></div>
      </div>
    </div>
    <div class="carousel-item">
      <div class="hero-slide">
        <img src="assets/img/slide2.jpg" alt="Orientamento scolastico">
        <div class="hero-caption"><h2>Scegli il tuo futuro con consapevolezza…</h2></div>
      </div>
    </div>
    <div class="carousel-item">
      <div class="hero-slide">
        <img src="assets/img/slide3.jpg" alt="Reti territoriali">
        <div class="hero-caption"><h2>Reti territoriali per l'orientamento…</h2></div>
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

<!-- Progetto per l'orientamento -->
<section class="section-progetto">
  <h3>Progetto per l'orientamento</h3>
  <div class="progetto-inner">
    <div class="progetto-logo">
      <?php if ($prj): ?>
      <img src="../<?= htmlspecialchars($prj['path_foto']) ?>" alt="Progetto Svelati">
      <?php else: ?>
      <img src="assets/img/logo.png" alt="Logo Svelati">
      <?php endif; ?>
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

<!-- I nostri ambiti -->
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

<?php render_footer(); ?>
<?php chiudi_pagina_pubblica(); ?>
