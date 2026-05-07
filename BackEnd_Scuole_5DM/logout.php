<?php
    session_start();

    //cancellazione totale della sessione
    session_destroy();
    header('Location:login.php');
    exit();

?>