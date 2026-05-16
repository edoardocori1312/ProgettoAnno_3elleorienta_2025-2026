<?php
/**
 * Fallback server-side geocoding tramite Nominatim.
 * Usato quando il browser non invia le coordinate.
 *
 * @return array ['lat' => float, 'lng' => float]  (0,0 se non trovato)
 */
function geocodifica(string $via, int $civico, string $citta): array {
    $q   = urlencode("$via $civico, $citta, Italia");
    $url = "https://nominatim.openstreetmap.org/search?q={$q}&format=json&limit=1";
    $ctx = stream_context_create([
        'http' => [
            'header'  => "User-Agent: 3elleorienta/1.0\r\nAccept-Language: it\r\n",
            'timeout' => 6,
        ],
    ]);
    $risposta = @file_get_contents($url, false, $ctx);
    if ($risposta) {
        $dati = json_decode($risposta, true);
        if (!empty($dati[0])) {
            return ['lat' => (float)$dati[0]['lat'], 'lng' => (float)$dati[0]['lon']];
        }
    }
    return ['lat' => 0.0, 'lng' => 0.0];
}
