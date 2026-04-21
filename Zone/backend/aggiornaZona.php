<?php
header('Content-Type: text/plain'); // Imposta l'intestazione del tipo di contenuto a testo semplice
require("../backend/config/datiConnessione.php"); // Include del file di configurazione per la connessione al database

if ($_SERVER["REQUEST_METHOD"] !== "POST") { // Se la richiesta non è di tipo POST, restituisce un errore
    http_response_code(405);
    echo "Metodo non consentito";
    exit();
}

// Validazione dei dati in ingresso: verifica che 'id' e 'nome' siano presenti e che 'nome' non sia vuoto
if (!isset($_POST["id"], $_POST["nome"]) || trim($_POST["nome"]) === '') { 
    http_response_code(400);
    echo "Dati mancanti o non validi";
    exit();
}

$id   = intval($_POST["id"]); // Converte 'id' in un intero
$nome = trim($_POST["nome"]); // Rimuove spazi bianchi all'inizio e alla fine di 'nome'

if (strlen($nome) < 3) {  // Se il nome ha meno di 3 caratteri, restituisce un errore
    http_response_code(400);
    echo "Il nome deve avere almeno 3 caratteri";
    exit();
}


if ($conn->connect_error) { 
    http_response_code(500);
    echo "Connessione fallita: " . $conn->connect_error;
    exit();
}

// Query per aggiornare il nome della zona con l'ID specificato
$stmt = $conn->prepare("UPDATE Zone SET nome = ? WHERE ID_zona = ?");
$stmt->bind_param("si", $nome, $id);
$stmt->execute();

// Verifica se l'aggiornamento ha avuto successo e restituisce un messaggio appropriato
if ($conn->affected_rows > 0) {
    echo "Zona aggiornata con successo";
} else {
    echo "Nessuna modifica apportata";
}

$stmt->close();
$conn->close();
?>
