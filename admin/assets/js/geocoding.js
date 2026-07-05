// Geocoding Nominatim condiviso tra scuola_form.php ed evento_form.php.
// Cerca le coordinate dell'indirizzo digitato e riempie i campi lat/lng.
// I campi non presenti nella pagina (es. lng_display, solo nel form scuola)
// vengono semplicemente saltati.
let geoTimer = null;

function geocodificaNominatim() {
    const via    = document.getElementById('via').value.trim();
    const civico = document.getElementById('n_civico').value.trim();
    const citta  = document.getElementById('id_citta');
    const nomeCitta = citta.options[citta.selectedIndex]?.text ?? '';
    const stato  = document.getElementById('geo_stato');

    if (!via || !civico || citta.value === '') return;

    stato.textContent = 'Ricerca coordinate...';
    const q = encodeURIComponent(via + ' ' + civico + ', ' + nomeCitta + ', Italia');
    fetch('https://nominatim.openstreetmap.org/search?q=' + q + '&format=json&limit=1', {
        headers: { 'Accept-Language': 'it' }
    })
    .then(r => r.json())
    .then(dati => {
        if (dati.length > 0) {
            const lat = parseFloat(dati[0].lat);
            const lng = parseFloat(dati[0].lon);
            document.getElementById('lat_hidden').value  = lat;
            document.getElementById('lng_hidden').value  = lng;
            document.getElementById('lat_display').value = lat.toFixed(6);
            const lngDisplay = document.getElementById('lng_display');
            if (lngDisplay) lngDisplay.value = lng.toFixed(6);
            stato.textContent = 'Coordinate trovate.';
        } else {
            stato.textContent = 'Indirizzo non trovato (le coordinate verranno cercate lato server).';
        }
    })
    .catch(() => stato.textContent = 'Errore nella ricerca coordinate.');
}

['via', 'n_civico', 'id_citta'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.addEventListener('change', () => {
        clearTimeout(geoTimer);
        geoTimer = setTimeout(geocodificaNominatim, 600);
    });
});

const btnGeo = document.getElementById('btn_geo');
if (btnGeo) btnGeo.addEventListener('click', geocodificaNominatim);
