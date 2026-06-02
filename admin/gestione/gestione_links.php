<?php
require_once __DIR__ . '/../../lib/foto.php';
require_once __DIR__ . '/../../lib/crud.php';

function leggiLinks(mysqli $conn, bool $includiEliminati = false): array {
    $where  = $includiEliminati ? '1=1' : 'l.data_eliminazione IS NULL';
    $result = $conn->query(
        "SELECT l.ID_link, l.titolo, l.descrizione, l.indirizzo, l.n_ordine, l.data_eliminazione,
                f.path_foto
         FROM   Links l
         LEFT JOIN Foto f ON l.id_foto = f.ID_foto AND f.data_eliminazione IS NULL
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
         LEFT JOIN Foto f ON l.id_foto = f.ID_foto AND f.data_eliminazione IS NULL
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
    if (!url_http_valido($indirizzo)) {
        return ['tipo' => 'errore', 'msg' => "L'indirizzo deve iniziare con http:// o https://."];
    }
    if (ordine_in_uso($conn, 'Links', 'ID_link', $ordine)) {
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

    $stmt = $conn->prepare('INSERT INTO Links (titolo, descrizione, indirizzo, n_ordine, id_foto) VALUES (?, ?, ?, ?, ?)');
    $stmt->bind_param('sssii', $titolo, $desc, $indirizzo, $ordine, $idFoto);
    try {
        $stmt->execute();
        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        $stmt->close();
        pulisci_foto($conn, $idFoto);
        return ['tipo' => 'errore', 'msg' => 'Errore nel salvataggio del link.'];
    }
    return ['tipo' => 'successo', 'msg' => 'Link "' . $titolo . '" aggiunto con successo.'];
}

function aggiornaLink(mysqli $conn, int $id, array $dati, array $file): array {
    $titolo    = trim($dati['titolo']      ?? '');
    $desc      = trim($dati['descrizione'] ?? '');
    $indirizzo = trim($dati['indirizzo']   ?? '');
    $ordine    = (int)($dati['n_ordine']   ?? 0);

    if ($titolo === '' || $desc === '' || $indirizzo === '' || $ordine <= 0) {
        return ['tipo' => 'errore', 'msg' => 'Tutti i campi obbligatori devono essere compilati.'];
    }
    if (!url_http_valido($indirizzo)) {
        return ['tipo' => 'errore', 'msg' => "L'indirizzo deve iniziare con http:// o https://."];
    }
    if (ordine_in_uso($conn, 'Links', 'ID_link', $ordine, $id)) {
        return ['tipo' => 'errore', 'msg' => "Il numero d'ordine $ordine è già in uso."];
    }

    if (foto_presente($file)) {
        $stmtOld = $conn->prepare('SELECT id_foto FROM Links WHERE ID_link = ?');
        $stmtOld->bind_param('i', $id);
        $stmtOld->execute();
        $vecchioIdFoto = ($stmtOld->get_result()->fetch_assoc())['id_foto'] ?? null;
        $stmtOld->close();
        try {
            $idFoto = uploadFoto($conn, $file, $titolo);
            assocLinkFoto($conn, $idFoto, $id);
        } catch (Exception $e) {
            return ['tipo' => 'errore', 'msg' => 'Foto: ' . $e->getMessage()];
        }
        if ($vecchioIdFoto) {
            delFoto($conn, (int)$vecchioIdFoto);
        }
    }

    $stmt = $conn->prepare('UPDATE Links SET titolo=?, descrizione=?, indirizzo=?, n_ordine=? WHERE ID_link=?');
    $stmt->bind_param('sssii', $titolo, $desc, $indirizzo, $ordine, $id);
    $stmt->execute();
    $stmt->close();
    return ['tipo' => 'successo', 'msg' => 'Link aggiornato con successo.'];
}

function eliminaLink(mysqli $conn, int $id): array {
    return soft_delete($conn, 'Links', 'ID_link', $id, 'CURDATE()', 'Link');
}

function ripristinaLink(mysqli $conn, int $id): array {
    return soft_restore($conn, 'Links', 'ID_link', $id, 'Link');
}
