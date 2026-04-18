<?php
$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "treelleorienta";

$conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connessione fallita: " . $conn->connect_error);
    }
?>