/* =============================================================
   main.js  —  script unico per 3ElleOrienta
   Contiene:
     1. Inizializzazione mappa Leaflet (mini-mappa circolare fissa)
     2. Marker rossi per ogni evento con coordinate
     3. Marker singolo (click mappa) + invio filtro al server via GET
     4. Filtro eventi per nome (ricerca in tempo reale sulle card)
     5. Modal per i dettagli delle card
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


/* ── 2. Marker eventi (segnaposti rossi) ───────────────────── */

// Icona rossa personalizzata per gli eventi
const redIcon = L.icon({
    iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
    shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
    iconSize:   [25, 41],
    iconAnchor: [12, 41],
    popupAnchor:[1, -34],
    shadowSize: [41, 41]
});

// Raccoglie tutte le card con coordinate e piazza un marker rosso per ognuna
const eventMarkers = [];

document.querySelectorAll('.info-card[data-lat][data-lng]').forEach(card => {
    const lat   = parseFloat(card.dataset.lat);
    const lng   = parseFloat(card.dataset.lng);
    const title = card.dataset.title || card.querySelector('.card-title')?.textContent || '';

    if (isNaN(lat) || isNaN(lng)) return;

    const marker = L.marker([lat, lng], { icon: redIcon })
        .addTo(map)
        .bindPopup(`<strong>${title}</strong>`, { maxWidth: 200 });

    // Click sul marker → apre il popup e il modal della card corrispondente
    marker.on('click', () => {
        card.click();
    });

    // Hover sulla card → evidenzia il marker aprendo il popup
    card.addEventListener('mouseenter', () => marker.openPopup());
    card.addEventListener('mouseleave', () => marker.closePopup());

    eventMarkers.push({ marker, card });
});

/**
 * Ricollega i marker Leaflet alle nuove card dopo un aggiornamento fetch.
 */
function rebindCardMarkers() {
    eventMarkers.forEach(({ marker }) => map.removeLayer(marker));
    eventMarkers.length = 0;

    document.querySelectorAll('.info-card[data-lat][data-lng]').forEach(card => {
        const lat   = parseFloat(card.dataset.lat);
        const lng   = parseFloat(card.dataset.lng);
        const title = card.dataset.title || '';
        if (isNaN(lat) || isNaN(lng)) return;
        const marker = L.marker([lat, lng], { icon: redIcon })
            .addTo(map)
            .bindPopup(`<strong>${title}</strong>`, { maxWidth: 200 });
        marker.on('click', () => card.click());
        card.addEventListener('mouseenter', () => marker.openPopup());
        card.addEventListener('mouseleave', () => marker.closePopup());
        eventMarkers.push({ marker, card });
    });
}

// Se ci sono marker, adatta la vista della mappa per mostrarli tutti
if (eventMarkers.length > 0) {
    // Usa fitBounds solo se non c'è già un filtro geo attivo (che ha già centrato la mappa)
    const urlParamsCheck = new URLSearchParams(window.location.search);
    if (!urlParamsCheck.has('lat') || !urlParamsCheck.has('lng')) {
        if (eventMarkers.length === 1) {
            // Con un solo marker, fitBounds farebbe zoom eccessivo: usa setView con zoom fisso
            const latlng = eventMarkers[0].marker.getLatLng();
            setTimeout(() => map.setView(latlng, 11), 250);
        } else {
            const group = L.featureGroup(eventMarkers.map(m => m.marker));
            const bounds = group.getBounds().pad(0.15);
            setTimeout(() => map.fitBounds(bounds, { maxZoom: 13 }), 250);
        }
    }
}


/* ── 3. Marker singolo + filtro server ─────────────────────── */

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
        url.searchParams.delete('page'); // torna alla pagina 1 con il nuovo filtro
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
        url.searchParams.delete('data');
        url.searchParams.delete('cerca');
        url.searchParams.delete('page');
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
            url.searchParams.delete('page');
            window.location.href = url.toString();
        }
    });
}

/* ── 6. Filtro per data ────────────────────────────────────── */

const dateInput   = document.getElementById('filtro_data');
const btnResetData = document.getElementById('btn_reset_data');

if (dateInput) {
    const openPicker = () => { try { dateInput.showPicker(); } catch (_) {} };
    dateInput.addEventListener('click', openPicker);
    dateInput.addEventListener('focus', openPicker);

    dateInput.addEventListener('change', function () {
        const url = new URL(window.location.href);
        if (dateInput.value) {
            url.searchParams.set('data', dateInput.value);
        } else {
            url.searchParams.delete('data');
        }
        url.searchParams.delete('page');
        window.location.href = url.toString();
    });
}

if (btnResetData) {
    btnResetData.addEventListener('click', function () {
        const url = new URL(window.location.href);
        url.searchParams.delete('data');
        url.searchParams.delete('page');
        window.location.href = url.toString();
    });
}

