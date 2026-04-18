<?php
// index.php — Registrazione Insegnante
// Logica PHP qui (sessioni, DB, ecc.)
session_start();
include 'connessioneDB.php';
// Controllo ruolo utente (da abilitare quando ci sono i dati di sessione)
// $isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$isAdmin = true;
$conn = new mysqli($HOST, $USER, $PSW, $DB);
if ($conn->connect_error) {
    //die("Connessione al database fallita: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['link_action'], $_POST['ID_link'])) {
    $idLink = intval($_POST['ID_link']);
    if ($_POST['link_action'] === 'edit') {
        $titolo = $_POST['titolo'] ?? '';
        $url_link = $_POST['url_link'] ?? '';
        $descrizione = $_POST['descrizione'] ?? '';
        $n_ordine = intval($_POST['n_ordine'] ?? 0);
        
        $updateStmt = $conn->prepare("UPDATE links SET titolo = ?, url_link = ?, descrizione = ?, n_ordine = ? WHERE ID_link = ?");
        $updateStmt->bind_param('sssii', $titolo, $url_link, $descrizione, $n_ordine, $idLink);
        $updateStmt->execute();
        $updateStmt->close();
    } elseif ($_POST['link_action'] === 'delete') {
        $today = date('Y-m-d');
        $updateStmt = $conn->prepare("UPDATE links SET data_eliminazione = ? WHERE ID_link = ?");
        $updateStmt->bind_param('si', $today, $idLink);
        $updateStmt->execute();
        $updateStmt->close();
    } elseif ($_POST['link_action'] === 'restore') {
        $updateStmt = $conn->prepare("UPDATE links SET data_eliminazione = NULL WHERE ID_link = ?");
        $updateStmt->bind_param('i', $idLink);
        $updateStmt->execute();
        $updateStmt->close();
    }
    header('Location: ?show_links=1');
    exit;
}

//prendo i  dati dei link
$stmt = $conn->prepare("SELECT links.*, foto.path_foto FROM links LEFT JOIN foto ON links.id_foto = foto.id_foto");
$stmt->execute();
$links = $stmt->get_result();

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<input type="checkbox" id="sidebar-toggle">

<aside class="sidebar">
    <div class="logo">
        <span class="logo-text"><img src="img/logo.png" alt="logo" width="40" height="40" style="object-fit: contain; vertical-align: middle;"> 3elleorienta</span>
        <label class="menu-toggle-label" for="sidebar-toggle" title="Apri/Chiudi menu">&#9776;</label>
    </div>

    <nav class="nav-group mt-2">

        <div class="nav-label">SCUOLA</div>
        <a href="#" class="nav-link active">
            <i class="bi bi-backpack-fill"></i>
            <span class="link-text">Scuola</span>
        </a>
        <a href="#" class="nav-link">
            <i class="bi bi-geo-fill"></i>
            <span class="link-text">Zona</span>
        </a>

        <div class="nav-label mt-2">AVVENIMENTI</div>
        <a href="#" class="nav-link">
            <i class="bi bi-calendar-fill"></i>
            <span class="link-text">Eventi</span>
        </a>
        <a href="#" class="nav-link">
            <i class="bi bi-lightbulb-fill"></i>
            <span class="link-text">Progetti</span>
        </a>
        <a href="#" class="nav-link">
            <i class="bi bi-link-45deg"></i>
            <span class="link-text">Link Utili</span>
        </a>

        <div class="nav-label mt-2">UTENTI</div>
        <a href="#" class="nav-link">
            <i class="bi bi-person-arms-up"></i>
            <span class="link-text">Orientatore</span>
        </a>
        <a href="#" class="nav-link">
            <i class="bi bi-person-raised-hand"></i>
            <span class="link-text">Insegnanti</span>
        </a>
        <a href="#" class="nav-link">
            <i class="bi bi-people-fill"></i>
            <span class="link-text">Lista Degli Insegnanti</span>
        </a>
        <a href="#" class="nav-link">
            <i class="bi bi-people-fill"></i>
            <span class="link-text">Elenco Degli Studenti</span>
        </a>
        <a href="#" class="nav-link">
            <i class="bi bi-mortarboard-fill"></i>
            <span class="link-text">Studenti</span>
        </a>
        <a href="#" class="nav-link">
            <i class="bi bi-people-fill"></i>
            <span class="link-text">Gestione Utenti</span>
        </a>

        <div class="nav-label mt-2">FILE</div>
        <a href="#" class="nav-link">
            <i class="bi bi-file-earmark-arrow-up-fill"></i>
            <span class="link-text">Carica Documento</span>
        </a>
        <a href="#" class="nav-link">
            <i class="bi bi-search"></i>
            <span class="link-text">Trova Documento</span>
        </a>

        <div class="nav-label mt-2">ALTRO</div>
        <a href="#" class="nav-link">
            <i class="bi bi-person-plus-fill"></i>
            <span class="link-text">Registra Insegnante</span>
        </a>
        <a href="#" class="nav-link">
            <i class="bi bi-tools"></i>
            <span class="link-text">Impostazioni</span>
        </a>

    </nav>
