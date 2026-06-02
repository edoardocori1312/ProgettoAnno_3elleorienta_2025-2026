<?php
require_once __DIR__ . '/../../lib/foto.php';
require_once __DIR__ . '/../../lib/crud.php';

function leggiProgetti(mysqli $conn, bool $includiEliminati = false): array {
    $where = $includiEliminati ? '1=1' : 'p.data_eliminazione IS NULL';
    $result = $conn->query(
        "SELECT p.ID_progetto, p.titolo, p.descrizione, p.n_ordine, p.data_eliminazione,
                f.path_foto
         FROM   Progetti p
         LEFT JOIN Foto f ON p.id_foto = f.ID_foto AND f.data_eliminazione IS NULL
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
         LEFT JOIN Foto f ON p.id_foto = f.ID_foto AND f.data_eliminazione IS NULL
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
    if (ordine_in_uso($conn, 'Progetti', 'ID_progetto', $ordine)) {
        return ['tipo' => 'errore', 'msg' => "Il numero d'ordine $ordine è già in uso."];
    }

    $idFoto = null;
    if (foto_presente($file)) {
        try {
            $idFoto = uploadFoto($conn, $file, $titolo);
        } catch (Exception $e) {
            return ['tipo' => 'errore', 'msg' => 'Foto: ' . $e->getMessage()];
        }
    }

    $stmt = $conn->prepare('INSERT INTO Progetti (titolo, descrizione, n_ordine, id_foto) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('ssii', $titolo, $desc, $ordine, $idFoto);
    try {
        $stmt->execute();
        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        $stmt->close();
        pulisci_foto($conn, $idFoto);
        return ['tipo' => 'errore', 'msg' => 'Errore nel salvataggio del progetto.'];
    }
    return ['tipo' => 'successo', 'msg' => 'Progetto "' . $titolo . '" aggiunto con successo.'];
}

function aggiornaProgetto(mysqli $conn, int $id, array $dati, array $file): array {
    $titolo  = trim($dati['titolo']      ?? '');
    $desc    = trim($dati['descrizione'] ?? '');
    $ordine  = (int)($dati['n_ordine']   ?? 0);

    if ($titolo === '' || $desc === '' || $ordine <= 0) {
        return ['tipo' => 'errore', 'msg' => 'Tutti i campi obbligatori devono essere compilati.'];
    }
    if (ordine_in_uso($conn, 'Progetti', 'ID_progetto', $ordine, $id)) {
        return ['tipo' => 'errore', 'msg' => "Il numero d'ordine $ordine è già in uso."];
    }

    if (foto_presente($file)) {
        $stmtOld = $conn->prepare('SELECT id_foto FROM Progetti WHERE ID_progetto = ?');
        $stmtOld->bind_param('i', $id);
        $stmtOld->execute();
        $vecchioIdFoto = ($stmtOld->get_result()->fetch_assoc())['id_foto'] ?? null;
        $stmtOld->close();
        try {
            $idFoto = uploadFoto($conn, $file, $titolo);
            assocProgettiFoto($conn, $idFoto, $id);
        } catch (Exception $e) {
            return ['tipo' => 'errore', 'msg' => 'Foto: ' . $e->getMessage()];
        }
        if ($vecchioIdFoto) {
            delFoto($conn, (int)$vecchioIdFoto);
        }
    }

    $stmt = $conn->prepare('UPDATE Progetti SET titolo=?, descrizione=?, n_ordine=? WHERE ID_progetto=?');
    $stmt->bind_param('ssii', $titolo, $desc, $ordine, $id);
    $stmt->execute();
    $stmt->close();
    return ['tipo' => 'successo', 'msg' => 'Progetto aggiornato con successo.'];
}

function eliminaProgetto(mysqli $conn, int $id): array {
    return soft_delete($conn, 'Progetti', 'ID_progetto', $id, 'CURDATE()', 'Progetto');
}

function ripristinaProgetto(mysqli $conn, int $id): array {
    return soft_restore($conn, 'Progetti', 'ID_progetto', $id, 'Progetto');
}
