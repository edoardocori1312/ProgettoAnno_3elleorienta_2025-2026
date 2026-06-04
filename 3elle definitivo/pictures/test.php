<?php
require_once "gestFoto.php";
include("../connessione/db.php");

// Connessione DB di esempio
	$conn = new mysqli($HOSTDB, $USERDB, $PASSDB, $NOMEDB);

if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}

$msg = "";

try {

    // TEST UPLOAD FOTO
    if (isset($_POST['upload'])) {

        if (!isset($_FILES['foto'])) {
            throw new Exception("Nessuna foto inviata.");
        }

        $idFoto = uploadFoto($conn, $_FILES['foto']);

        $msg = "Foto caricata con successo. ID Foto: " . $idFoto;
    }

    // TEST ASSOCIA SCUOLA
    if (isset($_POST['assoc_scuola'])) {

        assocScuolaFoto(
            $conn,
            $_POST['id_foto'],
            $_POST['cod_scuola']
        );

        $msg = "Foto associata alla scuola.";
    }

    // TEST ASSOCIA EVENTO
    if (isset($_POST['assoc_evento'])) {

        assocEventiFoto(
            $conn,
            $_POST['id_foto'],
            $_POST['id_evento']
        );

        $msg = "Foto associata all'evento.";
    }

    // TEST ELIMINA FOTO
    if (isset($_POST['delete_foto'])) {

        delFoto(
            $conn,
            $_POST['id_foto']
        );

        $msg = "Foto eliminata logicamente.";
    }

} catch (Exception $e) {
    $msg = "ERRORE: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Test Gestione Foto</title>

    <style>

        body{
            font-family: Arial;
            max-width: 700px;
            margin: 40px auto;
        }

        form{
            border:1px solid #ccc;
            padding:20px;
            margin-bottom:30px;
        }

        h2{
            margin-top:0;
        }

        input{
            margin-bottom:10px;
            width:100%;
            padding:8px;
        }

        button{
            padding:10px 20px;
            cursor:pointer;
        }

        .msg{
            background:#f4f4f4;
            padding:15px;
            margin-bottom:20px;
        }

    </style>
</head>
<body>

<h1>Pagina Test gestFoto.php</h1>

<?php if($msg != ""): ?>
    <div class="msg">
        <?= htmlspecialchars($msg) ?>
    </div>
<?php endif; ?>

<!-- TEST UPLOAD -->

<form method="POST" enctype="multipart/form-data">

    <h2>Upload Foto</h2>

    <input type="file" name="foto" required>

    <button type="submit" name="upload">
        Carica Foto
    </button>

</form>

<!-- TEST ASSOCIA SCUOLA -->

<form method="POST">

    <h2>Associa Foto a Scuola</h2>

    <input type="number" name="id_foto" placeholder="ID Foto" required>

    <input type="text" name="cod_scuola" placeholder="Codice Scuola" required>

    <button type="submit" name="assoc_scuola">
        Associa Scuola
    </button>

</form>

<!-- TEST ASSOCIA EVENTO -->

<form method="POST">

    <h2>Associa Foto a Evento</h2>

    <input type="number" name="id_foto" placeholder="ID Foto" required>

    <input type="number" name="id_evento" placeholder="ID Evento" required>

    <button type="submit" name="assoc_evento">
        Associa Evento
    </button>

</form>

<!-- TEST ELIMINA FOTO -->

<form method="POST">

    <h2>Elimina Foto</h2>

    <input type="number" name="id_foto" placeholder="ID Foto" required>

    <button type="submit" name="delete_foto">
        Elimina Foto
    </button>

</form>

</body>
</html>