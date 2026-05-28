<?php
// La sessione è già aperta da index.php tramite controlloSessione.php
$ruoloUtente = $_SESSION['ruoloUtente'] ?? '';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-semibold mb-0">
        <i class="bi bi-link-45deg me-2 text-secondary"></i>Link
    </h5>
    <button class="btn btn-primary d-flex align-items-center gap-2"
            data-bs-toggle="modal" data-bs-target="#modalAggiungiLink">
        <i class="bi bi-plus-circle"></i> Aggiungi link
    </button>
</div>

<div class="content-grid">
    <div class="grid-full">
        <div class="card-panel">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Titolo</th>
                            <th>URL</th>
                            <th>Categoria</th>
                            <th class="text-center">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="4" class="text-center text-secondary py-4">
                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                Nessun link presente.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Aggiungi Link -->
<div class="modal fade" id="modalAggiungiLink" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow">
            <div class="modal-header bg-light border-0">
                <h6 class="modal-title fw-bold">
                    <i class="bi bi-plus-circle-fill me-2"></i>Nuovo link
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
            </div>
            <form method="POST" action="index.php?page=link">
                <input type="hidden" name="page" value="link">
                <input type="hidden" name="azione" value="aggiungi">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Titolo <span class="text-danger">*</span></label>
                        <input type="text" name="titolo" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">URL <span class="text-danger">*</span></label>
                        <input type="url" name="url" class="form-control" placeholder="https://" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Categoria</label>
                        <input type="text" name="categoria" class="form-control">
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-floppy me-1"></i>Salva
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
