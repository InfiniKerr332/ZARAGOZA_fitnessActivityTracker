<?php
if (session_status() === PHP_SESSION_NONE) session_start();
ob_start();
require_once __DIR__ . '/functions.php';

$currentUser = getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$isUserSection = strpos($_SERVER['REQUEST_URI'], '/user/') !== false;
$isAdminSection = strpos($_SERVER['REQUEST_URI'], '/admin/') !== false;
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="FitTrack Pro - Your premium fitness tracking companion. Track workouts, nutrition, and body metrics with advanced analytics.">
    <title><?= $pageTitle ?? 'FitTrack Pro' ?> | <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">
    <script src="<?= BASE_URL ?>/assets/js/lucide.min.js"></script>
    <?php if (isset($needsChart) && $needsChart): ?>
    <script src="<?= BASE_URL ?>/assets/js/chart.umd.min.js"></script>
    <?php endif; ?>
</head>
<body class="<?= ($isUserSection || $isAdminSection) ? 'has-sidebar' : '' ?>">

<?php if ($isUserSection || $isAdminSection): ?>
<!-- Sidebar Navigation -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="<?= BASE_URL ?>/" class="sidebar-logo" style="display:flex; align-items:center; gap:12px;">
            <div class="logo-icon" style="background:transparent; padding:0; width:48px; height:48px; overflow:hidden; flex-shrink:0;">
                <img src="<?= BASE_URL ?>/assets/image/logo21.png" alt="FitTrack Pro" style="width:100%; height:100%; object-fit:cover; border-radius:50%; display:block;">
            </div>
            <span class="logo-text" style="font-size: 20px; font-weight: 700;"><?= APP_NAME ?></span>
        </a>
        <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
            <i data-lucide="panel-left-close"></i>
        </button>
    </div>

    <nav class="sidebar-nav">
        <?php if ($isAdminSection): ?>
        <div class="nav-section">
            <span class="nav-section-title">Admin Panel</span>
            <a href="<?= BASE_URL ?>/admin/dashboard.php" class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                <i data-lucide="layout-dashboard"></i><span>Dashboard</span>
            </a>
            <a href="<?= BASE_URL ?>/admin/users.php" class="nav-link <?= $currentPage === 'users' ? 'active' : '' ?>">
                <i data-lucide="users"></i><span>Users</span>
            </a>
            <a href="<?= BASE_URL ?>/admin/categories.php" class="nav-link <?= $currentPage === 'categories' ? 'active' : '' ?>">
                <i data-lucide="folder"></i><span>Categories</span>
            </a>
            <a href="<?= BASE_URL ?>/admin/exercises.php" class="nav-link <?= $currentPage === 'exercises' ? 'active' : '' ?>">
                <i data-lucide="dumbbell"></i><span>Exercises</span>
            </a>
            <a href="<?= BASE_URL ?>/admin/reports.php" class="nav-link <?= $currentPage === 'reports' ? 'active' : '' ?>">
                <i data-lucide="bar-chart-3"></i><span>Reports</span>
            </a>
            <a href="<?= BASE_URL ?>/admin/system-logs.php" class="nav-link <?= $currentPage === 'system-logs' ? 'active' : '' ?>">
                <i data-lucide="scroll-text"></i><span>System Logs</span>
            </a>
            <a href="<?= BASE_URL ?>/admin/delete-logs.php" class="nav-link <?= $currentPage === 'delete-logs' ? 'active' : '' ?>">
                <i data-lucide="trash-2"></i><span>Delete Logs</span>
            </a>
        </div>
        <?php else: ?>
        <div class="nav-section">
            <span class="nav-section-title">Overview</span>
            <a href="<?= BASE_URL ?>/user/dashboard.php" class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                <i data-lucide="layout-dashboard"></i><span>Dashboard</span>
            </a>
        </div>
        <div class="nav-section">
            <span class="nav-section-title">Tracking</span>
            <a href="<?= BASE_URL ?>/user/activities.php" class="nav-link <?= $currentPage === 'activities' ? 'active' : '' ?>">
                <i data-lucide="flame"></i><span>Activities</span>
            </a>
            <a href="<?= BASE_URL ?>/user/nutrition.php" class="nav-link <?= $currentPage === 'nutrition' ? 'active' : '' ?>">
                <i data-lucide="apple"></i><span>Nutrition</span>
            </a>
            <a href="<?= BASE_URL ?>/user/body-metrics.php" class="nav-link <?= $currentPage === 'body-metrics' ? 'active' : '' ?>">
                <i data-lucide="ruler"></i><span>Body Metrics</span>
            </a>
            <a href="<?= BASE_URL ?>/user/goals.php" class="nav-link <?= $currentPage === 'goals' ? 'active' : '' ?>">
                <i data-lucide="target"></i><span>Goals</span>
            </a>
        </div>
        <div class="nav-section">
            <span class="nav-section-title">Insights</span>
            <a href="<?= BASE_URL ?>/user/reports.php" class="nav-link <?= $currentPage === 'reports' ? 'active' : '' ?>">
                <i data-lucide="bar-chart-3"></i><span>Reports</span>
            </a>
        </div>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
        <?php if (isAdmin() && $isUserSection): ?>
        <a href="<?= BASE_URL ?>/admin/dashboard.php" class="nav-link">
            <i data-lucide="shield"></i><span>Admin Panel</span>
        </a>
        <?php elseif (isAdmin() && $isAdminSection): ?>
        <a href="<?= BASE_URL ?>/user/dashboard.php" class="nav-link">
            <i data-lucide="user"></i><span>User View</span>
        </a>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>/user/profile.php" class="nav-link <?= $currentPage === 'profile' ? 'active' : '' ?>">
            <i data-lucide="settings"></i><span>Profile</span>
        </a>
        <a href="<?= BASE_URL ?>/auth/logout.php" class="nav-link nav-link-danger">
            <i data-lucide="log-out"></i><span>Log Out</span>
        </a>
    </div>
