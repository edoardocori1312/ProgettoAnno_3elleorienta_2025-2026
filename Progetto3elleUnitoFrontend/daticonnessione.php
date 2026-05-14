<?php

    $HOSTDB = "localhost";
    $USERDB = "root";
    $PASSDB = "root";
    $NAMEDB = "treelleorienta";


    $conn = new mysqli($HOSTDB, $USERDB, $PASSDB, $NAMEDB);
    if ($conn->connect_error) {
        die("Connessione fallita: " . $conn->connect_error);
    }
?>
