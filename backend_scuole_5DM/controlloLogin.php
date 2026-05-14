<?php

    // Include il file che controlla se la sessione è valida
    include("controlloSessione.php");

    // Include il file con i dati di connessione al database
    include("dati_connessione.php");
    
    // Recupera i dati inviati dal form tramite POST
    $em = $_POST["email"];
    $pw = $_POST["password"];
    
    // Connessione al database usando i dati inclusi nel file dati_connessione.php
    $conn=mysqli_connect($HOSTDB,$USERDB,$PASSDB,$NOMEDB);

    // Controlla se la connessione al database è fallita
    if (!$conn) 
    {
        // Salva un messaggio di errore nella sessione
        $_SESSION["errore"]="errore connesione al database";  

        // Reindirizza alla pagina login
        header('Location:login.php');
        exit();
     
    }
    else
    {
        // Query SQL per cercare l'utente tramite email
        $sql = "select * from utenti where email = ?";

        // Prepara lo statement (query parametrizzata per sicurezza)
        $stmt = $conn->prepare($sql);

        // Controlla se lo statement è stato creato correttamente
        if ($stmt == false) 
        {
            $_SESSION["errore"]="errore nello statment"; 
            header('Location:index_scuole_backend.php');
            exit();
        }

        // Collega il parametro della query (?) alla variabile $em
        // "s" indica che il parametro è una stringa
        $stmt ->bind_param("s", $em);

        // Esegue la query
        if ($stmt -> execute()) 
        {
            // Ottiene il risultato della query
            $res = $stmt->get_result();

            // Recupera la riga del risultato come oggetto
            $riga = $res->fetch_object();

            // Controlla se la password inserita è uguale a quella nel database
            if ($riga->hash_password == $pw) 
            {
                // Salva i dati dell'utente nella sessione
                $_SESSION["emailUtente"]     = $em;
                $_SESSION["usernameUtente"]  = $riga->username;
                $_SESSION["idUtente"]        = $riga->ID_utente;
                $_SESSION["ruoloUtente"]     = $riga->tipo;
                $_SESSION["codScuolaUtente"] = $riga->cod_scuola; // NULL per ADMIN, valorizzato per SCOLASTICO

                // Reindirizza l'utente all'area riservata
                header('Location:index_scuole_backend.php');
            }
            else
            {
                // Se la password è sbagliata salva un errore
                $_SESSION["errore"]="credenziali errate";

                // Torna alla pagina index
                header('Location:login.php');
                exit();
            }
        }
    }

    // Chiude la connessione al database
    $conn->close();
?>