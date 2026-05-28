<?php

//Carica la foto su server e db, ritornando il suoi id. Bisogna prima passare la foto in POST e poi passargliela.
//https://www.ecommunication.it/come-fare/caricamento-di-immagini-sul-server-con-php-e-mysql-upload



/**
* Note: This file may contain artifacts of previous malicious infection.
* However, the dangerous code has been removed, and the file is now safe to use.
*/


function delFoto($conn, $id)
{
    $sql = "UPDATE Foto SET data_eliminazione=? WHERE ID_foto=?";

    $stmt = $conn->prepare($sql);
    if ($stmt == null) {
        throw new Exception("Errore durante la creazione del record.");
    }
    $stmt->bind_param("si", date("Y-m-d H:i:s"), $id);
    if (!$stmt->execute()) {
        throw new Exception("errore nell'esecuzione dello statement.");
    }
    $stmt->close();
}

function assocScuolaFoto($conn, $idFoto, $codScuola)
{
    $sql = "UPDATE Scuole SET id_foto=? WHERE COD_meccanografico=?";

    $stmt = $conn->prepare($sql);
    if ($stmt == null) {
        throw new Exception("Errore durante la creazione del record.");
    }
    $stmt->bind_param("is", $idFoto, $codScuola);
    if (!$stmt->execute()) {
        throw new Exception("Errore nell'esecuzione dello statement.");
    }
    $stmt->close();
}

function assocEventiFoto($conn, $idFoto, $idEvento)
{
    $sql = "UPDATE Eventi SET id_foto=? WHERE ID_evento=?";

    $stmt = $conn->prepare($sql);
    if ($stmt == null) {
        throw new Exception("Errore durante la creazione del record.");
    }
    $stmt->bind_param("ii", $idFoto, $idEvento);
    if (!$stmt->execute()) {
        throw new Exception("Errore nell'esecuzione dello statement.");
    }
    $stmt->close();
}

function assocProgettiFoto($conn, $idFoto, $idProgetto)
{
    $sql = "UPDATE Progetti SET id_foto=? WHERE ID_evento=?";

    $stmt = $conn->prepare($sql);
    if ($stmt == null) {
        throw new Exception("Errore durante la creazione del record.");
    }
    $stmt->bind_param("ii", $idFoto, $idProgetto);
    if (!$stmt->execute()) {
        throw new Exception("Errore nell'esecuzione dello statement.");
    }
    $stmt->close();
}

function assocLinkFoto($conn, $idFoto, $idLink)
{
    $sql = "UPDATE Links SET id_foto=? WHERE ID_link=?";

    $stmt = $conn->prepare($sql);
    if ($stmt == null) {
        throw new Exception("Errore durante la creazione del record.");
    }
    $stmt->bind_param("ii", $idFoto, $idLink);
    if (!$stmt->execute()) {
        throw new Exception("Errore nell'esecuzione dello statement.");
    }
    $stmt->close();
}
?>