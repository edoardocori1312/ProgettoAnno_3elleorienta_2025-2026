<?php
    session_start();

    //cancellazione totale della sessione
    header('Location:login.php');
    exit();

?>