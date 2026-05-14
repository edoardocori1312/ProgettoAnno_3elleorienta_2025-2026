<?php

/*
 * gestFoto.php — Libreria per la gestione delle foto sul server e nel database.
 *
 * Utilizzo:
 *   require_once 'gestFoto.php';
 *   $id = uploadFoto($conn, $_FILES['foto']);
 *   assocScuolaFoto($conn, $id, $codMeccanografico);
 *
 * Adatta le due variabili $FOTO_TARGET_PATH e $FOTO_PUBLIC_PATH
 * all'ambiente di produzione.
 */

// ── Configurazione percorsi ──────────────────────────────────────────────────
// Rileva automaticamente l'ambiente (locale Windows/UniServer oppure produzione Linux)
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    // Ambiente locale: salva nella cartella pictures/ accanto ai file PHP
    $FOTO_TARGET_PATH = __DIR__ . DIRECTORY_SEPARATOR . "pictures" . DIRECTORY_SEPARATOR;
    $FOTO_PUBLIC_PATH = "pictures/";
} else {
    // Ambiente di produzione Linux
    $FOTO_TARGET_PATH = "/home/uawit4pc/domains/3elleorienta.sviluppo.host/public_html/pictures/";
    $FOTO_PUBLIC_PATH = "3elleorienta.sviluppo.host/pictures/";
}

// Crea la cartella pictures/ se non esiste
if (!is_dir($FOTO_TARGET_PATH)) {
    mkdir($FOTO_TARGET_PATH, 0755, true);
}
// ────────────────────────────────────────────────────────────────────────────


/**
 * Carica una foto sul server e crea il record nella tabella Foto.
 *
 * @param  mysqli  $conn       Connessione al database
 * @param  array   $foto       Elemento di $_FILES (es. $_FILES['foto'])
 * @param  string  $nomeScuola Nome della scuola, usato come prefisso del file
 * @return int                 ID della foto appena inserita
 * @throws Exception           In caso di errore
 */
function uploadFoto($conn, $foto, $nomeScuola = '')
{
    global $FOTO_TARGET_PATH, $FOTO_PUBLIC_PATH;

    $allowed_types = ["image/jpeg", "image/png"];

    // Controllo errore upload
    if ($foto["error"] !== UPLOAD_ERR_OK) {
        throw new Exception("Errore upload file (codice: " . $foto["error"] . ").");
    }

    // Controllo dimensione (max 2 MB)
    if ($foto["size"] > 2 * 1024 * 1024) {
        throw new Exception("File troppo grande. Dimensione massima: 2 MB.");
    }

    // Controllo MIME type tramite getimagesize (compatibile senza estensione fileinfo)
    $img_info = getimagesize($foto["tmp_name"]);
    if ($img_info === false) {
        throw new Exception("Il file non è un'immagine valida.");
    }
    $mime = $img_info['mime'];
    if (!in_array($mime, $allowed_types)) {
        throw new Exception("Tipo file non valido. Sono accettati solo JPG e PNG.");
    }

    // Crea immagine da input
    $raw_data = file_get_contents($foto["tmp_name"]);
    $image    = imagecreatefromstring($raw_data);

    if ($image === false) {
        throw new Exception("Impossibile leggere l'immagine.");
    }

    // Costruisce il nome file: nome scuola (slug) + 4 caratteri casuali + .jpg
    // Esempio: iis_galileo_galilei_a3f9.jpg
    $slug      = strtolower(trim($nomeScuola));
    $slug      = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $slug); // rimuove accenti
    $slug      = preg_replace('/[^a-z0-9]+/', '_', $slug);         // sostituisce spazi/caratteri con _
    $slug      = trim($slug, '_');
    if ($slug === '') $slug = 'scuola';
    $rand4     = bin2hex(random_bytes(2));                          // 4 caratteri esadecimali
    $file_name   = $slug . '_' . $rand4 . ".jpg";
    $target_file = $FOTO_TARGET_PATH . $file_name;
    $path_file   = $FOTO_PUBLIC_PATH . $file_name;

    // Salva come JPEG (qualità 90)
    if (!imagejpeg($image, $target_file, 90)) {
        imagedestroy($image);
        throw new Exception("Errore durante il salvataggio dell'immagine sul server.");
    }
    imagedestroy($image);

    // Inserimento record nel DB
    $sql  = "INSERT INTO Foto (path_foto) VALUES (?)";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        unlink($target_file);
        throw new Exception("Errore durante la preparazione dello statement per Foto.");
    }
    $stmt->bind_param("s", $path_file);
    if (!$stmt->execute()) {
        unlink($target_file);
        $stmt->close();
        throw new Exception("Errore nell'inserimento del record Foto.");
    }
    $id = $stmt->insert_id;
    $stmt->close();

    return $id;
}

