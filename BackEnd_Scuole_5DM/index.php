<?php
session_start();

// Legge e cancella il messaggio flash (da inserimento/modifica/eliminazione)
$flash_msg = $_SESSION['flash_msg'] ?? '';
$flash_type = $_SESSION['flash_type'] ?? '';
unset($_SESSION['flash_msg'], $_SESSION['flash_type']);
?>
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — 3elleorienta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <input type="checkbox" id="sidebar-toggle">

    <aside class="sidebar">
        <div class="logo">
            <span class="logo-text">
                <img src="img/logo.png" alt="logo" width="40" height="40"
                    style="object-fit:contain;vertical-align:middle;"> 3elleorienta
            </span>
            <label class="menu-toggle-label" for="sidebar-toggle" title="Apri/Chiudi menu">☰</label>
        </div>
        <nav class="nav-group mt-2">
            <div class="nav-label">SCUOLA</div>
            <a href="#" class="nav-link active" data-section="Scuola" data-url="scuole.php?ajax=1">
                <i class="bi bi-backpack-fill"></i><span class="link-text">Scuola</span>
            </a>
            <a href="#" class="nav-link" data-section="Zona" data-url="zona.php?ajax=1">
                <i class="bi bi-geo-fill"></i><span class="link-text">Zona</span>
            </a>

            <div class="nav-label mt-2">AVVENIMENTI</div>
            <a href="#" class="nav-link" data-section="Eventi" data-url="eventi.php?ajax=1">
                <i class="bi bi-calendar-fill"></i><span class="link-text">Eventi</span>
            </a>
            <a href="#" class="nav-link" data-section="Progetti" data-url="progetti.php?ajax=1">
                <i class="bi bi-lightbulb-fill"></i><span class="link-text">Progetti</span>
            </a>
            <a href="#" class="nav-link" data-section="Link Utili" data-url="link_utili.php?ajax=1">
                <i class="bi bi-link-45deg"></i><span class="link-text">Link Utili</span>
            </a>

            <div class="nav-label mt-2">UTENTI</div>
            <a href="#" class="nav-link" data-section="Gestione Utenti" data-url="utenti.php?ajax=1">
                <i class="bi bi-people-fill"></i><span class="link-text">Gestione Utenti</span>
            </a>

            <div class="nav-label mt-2">ALTRO</div>
            <a href="#" class="nav-link" data-section="Impostazioni" data-url="impostazioni.php?ajax=1">
                <i class="bi bi-tools"></i><span class="link-text">Impostazioni</span>
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
                        <li class="breadcrumb-item active" id="breadcrumb-current">Scuola</li>
                    </ol>
                </nav>
            </div>
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
            <?php if ($flash_msg): ?>
                <div class="alert alert-<?= htmlspecialchars($flash_type) ?> alert-dismissible fade show mb-4" role="alert">
                    <?= $flash_msg ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <div class="text-center py-5 text-secondary">
                <div class="spinner-border spinner-border-sm me-2"></div> Caricamento…
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const navLinks = document.querySelectorAll('.nav-link[data-url]');
            const breadcrumbCurrent = document.getElementById('breadcrumb-current');
            const sidebarToggle = document.getElementById('sidebar-toggle');
            const mainContent = document.getElementById('main-content');

            /* ── Carica una sezione via AJAX ── */
            function loadSection(url, label) {
                const existingFlash = mainContent.querySelector('.alert');

                mainContent.innerHTML = `<div class="text-center py-5 text-secondary">
            <div class="spinner-border spinner-border-sm me-2"></div> Caricamento…
        </div>`;

                fetch(url)
                    .then(r => {
                        if (!r.ok) throw new Error('HTTP ' + r.status);
                        return r.text();
                    })
                    .then(html => {
                        mainContent.innerHTML = html;
                        if (existingFlash) {
                            mainContent.insertAdjacentElement('afterbegin', existingFlash);
                        }
                        // Dopo aver caricato il contenuto, inizializza i moduli specifici
                        initScuoleModule();
                    })
                    .catch(() => {
                        mainContent.innerHTML = `<div class="alert alert-danger m-3">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    Errore nel caricamento della sezione <strong>${label}</strong>.
                </div>`;
                    });
            }

            /* ── Navigazione sidebar ── */
            navLinks.forEach(link => {
                link.addEventListener('click', function (e) {
                    e.preventDefault();
                    navLinks.forEach(l => l.classList.remove('active'));
                    this.classList.add('active');
                    const label = this.dataset.section;
                    const url = this.dataset.url;
                    breadcrumbCurrent.textContent = label;
                    if (window.innerWidth <= 992) sidebarToggle.checked = false;
                    loadSection(url, label);
                });
            });

            // Carica la sezione attiva all'avvio
            const activeLink = document.querySelector('.nav-link.active[data-url]');
            if (activeLink) {
                loadSection(activeLink.dataset.url, activeLink.dataset.section);
            }

            /* ════════════════════════════════════════════════════════
               MODALE ELIMINAZIONE — registrato UNA SOLA VOLTA
            ════════════════════════════════════════════════════════ */
            const modalEliminaEl = document.getElementById('modalElimina');
            const modalEliminaIstanza = new bootstrap.Modal(modalEliminaEl);

            document.addEventListener('click', function (e) {
                const btn = e.target.closest('.btn-elimina-scuola');
                if (!btn) return;
                document.getElementById('modalNomeScuola').textContent = btn.dataset.nome;
                document.getElementById('modalEliminaBtn').dataset.elimUrl = btn.dataset.url;
                modalEliminaIstanza.show();
            });

            document.getElementById('modalEliminaBtn').addEventListener('click', function () {
                const url = this.dataset.elimUrl;
                if (!url) return;

                modalEliminaIstanza.hide();

                mainContent.innerHTML = '<div class="text-center py-5 text-secondary"><div class="spinner-border spinner-border-sm me-2"></div> Eliminazione in corso…</div>';

                fetch(url)
                    .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return fetch('scuole.php?ajax=1'); })
                    .then(r2 => r2.text())
                    .then(html => {
                        mainContent.innerHTML =
                            '<div class="alert alert-success alert-dismissible fade show mb-3" role="alert">' +
                            '<i class="bi bi-check-circle-fill me-2"></i>Scuola eliminata con successo.' +
                            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>' +
                            html;
                        initScuoleModule();
                    })
                    .catch(() => {
                        mainContent.innerHTML = '<div class="alert alert-danger m-3"><i class="bi bi-exclamation-triangle-fill me-2"></i>Errore durante l\'eliminazione.</div>';
                    });
            });

            /* ════════════════════════════════════════════════════════
               MODULO SCUOLE
               Viene chiamato ogni volta che scuole.php viene caricato
               nel main-content. Usa delegazione sul mainContent per
               non dipendere da quando gli elementi esistono nel DOM.
            ════════════════════════════════════════════════════════ */
            function initScuoleModule() {
                /* Controlla che il modulo scuole sia presente */
                if (!document.getElementById('btn-filtra')) return;

                /* ── 1. FILTRO TABELLA (rimane in index, nessun redirect) ── */
                function caricaFiltroScuole(cittaId, nome) {
                    let url = 'scuole.php?ajax=1';
                    if (cittaId && cittaId !== '0') url += '&id_citta=' + encodeURIComponent(cittaId);
                    if (nome) url += '&cerca=' + encodeURIComponent(nome);

                    const wrapper = document.getElementById('tabella-scuole-wrapper');
                    const contatore = document.getElementById('contatore-risultati');
                    if (!wrapper) return;

                    wrapper.innerHTML = '<div class="text-center text-secondary py-3"><div class="spinner-border spinner-border-sm me-2"></div> Ricerca…</div>';

                    fetch(url)
                        .then(r => r.text())
                        .then(html => {
                            const parser = new DOMParser();
                            const doc = parser.parseFromString(html, 'text/html');
                            const nuovoWrap = doc.getElementById('tabella-scuole-wrapper');
                            const nuovoCont = doc.getElementById('contatore-risultati');
                            if (nuovoWrap) wrapper.innerHTML = nuovoWrap.innerHTML;
                            if (nuovoCont && contatore) contatore.textContent = nuovoCont.textContent;
                        })
                        .catch(() => {
                            const wrapper = document.getElementById('tabella-scuole-wrapper');
                            if (wrapper) wrapper.innerHTML = '<div class="alert alert-danger m-2">Errore nel caricamento.</div>';
                        });
                }

                document.getElementById('btn-filtra').addEventListener('click', function () {
                    const cittaId = document.getElementById('f-citta').value;
                    const nome = document.getElementById('f-nome').value.trim();
                    caricaFiltroScuole(cittaId, nome);
                });

                document.getElementById('btn-reset').addEventListener('click', function () {
                    document.getElementById('f-citta').value = '0';
                    document.getElementById('f-nome').value = '';
                    caricaFiltroScuole('0', '');
                });

                document.getElementById('f-nome').addEventListener('keydown', function (e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        document.getElementById('btn-filtra').click();
                    }
                });

                /* ── 2. GEOCODING AUTOMATICO (lato client, Nominatim OSM) ── */
                const insVia = document.getElementById('ins-via');
                const insCivico = document.getElementById('ins-civico');
                const insCitta = document.getElementById('ins-citta');
                const latHidden = document.getElementById('lat_hidden');
                const lngHidden = document.getElementById('lng_hidden');
                const feedback = document.getElementById('geo-feedback');

                if (!insVia || !insCitta) return; // form non presente nella pagina

                let geoTimer = null;

                function getNomeCitta() {
                    const opt = insCitta.selectedOptions[0];
                    return opt ? (opt.dataset.nome || '') : '';
                }

                function geocodifica() {
                    const via = insVia.value.trim();
                    const civico = insCivico ? insCivico.value.trim() : '';
                    const citta = getNomeCitta();

                    latHidden.value = '0';
                    lngHidden.value = '0';

                    if (!via || !citta) {
                        feedback.textContent = '';
                        feedback.className = 'geo-status';
                        return;
                    }

                    const indirizzo = via + (civico ? ' ' + civico : '') + ', ' + citta + ', Italia';
                    feedback.textContent = '⏳ Ricerca coordinate in corso…';
                    feedback.className = 'geo-status loading';

                    const url = 'https://nominatim.openstreetmap.org/search?q='
                        + encodeURIComponent(indirizzo)
                        + '&format=json&limit=1&addressdetails=0';

                    fetch(url, {
                        headers: {
                            'Accept-Language': 'it',
                            'User-Agent': '3elleorienta/1.0'
                        }
                    })
                        .then(r => r.json())
                        .then(data => {
                            if (data && data.length > 0) {
                                latHidden.value = data[0].lat;
                                lngHidden.value = data[0].lon;
                                feedback.textContent = '✅ Coordinate trovate: '
                                    + parseFloat(data[0].lat).toFixed(5)
                                    + ', ' + parseFloat(data[0].lon).toFixed(5);
                                feedback.className = 'geo-status ok';
                            } else {
                                feedback.textContent = '⚠️ Indirizzo non trovato. Il server tenterà il geocoding al salvataggio.';
                                feedback.className = 'geo-status err';
                            }
                        })
                        .catch(() => {
                            feedback.textContent = '⚠️ Servizio geocoding non raggiungibile. Il server tenterà al salvataggio.';
                            feedback.className = 'geo-status err';
                        });
                }

                function triggerGeo() {
                    clearTimeout(geoTimer);
                    geoTimer = setTimeout(geocodifica, 600);
                }

                insVia.addEventListener('input', triggerGeo);
                insCivico.addEventListener('input', triggerGeo);
                insCitta.addEventListener('change', triggerGeo);
            }
        });
    </script>

    <!-- ═══ MODALE CONFERMA ELIMINAZIONE (globale, sempre nel DOM) ═══ -->
    <div class="modal fade" id="modalElimina" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h6 class="modal-title text-danger">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>Conferma Eliminazione
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-2">
                    <p class="mb-1" style="font-size:.9rem;">
                        Stai per eliminare la scuola:<br>
                        <strong id="modalNomeScuola" class="text-dark"></strong>
                    </p>
                    <p class="text-danger mb-0" style="font-size:.82rem;">
                        <i class="bi bi-info-circle me-1"></i>L'operazione non è reversibile.
                    </p>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Annulla</button>
                    <button type="button" id="modalEliminaBtn" class="btn btn-danger btn-sm">
                        <i class="bi bi-trash-fill me-1"></i>Elimina
                    </button>
                </div>
            </div>
        </div>
    </div>

</body>

</html>