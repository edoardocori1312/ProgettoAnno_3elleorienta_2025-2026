/* =============================================================
   main.js  —  script unico per 3ElleOrienta
   Contiene:
     1. Inizializzazione mappa Leaflet (mini-mappa circolare fissa)
     2. Marker singolo (click mappa) + invio filtro al server via GET
     3. Filtro eventi per nome (ricerca in tempo reale sulle card)
     4. Modal per i dettagli delle card
   ============================================================= */


/* ── 1. Mappa Leaflet ──────────────────────────────────────── */

// Crea la mappa centrata sulle Marche, senza il controllo zoom di default
const map = L.map('map', { zoomControl: false }).setView([43.52, 13.18], 8);

// Aggiunge il layer OpenStreetMap come sfondo
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
}).addTo(map);

// Definisce i confini geografici delle Marche (bounding box approssimativo)
// L'utente non può spostare la mappa al di fuori di questa area
const italyBounds = L.latLngBounds([42.68, 12.18], [43.97, 13.93]);
map.setMaxBounds(italyBounds);
map.options.maxBoundsViscosity = 1.0; // 1.0 = confini rigidi, non valicabili

// Forza il ridisegno dei tile dopo il caricamento della pagina
// (necessario per la mini-mappa circolare che parte nascosta)
setTimeout(() => map.invalidateSize(), 200);

// Se la pagina è già stata caricata con lat/lng in GET (filtro attivo),
// ripristina il marker sulla mappa senza ricaricare la pagina
const urlParams = new URLSearchParams(window.location.search);
const initLat   = parseFloat(urlParams.get('lat'));
const initLng   = parseFloat(urlParams.get('lng'));
if (!isNaN(initLat) && !isNaN(initLng)) {
    setSingleMarker(initLat, initLng, false); // false = non ricaricare la pagina
}


/* ── 2. Marker singolo + filtro server ─────────────────────── */

// Riferimenti globali al marker attivo e all'ultima posizione usata
window.singleMarker = null;
window.lastMarker   = null;

/**
 * Crea o sposta il marker sulla mappa nella posizione (lat, lng).
 * Se reload=true, ricarica la pagina aggiungendo lat e lng ai parametri GET
 * così PHP (events.php) filtra gli eventi entro 10 km dal punto scelto.
 *
 * @param {number}  lat    - Latitudine del punto selezionato
 * @param {number}  lng    - Longitudine del punto selezionato
 * @param {boolean} reload - Se true, ricarica la pagina con il filtro attivo
 */
function setSingleMarker(lat, lng, reload = true) {
    if (window.singleMarker) {
        window.singleMarker.setLatLng([lat, lng]);
    } else {
        window.singleMarker = L.marker([lat, lng]).addTo(map);
    }
    window.lastMarker = { lat, lng };

    const MIN_ZOOM = 10;
    if (map.getZoom() < MIN_ZOOM) {
        map.flyTo([lat, lng], MIN_ZOOM);
    } else {
        map.panTo([lat, lng]);
    }

    const lbl = document.getElementById('coordLabel');
    if (lbl) {
        lbl.textContent = lat.toFixed(5) + ', ' + lng.toFixed(5);
        lbl.classList.remove('hidden');
    }

    if (reload) {
        const url = new URL(window.location.href);
        url.searchParams.set('lat', lat.toFixed(6));
        url.searchParams.set('lng', lng.toFixed(6));
        // aggiunge il raggio corrente dallo slider
        const r = document.getElementById('radius_slider');
        if (r) url.searchParams.set('r', r.value);
        window.location.href = url.toString();
    }
}

// Gestisce il click sulla mappa: valida che il punto sia dentro le Marche,
// poi posiziona il marker e filtra gli eventi
map.on('click', function (e) {
    if (!italyBounds.contains(e.latlng)) {
        alert('Posizione fuori dalle Marche: scegli un punto nella regione.');
        return;
    }
    setSingleMarker(e.latlng.lat, e.latlng.lng);
});

// Pulsante "Mostra tutti": rimuove lat e lng dall'URL e ricarica la pagina
// così PHP mostra tutti gli eventi senza filtro geografico
const btnReset = document.getElementById('btn_reset_filtro');
if (btnReset) {
    btnReset.addEventListener('click', function () {
        const url = new URL(window.location.href);
        url.searchParams.delete('lat');
        url.searchParams.delete('lng');
        url.searchParams.delete('r');
        window.location.href = url.toString();
    });
}

/* ── 5. Slider raggio ──────────────────────────────────────── */

