<?php
require_once __DIR__ . '/../../lib/geo.php';
require_once __DIR__ . '/../../lib/foto.php';

function leggiCitta(mysqli $conn): array {
    $result = $conn->query('SELECT ID_citta, nome FROM Citta ORDER BY nome ASC');
    if (!$result) return [];
    $citta = [];
    while ($row = $result->fetch_assoc()) $citta[] = $row;
    return $citta;
}

function leggiAmbiti(mysqli $conn): array {
    $result = $conn->query('SELECT ID_ambito, nome FROM Ambiti ORDER BY nome ASC');
    if (!$result) return [];
    $ambiti = [];
    while ($row = $result->fetch_assoc()) $ambiti[] = $row;
    return $ambiti;
}

function leggiIndirizzi(mysqli $conn): array {
    $result = $conn->query('SELECT ID_indirizzo, nome FROM Indirizzi_studio ORDER BY ordine ASC');
    if (!$result) return [];
    $ind = [];
    while ($row = $result->fetch_assoc()) $ind[] = $row;
    return $ind;
}

function leggiScuole(mysqli $conn, bool $isAdmin, ?string $codScuolaUtente, string $filtroCitta = '', string $filtroNome = ''): array {
    $where = ['1=1'];
    $params = [];
    $types  = '';

    if (!$isAdmin && $codScuolaUtente !== null) {
        $where[] = 's.COD_meccanografico = ?';
        $params[] = $codScuolaUtente;
        $types .= 's';
    }
    if ($filtroNome !== '') {
        $like = '%' . $filtroNome . '%';
        $where[] = 's.nome LIKE ?';
        $params[] = $like;
        $types .= 's';
    }
    if ($filtroCitta !== '') {
        $where[] = 's.id_citta = ?';
        $params[] = (int)$filtroCitta;
        $types .= 'i';
    }

    $sql = 'SELECT s.COD_meccanografico, s.nome, s.descrizione, s.via, s.n_civico,
                   s.sito, s.latitudine, s.longitudine, s.id_foto,
                   c.nome AS nome_citta, f.path_foto
            FROM Scuole s
            LEFT JOIN Citta c ON s.id_citta = c.ID_citta
            LEFT JOIN Foto f ON s.id_foto = f.ID_foto AND f.data_eliminazione IS NULL
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY s.nome ASC';

    if ($types === '') {
        $result = $conn->query($sql);
        if (!$result) return [];
        $scuole = [];
        while ($row = $result->fetch_assoc()) $scuole[] = $row;
        return $scuole;
    }

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    $scuole = [];
    while ($row = $result->fetch_assoc()) $scuole[] = $row;
    return $scuole;
}

