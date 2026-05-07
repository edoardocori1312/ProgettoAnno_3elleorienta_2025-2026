<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$username    = $_SESSION["usernameUtente"] ?? 'Utente';

require '../config/db.php';
require 'backend/gestione_zone.php';

$conn = new mysqli($HOSTDB, $USERDB, $PASSDB, $NOMEDB);
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}

$flash = null;
if (isset($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['inserisci'])) {
        $_SESSION['flash'] = creaZona($conn, $_POST['zona'] ?? '');
        $conn->close();
        header("Location: zona.php");
        exit();

    } elseif (isset($_POST['modifica'])) {
        $esito = aggiornaZona($conn, (int)($_POST['id'] ?? 0), $_POST['nome'] ?? '');
        $_SESSION['flash'] = $esito;
        $conn->close();
        header("Location: " . ($esito['tipo'] === 'errore' ? "zona.php?modifica=" . (int)$_POST['id'] : "zona.php"));
        exit();

    } elseif (isset($_POST['id_zona'])) {
        $_SESSION['flash'] = rimuoviZona($conn, (int)$_POST['id_zona']);
        $conn->close();
        header("Location: zona.php");
        exit();
    }
}

$zone = leggiZone($conn);
$conn->close();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Zone — 3elleorienta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../style/style.css">
    <link rel="stylesheet" href="../style/Progettistyle.css">
</head>
<body>

<input type="checkbox" id="sidebar-toggle">

<aside class="sidebar">
    <div class="logo">
        <span class="logo-text">
            <img src="img/logo.png" alt="logo" width="40" height="40" style="object-fit:contain;vertical-align:middle;">
            3elleorienta
        </span>
        <label class="menu-toggle-label" for="sidebar-toggle" title="Apri/Chiudi menu">☰</label>
    </div>

    <nav class="nav-group mt-2">
        <div class="nav-label">SCUOLA</div>
        <a href="../index.php" class="nav-link">
            <i class="bi bi-backpack-fill"></i>
            <span class="link-text">Scuola</span>
        </a>
        <a href="zona.php" class="nav-link active">
            <i class="bi bi-geo-fill"></i>
            <span class="link-text">Zona</span>
        </a>

        <div class="nav-label mt-2">AVVENIMENTI</div>
        <a href="eventi.php" class="nav-link">
            <i class="bi bi-calendar-fill"></i>
            <span class="link-text">Eventi</span>
        </a>
        <a href="progetti.php" class="nav-link">
            <i class="bi bi-lightbulb-fill"></i>
            <span class="link-text">Progetti</span>
        </a>
        <a href="link.php" class="nav-link">
            <i class="bi bi-link-45deg"></i>
            <span class="link-text">Link Utili</span>
        </a>

        <div class="nav-label mt-2">UTENTI</div>
        <a href="utenti.php" class="nav-link">
            <i class="bi bi-people-fill"></i>
            <span class="link-text">Gestione Utenti</span>
        </a>

        <div class="nav-label mt-2">ALTRO</div>
        <a href="impostazioni.php" class="nav-link">
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
                    <li class="breadcrumb-item active" aria-current="page">Zone</li>
                </ol>
            </nav>
        </div>
        <div class="user-info d-flex align-items-center gap-2">
            <i class="bi bi-person-circle"></i>
            <span class="fw-semibold" style="font-size:0.88rem;"><?php echo htmlspecialchars($username); ?></span>
            <span class="text-secondary">|</span>
            <a href="logout.php" class="text-danger text-decoration-none" style="font-size:0.88rem;">Logout</a>
        </div>
    </header>

    <main class="page-content">

        <p class="fw-bold fs-5 mb-1" style="color:#2c3e50;">Gestione Zone</p>
        <p class="text-secondary mb-4" style="font-size:0.82rem;">Aggiungi, modifica o elimina le zone dal sistema.</p>

        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['tipo'] === 'successo' ? 'success' : 'danger'; ?> alert-dismissible fade show py-2" role="alert">
                <?php echo htmlspecialchars($flash['msg']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card-panel">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0" style="color:#2c3e50;">
                    <i class="bi bi-geo-fill me-1"></i> Lista Zone
                </h6>
                <button class="btn btn-primary btn-sm d-flex align-items-center gap-1"
                        data-bs-toggle="modal" data-bs-target="#modalAggiungiZona">
                    <i class="bi bi-plus-lg"></i> Aggiungi zona
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th style="width:70px;">ID</th>
                            <th>Nome</th>
                            <th class="text-end" style="width:180px;">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($zone)): ?>
                        <?php foreach ($zone as $row): ?>
                            <?php
                                $id     = (int)$row['ID_zona'];
                                $nome   = htmlspecialchars($row['nome']);
                                $nomeJs = addslashes($row['nome']);
                            ?>
                            <?php if (isset($_GET['modifica']) && (int)$_GET['modifica'] === $id): ?>
                                <form id="form-mod-<?php echo $id; ?>" method="POST" style="display:none">
                                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                                    <input type="hidden" name="modifica" value="1">
                                </form>
                                <tr>
                                    <td><?php echo $id; ?></td>
                                    <td>
                                        <input type="text" name="nome" value="<?php echo $nome; ?>"
                                               class="form-control form-control-sm"
                                               form="form-mod-<?php echo $id; ?>" required>
                                    </td>
                                    <td class="text-end">
                                        <button type="submit" form="form-mod-<?php echo $id; ?>"
                                                class="btn btn-success btn-sm">Salva</button>
                                        <a href="zona.php" class="btn btn-secondary btn-sm">Annulla</a>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <tr>
                                    <td><?php echo $id; ?></td>
                                    <td><?php echo $nome; ?></td>
                                    <td class="text-end">
                                        <a href="?modifica=<?php echo $id; ?>" class="btn btn-primary btn-sm">
                                            <i class="bi bi-pencil"></i> Modifica
                                        </a>
                                        <button class="btn btn-danger btn-sm"
                                                onclick="apriModalElimina(<?php echo $id; ?>, '<?php echo $nomeJs; ?>')">
                                            <i class="bi bi-trash"></i> Elimina
                                        </button>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="3" class="text-center text-secondary py-3">Nessuna zona trovata</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>

<!-- Modal Aggiungi Zona -->
<div class="modal fade" id="modalAggiungiZona" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold">Nuova zona</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="zona.php">
                <div class="modal-body">
                    <label class="form-label fw-semibold">Nome zona</label>
                    <input type="text" name="zona" class="form-control" required
                           placeholder="Es: Nord, Sud, Centro...">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" name="inserisci" class="btn btn-primary px-4">Salva</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Conferma Eliminazione -->
<div class="modal fade" id="modalElimina" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Conferma eliminazione</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="modal-elimina-msg" class="mb-0"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annulla</button>
                <form id="form-elimina" method="POST" action="zona.php" style="display:inline">
                    <input type="hidden" name="id_zona" id="modal-id-zona" value="">
                    <button type="submit" class="btn btn-danger px-4">Elimina</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function apriModalElimina(id, nome) {
    document.getElementById('modal-elimina-msg').textContent = 'Eliminare la zona "' + nome + '"?';
    document.getElementById('modal-id-zona').value = id;
    new bootstrap.Modal(document.getElementById('modalElimina')).show();
}
</script>

</body>
</html>
