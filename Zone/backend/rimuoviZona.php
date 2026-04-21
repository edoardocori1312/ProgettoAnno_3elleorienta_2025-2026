<?php
header('Content-Type: text/plain');
require("../backend/config/datiConnessione.php");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo "Metodo non consentito";
    exit();
}

if (!isset($_POST["id_zona"])) { // Se 'id_zona' non è presente nei dati POST, restituisce un errore
    http_response_code(400);
    echo "ID zona mancante";
    exit();
}

$id_zona = intval($_POST["id_zona"]); // Prende l'id della zona da eliminare e lo converte in un intero 

$conn = new mysqli($HOSTDB, $USERDB, $PASSDB, $NAMEDB);

if ($conn->connect_error) {
    http_response_code(500);
    echo "Connessione fallita: " . $conn->connect_error;
    exit();
}

// Controlla se la zona è usata in una città
$stmt = $conn->prepare("SELECT COUNT(*) FROM citta WHERE ID_zona = ?");
$stmt->bind_param("i", $id_zona);
$stmt->execute();
$stmt->bind_result($count);
$stmt->fetch();
$stmt->close();

if ($count > 0) {
    http_response_code(409);
    echo "La zona è utilizzata in una città e non può essere eliminata";
    $conn->close();
    exit();
}

// Elimina la zona
$stmt = $conn->prepare("DELETE FROM Zone WHERE ID_zona = ?");
$stmt->bind_param("i", $id_zona);
$stmt->execute();

if ($conn->affected_rows > 0) {
    echo "Zona eliminata con successo";
} else {
    http_response_code(404);
    echo "Zona non trovata";
}

$stmt->close();
$conn->close();
?>
