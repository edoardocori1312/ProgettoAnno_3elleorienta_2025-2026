<?php
$DB_HOST = getenv('DB_HOST') ?: 'localhost';
$DB_USER = getenv('DB_USER') ?: 'root';
$DB_PASS = getenv('DB_PASS') !== false ? getenv('DB_PASS') : 'root';
$DB_NAME = getenv('DB_NAME') ?: 'treelleorienta';

function db(): mysqli {
    global $DB_HOST, $DB_USER, $DB_PASS, $DB_NAME;
    try {
        $conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
    } catch (mysqli_sql_exception $e) {
        // Non esporre dettagli di connessione (host/utente/driver) al client.
        error_log('DB connect error: ' . $e->getMessage());
        http_response_code(503);
        exit('Servizio temporaneamente non disponibile.');
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}

/**
 * Esegue una SELECT senza parametri e restituisce tutte le righe.
 * Degrada a [] se la query fallisce, evitando un 500 grezzo sulle pagine pubbliche.
 */
function query_all(mysqli $conn, string $sql): array {
    try {
        $res = $conn->query($sql);
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    } catch (mysqli_sql_exception $e) {
        error_log('query_all failed: ' . $e->getMessage());
        return [];
    }
}

/**
 * Esegue una SELECT senza parametri e restituisce la prima riga (o null).
 */
function query_one(mysqli $conn, string $sql): ?array {
    try {
        $res = $conn->query($sql);
        $row = $res ? $res->fetch_assoc() : null;
        return $row ?: null;
    } catch (mysqli_sql_exception $e) {
        error_log('query_one failed: ' . $e->getMessage());
        return null;
    }
}
