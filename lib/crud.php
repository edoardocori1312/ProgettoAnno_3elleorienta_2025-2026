<?php
/**
 * Helper CRUD condivisi.
 *
 * IMPORTANTE: $tabella, $pk e $dateFn sono interpolati direttamente nell'SQL e
 * NON sono parametri preparati. Vanno passati SOLO come literal hardcoded dai
 * call site (mai da input utente), esattamente come avveniva nelle funzioni
 * originali. L'unico valore dinamico ($id / $ordine) resta sempre bound.
 */

// Soft-delete generico: valorizza data_eliminazione se la riga è ancora attiva.
function soft_delete(mysqli $conn, string $tabella, string $pk, int $id, string $dateFn, string $nome): array {
    $stmt = $conn->prepare("UPDATE `$tabella` SET data_eliminazione = $dateFn WHERE `$pk` = ? AND data_eliminazione IS NULL");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
    return $affected > 0
        ? ['tipo' => 'successo', 'msg' => "$nome eliminato."]
        : ['tipo' => 'errore',   'msg' => "$nome non trovato."];
}

// Ripristino generico: azzera data_eliminazione.
function soft_restore(mysqli $conn, string $tabella, string $pk, int $id, string $nome): array {
    $stmt = $conn->prepare("UPDATE `$tabella` SET data_eliminazione = NULL WHERE `$pk` = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
    return $affected > 0
        ? ['tipo' => 'successo', 'msg' => "$nome ripristinato."]
        : ['tipo' => 'errore',   'msg' => "$nome non trovato."];
}

// Verifica se un n_ordine è già in uso (escludendo opzionalmente una riga, per l'update).
function ordine_in_uso(mysqli $conn, string $tabella, string $pk, int $ordine, ?int $escludiId = null): bool {
    if ($escludiId === null) {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM `$tabella` WHERE n_ordine = ?");
        $stmt->bind_param('i', $ordine);
    } else {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM `$tabella` WHERE n_ordine = ? AND `$pk` != ?");
        $stmt->bind_param('ii', $ordine, $escludiId);
    }
    $stmt->execute();
    [$dup] = $stmt->get_result()->fetch_row();
    $stmt->close();
    return $dup > 0;
}
