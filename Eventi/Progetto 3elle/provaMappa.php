<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mappa</title>
  <!-- CSS di Leaflet -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
  <!-- CSS di Bootstrap per una UI più curata -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"/>
  <link rel="stylesheet" href="provaMappa.css"/>

  <!--
    Note d'uso (HTML):
    - Sostituisci il file `Svelati_Color_Pos-2048x1448-2.jpg` con il tuo logo, oppure usa l'SVG di fallback.
    - Aggiungi/duplica gli <article class="info-card"> nella sezione cards-wrapper per mostrare più elementi.
    - Le modifiche grafiche principali vanno fatte in provaMappa.css (variabile --mm-size, dimensione card, ecc.).
  -->
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

  <!-- Contenitore ricerca: qui l'utente digita e vede suggerimenti -->
  <div class="search-container" aria-hidden="false">
    <!-- La header principale ora è in cima alla pagina; qui rimane solo il filtro/search -->
    <div class="input-group">
      <!-- Cambia placeholder/width per adattare al tuo pubblico -->
      <input id="search" type="search" class="form-control" placeholder="Cerca luogo... (es. Milano)" autocomplete="off" aria-label="Cerca luogo">
      <span class="input-group-text">🔍</span>
    </div>
    <!-- I suggerimenti vengono popolati dinamicamente da provaMappa.js -->
    <ul id="suggestions" class="list-group mt-2" role="listbox" aria-label="Suggerimenti"></ul>
  </div>

  <!-- Sezione card centrata sotto il filtro: sostituisci le immagini con le tue
       Suggerimento: mantieni immagini con lo stesso aspect ratio per evitare crop strani.
  -->
  <section class="cards-wrapper" aria-label="Schede informative">
    <article class="card info-card" aria-labelledby="card1-title">
      <img src="placeholder-card.svg" class="card-img-top" alt="Immagine card 1">
      <div class="card-body">
        <h5 id="card1-title" class="card-title">Titolo Card 1</h5>
        <p class="card-text">Testo descrittivo breve per la card numero 1. Sostituisci l'immagine con la tua.</p>
      </div>
    </article>

    <article class="card info-card" aria-labelledby="card2-title">
      <img src="placeholder-card.svg" class="card-img-top" alt="Immagine card 2">
      <div class="card-body">
        <h5 id="card2-title" class="card-title">Titolo Card 2</h5>
        <p class="card-text">Testo descrittivo breve per la card numero 2. Puoi inserire qui una breve descrizione.</p>
      </div>
    </article>

    <article class="card info-card" aria-labelledby="card3-title">
      <img src="placeholder-card.svg" class="card-img-top" alt="Immagine card 3">
      <div class="card-body">
        <h5 id="card3-title" class="card-title">Titolo Card 3</h5>
        <p class="card-text">Testo descrittivo breve per la card numero 3. Testo dimostrativo.</p>
      </div>
    </article>
  </section>

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