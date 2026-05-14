
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

// Crea un'icona personalizzata rossa per i marker dei posti predefiniti
const redMarkerIcon = L.icon({
  iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
  shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
  iconSize: [25, 41],
  iconAnchor: [12, 41],
  popupAnchor: [1, -34],
  shadowSize: [41, 41]
});

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

// Funzione per calcolare la distanza tra due coordinate (formula di Haversine) in km
function getDistanceInKm(lat1, lng1, lat2, lng2){
  const R = 6371; // Raggio della Terra in km
  const dLat = (lat2 - lat1) * Math.PI / 180;
  const dLng = (lng2 - lng1) * Math.PI / 180;
  const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
            Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
            Math.sin(dLng/2) * Math.sin(dLng/2);
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
  return R * c;
}

// Filtra gli eventi per distanza dalla posizione cliccata sulla mappa (raggio di 10 km)
function filterEventsByDistance(lat, lng, radiusKm){
  const cards = document.querySelectorAll('.info-card');
  let visibleCount = 0;
  
  cards.forEach(function(card){
    // Ignora le card con la classe 'no-distance-filter' (es. il messaggio "nessun evento")
    if(card.classList.contains('no-distance-filter')){
      card.style.display = 'none'; // Nascondi il messaggio di "nessun evento" quando applichi un filtro
      return;
    }
    
    // Leggi lat/lng dalla card (devono essere in attributi data-lat e data-lng)
    const cardLat = parseFloat(card.getAttribute('data-lat'));
    const cardLng = parseFloat(card.getAttribute('data-lng'));
    
    if(!Number.isNaN(cardLat) && !Number.isNaN(cardLng)){
      const distance = getDistanceInKm(lat, lng, cardLat, cardLng);
      if(distance <= radiusKm){
        card.style.display = '';
        visibleCount++;
      } else {
        card.style.display = 'none';
      }
    }
  });
  
  // Se non ci sono eventi nel raggio, mostra il messaggio di "nessun evento"
  if(visibleCount === 0){
    const noEventCard = document.querySelector('.info-card.no-distance-filter');
    if(noEventCard){
      noEventCard.style.display = '';
    }
  }
  
  console.log('Filtro distanza: ' + visibleCount + ' eventi trovati nel raggio di ' + radiusKm + ' km');
}

// Resetta il filtro: mostra tutti gli eventi
function resetEventFilter(){
  const cards = document.querySelectorAll('.info-card');
  cards.forEach(function(card){
    card.style.display = '';
  });
  console.log('Filtro distanza resettato: tutti gli eventi visibili');
}

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

// Aggiungi marker rossi per tutti i posti predefiniti sulla mappa
places.forEach(function(place){
  L.marker([place.lat, place.lng], {icon: redMarkerIcon})
    .addTo(map)
    .bindPopup(place.name);
});

// Support both the original 'search' id and the new 'filtro_citta' id
const searchEl = document.getElementById('search') || document.getElementById('filtro_citta');
const suggestionsEl = document.getElementById('suggestions');

// --- Inizializza variabili del modal ---
const cardModal = document.getElementById('cardModal');
const modalImg = document.getElementById('modalImg');
const modalTitle = document.getElementById('modalTitle');
const modalText = document.getElementById('modalText');

function updateSuggestions(q){
  // Aggiorna la lista dei suggerimenti in base alla query digitata
  if(!suggestionsEl) return;
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
if(searchEl){
  searchEl.addEventListener('input', function(e){ 
    const q = e.target.value;
    updateSuggestions(q);
  // se l'utente ha digitato esattamente il nome di un luogo, selezionalo automaticamente
    // se l'utente ha digitato esattamente il nome di un luogo, selezionalo automaticamente
    if(q && q.trim().length>0){
      const exact = places.find(p => p.name.toLowerCase() === q.trim().toLowerCase());
      if(exact){
        if(!italyBounds.contains([exact.lat, exact.lng])){ alert('Posizione fuori dall\'Italia'); /* continue, allow marker */ }
        setSingleMarker(exact.lat, exact.lng);
        searchEl.value = exact.name;
        if(suggestionsEl) suggestionsEl.innerHTML = '';
      }
    }
  });
  // Gestione tasto Enter: se l'utente preme Invio e c'è almeno un suggerimento, selezioniamo il primo
  searchEl.addEventListener('keydown', function(e){
    if(e.key === 'Enter'){
      e.preventDefault();
      if(suggestionsEl){
        const first = suggestionsEl.querySelector('.list-group-item-action');
        if(first){ first.click(); }
      }
    }
  });
  // Nascondi i suggerimenti al blur (timeout breve per permettere il click su una voce)
  searchEl.addEventListener('blur', function(){ if(suggestionsEl) setTimeout(()=> suggestionsEl.innerHTML = '', 150); });
}

// Click sulla mappa: posizione del marker (crea o sposta)
// IMPORTANTE: Blocca i click fuori dall'Italia
map.on('click', function(e){
  const lat = e && e.latlng && e.latlng.lat;
  const lng = e && e.latlng && e.latlng.lng;
  console.log('Map clicked at', {lat: lat, lng: lng});
  
  // Controlla se il click è dentro i confini italiani
  if(typeof italyBounds !== 'undefined' && italyBounds && e && e.latlng){
    if(!italyBounds.contains(e.latlng)){
      console.warn('Click fuori dall\'Italia — click bloccato');
      alert('Puoi selezionare solo punti in Italia');
      return; // Blocca il click
    }
  }
  
  // Posiziona il marker solo se dentro l'Italia
  const nlat = parseFloat(lat);
  const nlng = parseFloat(lng);
  if(!Number.isNaN(nlat) && !Number.isNaN(nlng)){
    setSingleMarker(nlat, nlng);
    // Filtra gli eventi per distanza: mostra solo quelli entro 10 km dal punto cliccato
    filterEventsByDistance(nlat, nlng, 10);
  } else {
    console.warn('Invalid lat/lng from map click, marker not placed', lat, lng);
  }
});

// Assicura che i tiles vengano renderizzati correttamente dentro il contenitore circolare
setTimeout(function(){ map.invalidateSize(); }, 200);

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

// Apri modal al click su qualsiasi card .info-card usando event delegation a livello di documento
// Questo garantisce che funzioni indipendentemente dalla posizione delle card nel DOM
document.addEventListener('click', function(e){
  const card = e.target.closest('.info-card');
  if(!card) return;
  // Assicuriamoci che la card sia ancora nel DOM
  if(!document.body.contains(card)) return;
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
      try{
        if(typeof italyBounds !== 'undefined' && italyBounds && italyBounds.contains){
          // Se è dentro i confini, centriamo; altrimenti centriamo comunque
          setSingleMarker(nlat, nlng);
        } else {
          setSingleMarker(nlat, nlng);
        }
      } catch(err){
        console.warn('Errore centering marker from card:', err);
        setSingleMarker(nlat, nlng);
      }
    }
  }
});

// Close handlers
document.querySelectorAll('[data-close]').forEach(el => el.addEventListener('click', closeCardModal));
// Listener aggiuntivo mirato al pulsante di chiusura (più affidabile se ci sono sovrapposizioni)
const modalCloseBtn = document.getElementById('cardModalClose');
if(modalCloseBtn){ modalCloseBtn.addEventListener('click', closeCardModal); }
document.addEventListener('keydown', function(e){ if(e.key === 'Escape') closeCardModal(); });