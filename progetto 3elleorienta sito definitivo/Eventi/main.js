/* =============================================================
   main.js  —  script unico per 3ElleOrienta
   Contiene:
     1. Inizializzazione mappa Leaflet (mini-mappa circolare fissa)
     2. Marker rossi per ogni evento con coordinate
     3. Marker singolo (click mappa) + invio filtro al server via fetch
     4. Filtro eventi per nome (ricerca in tempo reale sulle card)
     5. Modal per i dettagli delle card
   ============================================================= */


/* ── 1. Mappa Leaflet ──────────────────────────────────────── */

const map = L.map('map', { zoomControl: false }).setView([43.52, 13.18], 8);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
}).addTo(map);

const italyBounds = L.latLngBounds([42.68, 12.18], [43.97, 13.93]);
map.setMaxBounds(italyBounds);
map.options.maxBoundsViscosity = 1.0;

setTimeout(() => map.invalidateSize(), 200);

const urlParams = new URLSearchParams(window.location.search);
const initLat   = parseFloat(urlParams.get('lat'));
const initLng   = parseFloat(urlParams.get('lng'));
if (!isNaN(initLat) && !isNaN(initLng)) {
    setSingleMarker(initLat, initLng, false);
}


/* ── 2. Marker eventi (segnaposti rossi) ───────────────────── */

const redIcon = L.icon({
    iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
    shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
    iconSize:   [25, 41],
    iconAnchor: [12, 41],
    popupAnchor:[1, -34],
    shadowSize: [41, 41]
});

const eventMarkers = [];

document.querySelectorAll('.info-card[data-lat][data-lng]').forEach(card => {
    const lat   = parseFloat(card.dataset.lat);
    const lng   = parseFloat(card.dataset.lng);
    const title = card.dataset.title || card.querySelector('.card-title')?.textContent || '';

    if (isNaN(lat) || isNaN(lng)) return;

    const marker = L.marker([lat, lng], { icon: redIcon })
        .addTo(map)
        .bindPopup(`<strong>${title}</strong>`, { maxWidth: 200 });

    marker.on('click', () => { card.click(); });

    card.addEventListener('mouseenter', () => marker.openPopup());
    card.addEventListener('mouseleave', () => marker.closePopup());

    eventMarkers.push({ marker, card });
});

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

if (eventMarkers.length > 0) {
    const urlParamsCheck = new URLSearchParams(window.location.search);
    if (!urlParamsCheck.has('lat') || !urlParamsCheck.has('lng')) {
        if (eventMarkers.length === 1) {
            const latlng = eventMarkers[0].marker.getLatLng();
            setTimeout(() => map.setView(latlng, 11), 250);
        } else {
            const group = L.featureGroup(eventMarkers.map(m => m.marker));
            const bounds = group.getBounds().pad(0.15);
            setTimeout(() => map.fitBounds(bounds, { maxZoom: 13 }), 250);
        }
    }
}


/* ── 3. fetchEvents — funzione centrale asincrona ─────────── */

/**
 * Aggiorna le card via fetch ?fragment=1 senza ricaricare la pagina.
 * Aggiorna anche la URL nella barra del browser (replaceState).
 *
 * @param {URLSearchParams} params - Parametri GET da applicare
 */
function fetchEvents(params) {
    // Rimuove sempre la pagina corrente quando cambia un filtro
    params.delete('page');

    // Aggiorna la barra del browser senza reload
    window.history.replaceState({}, '', '?' + params.toString());

    const fetchUrl = new URL(window.location.href);
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
            updateResetButton(params);
        })
        .catch(() => {
            if (wrapper) wrapper.style.opacity = '';
        });
}

/**
 * Mostra/nasconde il pulsante "Mostra tutti" in base ai filtri attivi.
 */
function updateResetButton(params) {
    const filtroAttivo =
        (params.has('lat') && params.has('lng')) ||
        params.has('data_da') ||
        params.has('data_a') ||
        (params.has('cerca') && params.get('cerca') !== '');

    let btn = document.getElementById('btn_reset_filtro');

    if (filtroAttivo && !btn) {
        // Crea il pulsante se non esiste
        const div = document.createElement('div');
        div.className = 'mb-3';
        div.id = 'reset_filtro_wrapper';
        div.innerHTML = '<button id="btn_reset_filtro" class="btn btn-sm btn-outline-secondary">'
                      + '<i class="bi bi-x"></i> Mostra tutti</button>';
        const center = document.querySelector('.col-center');
        if (center) center.insertBefore(div, center.firstChild);
        btn = document.getElementById('btn_reset_filtro');
        if (btn) btn.addEventListener('click', resetAllFilters);

    } else if (!filtroAttivo && btn) {
        // Rimuove il pulsante se non ci sono filtri
        const wrapper = document.getElementById('reset_filtro_wrapper') || btn.parentElement;
        if (wrapper) wrapper.remove();
    }
}


/* ── 4. Marker singolo + filtro server ─────────────────────── */

