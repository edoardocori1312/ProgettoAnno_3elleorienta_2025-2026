<aside class="sidebar">

    <!-- Logo + toggle collapse (desktop) -->
    <div class="logo">
        <div class="d-flex align-items-center gap-2">
            <img src="logo.png" width="32" height="32" alt="Logo 3elleorienta" style="border-radius:6px; object-fit:contain; flex-shrink:0;">
            <span class="logo-text">3elleorienta</span>
        </div>
        <label class="menu-toggle-label" for="sidebar-toggle" title="Comprimi menu">
            <i class="bi bi-layout-sidebar-reverse"></i>
        </label>
    </div>

    <nav class="flex-grow-1 mt-2">

        <!-- Sezione principale (tutti i ruoli) -->
        <div class="nav-group">

            <a href="index.php?page=scuole"
               class="nav-link <?= ($page === 'scuole' || $page === 'scuola_modifica') ? 'active' : '' ?>">
                <i class="bi bi-backpack-fill"></i>
                <span class="link-text">Scuole</span>
            </a>

            <a href="index.php?page=eventi"
               class="nav-link <?= $page === 'eventi' ? 'active' : '' ?>">
                <i class="bi bi-calendar-event-fill"></i>
                <span class="link-text">Eventi</span>
            </a>
        </div>

        <?php if (($_SESSION['ruoloUtente'] ?? '') === 'ADMIN'): ?>
        <!-- Sezione admin -->
        <div class="nav-group">

            <a href="index.php?page=zona"
               class="nav-link <?= $page === 'zona' ? 'active' : '' ?>">
                <i class="bi bi-geo-fill"></i>
                <span class="link-text">Zone</span>
            </a>

            <a href="index.php?page=utenti"
               class="nav-link <?= $page === 'utenti' ? 'active' : '' ?>">
                <i class="bi bi-people-fill"></i>
                <span class="link-text">Utenti</span>
            </a>

            <a href="index.php?page=progetti"
               class="nav-link <?= $page === 'progetti' ? 'active' : '' ?>">
                <i class="bi bi-grid-1x2-fill"></i>
                <span class="link-text">Progetti</span>
            </a>
			
			<a href="index.php?page=links"
               class="nav-link <?= $page === 'links' ? 'active' : '' ?>">
                <i class="bi bi-link-45deg"></i>
                <span class="link-text">Link utili</span>
            </a>
        </div>
        <?php endif; ?>

        <!-- Sezione account -->
        <div class="nav-group mt-auto">

            <a href="index.php?page=impostazioni"
               class="nav-link <?= $page === 'impostazioni' ? 'active' : '' ?>">
                <i class="bi bi-gear-fill"></i>
                <span class="link-text">Impostazioni</span>
            </a>

            <a href="logout.php" class="nav-link text-danger">
                <i class="bi bi-box-arrow-right"></i>
                <span class="link-text">Logout</span>
            </a>
        </div>

    </nav>

</aside>