const radiusSlider = document.getElementById('radius_slider');
const radiusValue  = document.getElementById('radius_value');

if (radiusSlider) {
    // Se l'URL ha già 'r', ripristina il valore sullo slider
    const urlR = parseInt(new URLSearchParams(window.location.search).get('r'), 10);
    if (!isNaN(urlR) && urlR >= 5 && urlR <= 100) {
        radiusSlider.value = urlR;
        radiusValue.textContent = urlR;
    }

    // Aggiorna il numero accanto allo slider mentre si trascina
    radiusSlider.addEventListener('input', () => {
        radiusValue.textContent = radiusSlider.value;
    });

    // Quando l'utente rilascia lo slider, ricarica con il nuovo raggio
    // (solo se c'è un marker, altrimenti non c'è nulla da filtrare)
    radiusSlider.addEventListener('change', () => {
        const url = new URL(window.location.href);
        if (url.searchParams.has('lat') && url.searchParams.has('lng')) {
            url.searchParams.set('r', radiusSlider.value);
            window.location.href = url.toString();
        }
    });
}

/* ── 6. Filtro per data ────────────────────────────────────── */

const dateInput   = document.getElementById('filtro_data');
const btnResetData = document.getElementById('btn_reset_data');

if (dateInput) {
    // Quando l'utente seleziona una data, ricarica la pagina con il parametro data
    dateInput.addEventListener('change', function () {
        const url = new URL(window.location.href);
        if (dateInput.value) {
            url.searchParams.set('data', dateInput.value);
        } else {
            url.searchParams.delete('data');
        }
        window.location.href = url.toString();
    });
}

if (btnResetData) {
    btnResetData.addEventListener('click', function () {
        const url = new URL(window.location.href);
        url.searchParams.delete('data');
        window.location.href = url.toString();
    });
}

const searchEl      = document.getElementById('filtro_citta');                 // input di ricerca
const suggestionsEl = document.getElementById('suggestions');                  // lista suggerimenti (mantenuta per compatibilità HTML)
const allCards      = Array.from(document.querySelectorAll('.info-card'));      // tutte le card eventi nel DOM

/**
 * Filtra le card visibili in base alla query di ricerca.
 * Confronta il testo della query con il titolo di ogni card (case-insensitive).
 * Se nessuna card corrisponde, mostra un messaggio di "nessun risultato".
 *
 * @param {string} q - Testo inserito dall'utente nella barra di ricerca
 */
function filterEvents(q) {
    const ql = q.trim().toLowerCase();
    let found = 0; // contatore delle card visibili dopo il filtro

    allCards.forEach(card => {
        const title   = card.querySelector('.card-title')?.textContent.toLowerCase() || '';
        const school  = (card.dataset.school  || '').toLowerCase();
        const address = (card.dataset.address || '').toLowerCase();
        const match = !ql || title.includes(ql) || school.includes(ql) || address.includes(ql);
        card.style.display = match ? '' : 'none';
        if (match) found++;
    });

    // Gestisce il messaggio "Nessun evento trovato"
    let noResult = document.getElementById('no_result_msg');
    if (found === 0) {
        // Crea il messaggio solo se non esiste già nel DOM
        if (!noResult) {
            noResult = document.createElement('p');
            noResult.id = 'no_result_msg';
            noResult.className = 'text-muted text-center w-100 mt-3';
            noResult.textContent = 'Nessun evento trovato.';
            document.querySelector('.cards-wrapper')?.appendChild(noResult);
        }
    } else {
        // Rimuove il messaggio se ci sono risultati
        noResult?.remove();
    }
}

if (searchEl) {
    // Aggiorna il placeholder per riflettere la nuova funzione del campo
    searchEl.placeholder = 'Cerca evento…';

    // Filtra le card ad ogni carattere digitato
    searchEl.addEventListener('input', function (e) {
        filterEvents(e.target.value);
    });

    // Premi Escape per azzerare il filtro e tornare a mostrare tutte le card
    searchEl.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            searchEl.value = '';
            filterEvents('');
        }
    });
}


/* ── 4. Modal card ─────────────────────────────────────────── */

// Riferimenti agli elementi del modal nel DOM
const cardModal        = document.getElementById('cardModal');
const modalImg         = document.getElementById('modalImg');
const modalTitle       = document.getElementById('modalTitle');
const modalText        = document.getElementById('modalText');
const modalDate        = document.getElementById('modalDate');
const modalAddress     = document.getElementById('modalAddress');
const modalOrario      = document.getElementById('modalOrario');
const modalPrenotabile = document.getElementById('modalPrenotabile');