function leggiScuola(mysqli $conn, string $cod): ?array {
    $stmt = $conn->prepare(
        'SELECT s.*, c.nome AS nome_citta
         FROM Scuole s LEFT JOIN Citta c ON s.id_citta = c.ID_citta
         WHERE s.COD_meccanografico = ?'
    );
    $stmt->bind_param('s', $cod);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function leggiAmbitiScuola(mysqli $conn, string $cod): array {
    $stmt = $conn->prepare('SELECT id_ambito FROM Scuole_Ambiti WHERE cod_scuola = ?');
    $stmt->bind_param('s', $cod);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    $ids = [];
    while ($row = $result->fetch_assoc()) $ids[] = (int)$row['id_ambito'];
    return $ids;
}

function leggiIndirizziScuola(mysqli $conn, string $cod): array {
    $stmt = $conn->prepare('SELECT id_indirizzo FROM Scuole_Indirizzi WHERE cod_scuola = ? ORDER BY n_ordine ASC');
    $stmt->bind_param('s', $cod);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    $ids = [];
    while ($row = $result->fetch_assoc()) $ids[] = (int)$row['id_indirizzo'];
    return $ids;
}

function creaScuola(mysqli $conn, array $dati, array $file): array {
    $cod  = trim($dati['cod']         ?? '');
    $nome = trim($dati['nome']        ?? '');
    $desc = trim($dati['descrizione'] ?? '');
    $sito = trim($dati['sito']        ?? '');
    $via  = trim($dati['via']         ?? '');
    $civ  = (int)($dati['n_civico']   ?? 0);
    $citta = (int)($dati['id_citta']  ?? 0);
    $lat  = (float)($dati['lat']      ?? 0);
    $lng  = (float)($dati['lng']      ?? 0);
    $ambiti    = $dati['ambiti']    ?? [];
    $indirizzi = $dati['indirizzi'] ?? [];

    if ($cod === '' || $nome === '' || $desc === '' || $via === '' || $civ <= 0 || $citta <= 0) {
        return ['tipo' => 'errore', 'msg' => 'Tutti i campi obbligatori devono essere compilati.'];
    }

    // Geocoding fallback
    if ($lat == 0 && $lng == 0) {
        $stmtC = $conn->prepare('SELECT nome FROM Citta WHERE ID_citta = ?');
        $stmtC->bind_param('i', $citta);
        $stmtC->execute();
        $nomeCitta = ($stmtC->get_result()->fetch_assoc())['nome'] ?? '';
        $stmtC->close();
        $geo = geocodifica($via, $civ, $nomeCitta);
        $lat = $geo['lat'];
        $lng = $geo['lng'];
    }

    // Upload foto
    $idFoto = null;
    $fotoPresente = isset($file['error']) && $file['error'] === UPLOAD_ERR_OK && $file['size'] > 0;
    if ($fotoPresente) {
        try {
            $idFoto = uploadFoto($conn, $file, $nome);
        } catch (Exception $e) {
            return ['tipo' => 'errore', 'msg' => 'Foto: ' . $e->getMessage()];
        }
    }

    $stmt = $conn->prepare(
        'INSERT INTO Scuole (COD_meccanografico, nome, descrizione, sito, via, n_civico, id_citta, latitudine, longitudine, id_foto)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->bind_param('sssssiiddi', $cod, $nome, $desc, $sito, $via, $civ, $citta, $lat, $lng, $idFoto);
    if (!$stmt->execute()) {
        $stmt->close();
        return ['tipo' => 'errore', 'msg' => 'Errore nel salvataggio della scuola (il codice esiste già?).'];
    }
    $stmt->close();

    _salvaAmbitiScuola($conn, $cod, $ambiti);
    _salvaIndirizziScuola($conn, $cod, $indirizzi);

    return ['tipo' => 'successo', 'msg' => 'Scuola "' . htmlspecialchars($nome) . '" aggiunta con successo.'];
}

function aggiornaScuola(mysqli $conn, string $cod, array $dati, array $file): array {
    $nome = trim($dati['nome']        ?? '');
    $desc = trim($dati['descrizione'] ?? '');
    $sito = trim($dati['sito']        ?? '');
    $via  = trim($dati['via']         ?? '');
    $civ  = (int)($dati['n_civico']   ?? 0);
    $citta = (int)($dati['id_citta']  ?? 0);
    $lat  = (float)($dati['lat']      ?? 0);
    $lng  = (float)($dati['lng']      ?? 0);
    $ambiti    = $dati['ambiti']    ?? [];
    $indirizzi = $dati['indirizzi'] ?? [];

    if ($nome === '' || $desc === '' || $via === '' || $civ <= 0 || $citta <= 0) {
        return ['tipo' => 'errore', 'msg' => 'Tutti i campi obbligatori devono essere compilati.'];
    }

    if ($lat == 0 && $lng == 0) {
        $stmtC = $conn->prepare('SELECT nome FROM Citta WHERE ID_citta = ?');
        $stmtC->bind_param('i', $citta);
        $stmtC->execute();
        $nomeCitta = ($stmtC->get_result()->fetch_assoc())['nome'] ?? '';
        $stmtC->close();
        $geo = geocodifica($via, $civ, $nomeCitta);
        $lat = $geo['lat'];
        $lng = $geo['lng'];
    }

    // Nuova foto
    $fotoPresente = isset($file['error']) && $file['error'] === UPLOAD_ERR_OK && $file['size'] > 0;
    if ($fotoPresente) {
        try {
            $idFoto = uploadFoto($conn, $file, $nome);
            assocScuolaFoto($conn, $idFoto, $cod);
        } catch (Exception $e) {
            return ['tipo' => 'errore', 'msg' => 'Foto: ' . $e->getMessage()];
        }
    }

    $stmt = $conn->prepare(
        'UPDATE Scuole SET nome=?, descrizione=?, sito=?, via=?, n_civico=?, id_citta=?, latitudine=?, longitudine=?
         WHERE COD_meccanografico=?'
    );
    $stmt->bind_param('ssssiidds', $nome, $desc, $sito, $via, $civ, $citta, $lat, $lng, $cod);
    $stmt->execute();
    $stmt->close();

    _salvaAmbitiScuola($conn, $cod, $ambiti);
    _salvaIndirizziScuola($conn, $cod, $indirizzi);

    return ['tipo' => 'successo', 'msg' => 'Scuola aggiornata con successo.'];
}

function eliminaScuola(mysqli $conn, string $cod): array {
    // Annulla FK in eventi
    $stmt = $conn->prepare('UPDATE Eventi SET cod_scuola = NULL WHERE cod_scuola = ?');
    $stmt->bind_param('s', $cod);
    $stmt->execute();
    $stmt->close();

    // Rimuovi associazioni
    foreach (['Scuole_Ambiti', 'Scuole_Indirizzi'] as $tabella) {
        $stmt = $conn->prepare("DELETE FROM $tabella WHERE cod_scuola = ?");
        $stmt->bind_param('s', $cod);
        $stmt->execute();
        $stmt->close();
    }

    $stmt = $conn->prepare('DELETE FROM Scuole WHERE COD_meccanografico = ?');
    $stmt->bind_param('s', $cod);
    $stmt->execute();
    $stmt->close();

    return $conn->affected_rows > 0
        ? ['tipo' => 'successo', 'msg' => 'Scuola eliminata.']
        : ['tipo' => 'errore',   'msg' => 'Scuola non trovata.'];
}

// Sostituisce tutti gli ambiti associati alla scuola
function _salvaAmbitiScuola(mysqli $conn, string $cod, array $ambiti): void {
    $stmt = $conn->prepare('DELETE FROM Scuole_Ambiti WHERE cod_scuola = ?');
    $stmt->bind_param('s', $cod);
    $stmt->execute();
    $stmt->close();
    foreach ($ambiti as $idAmbito) {
        $id = (int)$idAmbito;
        if ($id <= 0) continue;
        $stmt = $conn->prepare('INSERT INTO Scuole_Ambiti (cod_scuola, id_ambito) VALUES (?, ?)');
        $stmt->bind_param('si', $cod, $id);
        $stmt->execute();
        $stmt->close();
    }
}

// Sostituisce tutti gli indirizzi associati alla scuola
function _salvaIndirizziScuola(mysqli $conn, string $cod, array $indirizzi): void {
    $stmt = $conn->prepare('DELETE FROM Scuole_Indirizzi WHERE cod_scuola = ?');
    $stmt->bind_param('s', $cod);
    $stmt->execute();
    $stmt->close();
    foreach ($indirizzi as $ordine => $idIndirizzo) {
        $id  = (int)$idIndirizzo;
        $ord = (int)$ordine + 1;
        if ($id <= 0) continue;
        $stmt = $conn->prepare('INSERT INTO Scuole_Indirizzi (cod_scuola, id_indirizzo, n_ordine) VALUES (?, ?, ?)');
        $stmt->bind_param('sii', $cod, $id, $ord);
        $stmt->execute();
        $stmt->close();
    }
}
