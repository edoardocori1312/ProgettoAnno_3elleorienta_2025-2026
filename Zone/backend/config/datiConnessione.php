<?php

    $HOSTDB = "localhost"; 
    $USERDB = "root";
    $PASSDB = "root";
    $NAMEDB = "treelleorienta";


    $conn = new mysqli($HOSTDB, $USERDB, $PASSDB, $NAMEDB);

    // se la connessione fallisce restituisce un messaggio di errore e termina l'esecuzione dello script
    if ($conn->connect_error) {
        die("Connessione fallita: " . $conn->connect_error);
    }
?>