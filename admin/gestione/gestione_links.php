<?php
require_once __DIR__ . '/../../lib/foto.php';

function leggiLinks(mysqli $conn, bool $includiEliminati = false): array {
    $where  = $includiEliminati ? '1=1' : 'l.data_eliminazione IS NULL';
    $result = $conn->query(
        "SELECT l.ID_link, l.titolo, l.descrizione, l.indirizzo, l.n_ordine, l.data_eliminazione,
                f.path_foto
         FROM   Links l
         LEFT JOIN Foto f ON l.id_foto = f.ID_foto
         WHERE  $where
         ORDER  BY l.n_ordine ASC"
    );
    if (!$result) return [];
    $links = [];
    while ($row = $result->fetch_assoc()) $links[] = $row;
    return $links;
}

function leggiLink(mysqli $conn, int $id): ?array {
    $stmt = $conn->prepare(
        'SELECT l.*, f.path_foto
         FROM   Links l
         LEFT JOIN Foto f ON l.id_foto = f.ID_foto
         WHERE  l.ID_link = ?'
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function prossimoOrdineLinks(mysqli $conn): int {
    $result = $conn->query('SELECT COALESCE(MAX(n_ordine), 0) + 1 AS prossimo FROM Links');
    return (int)($result->fetch_assoc()['prossimo'] ?? 1);
}

function creaLink(mysqli $conn, array $dati, array $file): array {
    $titolo    = trim($dati['titolo']      ?? '');
    $desc      = trim($dati['descrizione'] ?? '');
    $indirizzo = trim($dati['indirizzo']   ?? '');
    $ordine    = (int)($dati['n_ordine']   ?? 0);

    if ($titolo === '' || $desc === '' || $indirizzo === '' || $ordine <= 0) {
        return ['tipo' => 'errore', 'msg' => 'Tutti i campi obbligatori devono essere compilati.'];
    }

    $stmtChk = $conn->prepare('SELECT COUNT(*) FROM Links WHERE n_ordine = ?');
    $stmtChk->bind_param('i', $ordine);
    $stmtChk->execute();
    $stmtChk->bind_result($dup);
    $stmtChk->fetch();
    $stmtChk->close();
    if ($dup > 0) {
        return ['tipo' => 'errore', 'msg' => "Il numero d'ordine $ordine è già in uso."];
    }

    $idFoto = null;
    $fotoOk = isset($file['error']) && $file['error'] === UPLOAD_ERR_OK && $file['size'] > 0;
    if ($fotoOk) {
        try {
            $idFoto = uploadFoto($conn, $file, $titolo);
        } catch (Exception $e) {
            return ['tipo' => 'errore', 'msg' => 'Foto: ' . $e->getMessage()];
        }
    }

    $stmt = $conn->prepare('INSERT INTO Links (titolo, descrizione, indirizzo, n_ordine, id_foto) VALUES (?, ?, ?, ?, ?)');
    $stmt->bind_param('sssii', $titolo, $desc, $indirizzo, $ordine, $idFoto);
    if (!$stmt->execute()) {
        $stmt->close();
        return ['tipo' => 'errore', 'msg' => 'Errore nel salvataggio del link.'];
    }
    $stmt->close();
    return ['tipo' => 'successo', 'msg' => 'Link "' . htmlspecialchars($titolo) . '" aggiunto con successo.'];
}

function aggiornaLink(mysqli $conn, int $id, array $dati, array $file): array {
    $titolo    = trim($dati['titolo']      ?? '');
    $desc      = trim($dati['descrizione'] ?? '');
    $indirizzo = trim($dati['indirizzo']   ?? '');
    $ordine    = (int)($dati['n_ordine']   ?? 0);

    if ($titolo === '' || $desc === '' || $indirizzo === '' || $ordine <= 0) {
        return ['tipo' => 'errore', 'msg' => 'Tutti i campi obbligatori devono essere compilati.'];
    }

    $stmtChk = $conn->prepare('SELECT COUNT(*) FROM Links WHERE n_ordine = ? AND ID_link != ?');
    $stmtChk->bind_param('ii', $ordine, $id);
    $stmtChk->execute();
    $stmtChk->bind_result($dup);
    $stmtChk->fetch();
    $stmtChk->close();
    if ($dup > 0) {
        return ['tipo' => 'errore', 'msg' => "Il numero d'ordine $ordine è già in uso."];
    }

    $fotoOk = isset($file['error']) && $file['error'] === UPLOAD_ERR_OK && $file['size'] > 0;
    if ($fotoOk) {
        try {
            $idFoto = uploadFoto($conn, $file, $titolo);
            assocLinkFoto($conn, $idFoto, $id);
        } catch (Exception $e) {
            return ['tipo' => 'errore', 'msg' => 'Foto: ' . $e->getMessage()];
        }
    }

    $stmt = $conn->prepare('UPDATE Links SET titolo=?, descrizione=?, indirizzo=?, n_ordine=? WHERE ID_link=?');
    $stmt->bind_param('sssii', $titolo, $desc, $indirizzo, $ordine, $id);
    $stmt->execute();
    $stmt->close();
    return ['tipo' => 'successo', 'msg' => 'Link aggiornato con successo.'];
}

function eliminaLink(mysqli $conn, int $id): array {
    $stmt = $conn->prepare('UPDATE Links SET data_eliminazione = CURDATE() WHERE ID_link = ? AND data_eliminazione IS NULL');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    return $conn->affected_rows > 0
        ? ['tipo' => 'successo', 'msg' => 'Link eliminato.']
        : ['tipo' => 'errore',   'msg' => 'Link non trovato.'];
}

function ripristinaLink(mysqli $conn, int $id): array {
    $stmt = $conn->prepare('UPDATE Links SET data_eliminazione = NULL WHERE ID_link = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    return $conn->affected_rows > 0
        ? ['tipo' => 'successo', 'msg' => 'Link ripristinato.']
        : ['tipo' => 'errore',   'msg' => 'Link non trovato.'];
}
