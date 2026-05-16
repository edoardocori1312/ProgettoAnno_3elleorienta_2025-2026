<?php
require_once __DIR__ . '/../../lib/geo.php';
require_once __DIR__ . '/../../lib/foto.php';

function leggiEventi(mysqli $conn, bool $isAdmin, ?string $codScuola, bool $includiEliminati = false): array {
    $where  = $includiEliminati ? ['1=1'] : ['e.data_eliminazione IS NULL'];
    $params = [];
    $types  = '';

    if (!$isAdmin && $codScuola !== null) {
        $where[] = 'e.cod_scuola = ?';
        $params[] = $codScuola;
        $types   .= 's';
    }

    $sql = 'SELECT e.ID_evento, e.titolo, e.descrizione_breve, e.target,
                   e.ora_inizio, e.ora_fine, e.visibile, e.prenotabile, e.data_eliminazione,
                   e.cod_scuola, s.nome AS nome_scuola,
                   e.id_citta, c.nome AS nome_citta,
                   f.path_foto
            FROM   Eventi e
            LEFT JOIN Scuole s ON e.cod_scuola = s.COD_meccanografico
            LEFT JOIN Citta  c ON e.id_citta   = c.ID_citta
            LEFT JOIN Foto   f ON e.id_foto    = f.ID_foto
            WHERE  ' . implode(' AND ', $where) . '
            ORDER  BY e.ora_inizio DESC';

    if ($types === '') {
        $result = $conn->query($sql);
        if (!$result) return [];
        $eventi = [];
        while ($row = $result->fetch_assoc()) $eventi[] = $row;
        return $eventi;
    }

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    $eventi = [];
    while ($row = $result->fetch_assoc()) $eventi[] = $row;
    return $eventi;
}

