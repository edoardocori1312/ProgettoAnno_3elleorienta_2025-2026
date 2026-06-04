/* 
===========================================================
PROMEMORIA DI CHE FA STO FILE
===========================================================
Questo file gestisce le funzionalità dinamiche della dashboard,
incluse navigazione, filtri, modali, aggiornamenti AJAX e anteprime.
*/

document.addEventListener('DOMContentLoaded', function () {
    const showLinks = (typeof window.SHOW_Links !== 'undefined') ? window.SHOW_Links : false;
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const breadcrumbCurrent = document.getElementById('breadcrumb-current');
    const dynamicContent = document.getElementById('dynamicContent');
    const linksContent = document.getElementById('LinksContent'); // C maiuscola

    const sectionsHTML = {
        "Scuola":          `<div class="content-grid"><div class="grid-third">colonna sx</div><div class="grid-third">colonna centrale</div><div class="grid-third">colonna dx</div></div>`,
        "Zona":            `<div class="content-grid"><div class="grid-third">colonna sx</div><div class="grid-third">colonna centrale</div><div class="grid-third">colonna dx</div></div>`,
        "Eventi":          `<div class="content-grid"><div class="grid-third">colonna sx</div><div class="grid-third">colonna centrale</div><div class="grid-third">colonna dx</div></div>`,
        "Progetti":        `<div class="content-grid"><div class="grid-third">colonna sx</div><div class="grid-third">colonna centrale</div><div class="grid-third">colonna dx</div></div>`,
        "Gestione Utenti": `<div class="content-grid"><div class="grid-third">colonna sx</div><div class="grid-third">colonna centrale</div><div class="grid-third">colonna dx</div></div>`,
        "Impostazioni":    `<div class="content-grid"><div class="grid-third">colonna sx</div><div class="grid-third">colonna centrale</div><div class="grid-third">colonna dx</div></div>`
    };

    // -------------------------------
    // PREFILL MODALE MODIFICA
    // Legato subito al DOMContentLoaded, indipendentemente dalla navigazione
    // -------------------------------
    const editModal = document.getElementById('editModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            if (!button) return;
            document.getElementById('editLinkId').value    = button.getAttribute('data-id');
            document.getElementById('editTitolo').value    = button.getAttribute('data-titolo');
            document.getElementById('editUrl').value       = button.getAttribute('data-url');
            document.getElementById('editDescrizione').value = button.getAttribute('data-descrizione');
            document.getElementById('editOrdine').value    = button.getAttribute('data-ordine');
        });
    }

    // -------------------------------
    // FILTRI TABELLA LINK
    // -------------------------------
    function initializeSearchFilters() {
        const table = document.getElementById('linksTable');
        if (!table || table.dataset.linksInit === '1') return;
        table.dataset.linksInit = '1';

        const searchTitoloInput  = document.getElementById('searchTitolo');
        const filterGiornoSelect = document.getElementById('filterGiorno');
        const filterMeseSelect   = document.getElementById('filterMese');
        const filterAnnoSelect   = document.getElementById('filterAnno');

        // Popola anni
        if (filterAnnoSelect && filterAnnoSelect.options.length <= 1) {
            const annoAttuale = new Date().getFullYear();
            for (let anno = annoAttuale; anno >= annoAttuale - 100; anno--) {
                const option = document.createElement('option');
                option.value = anno;
                option.textContent = anno;
                filterAnnoSelect.appendChild(option);
            }
        }

        function filterTable() {
            const searchTitolo = searchTitoloInput.value.toLowerCase().trim();
            const filterGiorno = filterGiornoSelect.value;
            const filterMese   = filterMeseSelect.value;
            const filterAnno   = filterAnnoSelect.value;
            const rows = table.querySelector('tbody').querySelectorAll('tr');

            for (let row of rows) {
                if (row.cells.length < 6) continue;

                const titoloCell          = row.cells[1].textContent.toLowerCase();
                const dataEliminazioneCell = row.cells[5].textContent.trim();

                let matchTitolo = !searchTitolo || titoloCell.includes(searchTitolo);
                let matchData   = true;

                if (filterGiorno || filterMese || filterAnno) {
                    if (dataEliminazioneCell !== '') {
                        const [day, month, year] = dataEliminazioneCell.split('/');
                        let dayMatch   = !filterGiorno || day   === filterGiorno;
                        let monthMatch = !filterMese   || month === filterMese;
                        let yearMatch  = !filterAnno   || ('20' + year) === filterAnno;
                        matchData = dayMatch && monthMatch && yearMatch;
                    } else {
                        matchData = false;
                    }
                }

                row.style.display = (matchTitolo && matchData) ? '' : 'none';
            }
        }

        searchTitoloInput?.addEventListener('keyup',   filterTable);
        filterGiornoSelect?.addEventListener('change', filterTable);
        filterMeseSelect?.addEventListener('change',   filterTable);
        filterAnnoSelect?.addEventListener('change',   filterTable);

        const resetButton = document.getElementById('resetFilters');
        if (resetButton && !resetButton.dataset.resetBound) {
            resetButton.dataset.resetBound = '1';
            resetButton.addEventListener('click', function () {
                searchTitoloInput.value  = '';
                filterGiornoSelect.value = '';
                filterMeseSelect.value   = '';
                filterAnnoSelect.value   = '';
                filterTable();
            });
        }
    }

    // -------------------------------
    // AGGIORNA TABELLA VIA AJAX
    // -------------------------------
    async function aggiornaTabella() {
        try {
            const response = await fetch(window.location.href.split('?')[0] + '?get_table=1');
            const html     = await response.text();
            const temp     = document.createElement('div');
            temp.innerHTML = html;

            const vecchioTbody = document.querySelector('#linksTable tbody');
            const nuovoTbody   = temp.querySelector('#linksTable tbody');

            if (vecchioTbody) {
                if (nuovoTbody) {
                    // Server returned a full table fragment
                    vecchioTbody.innerHTML = nuovoTbody.innerHTML;
                } else {
                    // Server returned only the rows (<tr>...)
                    vecchioTbody.innerHTML = html; 
                }

                document.querySelector('#linksTable')?.classList.add('table-update');
                setTimeout(() => {
                    document.querySelector('#linksTable')?.classList.remove('table-update');
                }, 500);

                const tableEl = document.querySelector('#linksTable');
                const oldInit = tableEl?.dataset.linksInit;
                if (oldInit) delete tableEl.dataset.linksInit;
                initializeSearchFilters();
                if (oldInit) tableEl.dataset.linksInit = oldInit;
            }
        } catch (error) {
            console.error('Errore:', error);
            window.location.reload();
        }
    }

    // -------------------------------
    // MESSAGGI ALERT 
    // -------------------------------
    function mostraMessaggio(messaggio, tipo) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${tipo} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3`;
        alertDiv.style.zIndex    = '9999';
        alertDiv.style.minWidth  = '300px';
        alertDiv.innerHTML = `${messaggio}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
        document.body.appendChild(alertDiv);
        setTimeout(() => alertDiv.remove(), 3000);
    }

    // -------------------------------
    // SUBMIT MODALE MODIFICA (AJAX)
    // -------------------------------
    const editForm = document.getElementById('editForm');
    if (editForm) {
        // validate n_ordine: must be empty or integer >= 1
        function validateOrderValue(val) {
            if (val === null || val === undefined || val === '') return true;
            const n = Number(val);
            return Number.isInteger(n) && n >= 1;
        }

        editForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            const ordineInput = document.getElementById('editOrdine');
            if (ordineInput && !validateOrderValue(ordineInput.value)) {
                mostraMessaggio('Il campo Ordine deve essere un numero intero >= 1', 'warning');
                return;
            }
            const submitBtn   = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;

            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Salvataggio...';
            submitBtn.disabled  = true;

            const formData = new FormData(this);
            formData.append('ajax', '1');

            try {
                const response = await fetch(window.location.href, {
                    method:  'POST',
                    body:    formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const result = await response.json();

                if (result.success) {
                    await aggiornaTabella();
                    bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();
                    mostraMessaggio(' Modifica salvata con successo!', 'success');
                } else {
                    throw new Error();
                }
            } catch {
                mostraMessaggio(' Errore durante il salvataggio', 'danger');
            } finally {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled  = false;
            }
        });
    }

    // -------------------------------
    // SUBMIT AGGIUNTA LINK (AJAX)
    // -------------------------------
    const addLinkForm = document.getElementById('addLinkForm');
    if (addLinkForm) {
        addLinkForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            const ordineInput = this.querySelector('input[name="n_ordine"]');
            if (ordineInput && !validateOrderValue(ordineInput.value)) {
                mostraMessaggio('Il campo Ordine deve essere un numero intero >= 1', 'warning');
                return;
            }
            const submitBtn    = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;

            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Aggiunta...';
            submitBtn.disabled  = true;

            const formData = new FormData(this);
            formData.append('ajax', '1');

            try {
                const response = await fetch(window.location.href, {
                    method:  'POST',
                    body:    formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const result = await response.json();

                if (result.success) {
                    await aggiornaTabella();
                    this.reset();
                    mostraMessaggio(' Link aggiunto con successo!', 'success');
                } else {
                    throw new Error();
                }
            } catch {
                mostraMessaggio(' Errore durante l\'aggiunta', 'danger');
            } finally {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled  = false;
            }
        });
    }

    // -------------------------------
    // CLICK MENU
    // -------------------------------
    document.querySelectorAll('.nav-link').forEach(function (link) {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
            this.classList.add('active');

            const linkText = this.querySelector('.link-text').textContent.trim();
            breadcrumbCurrent.textContent = linkText;

            if (linkText === 'Link Utili') {
                linksContent.style.display = 'block';
                dynamicContent.innerHTML   = '';
                initializeSearchFilters();
            } else if (sectionsHTML[linkText]) {
                linksContent.style.display = 'none';
                dynamicContent.innerHTML   = sectionsHTML[linkText];
            }

            if (window.innerWidth <= 992) sidebarToggle.checked = false;
        });
    });

    // -------------------------------
    // AUTO-OPEN LINK UTILI
    // -------------------------------
    if (showLinks) {
        const utiliLink = Array.from(document.querySelectorAll('.nav-link'))
            .find(link => link.querySelector('.link-text').textContent.trim() === 'Link Utili');
        if (utiliLink) utiliLink.click();
    } else {
        linksContent.style.display  = 'none';
        dynamicContent.innerHTML    = sectionsHTML['Scuola'];
        breadcrumbCurrent.textContent = 'Scuola';
        const scuolaLink = Array.from(document.querySelectorAll('.nav-link'))
            .find(link => link.querySelector('.link-text').textContent.trim() === 'Scuola');
        if (scuolaLink) scuolaLink.classList.add('active');
    }
});

