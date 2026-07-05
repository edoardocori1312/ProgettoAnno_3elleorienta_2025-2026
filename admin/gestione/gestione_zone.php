<?php
function creaZona(mysqli $conn, string $nome): array {
    $nome = trim($nome);
    if ($nome === '') {
        return ['tipo' => 'errore', 'msg' => 'Il nome della zona non può essere vuoto.'];
    }
    if (strlen($nome) < 3) {
        return ['tipo' => 'errore', 'msg' => 'Il nome deve avere almeno 3 caratteri.'];
    }
    $stmtCheck = $conn->prepare('SELECT COUNT(*) FROM Zone WHERE LOWER(nome) = LOWER(?)');
    $stmtCheck->bind_param('s', $nome);
    $stmtCheck->execute();
    [$count] = $stmtCheck->get_result()->fetch_row();
    $stmtCheck->close();
    if ($count > 0) {
        return ['tipo' => 'errore', 'msg' => 'La zona "' . $nome . '" esiste già.'];
    }
    $stmt = $conn->prepare('INSERT INTO Zone (nome) VALUES (?)');
    $stmt->bind_param('s', $nome);
    try {
        $stmt->execute();
        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        $stmt->close();
        return ['tipo' => 'errore', 'msg' => "Errore nell'inserimento della zona."];
    }
    return ['tipo' => 'successo', 'msg' => 'Zona "' . $nome . '" aggiunta con successo.'];
}

function leggiZone(mysqli $conn): array {
    $result = $conn->query('SELECT ID_zona, nome FROM Zone ORDER BY ID_zona ASC');
    if (!$result) return [];
    $zone = [];
    while ($row = $result->fetch_assoc()) {
        $zone[] = $row;
    }
    return $zone;
}

function aggiornaZona(mysqli $conn, int $id, string $nome): array {
    $nome = trim($nome);
    if ($id === 0 || $nome === '') {
        return ['tipo' => 'errore', 'msg' => 'Dati mancanti o non validi.'];
    }
    if (strlen($nome) < 3) {
        return ['tipo' => 'errore', 'msg' => 'Il nome deve avere almeno 3 caratteri.'];
    }
    $stmtCheck = $conn->prepare('SELECT COUNT(*) FROM Zone WHERE LOWER(nome) = LOWER(?) AND ID_zona != ?');
    $stmtCheck->bind_param('si', $nome, $id);
    $stmtCheck->execute();
    [$count] = $stmtCheck->get_result()->fetch_row();
    $stmtCheck->close();
    if ($count > 0) {
        return ['tipo' => 'errore', 'msg' => 'La zona "' . $nome . '" esiste già.'];
    }
    $stmt = $conn->prepare('UPDATE Zone SET nome = ? WHERE ID_zona = ?');
    $stmt->bind_param('si', $nome, $id);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
    return $affected > 0
        ? ['tipo' => 'successo', 'msg' => 'Zona modificata con successo.']
        : ['tipo' => 'errore',   'msg' => 'Nessuna modifica apportata.'];
}

function eliminaZona(mysqli $conn, int $id): array {
    $chk = $conn->prepare('SELECT COUNT(*) FROM Citta WHERE id_zona = ?');
    $chk->bind_param('i', $id);
    $chk->execute();
    [$count] = $chk->get_result()->fetch_row();
    $chk->close();
    if ($count > 0) {
        return ['tipo' => 'errore', 'msg' => 'Impossibile eliminare: la zona è associata a una o più città.'];
    }
    $stmt = $conn->prepare('DELETE FROM Zone WHERE ID_zona = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
    return $affected > 0
        ? ['tipo' => 'successo', 'msg' => 'Zona eliminata con successo.']
        : ['tipo' => 'errore',   'msg' => 'Zona non trovata.'];
}
