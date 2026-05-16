<?php
// Percorso fisico della cartella uploads (repo_root/uploads/)
define('UPLOADS_DIR', __DIR__ . '/../uploads/');

// Crea la cartella uploads se non esiste
if (!is_dir(UPLOADS_DIR)) {
    mkdir(UPLOADS_DIR, 0755, true);
}

/**
 * Carica una foto sul server e crea il record nella tabella Foto.
 *
 * @return int  ID della foto inserita
 * @throws Exception
 */
function uploadFoto(mysqli $conn, array $file, string $prefisso = ''): int {
    $allowed = ['image/jpeg', 'image/png'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Errore upload (codice: ' . $file['error'] . ').');
    }
    if ($file['size'] > 2 * 1024 * 1024) {
        throw new Exception('File troppo grande. Max 2 MB.');
    }

    $imgInfo = getimagesize($file['tmp_name']);
    if (!$imgInfo || !in_array($imgInfo['mime'], $allowed)) {
        throw new Exception('Tipo non valido. Accettati solo JPG e PNG.');
    }

    $image = imagecreatefromstring(file_get_contents($file['tmp_name']));
    if (!$image) {
        throw new Exception("Impossibile leggere l'immagine.");
    }

    $slug     = preg_replace('/[^a-z0-9]+/', '_', strtolower(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $prefisso)));
    $slug     = trim($slug, '_') ?: 'foto';
    $nome     = $slug . '_' . bin2hex(random_bytes(2)) . '.jpg';
    $percorso = UPLOADS_DIR . $nome;
    $pathDB   = 'uploads/' . $nome;

    if (!imagejpeg($image, $percorso, 90)) {
        imagedestroy($image);
        throw new Exception('Errore nel salvataggio del file.');
    }
    imagedestroy($image);

    $stmt = $conn->prepare('INSERT INTO Foto (path_foto) VALUES (?)');
    $stmt->bind_param('s', $pathDB);
    if (!$stmt->execute()) {
        unlink($percorso);
        throw new Exception("Errore nell'inserimento in Foto.");
    }
    $id = $stmt->insert_id;
    $stmt->close();
    return $id;
}

function delFoto(mysqli $conn, int $id): void {
    $now  = (new DateTime('now', new DateTimeZone('Europe/Rome')))->format('Y-m-d H:i:s');
    $stmt = $conn->prepare('UPDATE Foto SET data_eliminazione = ? WHERE ID_foto = ?');
    $stmt->bind_param('si', $now, $id);
    $stmt->execute();
    $stmt->close();
}

function assocScuolaFoto(mysqli $conn, int $idFoto, string $codScuola): void {
    $stmt = $conn->prepare('UPDATE Scuole SET id_foto = ? WHERE COD_meccanografico = ?');
    $stmt->bind_param('is', $idFoto, $codScuola);
    $stmt->execute();
    $stmt->close();
}

function assocEventiFoto(mysqli $conn, int $idFoto, int $idEvento): void {
    $stmt = $conn->prepare('UPDATE Eventi SET id_foto = ? WHERE ID_evento = ?');
    $stmt->bind_param('ii', $idFoto, $idEvento);
    $stmt->execute();
    $stmt->close();
}

function assocProgettiFoto(mysqli $conn, int $idFoto, int $idProgetto): void {
    $stmt = $conn->prepare('UPDATE Progetti SET id_foto = ? WHERE ID_progetto = ?');
    $stmt->bind_param('ii', $idFoto, $idProgetto);
    $stmt->execute();
    $stmt->close();
}

function assocLinkFoto(mysqli $conn, int $idFoto, int $idLink): void {
    $stmt = $conn->prepare('UPDATE Links SET id_foto = ? WHERE ID_link = ?');
    $stmt->bind_param('ii', $idFoto, $idLink);
    $stmt->execute();
    $stmt->close();
}
