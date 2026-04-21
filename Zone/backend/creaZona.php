<?php
header('Content-Type: text/plain');
require("../backend/config/datiConnessione.php");

if ($_SERVER["REQUEST_METHOD"] !== "POST") { // Se la richiesta non è di tipo POST, restituisce un errore
    http_response_code(405);
    echo "Metodo non consentito";
    exit();
}

if (!isset($_POST["nome"]) || trim($_POST["nome"]) === '') { // Se 'nome' non è presente o è vuoto, restituisce un errore
    http_response_code(400);
    echo "Il nome della zona non può essere vuoto";
    exit();
}

$nome = trim($_POST["nome"]); // Rimuove spazi bianchi all'inizio e alla fine di 'nome'

if (strlen($nome) < 3) {
    http_response_code(400);
    echo "Il nome deve avere almeno 3 caratteri";
    exit();
}


if ($conn->connect_error) {
    http_response_code(500);
    echo "Connessione fallita: " . $conn->connect_error;
    exit();
}

// Query per inserire una nuova zona nel database
$stmt = $conn->prepare("INSERT INTO zone (nome) VALUES (?)");

if (!$stmt) { 
    http_response_code(500);
    echo "Errore nella preparazione della query";
    $conn->close();
    exit();
}

$stmt->bind_param("s", $nome);

if ($stmt->execute()) {
    echo "Zona creata con successo";
} else {
    http_response_code(500);
    echo "Errore nell'inserimento della zona";
}

$stmt->close();
$conn->close();


?>
