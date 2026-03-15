<?php
// header_anggota.php
// dipanggil oleh semua halaman anggota supaya struktur HTML dan navbar konsisten
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current_page = $_GET['halaman'] ?? 'dashboard';

// Menu items for anggota
$menuItems = [
    ['href' => 'dashboard.php', 'icon' => 'fas fa-home', 'label' => 'Dashboard', 'active' => ''],
    ['href' => '?halaman=history', 'icon' => 'fas fa-history', 'label' => 'History Peminjaman', 'active' => 'history'],
    ['href' => 'pembayaran.php', 'icon' => 'fas fa-money-bill-wave', 'label' => 'Pembayaran Denda', 'active' => 'pembayaran'],
];

$userName = $_SESSION['nama_anggota'] ?? 'Anggota';
$userRole = 'Anggota';
$logoutUrl = 'logout.php';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? $pageTitle : 'Perpustakaan SMKN 8 PANDEGLANG'; ?></title>
    <link rel="icon" type="image/png" href="../favicon.png">
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php
    // additional stylesheets if provided
    if (!empty($extraCss) && is_array($extraCss)) {
        foreach ($extraCss as $cssFile) {
            echo "    <link rel=\"stylesheet\" href=\"{$cssFile}\">\n";
        }
    }
    ?>
</head>

<body>
    <!-- Toggle Button (for mobile) -->
    <button class="sidebar-toggle-btn" id="sidebarToggle">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay active" id="sidebarOverlay"></div>

    <!-- Sidebar - active by default so it's always visible -->
    <nav class="sidebar-nav active" id="sidebar">
        <!-- Toggle Button (Inside) - for collapse -->
        <button class="sidebar-collapse-btn" id="sidebarCollapseBtn">
            <i class="fas fa-chevron-left"></i>
        </button>

        <!-- Logo Area -->
        <div class="sidebar-header">
            <div class="sidebar-brand">
                <div class="brand-icon">
                    <i class="fas fa-book-reader"></i>
                </div>
                <div class="brand-text">
                    <span class="brand-title">Perpustakaan SMKN 8 PANDEGLANG</span>
                    <span class="brand-subtitle">Anggota</span>
                </div>
            </div>
        </div>

        <!-- Search -->
        <div class="sidebar-search">
            <div class="search-wrapper">
                <i class="fas fa-search search-icon"></i>
                <input type="text" class="search-input" placeholder="Search...">
                <span class="search-shortcut">⌘K</span>
            </div>
        </div>

        <!-- Navigation Menu -->
        <div class="sidebar-menu-wrapper">
            <ul class="sidebar-menu">
                <?php foreach ($menuItems as $item): ?>
                    <li>
                        <a href="<?= $item['href']; ?>"
                            class="<?= ($current_page == $item['active'] || ($current_page == '' && $item['active'] == '')) ? 'active' : ''; ?>"
                            data-tooltip="<?= $item['label']; ?>">
                            <i class="<?= $item['icon']; ?>"></i>
                            <span class="menu-text"><?= $item['label']; ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- User Profile -->
        <div class="sidebar-user">
            <div class="user-info">
                <div class="user-avatar">
                    <?= strtoupper(substr($userName, 0, 1)); ?>
                </div>
                <div class="user-details">
                    <span class="user-name"><?= htmlspecialchars($userName); ?></span>
                    <span class="user-role"><?= $userRole; ?></span>
                </div>
                <a href="<?= $logoutUrl; ?>" class="logout-btn" id="btnLogout" data-tooltip="Logout">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content shifted" id="mainContent">
        <div class="container mt-4">