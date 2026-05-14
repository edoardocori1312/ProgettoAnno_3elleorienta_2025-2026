<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mappa</title>
  <!-- CSS di Bootstrap per una UI più curata -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"/>
  <link rel="stylesheet" href="provaMappa.css"/>

</head>
<body>
  <!-- Header principale: logo a sinistra e menu a destra -->
  <!-- Commento: qui puoi cambiare l'immagine del logo, o inserire una versione più piccola per header -->
  <header class="site-header" role="banner">
    <div class="logo-wrap">
      <!-- Logo ridotto nel header (fallback a SVG se la JPG non è disponibile) -->
      <img src="Svelati_Color_Pos-2048x1448-2.jpg" alt="3ElleOrienta" class="logo" onerror="this.onerror=null; this.src='3ElleOrienta.svg'" />
    </div>
    <div class="header-menu">
      <!-- Menu dimostrativo: sostituisci con i link o controlli che preferisci -->
      <select id="fakeMenuTop" class="form-select form-select-sm" aria-label="Menu principale">
        <option selected>Seleziona...</option>
        <option value="optA">Opzione A</option>
        <option value="optB">Opzione B</option>
        <option value="setting1">Impostazione 1</option>
        <option value="info">Info</option>
      </select>
    </div>
  </header>





  <!-- Modal semplice per visualizzare dettagli card (senza dipendenze JS esterne) -->
  <!-- Nota: provaMappa.js apre/chiude il modal copiando l'immagine/titolo/test nella finestra -->
  <div id="cardModal" class="card-modal" aria-hidden="true" role="dialog" aria-modal="true">
    <div class="card-modal-backdrop" data-close></div>
    <div class="card-modal-inner" role="document">
      <button id="cardModalClose" class="card-modal-close" aria-label="Chiudi" data-close>&times;</button>
      <img id="modalImg" src="" alt="" class="modal-img">
      <div class="modal-content">
        <h4 id="modalTitle"></h4>
        <p id="modalText"></p>
      </div>
    </div>
  </div>

  <!-- Contenitore mappa: il div #map ospita la mappa Leaflet (mini-mappa circolare) -->
  <!-- L'etichetta #coordLabel viene aggiornata da JS quando si imposta un marker -->
  <div id="map">
    <div id="coordLabel" class="coord-label hidden" aria-hidden="true"></div>
  </div>

  <!-- JS di Leaflet -->
   <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
   <!-- Carica il JavaScript separato per la mappa (qui è tutto il comportamento) -->
   <script src="provaMappa.js"></script>
</body>
</html>