function leggiEvento(mysqli $conn, int $id): ?array {
    $stmt = $conn->prepare(
        'SELECT e.*, c.nome AS nome_citta, f.path_foto
         FROM   Eventi e
         LEFT JOIN Citta c ON e.id_citta = c.ID_citta
         LEFT JOIN Foto  f ON e.id_foto  = f.ID_foto
         WHERE  e.ID_evento = ?'
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function leggiScuoleEventi(mysqli $conn): array {
    $result = $conn->query('SELECT COD_meccanografico, nome FROM Scuole ORDER BY nome ASC');
    if (!$result) return [];
    $scuole = [];
    while ($row = $result->fetch_assoc()) $scuole[] = $row;
    return $scuole;
}

function leggiCittaEventi(mysqli $conn): array {
    $result = $conn->query('SELECT ID_citta, nome FROM Citta ORDER BY nome ASC');
    if (!$result) return [];
    $citta = [];
    while ($row = $result->fetch_assoc()) $citta[] = $row;
    return $citta;
}

function creaEvento(mysqli $conn, array $dati, array $file, bool $isAdmin, ?string $codScuola): array {
    $titolo    = trim($dati['titolo']            ?? '');
    $desc      = trim($dati['descrizione']       ?? '');
    $descBreve = trim($dati['descrizione_breve'] ?? '');
    $target    = $dati['target']                 ?? '';
    $oraInizio = str_replace('T', ' ', trim($dati['ora_inizio'] ?? ''));
    $oraFine   = str_replace('T', ' ', trim($dati['ora_fine']   ?? ''));
    $visibile  = isset($dati['visibile'])    ? 1 : 0;
    $prenot    = isset($dati['prenotabile']) ? 1 : 0;

    if ($titolo === '' || $desc === '' || $descBreve === '' || $oraInizio === '' || $oraFine === '') {
        return ['tipo' => 'errore', 'msg' => 'Tutti i campi obbligatori devono essere compilati.'];
    }
    if (!in_array($target, ['TERRITORIALE', 'SCOLASTICO'])) {
        return ['tipo' => 'errore', 'msg' => 'Seleziona un target valido.'];
    }

    if ($target === 'SCOLASTICO') {
        $codEv = $isAdmin ? trim($dati['cod_scuola'] ?? '') : $codScuola;
        if (!$codEv) return ['tipo' => 'errore', 'msg' => 'Seleziona una scuola.'];
        $via = null; $civico = null; $citta = null; $lat = null; $lng = null;
    } else {
        $codEv  = null;
        $via    = trim($dati['via']       ?? '');
        $civico = (int)($dati['n_civico'] ?? 0);
        $citta  = (int)($dati['id_citta'] ?? 0);
        $lat    = (float)($dati['lat']    ?? 0);
        $lng    = (float)($dati['lng']    ?? 0);
        if ($via === '' || $civico <= 0 || $citta <= 0) {
            return ['tipo' => 'errore', 'msg' => 'Via, civico e città sono obbligatori per eventi territoriali.'];
        }
        if ($lat == 0 && $lng == 0) {
            $stmtC = $conn->prepare('SELECT nome FROM Citta WHERE ID_citta = ?');
            $stmtC->bind_param('i', $citta);
            $stmtC->execute();
            $nomeCitta = ($stmtC->get_result()->fetch_assoc())['nome'] ?? '';
            $stmtC->close();
            $geo = geocodifica($via, $civico, $nomeCitta);
            $lat = $geo['lat'];
            $lng = $geo['lng'];
        }
    }

    $fotoOk = isset($file['error']) && $file['error'] === UPLOAD_ERR_OK && $file['size'] > 0;
    if (!$fotoOk) {
        return ['tipo' => 'errore', 'msg' => 'La foto è obbligatoria per un nuovo evento.'];
    }
    try {
        $idFoto = uploadFoto($conn, $file, $titolo);
    } catch (Exception $e) {
        return ['tipo' => 'errore', 'msg' => 'Foto: ' . $e->getMessage()];
    }

    if ($target === 'SCOLASTICO') {
        $stmt = $conn->prepare(
            'INSERT INTO Eventi (titolo, descrizione, descrizione_breve, target, ora_inizio, ora_fine,
                                 visibile, prenotabile, cod_scuola, id_foto)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('ssssssiisi', $titolo, $desc, $descBreve, $target,
                          $oraInizio, $oraFine, $visibile, $prenot, $codEv, $idFoto);
    } else {
        $stmt = $conn->prepare(
            'INSERT INTO Eventi (titolo, descrizione, descrizione_breve, target, ora_inizio, ora_fine,
                                 visibile, prenotabile, via_P, n_civico_P, id_citta, latitudine, longitudine, id_foto)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('ssssssiisiiddi', $titolo, $desc, $descBreve, $target,
                          $oraInizio, $oraFine, $visibile, $prenot,
                          $via, $civico, $citta, $lat, $lng, $idFoto);
    }

    if (!$stmt->execute()) {
        $stmt->close();
        return ['tipo' => 'errore', 'msg' => "Errore nel salvataggio dell'evento."];
    }
    $stmt->close();
    return ['tipo' => 'successo', 'msg' => 'Evento "' . htmlspecialchars($titolo) . '" creato con successo.'];
}

function aggiornaEvento(mysqli $conn, int $id, array $dati, array $file, bool $isAdmin, ?string $codScuola): array {
    $vecchio = leggiEvento($conn, $id);
    if (!$vecchio) return ['tipo' => 'errore', 'msg' => 'Evento non trovato.'];
    if (!$isAdmin && $vecchio['cod_scuola'] !== $codScuola) {
        return ['tipo' => 'errore', 'msg' => 'Non hai i permessi.'];
    }

    $titolo    = trim($dati['titolo']            ?? '');
    $desc      = trim($dati['descrizione']       ?? '');
    $descBreve = trim($dati['descrizione_breve'] ?? '');
    $target    = $dati['target']                 ?? '';
    $oraInizio = str_replace('T', ' ', trim($dati['ora_inizio'] ?? ''));
    $oraFine   = str_replace('T', ' ', trim($dati['ora_fine']   ?? ''));
    $visibile  = isset($dati['visibile'])    ? 1 : 0;
    $prenot    = isset($dati['prenotabile']) ? 1 : 0;

    if ($titolo === '' || $desc === '' || $descBreve === '' || $oraInizio === '' || $oraFine === '') {
        return ['tipo' => 'errore', 'msg' => 'Tutti i campi obbligatori devono essere compilati.'];
    }
    if (!in_array($target, ['TERRITORIALE', 'SCOLASTICO'])) {
        return ['tipo' => 'errore', 'msg' => 'Seleziona un target valido.'];
    }

    if ($target === 'SCOLASTICO') {
        $codEv = $isAdmin ? trim($dati['cod_scuola'] ?? '') : $codScuola;
        if (!$codEv) return ['tipo' => 'errore', 'msg' => 'Seleziona una scuola.'];
        $via = null; $civico = null; $citta = null; $lat = null; $lng = null;
    } else {
        $codEv  = null;
        $via    = trim($dati['via']       ?? '');
        $civico = (int)($dati['n_civico'] ?? 0);
        $citta  = (int)($dati['id_citta'] ?? 0);
        $lat    = (float)($dati['lat']    ?? 0);
        $lng    = (float)($dati['lng']    ?? 0);
        if ($via === '' || $civico <= 0 || $citta <= 0) {
            return ['tipo' => 'errore', 'msg' => 'Via, civico e città sono obbligatori per eventi territoriali.'];
        }
        if ($lat == 0 && $lng == 0) {
            $stmtC = $conn->prepare('SELECT nome FROM Citta WHERE ID_citta = ?');
            $stmtC->bind_param('i', $citta);
            $stmtC->execute();
            $nomeCitta = ($stmtC->get_result()->fetch_assoc())['nome'] ?? '';
            $stmtC->close();
            $geo = geocodifica($via, $civico, $nomeCitta);
            $lat = $geo['lat'];
            $lng = $geo['lng'];
        }
    }

    $fotoOk = isset($file['error']) && $file['error'] === UPLOAD_ERR_OK && $file['size'] > 0;
    if ($fotoOk) {
        try {
            $idFoto = uploadFoto($conn, $file, $titolo);
            assocEventiFoto($conn, $idFoto, $id);
        } catch (Exception $e) {
            return ['tipo' => 'errore', 'msg' => 'Foto: ' . $e->getMessage()];
        }
    }

    if ($target === 'SCOLASTICO') {
        $stmt = $conn->prepare(
            'UPDATE Eventi SET titolo=?, descrizione=?, descrizione_breve=?, target=?,
                               ora_inizio=?, ora_fine=?, visibile=?, prenotabile=?,
                               cod_scuola=?, via_P=NULL, n_civico_P=NULL, id_citta=NULL,
                               latitudine=NULL, longitudine=NULL
             WHERE ID_evento=?'
        );
        $stmt->bind_param('ssssssiisi', $titolo, $desc, $descBreve, $target,
                          $oraInizio, $oraFine, $visibile, $prenot, $codEv, $id);
    } else {
        $stmt = $conn->prepare(
            'UPDATE Eventi SET titolo=?, descrizione=?, descrizione_breve=?, target=?,
                               ora_inizio=?, ora_fine=?, visibile=?, prenotabile=?,
                               cod_scuola=NULL, via_P=?, n_civico_P=?, id_citta=?,
                               latitudine=?, longitudine=?
             WHERE ID_evento=?'
        );
        $stmt->bind_param('ssssssiisiiddi', $titolo, $desc, $descBreve, $target,
                          $oraInizio, $oraFine, $visibile, $prenot,
                          $via, $civico, $citta, $lat, $lng, $id);
    }
    $stmt->execute();
    $stmt->close();
    return ['tipo' => 'successo', 'msg' => 'Evento aggiornato con successo.'];
}

function eliminaEvento(mysqli $conn, int $id): array {
    $stmt = $conn->prepare('UPDATE Eventi SET data_eliminazione = NOW() WHERE ID_evento = ? AND data_eliminazione IS NULL');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    return $conn->affected_rows > 0
        ? ['tipo' => 'successo', 'msg' => 'Evento eliminato.']
        : ['tipo' => 'errore',   'msg' => 'Evento non trovato.'];
}

function ripristinaEvento(mysqli $conn, int $id): array {
    $stmt = $conn->prepare('UPDATE Eventi SET data_eliminazione = NULL WHERE ID_evento = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    return $conn->affected_rows > 0
        ? ['tipo' => 'successo', 'msg' => 'Evento ripristinato.']
        : ['tipo' => 'errore',   'msg' => 'Evento non trovato.'];
}