</aside>

<label class="sidebar-overlay" for="sidebar-toggle" aria-label="Chiudi menu"></label>

<div class="main-wrapper">
    <header class="top-bar">
        <div class="d-flex align-items-center gap-3">
            <label class="hamburger-label" for="sidebar-toggle" aria-label="Apri menu">
            <i class="bi bi-list"></i>
            </label>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item text-secondary">Dashboard</li>
                    <li class="breadcrumb-item active" aria-current="page" id="breadcrumb-current">Scuola</li>
                </ol>
            </nav>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const showLinks = <?= isset($_GET['show_links']) && $_GET['show_links'] == 1 ? 'true' : 'false' ?>;
                const sidebarToggle = document.getElementById('sidebar-toggle');
                const mainContent = document.getElementById('main-content');
                const breadcrumbCurrent = document.getElementById('breadcrumb-current');
                const pageContent = document.querySelector('.page-content');
                
                // Mappa dei contenuti per sezioni senza database
                const sectionsHTML = {
                    "Scuola": `
                        <div class="content-grid">
                            <div class="grid-third">colonna sx</div>
                            <div class="grid-third">colonna centrale</div>
                            <div class="grid-third">colonna dx</div>
                        </div>`,
                    "Zona": `
                        <div class="content-grid">
                            <div class="grid-third">colonna sx</div>
                            <div class="grid-third">colonna centrale</div>
                            <div class="grid-third">colonna dx</div>
                        </div>`,
                    "Eventi": `
                       <div class="content-grid">
                            <div class="grid-third">colonna sx</div>
                            <div class="grid-third">colonna centrale</div>
                            <div class="grid-third">colonna dx</div>
                        </div>`,
                    "Progetti": `
                        <div class="content-grid">
                            <div class="grid-third">colonna sx</div>
                            <div class="grid-third">colonna centrale</div>
                            <div class="grid-third">colonna dx</div>
                        </div>`,
                    "Gestione Utenti": `
                        <div class="content-grid">
                            <div class="grid-third">colonna sx</div>
                            <div class="grid-third">colonna centrale</div>
                            <div class="grid-third">colonna dx</div>
                        </div>`,
                    "Impostazioni": `
                        <div class="content-grid">
                            <div class="grid-third">colonna sx</div>-
                            <div class="grid-third">colonna centrale</div>
                            <div class="grid-third">colonna dx</div>
                        </div>`
                };
                
         
                const tableContent = `
<div class="content-grid">
    <div class="grid-full">
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" id="searchTitolo" placeholder="Cerca per titolo...">
                </div>
            </div>
        </div>
        
        <div class="row g-3 mb-3">
            <div class="col-md-12">
                <small class="text-muted">
                    <i class="bi bi-info-circle"></i> 
                    <strong>Filtra per data di eliminazione:</strong> Seleziona Giorno, Mese e/o Anno per visualizzare i link eliminati in una data specifica. Se utilizzi più criteri contemporaneamente, verranno combinati (es: visualizza i link eliminati il 14 Aprile).
                </small>
            </div>
        </div>
        
        <div class="row g-3 mb-3">
            <div class="col-md-2">
                <select class="form-select" id="filterGiorno">
                    <option value="">Giorno</option>
                    <option value="01">01</option><option value="02">02</option><option value="03">03</option>
                    <option value="04">04</option><option value="05">05</option><option value="06">06</option>
                    <option value="07">07</option><option value="08">08</option><option value="09">09</option>
                    <option value="10">10</option><option value="11">11</option><option value="12">12</option>
                    <option value="13">13</option><option value="14">14</option><option value="15">15</option>
                    <option value="16">16</option><option value="17">17</option><option value="18">18</option>
                    <option value="19">19</option><option value="20">20</option><option value="21">21</option>
                    <option value="22">22</option><option value="23">23</option><option value="24">24</option>
                    <option value="25">25</option><option value="26">26</option><option value="27">27</option>
                    <option value="28">28</option><option value="29">29</option><option value="30">30</option>
                    <option value="31">31</option>
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select" id="filterMese">
                    <option value="">Mese</option>
                    <option value="01">Gennaio</option><option value="02">Febbraio</option><option value="03">Marzo</option>
                    <option value="04">Aprile</option><option value="05">Maggio</option><option value="06">Giugno</option>
                    <option value="07">Luglio</option><option value="08">Agosto</option><option value="09">Settembre</option>
                    <option value="10">Ottobre</option><option value="11">Novembre</option><option value="12">Dicembre</option>
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select" id="filterAnno">
                    <option value="">Anno</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-outline-secondary w-100" id="resetFilters" type="button">
                    <i class="bi bi-arrow-clockwise"></i> Azzera Filtri
                </button>
            </div>
        </div>
        <table class="table table-striped" id="linksTable">
            <thead>
                <tr>
                    <th>ID_link</th>
                    <th>titolo</th>
                    <th>url_link</th>
                    <th>descrizione</th>
                    <th>n_ordine</th>
                    <th>data_eliminazione</th>
                    <th>id_foto</th>
                    <th>foto</th>
                    <?= $isAdmin ? '<th>Azioni</th>' : '' ?>
                </tr>
            </thead>
            <tbody>
                <?php
                while ($row = $links->fetch_assoc()) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($row['ID_link']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['titolo']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['url_link']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['descrizione']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['n_ordine']) . '</td>';
                    echo '<td>' . (!empty($row['data_eliminazione']) ? htmlspecialchars(date('d/m/y', strtotime($row['data_eliminazione']))) : '') . '</td>';
                    echo '<td>' . htmlspecialchars($row['id_foto']) . '</td>';
                    echo '<td><img src="' . htmlspecialchars($row['path_foto'] ?? '') . '" alt="Foto" width="50" height="50"></td>';
                    if ($isAdmin) {
                        echo '<td>';
                        echo '<button class="btn btn-sm btn-primary me-1" type="button" data-bs-toggle="modal" data-bs-target="#editModal" data-id="' . htmlspecialchars($row['ID_link']) . '" data-titolo="' . htmlspecialchars($row['titolo']) . '" data-url="' . htmlspecialchars($row['url_link']) . '" data-descrizione="' . htmlspecialchars($row['descrizione']) . '" data-ordine="' . htmlspecialchars($row['n_ordine']) . '">Modifica</button>';
                        if (!empty($row['data_eliminazione'])) {
                            echo '<form method="post" class="d-inline"><input type="hidden" name="ID_link" value="' . htmlspecialchars($row['ID_link']) . '"><input type="hidden" name="link_action" value="restore"><button type="submit" class="btn btn-sm btn-success">Reinserisci</button></form>';
                        } else {
                            echo '<form method="post" class="d-inline"><input type="hidden" name="ID_link" value="' . htmlspecialchars($row['ID_link']) . '"><input type="hidden" name="link_action" value="delete"><button type="submit" class="btn btn-sm btn-danger">Elimina</button></form>';
                        }
                        echo '</td>';
                    }
                    echo '</tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modale Modifica Link -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modifica Link</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editForm" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="link_action" value="edit">
                    <input type="hidden" name="ID_link" id="editLinkId">
                    
                    <div class="mb-3">
                        <label for="editTitolo" class="form-label">Titolo</label>
                        <input type="text" class="form-control" id="editTitolo" name="titolo" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editUrl" class="form-label">URL</label>
                        <input type="url" class="form-control" id="editUrl" name="url_link" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editDescrizione" class="form-label">Descrizione</label>
                        <textarea class="form-control" id="editDescrizione" name="descrizione" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editOrdine" class="form-label">Ordine</label>
                        <input type="number" class="form-control" id="editOrdine" name="n_ordine">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary">Salva Modifiche</button>
                </div>
            </form>
        </div>
    </div>
</div>
`;

                function initializeSearchFilters() {
                    const searchTitoloInput = document.getElementById('searchTitolo');
                    const filterGiornoSelect = document.getElementById('filterGiorno');
                    const filterMeseSelect = document.getElementById('filterMese');
                    const filterAnnoSelect = document.getElementById('filterAnno');
                    const table = document.getElementById('linksTable');
                    
                    // Genera anni dinamicamente (ultimi 100 anni) in ordine decrescente
                    const annoAttuale = new Date().getFullYear();
                    const annoInizio = annoAttuale - 100;
                    for (let anno = annoAttuale; anno >= annoInizio; anno--) {
                        const option = document.createElement('option');
                        option.value = anno;
                        option.textContent = anno;
                        filterAnnoSelect.appendChild(option);
                    }
                    
                    function filterTable() {
                        const searchTitolo = searchTitoloInput.value.toLowerCase().trim();
                        const filterGiorno = filterGiornoSelect.value;
                        const filterMese = filterMeseSelect.value;
                        const filterAnno = filterAnnoSelect.value;
                        const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
                        
                        for (let row of rows) {
                            const titoloCell = row.cells[1].textContent.toLowerCase();
                            const dataEliminazioneCell = row.cells[5].textContent.trim();
                            
                            let matchTitolo = true;
                            let matchData = true;
                            
                            if (searchTitolo) {
                                matchTitolo = titoloCell.includes(searchTitolo);
                            }
                            
                            if (filterGiorno || filterMese || filterAnno) {
                                if (dataEliminazioneCell !== '') {
                                    const [day, month, year] = dataEliminazioneCell.split('/');
                                    
                                    let dayMatch = !filterGiorno || day === filterGiorno;
                                    let monthMatch = !filterMese || month === filterMese;
                                    let yearMatch = !filterAnno || ('20' + year) === filterAnno;
                                    
                                    matchData = dayMatch && monthMatch && yearMatch;
                                } else {
                                    matchData = false;
                                }
                            }
                            
                            row.style.display = (matchTitolo && matchData) ? '' : 'none';
                        }
                    }
                    
                    searchTitoloInput.addEventListener('keyup', filterTable);
                    filterGiornoSelect.addEventListener('change', filterTable);
                    filterMeseSelect.addEventListener('change', filterTable);
                    filterAnnoSelect.addEventListener('change', filterTable);
                    
                    // Event listener per pulsante reset filtri
                    const resetButton = document.getElementById('resetFilters');
                    resetButton.addEventListener('click', function() {
                        searchTitoloInput.value = '';
                        filterGiornoSelect.value = '';
                        filterMeseSelect.value = '';
                        filterAnnoSelect.value = '';
                        filterTable();
                    });
                    
                    // Event listener per modale modifica
                    const editModal = document.getElementById('editModal');
                    if (editModal) {
                        editModal.addEventListener('show.bs.modal', function(event) {
                            const button = event.relatedTarget;
                            const linkId = button.getAttribute('data-id');
                            const titolo = button.getAttribute('data-titolo');
                            const url = button.getAttribute('data-url');
                            const descrizione = button.getAttribute('data-descrizione');
                            const ordine = button.getAttribute('data-ordine');
                            
                            document.getElementById('editLinkId').value = linkId;
                            document.getElementById('editTitolo').value = titolo;
                            document.getElementById('editUrl').value = url;
                            document.getElementById('editDescrizione').value = descrizione;
                            document.getElementById('editOrdine').value = ordine;
                        });
                        
                        document.getElementById('editForm').addEventListener('submit', function(e) {
                            e.preventDefault();
                            this.submit();
                        });
                    }
                }

                // Aggiorna al click su ogni voce
                document.querySelectorAll('.nav-link').forEach(function (link) {
                    link.addEventListener('click', function (e) {
                        e.preventDefault();
                        document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
                        this.classList.add('active');
                        
                        const linkText = this.querySelector('.link-text').textContent.trim();
                        breadcrumbCurrent.textContent = linkText;
                        
                        if (linkText === 'Link Utili') {
                            pageContent.innerHTML = tableContent;
                            initializeSearchFilters();
                        } else if (sectionsHTML[linkText]) {
                            pageContent.innerHTML = sectionsHTML[linkText];
                        }
                        
                        // Chiude la sidebar su mobile
                        if (window.innerWidth <= 992) {
                            sidebarToggle.checked = false;
                        }
                    });
                });

                if (showLinks) {
                    const utiliLink = Array.from(document.querySelectorAll('.nav-link')).find(link => link.querySelector('.link-text').textContent.trim() === 'Link Utili');
                    if (utiliLink) {
                        utiliLink.click();
                    }
                }
            });
        </script>
        <div class="user-info d-flex align-items-center gap-2">
            <i class="bi bi-person-circle"></i>
            <span class="fw-semibold" style="font-size:0.88rem;">
                <?php echo htmlspecialchars('admin'); ?>
            </span>
            <span class="text-secondary">|</span>
            <a href="logout.php" class="text-danger text-decoration-none" style="font-size:0.88rem;">Logout</a>
        </div>
    </header>

    <main class="page-content" id="main-content">
        
        <!-- Contenuto principale -->    
        <div class="content-grid">
            
            <div class="grid-third">
                colonna sx 
            </div>
            <div class="grid-third">
                colonna centrale
            </div>
            <div class="grid-third">
                colonna dx 
            </div>

        </div>
    </main>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
