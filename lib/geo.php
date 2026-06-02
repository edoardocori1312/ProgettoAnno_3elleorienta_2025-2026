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

/**
 * Se le coordinate non sono state fornite (lat/lng == 0), risolve il nome della
 * città dall'ID e tenta la geocodifica server-side. Altrimenti restituisce le
 * coordinate ricevute invariate. Centralizza il blocco prima duplicato in
 * crea/aggiorna di Eventi e Scuole.
 *
 * @return array ['lat' => float, 'lng' => float]
 */
function geocodifica_se_mancante(mysqli $conn, float $lat, float $lng, string $via, int $civico, int $idCitta): array {
    if ($lat != 0.0 || $lng != 0.0) {
        return ['lat' => $lat, 'lng' => $lng];
    }
    $stmtC = $conn->prepare('SELECT nome FROM Citta WHERE ID_citta = ?');
    $stmtC->bind_param('i', $idCitta);
    $stmtC->execute();
    $nomeCitta = ($stmtC->get_result()->fetch_assoc())['nome'] ?? '';
    $stmtC->close();
    return geocodifica($via, $civico, $nomeCitta);
}
