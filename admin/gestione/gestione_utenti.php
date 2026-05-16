<?php
function leggiUtenti(mysqli $conn): array {
    $result = $conn->query(
        'SELECT u.ID_utente, u.username, u.email, u.tipo, u.stato, u.cod_scuola,
                s.nome AS nome_scuola
         FROM   Utenti u
         LEFT JOIN Scuole s ON u.cod_scuola = s.COD_meccanografico
         ORDER  BY u.tipo ASC, u.username ASC'
    );
    if (!$result) return [];
    $utenti = [];
    while ($row = $result->fetch_assoc()) $utenti[] = $row;
    return $utenti;
}

function leggiUtente(mysqli $conn, int $id): ?array {
    $stmt = $conn->prepare(
        'SELECT u.ID_utente, u.username, u.email, u.tipo, u.stato, u.cod_scuola
         FROM   Utenti u
         WHERE  u.ID_utente = ?'
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function leggiScuoleUtenti(mysqli $conn): array {
    $result = $conn->query('SELECT COD_meccanografico, nome FROM Scuole ORDER BY nome ASC');
    if (!$result) return [];
    $scuole = [];
    while ($row = $result->fetch_assoc()) $scuole[] = $row;
    return $scuole;
}

function creaUtente(mysqli $conn, array $dati): array {
    $username  = trim($dati['username']  ?? '');
    $email     = trim($dati['email']     ?? '');
    $password  = $dati['password']        ?? '';
    $tipo      = $dati['tipo']            ?? '';
    $stato     = $dati['stato']           ?? 'ATTIVO';
    $codScuola = $tipo === 'SCOLASTICO' ? trim($dati['cod_scuola'] ?? '') : null;

    if ($username === '' || $email === '' || $password === '') {
        return ['tipo' => 'errore', 'msg' => 'Username, email e password sono obbligatori.'];
    }
    if (!in_array($tipo, ['ADMIN', 'SCOLASTICO'])) {
        return ['tipo' => 'errore', 'msg' => 'Tipo non valido.'];
    }
    if ($tipo === 'SCOLASTICO' && !$codScuola) {
        return ['tipo' => 'errore', 'msg' => 'Seleziona una scuola per un utente SCOLASTICO.'];
    }

    $stmtChk = $conn->prepare('SELECT COUNT(*) FROM Utenti WHERE username = ? OR email = ?');
    $stmtChk->bind_param('ss', $username, $email);
    $stmtChk->execute();
    $stmtChk->bind_result($dup);
    $stmtChk->fetch();
    $stmtChk->close();
    if ($dup > 0) {
        return ['tipo' => 'errore', 'msg' => 'Username o email già in uso.'];
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare(
        'INSERT INTO Utenti (username, hash_password, email, tipo, stato, cod_scuola) VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->bind_param('ssssss', $username, $hash, $email, $tipo, $stato, $codScuola);
    if (!$stmt->execute()) {
        $stmt->close();
        return ['tipo' => 'errore', 'msg' => 'Errore nel salvataggio.'];
    }
    $stmt->close();
    return ['tipo' => 'successo', 'msg' => 'Utente "' . htmlspecialchars($username) . '" creato.'];
}

function aggiornaUtente(mysqli $conn, int $id, array $dati): array {
    $username  = trim($dati['username']  ?? '');
    $email     = trim($dati['email']     ?? '');
    $password  = $dati['password']        ?? '';
    $tipo      = $dati['tipo']            ?? '';
    $stato     = $dati['stato']           ?? 'ATTIVO';
    $codScuola = $tipo === 'SCOLASTICO' ? trim($dati['cod_scuola'] ?? '') : null;

    if ($username === '' || $email === '') {
        return ['tipo' => 'errore', 'msg' => 'Username e email sono obbligatori.'];
    }
    if (!in_array($tipo, ['ADMIN', 'SCOLASTICO'])) {
        return ['tipo' => 'errore', 'msg' => 'Tipo non valido.'];
    }
    if ($tipo === 'SCOLASTICO' && !$codScuola) {
        return ['tipo' => 'errore', 'msg' => 'Seleziona una scuola per un utente SCOLASTICO.'];
    }

    $stmtChk = $conn->prepare('SELECT COUNT(*) FROM Utenti WHERE (username = ? OR email = ?) AND ID_utente != ?');
    $stmtChk->bind_param('ssi', $username, $email, $id);
    $stmtChk->execute();
    $stmtChk->bind_result($dup);
    $stmtChk->fetch();
    $stmtChk->close();
    if ($dup > 0) {
        return ['tipo' => 'errore', 'msg' => 'Username o email già in uso.'];
    }

    if ($password !== '') {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare(
            'UPDATE Utenti SET username=?, email=?, hash_password=?, tipo=?, stato=?, cod_scuola=? WHERE ID_utente=?'
        );
        $stmt->bind_param('ssssssi', $username, $email, $hash, $tipo, $stato, $codScuola, $id);
    } else {
        $stmt = $conn->prepare(
            'UPDATE Utenti SET username=?, email=?, tipo=?, stato=?, cod_scuola=? WHERE ID_utente=?'
        );
        $stmt->bind_param('sssssi', $username, $email, $tipo, $stato, $codScuola, $id);
    }
    $stmt->execute();
    $stmt->close();
    return ['tipo' => 'successo', 'msg' => 'Utente aggiornato.'];
}

function eliminaUtente(mysqli $conn, int $id): array {
    $stmt = $conn->prepare('DELETE FROM Utenti WHERE ID_utente = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    return $conn->affected_rows > 0
        ? ['tipo' => 'successo', 'msg' => 'Utente eliminato.']
        : ['tipo' => 'errore',   'msg' => 'Utente non trovato.'];
}
