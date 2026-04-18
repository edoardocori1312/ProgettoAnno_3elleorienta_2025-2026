/*
  Commenti generali (in italiano) - provaMappa.js

  Scopo del file:
  - Inizializza la mappa Leaflet con tile OpenStreetMap.
  - Gestisce un singolo marker (creato/spostato al click o tramite ricerca).
  - Fornisce una ricerca locale (array "places") con suggerimenti live.
  - Apre un modal quando si clicca una card per vedere i dettagli.

  "Contract" rapido:
  - Input: interazioni utente (click mappa, testo in input, click sulle card).
  - Output: posizionamento del marker sulla mappa, aggiornamento etichetta coordinate,
    apertura/chiusura del modal con contenuti della card.
  - Stato globale: window.singleMarker (istanza L.marker) e window.lastMarker (coords).

  Note per migliorare la grafica/UX:
  - Cambia la variabile CSS --mm-size (in provaMappa.css) per aumentare/diminuire la mini-mappa.
  - Per animazioni più morbide usa map.flyTo; per movimenti istantanei usa map.panTo.
  - Per aggiungere luoghi reali usare un servizio (API) o un file JSON invece dell'array statico `places`.

*/

// Inizializza la mappa (es. coordinate Milano) — disabilitiamo il controllo zoom (+/-)
const map = L.map('map', { zoomControl: false }).setView([45.4642, 9.19], 13);

// Layer di tile (OpenStreetMap)
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  maxZoom: 19,
  attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
}).addTo(map);

// Restringi la mappa all'Italia usando una bounding box approssimativa (lat, lng)
// SouthWest: 35.0N, 6.0E  — NorthEast: 47.5N, 19.5E
const italyBounds = L.latLngBounds([35.0, 6.0], [47.5, 19.5]);
map.setMaxBounds(italyBounds);
// rendi i confini 'rigidi' in modo che l'utente non possa spostarsi fuori
map.options.maxBoundsViscosity = 1.0;

// Comportamento marker singolo: crea un solo marker o lo sposta ai click successivi
// Variabili globali usate per mantenere lo stato tra le chiamate
window.singleMarker = null; // istanza L.marker quando presente
window.lastMarker = null; // coordinate dell'ultimo marker aggiunto/aggiornato

function setSingleMarker(lat, lng){
  // Se esiste già, lo spostiamo; altrimenti lo creiamo
  if(window.singleMarker){
    window.singleMarker.setLatLng([lat, lng]);
  } else {
    window.singleMarker = L.marker([lat, lng]).addTo(map);
  }

  // Salviamo le coordinate nell'oggetto globale per un eventuale uso futuro
  window.lastMarker = {lat: lat, lng: lng};

  /*
    Comportamento della camera:
    - Se l'utente è molto distante (zoom basso) facciamo un flyTo con zoom minimo
    - Altrimenti semplicemente recentriamo per non cambiare l'esperienza di zoom
    Regola `minZoomForMarker` per cambiare quanto ci si avvicina automaticamente.
  */
  const currentZoom = map.getZoom();
  const minZoomForMarker = 10; // se lo zoom è più lontano, aumentiamo fino a questo valore
  if(currentZoom < minZoomForMarker){
    map.flyTo([lat, lng], minZoomForMarker);
  } else {
    map.panTo([lat, lng]);
  }

  // Aggiorna l'etichetta delle coordinate (visibile nella mini-mappa)
  const lbl = document.getElementById('coordLabel');
  if(lbl){
    lbl.textContent = lat.toFixed(6) + ', ' + lng.toFixed(6);
    lbl.classList.remove('hidden');
  }
  console.log('Posizione marker impostata:', window.lastMarker);
}

/* --- DA FARE CON IL DB --- */
/*
  Elenco di luoghi di esempio (sostituire con dati reali/DB se disponibili).
  Consigli:
  - Se hai molti luoghi, usa paginazione o autocomplete con query al server.
  - Mantieni lat/lng in formato numerico e name come stringa.
*/
const places = [
  {name: 'Milano', lat:45.4642, lng:9.19},
  {name: 'Roma', lat:41.9028, lng:12.4964},
  {name: 'Torino', lat:45.0703, lng:7.6869},
  {name: 'Venezia', lat:45.4408, lng:12.3155},
  {name: 'Firenze', lat:43.7696, lng:11.2558},
  {name: 'Bologna', lat:44.4949, lng:11.3426},
  {name: 'Napoli', lat:40.8518, lng:14.2681},
  {name: 'Palermo', lat:38.1157, lng:13.3615},
  {name: 'Catania', lat:37.5079, lng:15.0830},
  {name: 'Bari', lat:41.1171, lng:16.8719}
];

const searchEl = document.getElementById('search');
const suggestionsEl = document.getElementById('suggestions');

