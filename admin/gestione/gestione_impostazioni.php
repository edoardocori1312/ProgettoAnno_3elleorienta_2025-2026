<?php
function cambiaPassword(mysqli $conn, int $uid, string $attuale, string $nuova, string $conferma): array {
    if ($attuale === '' || $nuova === '' || $conferma === '') {
        return ['tipo' => 'errore', 'msg' => 'Tutti i campi sono obbligatori.'];
    }
    if ($nuova !== $conferma) {
        return ['tipo' => 'errore', 'msg' => 'La nuova password e la conferma non corrispondono.'];
    }
    if (strlen($nuova) < 6) {
        return ['tipo' => 'errore', 'msg' => 'La nuova password deve avere almeno 6 caratteri.'];
    }

    $stmt = $conn->prepare('SELECT hash_password FROM Utenti WHERE ID_utente = ?');
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $riga = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$riga || !password_verify($attuale, $riga['hash_password'])) {
        return ['tipo' => 'errore', 'msg' => 'Password attuale non corretta.'];
    }

    $hash = password_hash($nuova, PASSWORD_DEFAULT);
    $stmt = $conn->prepare('UPDATE Utenti SET hash_password = ? WHERE ID_utente = ?');
    $stmt->bind_param('si', $hash, $uid);
    $stmt->execute();
    $stmt->close();
    return ['tipo' => 'successo', 'msg' => 'Password aggiornata con successo.'];
}

function leggiProfiloUtente(mysqli $conn, int $uid): ?array {
    $stmt = $conn->prepare(
        'SELECT u.username, u.email, u.tipo, u.stato, s.nome AS nome_scuola
         FROM   Utenti u
         LEFT JOIN Scuole s ON u.cod_scuola = s.COD_meccanografico
         WHERE  u.ID_utente = ?'
    );
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}
