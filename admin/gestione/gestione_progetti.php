<?php
require_once __DIR__ . '/../../lib/foto.php';

function leggiProgetti(mysqli $conn, bool $includiEliminati = false): array {
    $where = $includiEliminati ? '1=1' : 'p.data_eliminazione IS NULL';
    $result = $conn->query(
        "SELECT p.ID_progetto, p.titolo, p.descrizione, p.n_ordine, p.data_eliminazione,
                f.path_foto
         FROM   Progetti p
         LEFT JOIN Foto f ON p.id_foto = f.ID_foto
         WHERE  $where
         ORDER  BY p.n_ordine ASC"
    );
    if (!$result) return [];
    $progetti = [];
    while ($row = $result->fetch_assoc()) $progetti[] = $row;
    return $progetti;
}

function leggiProgetto(mysqli $conn, int $id): ?array {
    $stmt = $conn->prepare(
        'SELECT p.*, f.path_foto
         FROM   Progetti p
         LEFT JOIN Foto f ON p.id_foto = f.ID_foto
         WHERE  p.ID_progetto = ?'
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function prossimoOrdineProgetti(mysqli $conn): int {
    $result = $conn->query('SELECT COALESCE(MAX(n_ordine), 0) + 1 AS prossimo FROM Progetti');
    return (int)($result->fetch_assoc()['prossimo'] ?? 1);
}

function creaProgetto(mysqli $conn, array $dati, array $file): array {
    $titolo  = trim($dati['titolo']      ?? '');
    $desc    = trim($dati['descrizione'] ?? '');
    $ordine  = (int)($dati['n_ordine']   ?? 0);

    if ($titolo === '' || $desc === '' || $ordine <= 0) {
        return ['tipo' => 'errore', 'msg' => 'Tutti i campi obbligatori devono essere compilati.'];
    }

    $stmtChk = $conn->prepare('SELECT COUNT(*) FROM Progetti WHERE n_ordine = ?');
    $stmtChk->bind_param('i', $ordine);
    $stmtChk->execute();
    [$dup] = $stmtChk->get_result()->fetch_row();
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

    $stmt = $conn->prepare('INSERT INTO Progetti (titolo, descrizione, n_ordine, id_foto) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('ssii', $titolo, $desc, $ordine, $idFoto);
    if (!$stmt->execute()) {
        $stmt->close();
        return ['tipo' => 'errore', 'msg' => 'Errore nel salvataggio del progetto.'];
    }
    $stmt->close();
    return ['tipo' => 'successo', 'msg' => 'Progetto "' . htmlspecialchars($titolo) . '" aggiunto con successo.'];
}

function aggiornaProgetto(mysqli $conn, int $id, array $dati, array $file): array {
    $titolo  = trim($dati['titolo']      ?? '');
    $desc    = trim($dati['descrizione'] ?? '');
    $ordine  = (int)($dati['n_ordine']   ?? 0);

    if ($titolo === '' || $desc === '' || $ordine <= 0) {
        return ['tipo' => 'errore', 'msg' => 'Tutti i campi obbligatori devono essere compilati.'];
    }

    $stmtChk = $conn->prepare('SELECT COUNT(*) FROM Progetti WHERE n_ordine = ? AND ID_progetto != ?');
    $stmtChk->bind_param('ii', $ordine, $id);
    $stmtChk->execute();
    [$dup] = $stmtChk->get_result()->fetch_row();
    $stmtChk->close();
    if ($dup > 0) {
        return ['tipo' => 'errore', 'msg' => "Il numero d'ordine $ordine è già in uso."];
    }

    $fotoOk = isset($file['error']) && $file['error'] === UPLOAD_ERR_OK && $file['size'] > 0;
    if ($fotoOk) {
        try {
            $idFoto = uploadFoto($conn, $file, $titolo);
            assocProgettiFoto($conn, $idFoto, $id);
        } catch (Exception $e) {
            return ['tipo' => 'errore', 'msg' => 'Foto: ' . $e->getMessage()];
        }
    }

    $stmt = $conn->prepare('UPDATE Progetti SET titolo=?, descrizione=?, n_ordine=? WHERE ID_progetto=?');
    $stmt->bind_param('ssii', $titolo, $desc, $ordine, $id);
    $stmt->execute();
    $stmt->close();
    return ['tipo' => 'successo', 'msg' => 'Progetto aggiornato con successo.'];
}

function eliminaProgetto(mysqli $conn, int $id): array {
    $stmt = $conn->prepare('UPDATE Progetti SET data_eliminazione = CURDATE() WHERE ID_progetto = ? AND data_eliminazione IS NULL');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    return $conn->affected_rows > 0
        ? ['tipo' => 'successo', 'msg' => 'Progetto eliminato.']
        : ['tipo' => 'errore',   'msg' => 'Progetto non trovato.'];
}

function ripristinaProgetto(mysqli $conn, int $id): array {
    $stmt = $conn->prepare('UPDATE Progetti SET data_eliminazione = NULL WHERE ID_progetto = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    return $conn->affected_rows > 0
        ? ['tipo' => 'successo', 'msg' => 'Progetto ripristinato.']
        : ['tipo' => 'errore',   'msg' => 'Progetto non trovato.'];
}
