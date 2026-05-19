<?php
if(!isset($conn)){
  include_once("/home/uawit4pc/domains/3elleorienta.sviluppo.host/public_html/connessione/db.php");
  $conn = new mysqli($HOSTDB, $USERDB, $PASSDB, $NOMEDB);
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$events = [];
if(isset($conn) && $conn instanceof mysqli){
  $sql = "SELECT ID_evento AS id, titolo AS title, descrizione_breve AS summary, descrizione AS description, ora_inizio, ora_fine, via_P AS address, n_civico_P AS number, path_foto FROM Eventi 
  INNER JOIN Foto on Eventi.id_foto=Foto.ID_foto
  WHERE visibile = 1 ORDER BY ora_inizio DESC LIMIT 8";
  if($res = $conn->query($sql)){
    while($row = $res->fetch_assoc()){
      $events[] = $row;
    }
    $res->free();
  }
}

// Se non ci sono eventi, mostra messaggio o sample
if(count($events) === 0){
  $events = [
    ['id'=>0,'title'=>'Nessun evento trovato','summary'=>'Controlla il pannello di amministrazione','description'=>'Al momento non ci sono eventi visibili.','ora_inizio'=>null,'ora_fine'=>null,'address'=>'','number'=>'','image_id'=>null]
  ];
}

// Stampiamo l'HTML della sezione eventi
?>
<section id="events_section_wrapper" class="mt-4" aria-label="Sezione Eventi">
  <div class="container">
    <div id="events_section" class="cards-wrapper" aria-live="polite">
<?php foreach($events as $evt):
      $img=$evt['path_foto'];
      $date = $evt['ora_inizio'] ? date('Y-m-d', strtotime($evt['ora_inizio'])) : '';
      $address = trim(($evt['address'] ?? '') . ' ' . ($evt['number'] ?? ''));
?>
      <article class="card info-card" role="article" aria-labelledby="evt-<?php echo htmlspecialchars($evt['id']); ?>-title">
        <img src="<?php echo htmlspecialchars($img); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($evt['title']); ?>">
        <div class="card-body">
          <h5 id="evt-<?php echo htmlspecialchars($evt['id']); ?>-title" class="card-title"><?php echo htmlspecialchars($evt['title']); ?></h5>
          <p class="card-text"><?php echo htmlspecialchars($evt['summary'] ?? substr($evt['description'] ?? '',0,140)); ?></p>
          <div class="mt-2"><small class="text-muted"><?php echo htmlspecialchars($date . ' • ' . $address); ?></small></div>
        </div>
      </article>
<?php endforeach; ?>
    </div>
  </div>
</section>