/**
 * Apre il modal con i dati dell'evento selezionato.
 * Popola dinamicamente tutti i campi (immagine, titolo, testo, data, ecc.).
 *
 * @param {string} imgSrc      - URL dell'immagine della card
 * @param {string} title       - Titolo dell'evento
 * @param {string} text        - Descrizione breve dell'evento
 * @param {string} date        - Data formattata (gg/mm/aaaa)
 * @param {string} address     - Indirizzo dell'evento
 * @param {string} oraInizio   - Orario di inizio (HH:MM)
 * @param {string} oraFine     - Orario di fine (HH:MM)
 * @param {string} prenotabile - '1' se prenotabile, '0' altrimenti
 */
function openCardModal(imgSrc, title, text, date, address, oraInizio, oraFine, prenotabile) {
    if (!cardModal) return;

    // Popola immagine e testi principali
    modalImg.src           = imgSrc || '';
    modalImg.alt           = title  || '';
    modalTitle.textContent = title  || '';
    modalText.textContent  = text   || '';

    // Data — mostra l'icona calendario solo se il dato è presente
    modalDate.innerHTML = date
        ? '<i class="bi bi-calendar"></i> ' + date
        : '';

    // Indirizzo — mostra l'icona mappa solo se il dato è presente
    modalAddress.innerHTML = address
        ? '<i class="bi bi-map"></i> ' + address
        : '';

    // Orario — mostra inizio e, se presente, anche fine separati da "–"
    if (oraInizio) {
        modalOrario.innerHTML = '<i class="bi bi-clock"></i> ' + oraInizio + (oraFine ? ' – ' + oraFine : '');
    } else {
        modalOrario.innerHTML = '';
    }

    // Badge prenotabilità: verde se prenotabile, grigio altrimenti
    if (prenotabile === '1') {
        modalPrenotabile.innerHTML = '<span class="badge bg-success"><i class="bi bi-check-circle"></i> Prenotabile</span>';
    } else {
        modalPrenotabile.innerHTML = '<span class="badge bg-secondary"><i class="bi bi-x-circle"></i> Non prenotabile</span>';
    }

    // Rende il modal visibile (aria-hidden gestisce anche la visibilità CSS)
    cardModal.setAttribute('aria-hidden', 'false');
}

/**
 * Chiude il modal e resetta tutti i campi per evitare residui alla riapertura.
 */
function closeCardModal() {
    if (!cardModal) return;
    cardModal.setAttribute('aria-hidden', 'true');
    modalImg.src               = '';
    modalTitle.textContent     = '';
    modalText.textContent      = '';
    modalDate.innerHTML        = '';
    modalAddress.innerHTML     = '';
    modalOrario.innerHTML      = '';
    modalPrenotabile.innerHTML = '';
}

// Delega il click sul contenitore delle card:
// intercetta il click su qualsiasi card figlia e apre il modal
const cardsWrapper = document.querySelector('.cards-wrapper');
if (cardsWrapper) {
    cardsWrapper.addEventListener('click', function (e) {
        const card = e.target.closest('.info-card');
        if (!card) return; // click fuori da una card → ignora

        // Recupera i dati visuali dalla card cliccata
        const imgEl   = card.querySelector('img');
        const titleEl = card.querySelector('.card-title');
        const textEl  = card.querySelector('.card-text');

        // Apre il modal con i dati della card
        openCardModal(
            imgEl   ? imgEl.src           : '',
            titleEl ? titleEl.textContent : '',
            card.dataset.description || (textEl ? textEl.textContent : ''),
            card.dataset.date        || '',
            card.dataset.address     || '',
            card.dataset.oraInizio   || '',
            card.dataset.oraFine     || '',
            card.dataset.prenotabile || '0'
        );

        // Se la card ha coordinate, sposta il marker sulla mappa
        // senza ricaricare la pagina (reload = false)
        const lat = parseFloat(card.dataset.lat);
        const lng = parseFloat(card.dataset.lng);
        if (!isNaN(lat) && !isNaN(lng) && italyBounds.contains([lat, lng])) {
            setSingleMarker(lat, lng, false);
        }
    });
}

// Chiude il modal cliccando sul backdrop o sul pulsante ×
document.querySelectorAll('[data-close]').forEach(el => el.addEventListener('click', closeCardModal));

// Chiude il modal premendo il tasto Escape
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeCardModal(); });