// -------------------------------
// PREVIEW MODALE (descrizioni + immagini)
// -------------------------------
document.addEventListener('click', function (e) {
    const desc = e.target.closest('.desc-preview');
    if (desc) {
        const full = desc.getAttribute('data-full') || '';
        let modalEl = document.getElementById('previewModal');
        if (!modalEl) {
            modalEl = document.createElement('div');
            modalEl.innerHTML = `
<div class="modal fade" id="previewModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Anteprima</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="word-break:break-word;"></div>
    </div>
  </div>
</div>`;
            document.body.appendChild(modalEl);
            modalEl = document.getElementById('previewModal');
        }
        modalEl.querySelector('.modal-title').textContent = 'Descrizione completa';
        modalEl.querySelector('.modal-body').innerHTML    = '<p>' + full.replace(/\n/g, '<br>') + '</p>';
        new bootstrap.Modal(modalEl).show();
        return;
    }

    const img = e.target.closest('.img-preview');
    if (img) {
        const src = img.getAttribute('data-full-src') || img.src;
        let modalEl = document.getElementById('previewModal');
        if (!modalEl) {
            modalEl = document.createElement('div');
            modalEl.innerHTML = `
<div class="modal fade" id="previewModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Anteprima</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="word-break:break-word;"></div>
    </div>
  </div>
</div>`;
            document.body.appendChild(modalEl);
            modalEl = document.getElementById('previewModal');
        }
        modalEl.querySelector('.modal-title').textContent = 'Foto';
        modalEl.querySelector('.modal-body').innerHTML    =
            `<div class="text-center"><img src="${src}" style="max-width:100%; height:auto; border-radius:6px;"></div>`;
        new bootstrap.Modal(modalEl).show();
        return;
    }
});
