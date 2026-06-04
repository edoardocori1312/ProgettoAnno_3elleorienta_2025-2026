<?php
/**
 * events.php
 * Popola l'array $events con i dati dal DB.
 * Filtri supportati: lat/lng (geo), data, cerca (testo libero).
 * Viene incluso da index.php e da events_fragment.php.
 */

if (!isset($conn)) {
    include_once '../connessione/db.php';
    $conn = new mysqli($HOSTDB, $USERDB, $PASSDB, $NOMEDB);
}

$events = [];

// Filtro geografico
$filtro_lat = isset($_GET['lat']) ? (float) $_GET['lat'] : null;
$filtro_lng = isset($_GET['lng']) ? (float) $_GET['lng'] : null;
$raggio_km  = isset($_GET['r'])   ? (int)   $_GET['r']   : 30;
if ($raggio_km < 5)   $raggio_km = 5;
if ($raggio_km > 100) $raggio_km = 100;

// Filtro data
$filtro_data = null;
if (isset($_GET['data']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['data'])) {
    $filtro_data = $_GET['data'];
}

// Filtro testo libero
$filtro_cerca      = isset($_GET['cerca']) ? trim($_GET['cerca']) : '';
$filtro_cerca_like = $filtro_cerca !== '' ? '%' . $filtro_cerca . '%' : null;

// Paginazione
$per_page     = 6;
$page_current = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset       = ($page_current - 1) * $per_page;
$total_events = 0;

// Clausole WHERE comuni a tutte le query
$where_non_eliminati = "AND e.data_eliminazione IS NULL ";
$where_cerca = $filtro_cerca_like
    ? "AND (e.titolo LIKE ? OR s.nome LIKE ? OR e.via_P LIKE ? OR s.via LIKE ?) "
    : "";

