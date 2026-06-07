<?php

//Carica la foto su server e db, ritornando il suoi id. Bisogna prima passare la foto in POST e poi passargliela.
//https://www.ecommunication.it/come-fare/caricamento-di-immagini-sul-server-con-php-e-mysql-upload


function uploadFoto($conn, $foto)
{
    $id = -1;
    $sql = "INSERT INTO Foto (path_foto) VALUES (?);";
	$target_path = "/home/uawit4pc/domains/3elleorienta.sviluppo.host/public_html/pictures/upload/";
	$path = "https://3elleorienta.sviluppo.host/pictures/upload/";
    $allowed_types = [
        "image/jpeg",
        "image/png"
    ];


    //Controllo se il file è stato caricato
    if ($foto["error"] !== UPLOAD_ERR_OK) {
        throw new Exception("Errore upload file.");
    }

    //Controllo dimensione 2MB
    if ($foto["size"] > 2 * 1024 * 1024) {
        throw new Exception("File troppo grande.");
    }

    //Controllo MIME
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $foto["tmp_name"]);

    if (!in_array($mime, $allowed_types)) {
        throw new Exception("Tipo file non valido.");
    }

    //Verifica che sia davvero un'immagine
    if (getimagesize($foto["tmp_name"]) === false) {
        throw new Exception("Il file deve essere una foto.");
    }

    //Crea immagine da input
    $raw_data = file_get_contents($foto["tmp_name"]);
    $image = imagecreatefromstring($raw_data);

    if ($image === false) {
        throw new Exception("Impossibile leggere l'immagine.");
    }

    //Nome file sicuro random
    $file_name = bin2hex(random_bytes(8)) . ".jpg";
    $target_file = $target_path . $file_name;
	$path_file = $path . $file_name;

    //Conversione in jpg
    if (imagejpeg($image, $target_file, 90)) {
        echo "Upload completato.";
    } else {
        throw new Exception("Errore durante il salvataggio.");
    }


    //Inserimento record su DB
    $stmt = $conn->prepare($sql);
    if ($stmt == null) {
        unlink($target_file);
        throw new Exception("Errore durante la creazione del record.");
    }
    $stmt->bind_param("s", $path_file);
    if (!$stmt->execute()) {
        unlink($target_file);
        throw new Exception("Errore nell'esecuzione dello statement.");
    }
    $id = $stmt->insert_id;
    $stmt->close();

    return $id;
}

function delFoto($conn, $id)
{
    // 1. Recupera il path prima di eliminare
    $sql = "SELECT path_foto FROM Foto WHERE ID_foto = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === null) {
        throw new Exception("Errore durante la preparazione dello statement.");
    }
    $stmt->bind_param("i", $id);
    if (!$stmt->execute()) {
        $stmt->close();
        throw new Exception("Errore nell'esecuzione dello statement.");
    }

    $result = $stmt->get_result();
    $riga = $result->fetch_assoc();
    $stmt->close();

    if ($riga === null) {
        throw new Exception("Foto non trovata.");
    }

    // 2. Elimina il file fisico
    $pathFoto = $riga['path_foto'];
    if (file_exists($pathFoto)) {
        if (!unlink($pathFoto)) {
            throw new Exception("Errore durante l'eliminazione del file.");
        }
    }

    // 3. Elimina il record dal DB
    $sql = "DELETE FROM Foto WHERE ID_foto = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === null) {
        throw new Exception("Errore durante la preparazione dello statement.");
    }
    $stmt->bind_param("i", $id);
    if (!$stmt->execute()) {
        $stmt->close();
        throw new Exception("Errore nell'eliminazione del record.");
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
    $sql = "UPDATE Link SET id_foto=? WHERE ID_link=?";

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