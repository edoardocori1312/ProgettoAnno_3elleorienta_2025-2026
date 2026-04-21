<?php
header('Content-Type: application/json');
require("../backend/config/datiConnessione.php");


if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["errore" => "Connessione fallita: " . $conn->connect_error]);
    exit();
}

// Query per selezionare tutte le zone dal database, ordinandole per ID in modo crescente (default)
$result = $conn->query("SELECT ID_zona AS id, nome FROM Zone ORDER BY ID_zona ASC");

if (!$result) {
    http_response_code(500);
    echo json_encode(["errore" => "Errore nella query: " . $conn->error]);
    $conn->close();
    exit();
}

$zone = []; 
while ($row = $result->fetch_assoc()) {
    $zone[] = $row;
}

echo json_encode($zone); // Restituisce l'elenco delle zone in formato JSON
$conn->close();
?>