</aside>

<!-- Main Content Area -->
<main class="main-content">
    <!-- Top Bar -->
    <header class="topbar">
        <button class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Open menu">
            <i data-lucide="menu"></i>
        </button>
        <div class="topbar-left">
            <h1 class="page-title"><?= $pageTitle ?? 'Dashboard' ?></h1>
        </div>
        <div class="topbar-right">
            <div class="user-menu" id="userMenu">
                <button class="user-menu-btn" id="userMenuBtn">
                    <div class="user-avatar">
                        <?= strtoupper(substr($currentUser['first_name'] ?? $currentUser['username'] ?? 'U', 0, 1)) ?>
                    </div>
                    <span class="user-name"><?= htmlspecialchars($currentUser['first_name'] ?? $currentUser['username'] ?? 'User') ?></span>
                    <i data-lucide="chevron-down" class="user-chevron"></i>
                </button>
                <div class="user-dropdown" id="userDropdown">
                    <div class="dropdown-header">
                        <strong><?= htmlspecialchars(($currentUser['first_name'] ?? '') . ' ' . ($currentUser['last_name'] ?? '')) ?></strong>
                        <span><?= htmlspecialchars($currentUser['email'] ?? '') ?></span>
                    </div>
                    <div class="dropdown-divider"></div>
                    <a href="<?= BASE_URL ?>/user/profile.php" class="dropdown-item"><i data-lucide="user"></i> Profile</a>
                    <a href="<?= BASE_URL ?>/auth/logout.php" class="dropdown-item dropdown-item-danger"><i data-lucide="log-out"></i> Log Out</a>
                </div>
            </div>
        </div>
    </header>
    
    <div class="content-area">
        <?= displayFlashMessages() ?>
<?php else: ?>
<!-- Public pages (landing, auth) -->
<?php endif; ?>