const searchEl      = document.getElementById('filtro_citta');                 // input di ricerca
const suggestionsEl = document.getElementById('suggestions');                  // lista suggerimenti (mantenuta per compatibilità HTML)
const allCards      = Array.from(document.querySelectorAll('.info-card'));      // tutte le card eventi nel DOM

/**
 * Aggiorna le card via fetch senza ricaricare la pagina.
 * Usa ?fragment=1 su index.php per ottenere solo l'HTML delle card.
 * Il focus sulla barra di ricerca rimane intatto.
 */
function filterEvents(q) {
    const url = new URL(window.location.href);
    if (q.trim() !== '') {
        url.searchParams.set('cerca', q.trim());
    } else {
        url.searchParams.delete('cerca');
    }
    url.searchParams.delete('page');

    // Aggiorna l'URL nella barra del browser senza ricaricare
    window.history.replaceState({}, '', url.toString());

    // Fetch del fragment
    const fetchUrl = new URL(url.toString());
    fetchUrl.searchParams.set('fragment', '1');

    const wrapper = document.getElementById('events_section');
    if (wrapper) wrapper.style.opacity = '0.4';

    fetch(fetchUrl.toString())
        .then(r => r.text())
        .then(html => {
            if (wrapper) {
                wrapper.innerHTML = html;
                wrapper.style.opacity = '';
            }
            rebindCardMarkers();
        })
        .catch(() => {
            if (wrapper) wrapper.style.opacity = '';
        });
}

if (searchEl) {
    searchEl.placeholder = 'Cerca evento…';

    searchEl.addEventListener('input', function (e) {
        filterEvents(e.target.value);
    });

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
const modalSchool      = document.getElementById('modalSchool');
const modalSchoolAddress = document.getElementById('modalSchoolAddress');
const modalPrenotabile = document.getElementById('modalPrenotabile');

/**
 * Apre il modal con i dati dell'evento selezionato.
 */
function openCardModal(imgSrc, title, text, date, address, oraInizio, oraFine, prenotabile, school, schoolAddress) {
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

    // Nome scuola
    if (modalSchool) {
        modalSchool.innerHTML = school
            ? '<i class="bi bi-building"></i> ' + school
            : '';
    }

    // Indirizzo scuola
    if (modalSchoolAddress) {
        modalSchoolAddress.innerHTML = schoolAddress
            ? '<i class="bi bi-signpost"></i> ' + schoolAddress
            : '';
    }

    // Badge prenotabilità: verde se prenotabile, grigio altrimenti
    if (prenotabile === '1') {
        modalPrenotabile.innerHTML = '<span class="badge bg-success"><i class="bi bi-check-circle"></i> Prenotabile</span>';
    } else {
        modalPrenotabile.innerHTML = '<span class="badge bg-secondary"><i class="bi bi-x-circle"></i> Non prenotabile</span>';
    }

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
    if (modalSchool)        modalSchool.innerHTML        = '';
    if (modalSchoolAddress) modalSchoolAddress.innerHTML = '';
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
            card.dataset.description    || (textEl ? textEl.textContent : ''),
            card.dataset.date           || '',
            card.dataset.address        || '',
            card.dataset.oraInizio      || '',
            card.dataset.oraFine        || '',
            card.dataset.prenotabile    || '0',
            card.dataset.school         || '',
            card.dataset.schoolAddress  || ''
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

/* ── 7. Dropdown città marchigiane ─────────────────────────── */

const selectCitta = document.getElementById('select_citta');

if (selectCitta) {
    // Se c'è già un filtro lat/lng attivo nell'URL, prova a selezionare la città corrispondente
    const urlLat = urlParams.get('lat');
    const urlLng = urlParams.get('lng');
    if (urlLat && urlLng) {
        for (const opt of selectCitta.options) {
            if (!opt.value) continue;
            const [oLat, oLng] = opt.value.split(',');
            if (Math.abs(parseFloat(oLat) - parseFloat(urlLat)) < 0.01 &&
                Math.abs(parseFloat(oLng) - parseFloat(urlLng)) < 0.01) {
                selectCitta.value = opt.value;
                break;
            }
        }
    }

    selectCitta.addEventListener('change', function () {
        if (!this.value) {
            // Opzione vuota → rimuovi filtro geografico
            const url = new URL(window.location.href);
            url.searchParams.delete('lat');
            url.searchParams.delete('lng');
            url.searchParams.delete('r');
            url.searchParams.delete('page');
            window.location.href = url.toString();
            return;
        }

        const [lat, lng] = this.value.split(',').map(Number);
        const r = document.getElementById('radius_slider');
        const url = new URL(window.location.href);
        url.searchParams.set('lat', lat.toFixed(6));
        url.searchParams.set('lng', lng.toFixed(6));
        url.searchParams.set('r', r ? r.value : 30);
        url.searchParams.delete('page');
        window.location.href = url.toString();
    });
}