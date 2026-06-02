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
    if ($sito !== '' && !url_http_valido($sito)) {
        return ['tipo' => 'errore', 'msg' => 'Il sito web deve iniziare con http:// o https://.'];
    }

    $geo = geocodifica_se_mancante($conn, $lat, $lng, $via, $civ, $citta);
    $lat = $geo['lat'];
    $lng = $geo['lng'];

    // Upload foto (opzionale)
    $idFoto = null;
    if (foto_presente($file)) {
        try {
            $idFoto = uploadFoto($conn, $file, $nome);
        } catch (Exception $e) {
            return ['tipo' => 'errore', 'msg' => 'Foto: ' . $e->getMessage()];
        }
    }

    // INSERT scuola + associazioni in transazione: se qualcosa fallisce, rollback
    // completo e pulizia della foto appena caricata (niente orfani).
    try {
        $conn->begin_transaction();
        $stmt = $conn->prepare(
            'INSERT INTO Scuole (COD_meccanografico, nome, descrizione, sito, via, n_civico, id_citta, latitudine, longitudine, id_foto)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('sssssiiddi', $cod, $nome, $desc, $sito, $via, $civ, $citta, $lat, $lng, $idFoto);
        $stmt->execute();
        $stmt->close();

        _salvaAmbitiScuola($conn, $cod, $ambiti);
        _salvaIndirizziScuola($conn, $cod, $indirizzi);
        $conn->commit();
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        pulisci_foto($conn, $idFoto);
        return ['tipo' => 'errore', 'msg' => 'Errore nel salvataggio della scuola (il codice esiste già?).'];
    }

    return ['tipo' => 'successo', 'msg' => 'Scuola "' . $nome . '" aggiunta con successo.'];
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
    if ($sito !== '' && !url_http_valido($sito)) {
        return ['tipo' => 'errore', 'msg' => 'Il sito web deve iniziare con http:// o https://.'];
    }

    $geo = geocodifica_se_mancante($conn, $lat, $lng, $via, $civ, $citta);
    $lat = $geo['lat'];
    $lng = $geo['lng'];

    // Nuova foto (opzionale): carica, ri-punta la scuola e memorizza la vecchia per pulirla dopo il commit.
    $vecchioIdFoto = null;
    if (foto_presente($file)) {
        $stmtOld = $conn->prepare('SELECT id_foto FROM Scuole WHERE COD_meccanografico = ?');
        $stmtOld->bind_param('s', $cod);
        $stmtOld->execute();
        $vecchioIdFoto = ($stmtOld->get_result()->fetch_assoc())['id_foto'] ?? null;
        $stmtOld->close();
        try {
            $idFoto = uploadFoto($conn, $file, $nome);
            assocScuolaFoto($conn, $idFoto, $cod);
        } catch (Exception $e) {
            return ['tipo' => 'errore', 'msg' => 'Foto: ' . $e->getMessage()];
        }
    }

    try {
        $conn->begin_transaction();
        $stmt = $conn->prepare(
            'UPDATE Scuole SET nome=?, descrizione=?, sito=?, via=?, n_civico=?, id_citta=?, latitudine=?, longitudine=?
             WHERE COD_meccanografico=?'
        );
        $stmt->bind_param('ssssiidds', $nome, $desc, $sito, $via, $civ, $citta, $lat, $lng, $cod);
        $stmt->execute();
        $stmt->close();

        _salvaAmbitiScuola($conn, $cod, $ambiti);
        _salvaIndirizziScuola($conn, $cod, $indirizzi);
        $conn->commit();
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        return ['tipo' => 'errore', 'msg' => "Errore nell'aggiornamento della scuola."];
    }

    // Pulisci la vecchia foto (soft-delete + unlink) solo dopo il commit.
    if ($vecchioIdFoto) {
        delFoto($conn, (int)$vecchioIdFoto);
    }

    return ['tipo' => 'successo', 'msg' => 'Scuola aggiornata con successo.'];
}

function eliminaScuola(mysqli $conn, string $cod): array {
    // Blocca se ci sono utenti collegati: la FK fk_UtentiScuola è RESTRICT e
    // scollegarli (cod_scuola=NULL) darebbe loro visibilità su tutti gli eventi.
    $stmt = $conn->prepare('SELECT COUNT(*) FROM Utenti WHERE cod_scuola = ?');
    $stmt->bind_param('s', $cod);
    $stmt->execute();
    [$nUtenti] = $stmt->get_result()->fetch_row();
    $stmt->close();
    if ($nUtenti > 0) {
        return ['tipo' => 'errore', 'msg' => 'Impossibile eliminare: ci sono utenti collegati a questa scuola. Riassegnali o eliminali prima.'];
    }

    // Blocca se ci sono eventi collegati (anche soft-deleted): annullarne il
    // cod_scuola violerebbe il CHECK degli eventi scolastici, e la FK lo impedisce.
    $stmt = $conn->prepare('SELECT COUNT(*) FROM Eventi WHERE cod_scuola = ?');
    $stmt->bind_param('s', $cod);
    $stmt->execute();
    [$nEventi] = $stmt->get_result()->fetch_row();
    $stmt->close();
    if ($nEventi > 0) {
        return ['tipo' => 'errore', 'msg' => 'Impossibile eliminare: ci sono eventi collegati a questa scuola. Eliminali prima.'];
    }

    // Recupera la foto per pulirla dopo la cancellazione.
    $stmt = $conn->prepare('SELECT id_foto FROM Scuole WHERE COD_meccanografico = ?');
    $stmt->bind_param('s', $cod);
    $stmt->execute();
    $idFoto = ($stmt->get_result()->fetch_assoc())['id_foto'] ?? null;
    $stmt->close();

    try {
        $conn->begin_transaction();
        foreach (['Scuole_Ambiti', 'Scuole_Indirizzi'] as $tabella) {
            $stmt = $conn->prepare("DELETE FROM $tabella WHERE cod_scuola = ?");
            $stmt->bind_param('s', $cod);
            $stmt->execute();
            $stmt->close();
        }
        $stmt = $conn->prepare('DELETE FROM Scuole WHERE COD_meccanografico = ?');
        $stmt->bind_param('s', $cod);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        if ($affected === 0) {
            $conn->rollback();
            return ['tipo' => 'errore', 'msg' => 'Scuola non trovata.'];
        }
        $conn->commit();
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        return ['tipo' => 'errore', 'msg' => "Errore durante l'eliminazione della scuola."];
    }

    if ($idFoto) {
        delFoto($conn, (int)$idFoto);
    }
    return ['tipo' => 'successo', 'msg' => 'Scuola eliminata.'];
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
