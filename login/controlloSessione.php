<?php
    // Controlla se la variabile di sessione "emailUtente" NON esiste
    if(!isset($_SESSION["emailUtente"]))
    {
        // Se non esiste significa che l'utente non ha effettuato il login
        // quindi viene reindirizzato alla pagina di login
        header("location:login.php");
    }
?>