/**
 * Marca una foto come eliminata (soft delete) impostando data_eliminazione.
 *
 * @param  mysqli $conn
 * @param  int    $id   ID_foto
 * @throws Exception
 */
function delFoto($conn, $id)
{
    $sql  = "UPDATE Foto SET data_eliminazione=? WHERE ID_foto=?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception("Errore durante la preparazione dello statement per delFoto.");
    }
    $now = (new DateTime('now', new DateTimeZone('Europe/Rome')))->format('Y-m-d H:i:s');
    $stmt->bind_param("si", $now, $id);
    if (!$stmt->execute()) {
        $stmt->close();
        throw new Exception("Errore nell'esecuzione di delFoto.");
    }
    $stmt->close();
}

/**
 * Associa una foto a una scuola.
 */
function assocScuolaFoto($conn, $idFoto, $codScuola)
{
    $sql  = "UPDATE Scuole SET id_foto=? WHERE COD_meccanografico=?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception("Errore durante la preparazione dello statement per assocScuolaFoto.");
    }
    $stmt->bind_param("is", $idFoto, $codScuola);
    if (!$stmt->execute()) {
        $stmt->close();
        throw new Exception("Errore nell'esecuzione di assocScuolaFoto.");
    }
    $stmt->close();
}

/**
 * Associa una foto a un evento.
 */
function assocEventiFoto($conn, $idFoto, $idEvento)
{
    $sql  = "UPDATE Eventi SET id_foto=? WHERE ID_evento=?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception("Errore durante la preparazione dello statement per assocEventiFoto.");
    }
    $stmt->bind_param("ii", $idFoto, $idEvento);
    if (!$stmt->execute()) {
        $stmt->close();
        throw new Exception("Errore nell'esecuzione di assocEventiFoto.");
    }
    $stmt->close();
}

/**
 * Associa una foto a un progetto.
 */
function assocProgettiFoto($conn, $idFoto, $idProgetto)
{
    // FIX: era erroneamente "UPDATE Eventi" — corretto in "UPDATE Progetti"
    $sql  = "UPDATE Progetti SET id_foto=? WHERE ID_progetto=?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception("Errore durante la preparazione dello statement per assocProgettiFoto.");
    }
    $stmt->bind_param("ii", $idFoto, $idProgetto);
    if (!$stmt->execute()) {
        $stmt->close();
        throw new Exception("Errore nell'esecuzione di assocProgettiFoto.");
    }
    $stmt->close();
}

/**
 * Associa una foto a un link.
 */
function assocLinkFoto($conn, $idFoto, $idLink)
{
    // FIX: era erroneamente "UPDATE Eventi WHERE ID_link" — corretto in "UPDATE Links"
    $sql  = "UPDATE Links SET id_foto=? WHERE ID_link=?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception("Errore durante la preparazione dello statement per assocLinkFoto.");
    }
    $stmt->bind_param("ii", $idFoto, $idLink);
    if (!$stmt->execute()) {
        $stmt->close();
        throw new Exception("Errore nell'esecuzione di assocLinkFoto.");
    }
    $stmt->close();
}
?>
