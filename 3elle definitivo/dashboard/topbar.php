<header class="top-bar">

    <div class="d-flex align-items-center gap-3">
        <!-- Hamburger mobile -->
        <label class="hamburger-label" for="sidebar-toggle" aria-label="Apri menu">
            <i class="bi bi-list"></i>
        </label>

        <!-- Breadcrumb dinamico -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="index.php" class="text-decoration-none text-secondary">Dashboard</a>
                </li>
                <?php if ($page === 'scuola_modifica'): ?>
                    <li class="breadcrumb-item">
                        <a href="index.php?page=scuole" class="text-decoration-none text-secondary">Scuole</a>
                    </li>
                <?php endif; ?>
                <li class="breadcrumb-item active" aria-current="page">
                    <?= htmlspecialchars($pageTitle) ?>
                </li>
            </ol>
        </nav>
    </div>

    <!-- Info utente -->
    <div class="user-info d-flex align-items-center gap-2">
        <i class="bi bi-person-circle fs-5 text-secondary"></i>
        <span class="fw-semibold" style="font-size:0.88rem;">
            <?= htmlspecialchars($_SESSION['usernameUtente'] ?? 'Utente') ?>
        </span>
        <span class="text-secondary">|</span>
        <a href="logout.php" class="text-danger text-decoration-none" style="font-size:0.88rem;">
            <i class="bi bi-box-arrow-right me-1"></i>Logout
        </a>
    </div>

</header>