window.singleMarker = null;
window.lastMarker   = null;

/**
 * Crea o sposta il marker sulla mappa in (lat, lng).
 * Se reload=true usa fetchEvents (asincrono) per filtrare le card.
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
        const params = new URLSearchParams(window.location.search);
        params.set('lat', lat.toFixed(6));
        params.set('lng', lng.toFixed(6));
        const r = document.getElementById('radius_slider');
        if (r) params.set('r', r.value);
        fetchEvents(params);
    }
}

map.on('click', function (e) {
    if (!italyBounds.contains(e.latlng)) {
        alert('Posizione fuori dalle Marche: scegli un punto nella regione.');
        return;
    }
    setSingleMarker(e.latlng.lat, e.latlng.lng);
});


/* ── 5. Pulsante reset tutti i filtri ──────────────────────── */

function resetAllFilters() {
    // Rimuovi marker dalla mappa
    if (window.singleMarker) {
        map.removeLayer(window.singleMarker);
        window.singleMarker = null;
        window.lastMarker   = null;
    }

    // Nascondi label coordinate
    const lbl = document.getElementById('coordLabel');
    if (lbl) lbl.classList.add('hidden');

    // Resetta i controlli UI
    const dateInputDa = document.getElementById('filtro_data_da');
    const dateInputA  = document.getElementById('filtro_data_a');
    if (dateInputDa) dateInputDa.value = '';
    if (dateInputA)  dateInputA.value  = '';

    const searchEl = document.getElementById('filtro_citta');
    if (searchEl) searchEl.value = '';

    const selectCitta = document.getElementById('select_citta');
    if (selectCitta) selectCitta.value = '';

    fetchEvents(new URLSearchParams());
}

// Collega il pulsante già presente nel DOM al caricamento (se filtro era attivo)
const btnReset = document.getElementById('btn_reset_filtro');
if (btnReset) {
    btnReset.addEventListener('click', resetAllFilters);
}


/* ── 6. Slider raggio ──────────────────────────────────────── */

const radiusSlider = document.getElementById('radius_slider');
const radiusValue  = document.getElementById('radius_value');

if (radiusSlider) {
    const urlR = parseInt(new URLSearchParams(window.location.search).get('r'), 10);
    if (!isNaN(urlR) && urlR >= 5 && urlR <= 100) {
        radiusSlider.value = urlR;
        if (radiusValue) radiusValue.textContent = urlR;
    }

    radiusSlider.addEventListener('input', () => {
        if (radiusValue) radiusValue.textContent = radiusSlider.value;
    });

    radiusSlider.addEventListener('change', () => {
        const params = new URLSearchParams(window.location.search);
        if (params.has('lat') && params.has('lng')) {
            params.set('r', radiusSlider.value);
            fetchEvents(params);
        }
    });
}


/* ── 7. Filtro per data (range) ────────────────────────────── */

const dateInputDa  = document.getElementById('filtro_data_da');
const dateInputA   = document.getElementById('filtro_data_a');
const btnResetData = document.getElementById('btn_reset_data');

// Blocca data_a prima di data_da
if (dateInputDa) {
    // Min iniziale (se filtro già attivo dall'URL)
    if (dateInputDa.value && dateInputA) dateInputA.min = dateInputDa.value;

    dateInputDa.addEventListener('change', () => {
        if (dateInputA) dateInputA.min = dateInputDa.value || '';
        if (dateInputA && dateInputA.value && dateInputA.value < dateInputDa.value) {
            dateInputA.value = '';
        }
    });
}

function applyDateRange() {
    const params = new URLSearchParams(window.location.search);
    if (dateInputDa && dateInputDa.value) {
        params.set('data_da', dateInputDa.value);
    } else {
        params.delete('data_da');
    }
    if (dateInputA && dateInputA.value) {
        params.set('data_a', dateInputA.value);
    } else {
        params.delete('data_a');
    }
    fetchEvents(params);
}

[dateInputDa, dateInputA].forEach(input => {
    if (!input) return;
    const openPicker = () => { try { input.showPicker(); } catch (_) {} };
    input.addEventListener('click', openPicker);
    input.addEventListener('focus', openPicker);
    input.addEventListener('change', applyDateRange);
});

if (btnResetData) {
    btnResetData.addEventListener('click', function () {
        if (dateInputDa) dateInputDa.value = '';
        if (dateInputA)  dateInputA.value  = '';
        const params = new URLSearchParams(window.location.search);
        params.delete('data_da');
        params.delete('data_a');
        fetchEvents(params);
    });
}


/* ── 8. Ricerca testo ──────────────────────────────────────── */

const searchEl      = document.getElementById('filtro_citta');
const suggestionsEl = document.getElementById('suggestions');

