<?php
// CORREZIONE PERCORSI INCLUSIONE FILE
include_once("../controlloSessione.php");
    include_once("../connessione/db.php");
    include_once("../pictures/gestFoto.php");

    // prende i dati della sessione
    $emailUtente = $_SESSION["emailUtente"];
    $username = $_SESSION["usernameUtente"];
    $idUtente = $_SESSION["idUtente"];
    $ruoloUtente = $_SESSION["ruoloUtente"];

    $conn = new mysqli($HOSTDB, $USERDB, $PASSDB, $NOMEDB);

    if($conn->connect_error)
    {
        die("Connessione non stabilita");
    }

    // ==========================================
    // FUNZIONE PER VALIDARE L'URL
    // ==========================================
    function validaUrl($url) {
        // Rimuovi spazi bianchi
        $url = trim($url);
       
        // Se l'URL è vuoto
        if (empty($url)) {
            return false;
        }
       
        // Aggiungi http:// se non c'è nessun protocollo
        if (!preg_match('/^https?:\/\//', $url)) {
            $url = 'http://' . $url;
        }
       
        // Valida l'URL
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return true;
        }
       
        return false;
    }

    // ==========================================
    // CLASSE LINKMANAGER INTEGRATA NEL BACKEND
    // ==========================================
    class LinkManager {
        private $conn;

        public function __construct(mysqli $conn) {
            $this->conn = $conn;
        }

        public function getLinks(string $filtroTitolo = "") {
            if ($filtroTitolo !== "") {

                $query = "SELECT l.*, f.path_foto
                FROM Links l
                LEFT JOIN Foto f ON l.id_foto = f.ID_foto
                WHERE l.titolo LIKE ?
                ORDER BY l.n_ordine ASC";
                $stmt = $this->conn->prepare($query);
                $paramRicerca = "%" . $filtroTitolo . "%";
                $stmt->bind_param("s", $paramRicerca);
            } else {
                $query = "SELECT l.*, f.path_foto
                    FROM Links l
                    LEFT JOIN Foto f ON l.id_foto = f.ID_foto
                     ORDER BY l.n_ordine ASC";
                $stmt = $this->conn->prepare($query);
            }
            $stmt->execute();
            $risultato = $stmt->get_result();
            $stmt->close();
            return $risultato;
        }

        // Verifica se esiste già un link con questo numero d'ordine
        public function verificaOrdineEsistente(int $n_ordine, ?int $idEscluso = null): bool {
            $query = "SELECT COUNT(*) as count FROM Links WHERE n_ordine = ? AND data_eliminazione IS NULL";
           
            if ($idEscluso !== null) {
                $query .= " AND ID_link != ?";
            }
           
            $stmt = $this->conn->prepare($query);
           
            if ($idEscluso !== null) {
                $stmt->bind_param("ii", $n_ordine, $idEscluso);
            } else {
                $stmt->bind_param("i", $n_ordine);
            }
           
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
           
            return $row['count'] > 0;
        }

        public function aggiungiLink(string $titolo, string $url_link, string $descrizione, int $n_ordine, $id_foto): bool {
            $query = "INSERT INTO Links (titolo, url_link, descrizione, n_ordine, id_foto) VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($query);
            if (!$stmt) return false;
             $stmt->bind_param("sssii", $titolo, $url_link, $descrizione, $n_ordine, $id_foto);
            $ok = $stmt->execute();
            $stmt->close();
             return (bool)$ok;
        }

        public function updateLink(int $id, string $titolo, string $url_link, string $descrizione, int $n_ordine, $id_foto): bool {
            $query = "UPDATE Links SET titolo = ?, url_link = ?, descrizione = ?, n_ordine = ?, id_foto = ? WHERE ID_link = ?";
            $stmt = $this->conn->prepare($query);
            if (!$stmt) return false;
            $stmt->bind_param("sssiii", $titolo, $url_link, $descrizione, $n_ordine, $id_foto, $id);
             $ok = $stmt->execute();
             $stmt->close();
            return (bool)$ok;
        }

        public function softDelete(int $id): bool {
            $query = "UPDATE Links SET data_eliminazione = NOW() WHERE ID_link = ?";
            $stmt = $this->conn->prepare($query);
            if (!$stmt) return false;
            $stmt->bind_param("i", $id);
            $ok = $stmt->execute();
            $stmt->close();
            return (bool)$ok;
        }

        public function ripristinaLink(int $id): bool {
            $query = "UPDATE Links SET data_eliminazione = NULL WHERE ID_link = ?";
            $stmt = $this->conn->prepare($query);
            if (!$stmt) return false;
            $stmt->bind_param("i", $id);
            $ok = $stmt->execute();
            $stmt->close();
            return (bool)$ok;
        }
    }

    $linkManager = new LinkManager($conn);

    // ==========================================
    // CONTROLLER DELLE AZIONI POST
    // ==========================================
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['azione'])) {
        if ($_POST['azione'] === 'elimina') {
            $idLink = intval($_POST['idLink']);
            $linkManager->softDelete($idLink);
            echo "<script>window.location.href = 'index.php?page=links';</script>";
            exit();
        }
        elseif ($_POST['azione'] === 'salva_modifica') {
            $idDaModificare = intval($_POST['idDaModificare']);
            $titolo = trim($_POST['titolo']);
            $url_link = trim($_POST['url_link']);
            $descrizione = trim($_POST['descrizione']);
            $nOrdine = intval($_POST['n_ordine']);
           
            // Validazione URL
            if (!validaUrl($url_link)) {
                echo "<script>
                        alert('ERRORE: L\\'URL inserito non è valido! Assicurati di inserire un indirizzo web corretto (es. https://www.esempio.it)');
                        window.location.href = 'index.php?page=links';
                      </script>";
                exit();
            }
           
            // Verifica se esiste già un altro link con lo stesso numero d'ordine
            if ($linkManager->verificaOrdineEsistente($nOrdine, $idDaModificare)) {
                echo "<script>
                        alert('ERRORE: Esiste già un link con il numero ordine " . $nOrdine . "! Scegli un altro numero.');
                        window.location.href = 'index.php?page=links';
                      </script>";
                exit();
            }
           
            // Gestione foto - OBBLIGATORIA
            $id_foto = null;
           
            // Se è stata caricata una nuova foto
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                try {
                    $id_foto = uploadFoto($conn, $_FILES['foto']);
                } catch (Exception $e) {
                    die("Errore upload foto: " . $e->getMessage());
                }
            }
            // Altrimenti mantieni la foto esistente
            elseif (!empty($_POST['id_foto_corrente'])) {
                $id_foto = $_POST['id_foto_corrente'];
            }
           
            // Verifica se la foto è presente (campo obbligatorio)
            if (empty($id_foto)) {
                echo "<script>
                        alert('ERRORE: La foto è obbligatoria! Devi selezionare un\\'immagine per il link.');
                        window.location.href = 'index.php?page=links';
                      </script>";
                exit();
            }

            $linkManager->updateLink($idDaModificare, $titolo, $url_link, $descrizione, $nOrdine, $id_foto);
            echo "<script>window.location.href = 'index.php?page=links';</script>";
            exit();
        }
        elseif($_POST['azione'] === "aggiungi")
        {
            $titolo      = trim($_POST["titolo"]);
            $url_link    = trim($_POST["url_link"]);
            $descrizione = trim($_POST["descrizione"]);
            $n_ordine    = (int)$_POST["n_ordine"];
           
            // Validazione URL
            if (!validaUrl($url_link)) {
                echo "<script>
                        alert('ERRORE: L\\'URL inserito non è valido! Assicurati di inserire un indirizzo web corretto (es. https://www.esempio.it)');
                        window.location.href = 'index.php?page=links';
                      </script>";
                exit();
            }

            // Verifica se esiste già un link con questo numero d'ordine
            if ($linkManager->verificaOrdineEsistente($n_ordine)) {
                echo "<script>
                        alert('ERRORE: Esiste già un link con il numero ordine " . $n_ordine . "! Scegli un altro numero.');
                        window.location.href = 'index.php?page=links';
                      </script>";
                exit();
            }

            // Gestione upload foto - OBBLIGATORIA
            $id_foto = null;
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                try {
                    $id_foto = uploadFoto($conn, $_FILES['foto']);
                } catch (Exception $e) {
                    die("Errore upload foto: " . $e->getMessage());
                }
            }
           
            // Verifica se la foto è stata caricata (campo obbligatorio)
            if (empty($id_foto)) {
                echo "<script>
                        alert('ERRORE: La foto è obbligatoria! Devi selezionare un\\'immagine per il link.');
                        window.location.href = 'index.php?page=links';
                      </script>";
                exit();
            }

            $linkManager->aggiungiLink($titolo, $url_link, $descrizione, $n_ordine, $id_foto);
            echo "<script>window.location.href = 'index.php?page=links';</script>";
            exit();
        }
        elseif ($_POST['azione'] === 'ripristina') {
            $idLink = intval($_POST['idLink']);
            $linkManager->ripristinaLink($idLink);
echo "<script>window.location.href = 'index.php?page=links';</script>";
            exit();
        }
    }

    // ==========================================
    // STRUTTURAZIONE DATI TABELLA
    // ==========================================
    $filtroTitolo = "";
    if(isset($_GET['filtro_titolo']) && !empty(trim($_GET['filtro_titolo'])))
    {
        $filtroTitolo = trim($_GET['filtro_titolo']);
    }

    $risultato = $linkManager->getLinks($filtroTitolo);
    $tabella = "";

    if($risultato->num_rows > 0)
    {
        while($riga = $risultato->fetch_object())
        {
            $idLink = ($riga->ID_link !== null) ? $riga->ID_link : "";
            $titolo = ($riga->titolo !== null) ? $riga->titolo : "";
            $url_link = ($riga->url_link !== null) ? $riga->url_link : "";
            $nOrdine = ($riga->n_ordine !== null) ? $riga->n_ordine : "";
            $dataEliminazione = ($riga->data_eliminazione !== null) ? $riga->data_eliminazione : "";
            $descrizione = ($riga->descrizione !== null) ? $riga->descrizione : "";

            $classeRiga = !empty($dataEliminazione) ? "class='text-muted table-light' style='opacity: 0.6;'" : "";

            $tabella .= "<tr {$classeRiga}>";
            $tabella .= "<td class='px-3'>". htmlspecialchars($idLink) ."</td>";
            $tabella .= "<td>". htmlspecialchars($titolo) ."</td>";
            $tabella .= "<td class='text-center'>". htmlspecialchars($nOrdine) ."</td>";
           
            if (!empty($dataEliminazione)) {
                $tabella .= "<td>". htmlspecialchars($dataEliminazione) ."</td>";
            } else {
                $tabella .= "<td><span class='badge bg-success text-white fw-semibold'>Attivo</span></td>";
            }
            // Dopo la colonna Data eliminazione
            $tabella .= "<td>";
            if (!empty($riga->id_foto)) {
                $tabella .= htmlspecialchars($riga->id_foto);
                if (!empty($riga->path_foto)) {
                    $tabella .= " <i class='bi bi-image'></i>";
                }
            } else {
                $tabella .= "<span class='text-muted'>—</span>";
            }
            $tabella .= "</td>";
           
            $tabella .= "<td><a href='". htmlspecialchars($url_link, ENT_QUOTES) ."' target='_blank' class='text-truncate d-inline-block' style='max-width: 200px;'><i class='bi bi-box-arrow-up-right me-1'></i>Apri Collegamento</a></td>";
           
            $tabella .= "<td class='text-end'>";
           
            $titoloEsc = htmlspecialchars($titolo, ENT_QUOTES);
            $urlEsc = htmlspecialchars($url_link, ENT_QUOTES);
            $descrizioneEsc = htmlspecialchars($descrizione, ENT_QUOTES);
            $idFoto = ($riga->id_foto !== null) ? $riga->id_foto : "";

            $tabella .= "<button type='button' class='btn btn-sm btn-outline-primary me-1'
                            data-bs-toggle='modal'
                            data-bs-target='#modalModificaLink'
                            data-id='{$idLink}'
                            data-titolo='{$titoloEsc}'
                            data-url='{$urlEsc}'
                            data-descrizione='{$descrizioneEsc}'
                            data-ordine='{$nOrdine}'
                            data-idfoto='{$idFoto}' >
                            <i class='bi bi-pencil'></i> Modifica
                        </button>";  
           
            if (empty($dataEliminazione)) {
                $tabella .= "<form method='POST' action='' style='display:inline;' onsubmit=\"return confirm('Sei sicuro di voler nascondere questo link?');\">
<input type='hidden' name='page' value='links'>
                                <input type='hidden' name='idLink' value='{$idLink}'>
                                <input type='hidden' name='azione' value='elimina'>
                                <button type='submit' class='btn btn-sm btn-outline-danger'>
                                    <i class='bi bi-trash'></i> Elimina
                                </button>
                            </form>";
            }
            else {
                $tabella .= "<form method='POST' action='' style='display:inline;'>
<input type='hidden' name='page' value='links'>
                                <input type='hidden' name='idLink' value='{$idLink}'>
                                <input type='hidden' name='azione' value='ripristina'>
                                <button type='submit' class='btn btn-sm btn-outline-warning'>
                                    <i class='bi bi-arrow-counterclockwise'></i> Ripristina
                                </button>
                            </form>";
            }
           
            $tabella .= "</td>";
            $tabella .= "</tr>";
        }
    }
    else
    {
        $tabella = "<tr><td colspan='7' class='text-center py-4 text-muted'>Nessun link trovato.</td></tr>";
    }

    $conn->close();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - 3elleorienta</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../styles/style.css">
    <link rel="stylesheet" href="../styles/Progettistyle.css">
</head>
<body>

<div class="container-fluid px-4">
    <div class="card shadow-sm mb-3 border-0">
        <div class="card-body py-3">
            <form method="GET" action="">
<input type='hidden' name='page' value='links'>
                <input type="hidden" name="azione" value="filtra">
                <div class="row g-2 align-items-center">
                    <div class="col-md-10 col-12">
                        <div class="input-group">
                            <span class="input-group-text bg-white">
                                <i class="bi bi-search"></i>
                            </span>
                            <input value="<?php echo htmlspecialchars($filtroTitolo); ?>" type="text" name="filtro_titolo" class="form-control" placeholder="Cerca Il Link..." autocomplete="off">
                        </div>
                    </div>
                    <div class="col-md-2 col-12 d-grid">
                        <button type="submit" class="btn btn-success fw-semibold">
                            <i class="bi bi-search me-1"></i> Cerca
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <button class="btn btn-primary shadow-sm d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#modalNuovoLink">
            <i class="bi bi-plus-circle"></i> Aggiungi link
        </button>
    </div>
   
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="px-3">ID Link</th>
                    <th>Titolo</th>
                    <th class="text-center">N.ordine</th>
                    <th>Data eliminazione</th>
                    <th>Foto</th>                    
                    <th>URL Destinazione</th>
                    <th class="text-end">Azioni</th>
                </tr>
            </thead>
            <tbody>
                <?php echo $tabella; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Nuovo Link -->
<div class="modal fade" id="modalNuovoLink" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold">Nuovo Link</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
            </div>
            <div class="modal-body">
                <form id="linkForm" action="" method="POST" enctype="multipart/form-data" onsubmit="return validaForm()">
<input type='hidden' name='page' value='links'>
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Titolo Link *</label>
                            <input type="text" name="titolo" class="form-control" required placeholder="Es: Portale MIUR">
                        </div>
                       
                        <div class="col-12">
                            <label class="form-label fw-semibold">Descrizione *</label>
                            <textarea name="descrizione" class="form-control" rows="4" required placeholder="Descrivi il collegamento utile..."></textarea>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">N. Ordine *</label>
                            <input type="number" name="n_ordine" class="form-control" required placeholder="Es: 1">
                        </div>
                       
                        <div class="col-12">
                            <label class="form-label fw-semibold">Indirizzo Internet (URL) *</label>
                            <input type="url" class="form-control" id="url_input" name="url_link" required placeholder="https://esempio.it">
                            <div class="form-text">Inserisci l'URL completo (es. https://www.esempio.it)</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Foto * (obbligatoria)</label>
                            <input type="file" class="form-control" id="foto_input" name="foto" accept="image/*" required>
                            <div class="form-text">Carica un'immagine per il link (obbligatorio)</div>
                        </div>
                    </div>
                    <div class="modal-footer mt-3 pb-0 pe-0">
                        <input type='hidden' name='azione' value='aggiungi'>
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annulla</button>
                        <button type="submit" class="btn btn-primary px-4">Salva</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Modifica Link -->
<div class="modal fade" id="modalModificaLink" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold">Modifica Link</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
            </div>
            <div class="modal-body">
                <form id="formModifica" action="" method="POST" enctype="multipart/form-data" onsubmit="return validaFormModifica()">
<input type='hidden' name='page' value='links'>
                    <input type="hidden" name="azione" value="salva_modifica">
                    <input type="hidden" name="idDaModificare" id="mod_idDaModificare">
                    <input type="hidden" name="id_foto_corrente" id="mod_id_foto_corrente">

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold text-secondary">Titolo Link *</label>
                            <input type="text" id="mod_titolo" name="titolo" class="form-control" required>
                        </div>
                       
                        <div class="col-md-4">
                            <label class="form-label fw-semibold text-secondary">N. Ordine *</label>
                            <input type="number" id="mod_ordine" name="n_ordine" class="form-control" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold text-secondary">Descrizione *</label>
                            <textarea id="mod_descrizione" name="descrizione" class="form-control" rows="4" required></textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold text-secondary">Indirizzo Internet (URL) *</label>
                            <input type="url" class="form-control" id="mod_url" name="url_link" required>
                            <div class="form-text">Inserisci l'URL completo (es. https://www.esempio.it)</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold text-secondary">Foto * (obbligatoria - carica nuova per sostituire)</label>
                            <input type="file" class="form-control" id="mod_foto" name="foto" accept="image/*">
                            <div id="mod_badge_foto" class="form-text mt-2"></div>
                            <div class="form-text text-warning">Attenzione: Se non carichi una nuova foto, verrà mantenuta quella esistente.</div>
                        </div>
                    </div>
                    <div class="modal-footer mt-3 pb-0 pe-0">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annulla</button>
                        <button type="submit" class="btn btn-primary px-4">Salva Modifiche</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Funzione per validare l'URL lato client
function isValidUrl(url) {
    if (!url || url.trim() === "") return false;
   
    try {
        const urlObj = new URL(url);
        return urlObj.protocol === "http:" || urlObj.protocol === "https:";
    } catch(e) {
        return false;
    }
}

function validaForm() {
    // Validazione URL
    var url = document.getElementById('url_input').value;
    if (!isValidUrl(url)) {
        alert('ERRORE: L\'URL inserito non è valido!\n\nAssicurati di inserire un indirizzo web corretto (es. https://www.esempio.it)');
        return false;
    }
   
    // Validazione foto (obbligatoria)
    var foto = document.getElementById('foto_input').files.length;
    if (foto === 0) {
        alert('ERRORE: La foto è obbligatoria!\n\nDevi selezionare un\'immagine per il link.');
        return false;
    }
   
    return true;
}

function validaFormModifica() {
    // Validazione URL
    var url = document.getElementById('mod_url').value;
    if (!isValidUrl(url)) {
        alert('ERRORE: L\'URL inserito non è valido!\n\nAssicurati di inserire un indirizzo web corretto (es. https://www.esempio.it)');
        return false;
    }
   
    // Validazione foto: se non c'è una foto corrente e non ne carica una nuova
    var idFotoCorrente = document.getElementById('mod_id_foto_corrente').value;
    var nuovaFoto = document.getElementById('mod_foto').files.length;
   
    if ((!idFotoCorrente || idFotoCorrente === "" || idFotoCorrente === "0") && nuovaFoto === 0) {
        alert('ERRORE: La foto è obbligatoria!\n\nDevi caricare un\'immagine per il link.');
        return false;
    }
   
    return true;
}

document.addEventListener('DOMContentLoaded', function () {
    var modalModifica = document.getElementById('modalModificaLink');
   
    if(modalModifica) {
        modalModifica.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
           
            var id = button.getAttribute('data-id');
            var titolo = button.getAttribute('data-titolo');
            var url = button.getAttribute('data-url');
            var descrizione = button.getAttribute('data-descrizione');
            var ordine = button.getAttribute('data-ordine');
            var idfoto = button.getAttribute('data-idfoto');
           
            var badgeContainer = modalModifica.querySelector('#mod_badge_foto');
            if(idfoto && idfoto.trim() !== "" && idfoto !== "0") {
                badgeContainer.innerHTML = '<span class="badge bg-info text-dark mt-1"><i class="bi bi-image me-1"></i> ID Foto Attuale: ' + idfoto + ' (verrà mantenuta se non carichi una nuova immagine)</span>';
            } else {
                badgeContainer.innerHTML = '<span class="text-danger"><i class="bi bi-exclamation-triangle"></i> Nessuna foto associata! Devi caricarne una obbligatoriamente.</span>';
            }
           
            modalModifica.querySelector('#mod_idDaModificare').value = id;
            modalModifica.querySelector('#mod_titolo').value = titolo;
            modalModifica.querySelector('#mod_url').value = url;
            modalModifica.querySelector('#mod_descrizione').value = descrizione;
            modalModifica.querySelector('#mod_ordine').value = ordine;
            modalModifica.querySelector('#mod_id_foto_corrente').value = idfoto || '';
           
            // Reset del campo file
            modalModifica.querySelector('#mod_foto').value = '';
        });
    }
});
</script>
</body>
</html>