<?php
$DB_HOST = getenv('DB_HOST') ?: 'localhost';
$DB_USER = getenv('DB_USER') ?: 'root';
$DB_PASS = getenv('DB_PASS') !== false ? getenv('DB_PASS') : 'root';
$DB_NAME = getenv('DB_NAME') ?: 'treelleorienta';

function db(): mysqli {
    global $DB_HOST, $DB_USER, $DB_PASS, $DB_NAME;
    $conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
    if ($conn->connect_error) {
        die('Errore di connessione al database: ' . $conn->connect_error);
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}