function filterEvents(q) {
    const params = new URLSearchParams(window.location.search);
    if (q.trim() !== '') {
        params.set('cerca', q.trim());
    } else {
        params.delete('cerca');
    }
    fetchEvents(params);
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


/* ── 9. Dropdown città marchigiane ─────────────────────────── */

const selectCitta = document.getElementById('select_citta');

if (selectCitta) {
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
        const params = new URLSearchParams(window.location.search);

        if (!this.value) {
            params.delete('lat');
            params.delete('lng');
            params.delete('r');
            if (window.singleMarker) {
                map.removeLayer(window.singleMarker);
                window.singleMarker = null;
            }
            fetchEvents(params);
            return;
        }

        const [lat, lng] = this.value.split(',').map(Number);
        const r = document.getElementById('radius_slider');
        params.set('lat', lat.toFixed(6));
        params.set('lng', lng.toFixed(6));
        params.set('r', r ? r.value : 30);

        // Sposta il marker sulla mappa senza triggerare un secondo fetch
        setSingleMarker(lat, lng, false);

        fetchEvents(params);
    });
}


/* ── 10. Modal card ────────────────────────────────────────── */

const cardModal          = document.getElementById('cardModal');
const modalImg           = document.getElementById('modalImg');
const modalTitle         = document.getElementById('modalTitle');
const modalText          = document.getElementById('modalText');
const modalDate          = document.getElementById('modalDate');
const modalAddress       = document.getElementById('modalAddress');
const modalOrario        = document.getElementById('modalOrario');
const modalSchool        = document.getElementById('modalSchool');
const modalSchoolAddress = document.getElementById('modalSchoolAddress');
const modalPrenotabile   = document.getElementById('modalPrenotabile');

function openCardModal(imgSrc, title, text, date, address, oraInizio, oraFine, prenotabile, school, schoolAddress) {
    if (!cardModal) return;

    modalImg.src           = imgSrc || '';
    modalImg.alt           = title  || '';
    modalTitle.textContent = title  || '';
    modalText.textContent  = text   || '';

    modalDate.innerHTML = date
        ? '<i class="bi bi-calendar"></i> ' + date
        : '';

    modalAddress.innerHTML = address
        ? '<i class="bi bi-map"></i> ' + address
        : '';

    if (oraInizio) {
        modalOrario.innerHTML = '<i class="bi bi-clock"></i> ' + oraInizio + (oraFine ? ' – ' + oraFine : '');
    } else {
        modalOrario.innerHTML = '';
    }

    if (modalSchool) {
        modalSchool.innerHTML = school
            ? '<i class="bi bi-building"></i> ' + school
            : '';
    }

    if (modalSchoolAddress) {
        modalSchoolAddress.innerHTML = schoolAddress
            ? '<i class="bi bi-signpost"></i> ' + schoolAddress
            : '';
    }

    if (prenotabile === '1') {
        modalPrenotabile.innerHTML = '<span class="badge bg-success"><i class="bi bi-check-circle"></i> Prenotabile</span>';
    } else {
        modalPrenotabile.innerHTML = '<span class="badge bg-secondary"><i class="bi bi-x-circle"></i> Non prenotabile</span>';
    }

    cardModal.setAttribute('aria-hidden', 'false');
}

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

/* ── Paginazione asincrona ─────────────────────────────────── */

document.querySelector('.col-center').addEventListener('click', function (e) {
    const btn = e.target.closest('.page-btn:not(.page-btn--disabled):not(.page-btn--active)');
    if (!btn) return;
    e.preventDefault();
    const href = btn.getAttribute('href');
    if (!href) return;
    const page = new URL(href, window.location.href).searchParams.get('page');
    if (!page) return;
    const params = new URLSearchParams(window.location.search);
    params.set('page', page);
    // Non cancellare page in fetchEvents — sovrascriviamo prima il delete
    const wrapper = document.getElementById('events_section');
    if (wrapper) wrapper.style.opacity = '0.4';
    window.history.replaceState({}, '', '?' + params.toString());
    const fetchUrl = new URL(window.location.href);
    fetchUrl.searchParams.set('fragment', '1');
    fetch(fetchUrl.toString())
        .then(r => r.text())
        .then(html => {
            if (wrapper) { wrapper.innerHTML = html; wrapper.style.opacity = ''; }
            rebindCardMarkers();
        })
        .catch(() => { if (wrapper) wrapper.style.opacity = ''; });
});

const cardsWrapper = document.querySelector('.cards-wrapper');
if (cardsWrapper) {
    cardsWrapper.addEventListener('click', function (e) {
        const card = e.target.closest('.info-card');
        if (!card) return;

        const imgEl   = card.querySelector('img');
        const titleEl = card.querySelector('.card-title');
        const textEl  = card.querySelector('.card-text');

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

        const lat = parseFloat(card.dataset.lat);
        const lng = parseFloat(card.dataset.lng);
        if (!isNaN(lat) && !isNaN(lng) && italyBounds.contains([lat, lng])) {
            setSingleMarker(lat, lng, false);
        }
    });
}

document.querySelectorAll('[data-close]').forEach(el => el.addEventListener('click', closeCardModal));
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeCardModal(); });