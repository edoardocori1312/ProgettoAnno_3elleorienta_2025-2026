<?php
if(!isset($conn)){
  include_once(__DIR__ . '/../daticonnessione.php');
}

$events = [];
if(isset($conn) && $conn instanceof mysqli){
  // Se c'è un filtro per città, applicalo; altrimenti mostra tutti gli eventi
  $filterCity = isset($_GET['citta']) ? trim($_GET['citta']) : '';
  
  if($filterCity !== ''){
    $sql = "SELECT e.ID_evento AS id, e.titolo AS title, e.descrizione_breve AS summary, e.descrizione AS description, e.ora_inizio, e.ora_fine, e.via_P AS address, e.n_civico_P AS number, e.id_foto as image_id, e.lat, e.lng 
            FROM eventi e
            LEFT JOIN Citta c ON e.id_citta = c.ID_citta
            WHERE e.visibile = 1 AND LOWER(c.nome) LIKE LOWER(?)
            ORDER BY e.ora_inizio DESC";
    $stmt = $conn->prepare($sql);
    $likeFilter = '%' . $filterCity . '%';
    $stmt->bind_param('s', $likeFilter);
    $stmt->execute();
    $res = $stmt->get_result();
  } else {
    // Mostra tutti gli eventi senza filtro
    $sql = "SELECT ID_evento AS id, titolo AS title, descrizione_breve AS summary, descrizione AS description, ora_inizio, ora_fine, via_P AS address, n_civico_P AS number, id_foto as image_id, lat, lng FROM eventi WHERE visibile = 1 ORDER BY ora_inizio DESC";
    $res = $conn->query($sql);
  }
  
  if($res){
    while($row = $res->fetch_assoc()){
      $events[] = $row;
    }
    $res->free();
  }
}

// Se non ci sono eventi, mostra messaggio o sample
if(count($events) === 0){
  $events = [
    ['id'=>0,'title'=>'Nessun evento trovato','summary'=>'Controlla il pannello di amministrazione','description'=>'Al momento non ci sono eventi visibili.','ora_inizio'=>null,'ora_fine'=>null,'address'=>'','number'=>'','image_id'=>null,'lat'=>null,'lng'=>null]
  ];
}

// Stampiamo l'HTML della sezione eventi
?>
<section id="events_section_wrapper" class="mt-4" aria-label="Sezione Eventi">
  <div class="container">
    <div id="events_section" class="cards-wrapper" aria-live="polite">
<?php foreach($events as $evt):
      $img = ($evt['image_id']) ? 'uploads/' . $evt['image_id'] : '../stile/placeholder-card.svg';
      $date = $evt['ora_inizio'] ? date('Y-m-d', strtotime($evt['ora_inizio'])) : '';
      $address = trim(($evt['address'] ?? '') . ' ' . ($evt['number'] ?? ''));
      $lat = isset($evt['lat']) && $evt['lat'] ? floatval($evt['lat']) : 0;
      $lng = isset($evt['lng']) && $evt['lng'] ? floatval($evt['lng']) : 0;
      // Aggiungi la classe 'no-distance-filter' per le card fittizie (id=0)
      $noFilterClass = ($evt['id'] == 0) ? ' no-distance-filter' : '';
?>
      <article class="card info-card<?php echo $noFilterClass; ?>" role="article" aria-labelledby="evt-<?php echo htmlspecialchars($evt['id']); ?>-title" data-lat="<?php echo $lat; ?>" data-lng="<?php echo $lng; ?>">
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