function updateSuggestions(q){
  // Aggiorna la lista dei suggerimenti in base alla query digitata
  suggestionsEl.innerHTML = '';
  if(!q || q.trim().length === 0) return;
  const ql = q.toLowerCase();
  const matches = places.filter(p => p.name.toLowerCase().includes(ql)).slice(0,8);
  if(matches.length === 0){
    const li = document.createElement('li');
    li.className = 'list-group-item list-group-item-secondary disabled';
    li.textContent = 'nessun risultato con queste lettere';
    suggestionsEl.appendChild(li);
    return;
  }

  matches.forEach(p => {
    const li = document.createElement('li');
    li.className = 'list-group-item list-group-item-action';
    li.textContent = p.name;
    li.tabIndex = 0;
    li.addEventListener('click', function(){
      // Controllo che la posizione sia dentro i confini definiti (italyBounds)
      if(!italyBounds.contains([p.lat, p.lng])){ alert('Posizione fuori dall\'Italia'); return; }
      // Posizioniamo il marker e chiudiamo la lista suggerimenti
      setSingleMarker(p.lat, p.lng);
      searchEl.value = p.name;
      suggestionsEl.innerHTML = '';
    });
    li.addEventListener('keydown', function(ev){ if(ev.key === 'Enter') li.click(); });
    suggestionsEl.appendChild(li);
  });
}

// Al variare dell'input aggiorniamo i suggerimenti e gestiamo la selezione automatica
searchEl.addEventListener('input', function(e){ 
  const q = e.target.value;
  updateSuggestions(q);
  // se l'utente ha digitato esattamente il nome di un luogo, selezionalo automaticamente
  if(q && q.trim().length>0){
    const exact = places.find(p => p.name.toLowerCase() === q.trim().toLowerCase());
    if(exact){
      if(!italyBounds.contains([exact.lat, exact.lng])){ alert('Posizione fuori dall\'Italia'); return; }
      setSingleMarker(exact.lat, exact.lng);
      searchEl.value = exact.name;
      suggestionsEl.innerHTML = '';
    }
  }
});
// Gestione tasto Enter: se l'utente preme Invio e c'è almeno un suggerimento, selezioniamo il primo
searchEl.addEventListener('keydown', function(e){
  if(e.key === 'Enter'){
    e.preventDefault();
    const first = suggestionsEl.querySelector('.list-group-item-action');
    if(first){ first.click(); }
  }
});
// Nascondi i suggerimenti al blur (timeout breve per permettere il click su una voce)
searchEl.addEventListener('blur', function(){ setTimeout(()=> suggestionsEl.innerHTML = '', 150); });

// Click sulla mappa: posizione del marker (crea o sposta)
map.on('click', function(e){
  const lat = e.latlng.lat;
  const lng = e.latlng.lng;
  // controlla che il click sia dentro i confini dell'Italia
  if(!italyBounds.contains(e.latlng)){
    alert('Posizione fuori dall\'Italia: scegli un punto interno ai confini.');
    return;
  }
  setSingleMarker(lat, lng);
});

// Assicura che i tiles vengano renderizzati correttamente dentro il contenitore circolare
setTimeout(function(){ map.invalidateSize(); }, 200);

// --- Modal per le card cliccabili ---
const cardModal = document.getElementById('cardModal');
const modalImg = document.getElementById('modalImg');
const modalTitle = document.getElementById('modalTitle');
const modalText = document.getElementById('modalText');

function openCardModal(imgSrc, title, text){
  if(!cardModal) return;
  modalImg.src = imgSrc || '';
  modalImg.alt = title || '';
  modalTitle.textContent = title || '';
  modalText.textContent = text || '';
  cardModal.setAttribute('aria-hidden','false');
}

function closeCardModal(){
  if(!cardModal) return;
  cardModal.setAttribute('aria-hidden','true');
  // svuota contenuti opzionale
  modalImg.src = '';
  modalTitle.textContent = '';
  modalText.textContent = '';
}

// Apri modal al click su qualsiasi card .info-card usando event delegation
// Questo garantisce che funzioni anche per card generate dinamicamente lato server
const cardsWrapper = document.querySelector('.cards-wrapper');
if(cardsWrapper){
  cardsWrapper.addEventListener('click', function(e){
    // Trova l'articolo .info-card più vicino al target
    const card = e.target.closest('.info-card');
    if(!card || !cardsWrapper.contains(card)) return;
    card.style.cursor = 'pointer';

    // Recupera contenuti per il modal
    const imgEl = card.querySelector('img');
    const titleEl = card.querySelector('.card-title');
    const textEl = card.querySelector('.card-text');
    const imgSrc = imgEl ? imgEl.src : '';
    const title = titleEl ? titleEl.textContent : '';
    const text = textEl ? textEl.textContent : '';
    openCardModal(imgSrc, title, text);

    // Se la card ha coordinate (data-lat/data-lng), posiziona il marker sulla mappa
    const lat = card.dataset.lat;
    const lng = card.dataset.lng;
    if(lat && lng){
      const nlat = parseFloat(lat);
      const nlng = parseFloat(lng);
      if(!Number.isNaN(nlat) && !Number.isNaN(nlng)){
        // assicuriamoci che siano dentro i confini definiti
        if(italyBounds.contains([nlat, nlng])){
          setSingleMarker(nlat, nlng);
        }
      }
    }
  });
}

// Close handlers
document.querySelectorAll('[data-close]').forEach(el => el.addEventListener('click', closeCardModal));
// Listener aggiuntivo mirato al pulsante di chiusura (più affidabile se ci sono sovrapposizioni)
const modalCloseBtn = document.getElementById('cardModalClose');
if(modalCloseBtn){ modalCloseBtn.addEventListener('click', closeCardModal); }
document.addEventListener('keydown', function(e){ if(e.key === 'Escape') closeCardModal(); });