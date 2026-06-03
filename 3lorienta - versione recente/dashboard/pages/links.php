<?php
	include("../controlloSessione.php");
    include("../connessione/db.php");

    $conn = new mysqli($HOSTDB, $USERDB, $PASSDB, $NOMEDB);

    if($conn->connect_error)
    {
        die("Connessione non stabilita");
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
                $query = "SELECT * FROM Links WHERE titolo LIKE ? ORDER BY n_ordine ASC";
                $stmt = $this->conn->prepare($query);
                $paramRicerca = "%" . $filtroTitolo . "%";
                $stmt->bind_param("s", $paramRicerca);
            } else {
                $query = "SELECT * FROM Links ORDER BY n_ordine ASC";
                $stmt = $this->conn->prepare($query);
            }
            $stmt->execute();
            $risultato = $stmt->get_result();
            $stmt->close();
            return $risultato;
        }

        public function aggiungiLink(string $titolo, string $url_link, string $descrizione, int $n_ordine, int $id_foto = 0): bool {
            $query = "INSERT INTO Links (titolo, url_link, descrizione, n_ordine, id_foto) VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($query);
            if (!$stmt) return false;
            $stmt->bind_param("sssii", $titolo, $url_link, $descrizione, $n_ordine, $id_foto);
            $ok = $stmt->execute();
            $stmt->close();
            return (bool)$ok;
        }

        public function updateLink(int $id, string $titolo, string $url_link, string $descrizione, int $n_ordine): bool {
            $query = "UPDATE Links SET titolo = ?, url_link = ?, descrizione = ?, n_ordine = ? WHERE ID_link = ?";
            $stmt = $this->conn->prepare($query);
            if (!$stmt) return false;
            $stmt->bind_param("sssii", $titolo, $url_link, $descrizione, $n_ordine, $id);
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

            $linkManager->updateLink($idDaModificare, $titolo, $url_link, $descrizione, $nOrdine);
            echo "<script>window.location.href = 'index.php?page=links';</script>";
            exit();
        }
        elseif($_POST['azione'] === "aggiungi") 
        {
            $titolo      = trim($_POST["titolo"]);
            $url_link    = trim($_POST["url_link"]);
            $descrizione = trim($_POST["descrizione"]);
            $n_ordine    = (int)$_POST["n_ordine"];
            $id_foto     = isset($_POST["id_foto"]) && $_POST["id_foto"] !== "" ? (int)$_POST["id_foto"] : 0;

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
            
            $tabella .= "<td><a href='". htmlspecialchars($url_link, ENT_QUOTES) ."' target='_blank' class='text-truncate d-inline-block' style='max-width: 200px;'><i class='bi bi-box-arrow-up-right me-1'></i>Apri Collegamento</a></td>";
            
            $tabella .= "<td class='text-end'>";
            
            $titoloEsc = htmlspecialchars($titolo, ENT_QUOTES);
            $urlEsc = htmlspecialchars($url_link, ENT_QUOTES);
            $descrizioneEsc = htmlspecialchars($descrizione, ENT_QUOTES);

            $tabella .= "<button type='button' class='btn btn-sm btn-outline-primary me-1' 
                            data-bs-toggle='modal' 
                            data-bs-target='#modalModificaLink'
                            data-id='{$idLink}'
                            data-titolo='{$titoloEsc}'
                            data-url='{$urlEsc}'
                            data-descrizione='{$descrizioneEsc}'
                            data-ordine='{$nOrdine}'>
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
        $tabella = "<tr><td colspan='6' class='text-center py-4 text-muted'>Nessun link trovato.</td></tr>";
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
    
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th class="px-3">ID Link</th>
                <th>Titolo</th>
                <th class="text-center">N.ordine</th>
                <th>Data eliminazione</th>
                <th>URL Destinazione</th>
                <th class="text-end">Azioni</th>
            </tr>
        </thead>
        <tbody>
            <?php echo $tabella; ?>
        </tbody>
    </table>
</div>

<div class="modal fade" id="modalNuovoLink" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold">Nuovo Link</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
            </div>
            <div class="modal-body">
                <form id="linkForm" action="" method="POST">
					<input type='hidden' name='page' value='links'>
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Titolo Link</label>
                            <input type="text" name="titolo" class="form-control" required placeholder="Es: Portale MIUR">
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label fw-semibold">Descrizione</label>
                            <textarea name="descrizione" class="form-control" rows="4" required placeholder="Descrivi il collegamento utile..."></textarea>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">N. Ordine</label>
                            <input type="number" name="n_ordine" class="form-control" required placeholder="Es: 1">
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label fw-semibold">Indirizzo Internet (URL)</label>
                            <input type="url" class="form-control" name="url_link" required placeholder="https://esempio.it">
                        </div>
                         <div class="col-12">
                            <label class="form-label fw-semibold">ID foto</label>
                            <input type="number" name="id_foto" class="form-control" placeholder="Es: 123">
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

<div class="modal fade" id="modalModificaLink" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold">Modifica Link</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
            </div>
            <div class="modal-body">
                <form id="formModifica" action="" method="POST">
					<input type='hidden' name='page' value='links'>
                    <input type="hidden" name="azione" value="salva_modifica">
                    <input type="hidden" name="idDaModificare" id="mod_idDaModificare">

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold text-secondary">Titolo Link</label>
                            <input type="text" id="mod_titolo" name="titolo" class="form-control" required>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label fw-semibold text-secondary">N. Ordine</label>
                            <input type="number" id="mod_ordine" name="n_ordine" class="form-control" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold text-secondary">Descrizione</label>
                            <textarea id="mod_descrizione" name="descrizione" class="form-control" rows="4" required></textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold text-secondary">Indirizzo Internet (URL)</label>
                            <input type="url" class="form-control" id="mod_url" name="url_link" required>
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
            
            modalModifica.querySelector('#mod_idDaModificare').value = id;
            modalModifica.querySelector('#mod_titolo').value = titolo;
            modalModifica.querySelector('#mod_url').value = url;
            modalModifica.querySelector('#mod_descrizione').value = descrizione;
            modalModifica.querySelector('#mod_ordine').value = ordine;
        });
    }
});
</script>
</body>
</html>