if (isset($conn) && $conn instanceof mysqli) {

    if ($filtro_lat !== null && $filtro_lng !== null) {

        // ── COUNT geo ──
        $sql_count_geo = "
            SELECT COUNT(*) AS tot
            FROM Eventi e
            LEFT JOIN Scuole s ON s.COD_meccanografico = e.cod_scuola
            LEFT JOIN Foto f ON e.id_foto = f.ID_foto
            WHERE e.visibile = 1
              " . $where_non_eliminati . "
              AND (
                  (e.target = 'scolastico' AND s.coordinate IS NOT NULL)
                  OR
                  (e.target <> 'scolastico' AND e.coordinate IS NOT NULL)
              )
              " . ($filtro_data ? "AND DATE(e.ora_inizio) = ? " : "")
              . $where_cerca . "
              AND ST_Distance_Sphere(
                    CASE WHEN e.target = 'scolastico' THEN s.coordinate ELSE e.coordinate END,
                    ST_GeomFromText(CONCAT('POINT(', ?, ' ', ?, ')'))
                  ) <= ? * 1000
        ";
        $stmtC = $conn->prepare($sql_count_geo);
        $types = '';
        $params = [];
        if ($filtro_data)       { $types .= 's'; $params[] = &$filtro_data; }
        if ($filtro_cerca_like) { $types .= 'ssss'; $params[] = &$filtro_cerca_like; $params[] = &$filtro_cerca_like; $params[] = &$filtro_cerca_like; $params[] = &$filtro_cerca_like; }
        $types .= 'ddd';
        $params[] = &$filtro_lng; $params[] = &$filtro_lat; $params[] = &$raggio_km;
        array_unshift($params, $types);
        call_user_func_array([$stmtC, 'bind_param'], $params);
        $stmtC->execute();
        $total_events = $stmtC->get_result()->fetch_assoc()['tot'] ?? 0;
        $stmtC->close();

        // ── SELECT geo paginata ──
        $sql_geo = "
            SELECT
                e.ID_evento         AS id,
                e.titolo            AS title,
                e.descrizione_breve AS summary,
                e.descrizione       AS description,
                e.ora_inizio,
                e.ora_fine,
                e.via_P             AS address,
                e.n_civico_P        AS number,
                f.path_foto         AS image_path,
                e.prenotabile,
                e.target,
                s.nome              AS school_name,
                s.via               AS school_via,
                s.n_civico          AS school_n_civico,
                ROUND(ST_Distance_Sphere(
                    CASE WHEN e.target = 'scolastico' THEN s.coordinate ELSE e.coordinate END,
                    ST_GeomFromText(CONCAT('POINT(', ?, ' ', ?, ')'))
                ) / 1000, 1) AS distanza_km,
                ST_Y(CASE WHEN e.target = 'scolastico' THEN s.coordinate ELSE e.coordinate END) AS coord_lat,
                ST_X(CASE WHEN e.target = 'scolastico' THEN s.coordinate ELSE e.coordinate END) AS coord_lng
            FROM Eventi e
            LEFT JOIN Scuole s ON s.COD_meccanografico = e.cod_scuola
            LEFT JOIN Foto f ON e.id_foto = f.ID_foto
            WHERE e.visibile = 1
              " . $where_non_eliminati . "
              AND (
                  (e.target = 'scolastico' AND s.coordinate IS NOT NULL)
                  OR
                  (e.target <> 'scolastico' AND e.coordinate IS NOT NULL)
              )
              " . ($filtro_data ? "AND DATE(e.ora_inizio) = ? " : "")
              . $where_cerca . "
              AND ST_Distance_Sphere(
                    CASE WHEN e.target = 'scolastico' THEN s.coordinate ELSE e.coordinate END,
                    ST_GeomFromText(CONCAT('POINT(', ?, ' ', ?, ')'))
                  ) <= ? * 1000
            ORDER BY distanza_km ASC
            LIMIT ? OFFSET ?
        ";
        $stmt = $conn->prepare($sql_geo);
        $types2 = 'dd';
        $params2 = [&$filtro_lng, &$filtro_lat];
        if ($filtro_data)       { $types2 .= 's'; $params2[] = &$filtro_data; }
        if ($filtro_cerca_like) { $types2 .= 'ssss'; $params2[] = &$filtro_cerca_like; $params2[] = &$filtro_cerca_like; $params2[] = &$filtro_cerca_like; $params2[] = &$filtro_cerca_like; }
        $types2 .= 'dddii';
        $params2[] = &$filtro_lng; $params2[] = &$filtro_lat; $params2[] = &$raggio_km;
        $params2[] = &$per_page;   $params2[] = &$offset;
        array_unshift($params2, $types2);
        call_user_func_array([$stmt, 'bind_param'], $params2);
        $stmt->execute();
        $res = $stmt->get_result();

    } else {
        // ── Nessun filtro geografico ──

        // COUNT base
        $sql_count = "
            SELECT COUNT(*) AS tot
            FROM Eventi e
            LEFT JOIN Scuole s ON s.COD_meccanografico = e.cod_scuola
            LEFT JOIN Foto f ON e.id_foto = f.ID_foto
            WHERE e.visibile = 1
              " . $where_non_eliminati . "
            " . ($filtro_data ? "AND DATE(e.ora_inizio) = ? " : "")
            . $where_cerca;

        $stmtC2 = $conn->prepare($sql_count);
        $typesC = '';
        $paramsC = [];
        if ($filtro_data)       { $typesC .= 's'; $paramsC[] = &$filtro_data; }
        if ($filtro_cerca_like) { $typesC .= 'ssss'; $paramsC[] = &$filtro_cerca_like; $paramsC[] = &$filtro_cerca_like; $paramsC[] = &$filtro_cerca_like; $paramsC[] = &$filtro_cerca_like; }
        if ($typesC !== '') {
            array_unshift($paramsC, $typesC);
            call_user_func_array([$stmtC2, 'bind_param'], $paramsC);
        }
        $stmtC2->execute();
        $total_events = $stmtC2->get_result()->fetch_assoc()['tot'] ?? 0;
        $stmtC2->close();

        // SELECT base paginata
        $sql_no_geo = "
            SELECT
                e.ID_evento         AS id,
                e.titolo            AS title,
                e.descrizione_breve AS summary,
                e.descrizione       AS description,
                e.ora_inizio,
                e.ora_fine,
                e.via_P             AS address,
                e.n_civico_P        AS number,
                f.path_foto         AS image_path,
                e.prenotabile,
                e.target,
                s.nome              AS school_name,
                s.via               AS school_via,
                s.n_civico          AS school_n_civico,
                NULL                AS distanza_km,
                ST_Y(CASE WHEN e.target = 'scolastico' THEN s.coordinate ELSE e.coordinate END) AS coord_lat,
                ST_X(CASE WHEN e.target = 'scolastico' THEN s.coordinate ELSE e.coordinate END) AS coord_lng
            FROM Eventi e
            LEFT JOIN Scuole s ON s.COD_meccanografico = e.cod_scuola
            LEFT JOIN Foto f ON e.id_foto = f.ID_foto
            WHERE e.visibile = 1
              " . $where_non_eliminati . "
            " . ($filtro_data ? "AND DATE(e.ora_inizio) = ? " : "")
            . $where_cerca . "
            ORDER BY e.ora_inizio DESC
            LIMIT ? OFFSET ?
        ";
        $stmt2 = $conn->prepare($sql_no_geo);
        $types3 = '';
        $params3 = [];
        if ($filtro_data)       { $types3 .= 's'; $params3[] = &$filtro_data; }
        if ($filtro_cerca_like) { $types3 .= 'ssss'; $params3[] = &$filtro_cerca_like; $params3[] = &$filtro_cerca_like; $params3[] = &$filtro_cerca_like; $params3[] = &$filtro_cerca_like; }
        $types3 .= 'ii';
        $params3[] = &$per_page; $params3[] = &$offset;
        array_unshift($params3, $types3);
        call_user_func_array([$stmt2, 'bind_param'], $params3);
        $stmt2->execute();
        $res = $stmt2->get_result();
    }

    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $events[] = $row;
        }
        $res->free();
    }
}

$total_pages = ($total_events > 0) ? (int)ceil($total_events / $per_page) : 1;

if (count($events) === 0) {
    if ($filtro_cerca !== '' && $filtro_data) {
        $msg = 'Nessun evento trovato per "' . htmlspecialchars($filtro_cerca) . '" nel giorno ' . date('d/m/Y', strtotime($filtro_data)) . '.';
    } elseif ($filtro_cerca !== '' && $filtro_lat !== null) {
        $msg = 'Nessun evento trovato per "' . htmlspecialchars($filtro_cerca) . '" entro ' . $raggio_km . ' km dal punto selezionato.';
    } elseif ($filtro_cerca !== '') {
        $msg = 'Nessun evento trovato per "' . htmlspecialchars($filtro_cerca) . '".';
    } elseif ($filtro_data) {
        $msg = 'Nessun evento trovato per il ' . date('d/m/Y', strtotime($filtro_data)) . '.';
    } elseif ($filtro_lat !== null) {
        $msg = 'Nessun evento trovato entro ' . $raggio_km . ' km dal punto selezionato.';
    } else {
        $msg = 'Al momento non ci sono eventi visibili.';
    }

    $events = [[
        'id'          => 0,
        'title'       => 'Nessun evento trovato',
        'summary'     => $msg,
        'description' => $msg,
        'ora_inizio'  => null,
        'ora_fine'    => null,
        'address'     => '',
        'number'      => '',
        'image_path'  => null,
        'distanza_km' => null,
        'school_name' => null,
    ]];
}