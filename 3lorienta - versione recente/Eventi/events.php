<?php
/**
 * events.php
 * Popola l'array $events con i dati dal DB.
 * Se in GET arrivano lat e lng, filtra gli eventi entro 10 km dal punto selezionato.
 * Viene incluso da index.php che gestisce il rendering HTML.
 */

if (!isset($conn)) {
    include_once '../connessione/db.php';
	$conn = new mysqli($HOSTDB, $USERDB, $PASSDB, $NOMEDB);
}

$events = [];

// Legge lat/lng dal GET se l'utente ha selezionato un punto sulla mappa
$filtro_lat = isset($_GET['lat']) ? (float) $_GET['lat'] : null;
$filtro_lng = isset($_GET['lng']) ? (float) $_GET['lng'] : null;
$raggio_km = isset($_GET['r']) ? (int) $_GET['r'] : 30;
if ($raggio_km < 5)   
    $raggio_km = 5;
if ($raggio_km > 100) 
    $raggio_km = 100;

// Legge la data dal GET (formato YYYY-MM-DD) se l'utente ha selezionato un giorno nel calendario
$filtro_data = null;
if (isset($_GET['data']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['data'])) {
    $filtro_data = $_GET['data'];
}

if (isset($conn) && $conn instanceof mysqli) {

    if ($filtro_lat !== null && $filtro_lng !== null) {
    // Coordinate effettive: per gli eventi territoriali usa eventi.coordinate,
    // per gli scolastici prende le coordinate della scuola via cod_scuola.
    // COALESCE sceglie la prima non-NULL.
    $stmt = $conn->prepare("
        SELECT
            e.ID_evento         AS id,
            e.titolo            AS title,
            e.descrizione_breve AS summary,
            e.descrizione       AS description,
            e.ora_inizio,
            e.ora_fine,
            e.via_P             AS address,
            e.n_civico_P        AS number,
            f.path_foto          AS image_path,
            e.prenotabile,
            e.target,
            s.nome             AS school_name,
            ROUND(ST_Distance_Sphere(
                CASE
                    WHEN e.target = 'scolastico' THEN s.coordinate
                    ELSE e.coordinate
                END,
                ST_GeomFromText(CONCAT('POINT(', ?, ' ', ?, ')'))
            ) / 1000, 1) AS distanza_km
        FROM Eventi e
        LEFT JOIN Scuole s ON s.COD_meccanografico = e.cod_scuola
		INNER JOIN Foto f ON e.id_foto=f.ID_foto
        WHERE e.visibile = 1
          AND (
              (e.target = 'scolastico' AND s.coordinate IS NOT NULL)
              OR
              (e.target <> 'scolastico' AND e.coordinate IS NOT NULL)
          )
          " . ($filtro_data ? "AND DATE(e.ora_inizio) = ? " : "") . "
          AND ST_Distance_Sphere(
                CASE
                    WHEN e.target = 'scolastico' THEN s.coordinate
                    ELSE e.coordinate
                END,
                ST_GeomFromText(CONCAT('POINT(', ?, ' ', ?, ')'))
              ) <= ? * 1000
        ORDER BY distanza_km ASC
        LIMIT 8
    ");
    // bind: lng, lat (per distanza_km nel SELECT), [data,] lng, lat, raggio (per il WHERE)
    if ($filtro_data) {
        $stmt->bind_param('ddsdddd', $filtro_lng, $filtro_lat, $filtro_data, $filtro_lng, $filtro_lat, $raggio_km);
    } else {
        $stmt->bind_param('ddddd', $filtro_lng, $filtro_lat, $filtro_lng, $filtro_lat, $raggio_km);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    } else {
        // Nessun filtro geografico: mostra gli ultimi 8 eventi per data
        $sql_no_geo = "
            SELECT
                ID_evento         AS id,
                titolo            AS title,
                descrizione_breve AS summary,
                descrizione       AS description,
                ora_inizio,
                ora_fine,
                via_P             AS address,
                n_civico_P        AS number,
                f.path_foto           AS image_path,
                prenotabile,
                NULL              AS school_name,
                NULL              AS distanza_km
            FROM Eventi e
			INNER JOIN Foto f ON e.id_foto=f.ID_foto
            WHERE visibile = 1
            " . ($filtro_data ? "AND DATE(ora_inizio) = ? " : "") . "
            ORDER BY ora_inizio DESC
            LIMIT 8
        ";
        if ($filtro_data) {
            $stmt2 = $conn->prepare($sql_no_geo);
            $stmt2->bind_param('s', $filtro_data);
            $stmt2->execute();
            $res = $stmt2->get_result();
        } else {
            $res = $conn->query($sql_no_geo);
        }
    }

    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $events[] = $row;
        }
        $res->free();
    }
}

if (count($events) === 0) {
    if ($filtro_data) {
        $msg = 'Nessun evento trovato per il ' . date('d/m/Y', strtotime($filtro_data)) . '.';
    } elseif ($filtro_lat !== null) {
        $msg = 'Nessun evento trovato entro ' . $raggio_km . ' km dal punto selezionato.';
    } else {
        $msg = 'Al momento non ci sono eventi visibili.';
    }

    $events = [[
        'id'           => 0,
        'title'        => 'Nessun evento trovato',
        'summary'      => $msg,
        'description'  => $msg,
        'ora_inizio'   => null,
        'ora_fine'     => null,
        'address'      => '',
        'number'       => '',
        'image_path'     => null,
        'distanza_km'  => null,
        'school_name'  => null,
    ]];
}