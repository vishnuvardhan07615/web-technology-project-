<?php
/**
 * ============================================================================
 * MotoCare – Two-Wheeler Service & Mechanic Shop Management System
 * File: includes/mechanic-header.php
 * Stage 2: Mechanic Portal Header Template
 * ============================================================================
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/auth.php';

$pageTitle = $pageTitle ?? 'Technician Portal – MotoCare';
$currentPage = $currentPage ?? basename($_SERVER['PHP_SELF']);
$mechanic = getCurrentMechanic();
$rootPath = '../';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo sanitizeInput($pageTitle); ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="icon" type="image/svg+xml" href="<?php echo $rootPath; ?>images/logo/favicon.svg">
  <link rel="stylesheet" href="<?php echo $rootPath; ?>css/style.css">
</head>
<body>

  <header class="site-header" id="siteHeader">
    <div class="container">
      <nav class="navbar" aria-label="Mechanic Navigation">
        <a href="<?php echo $rootPath; ?>mechanic/dashboard.php" class="brand-logo" title="MotoCare Mechanic Workshop Portal">
          <img src="<?php echo $rootPath; ?>images/logo/logo.svg" alt="MotoCare Logo" width="160" height="38">
          <span class="service-tag" style="background: rgba(16,185,129,0.15); color: var(--color-green); border-color: rgba(16,185,129,0.3); font-size: 0.7rem;">Technician Bay</span>
        </a>

        <ul class="nav-menu" id="primaryNavMenu">
          <li><a href="<?php echo $rootPath; ?>mechanic/dashboard.php" class="nav-link <?php echo ($currentPage === 'dashboard.php') ? 'active' : ''; ?>">Dashboard</a></li>
          <li><a href="<?php echo $rootPath; ?>mechanic/assigned-services.php" class="nav-link <?php echo ($currentPage === 'assigned-services.php') ? 'active' : ''; ?>">Assigned Services</a></li>
          <li><a href="<?php echo $rootPath; ?>mechanic/assigned-services.php" class="nav-link <?php echo ($currentPage === 'job-card.php' || $currentPage === 'service-details.php') ? 'active' : ''; ?>">Job Cards / Service Work</a></li>
          <li class="mobile-cta">
            <a href="<?php echo $rootPath; ?>mechanic/logout.php" class="btn btn-secondary btn-block">Logout (<?php echo htmlspecialchars($mechanic['name'] ?? 'Technician'); ?>)</a>
          </li>
        </ul>

        <div class="nav-actions">
          <div style="display: flex; align-items: center; gap: 0.75rem;">
            <span style="font-size: 0.85rem; color: var(--text-muted); display: flex; align-items: center; gap: 0.4rem;">
              <span class="pulse-dot" style="background-color: var(--color-green);"></span>
              <strong style="color: #ffffff;"><?php echo htmlspecialchars($mechanic['name'] ?? 'Technician'); ?></strong>
            </span>
            <a href="<?php echo $rootPath; ?>mechanic/logout.php" class="btn btn-outline btn-sm btn-desktop">Logout</a>
            <a href="<?php echo $rootPath; ?>index.php" class="btn btn-secondary btn-sm btn-desktop" title="Public site">Public Site</a>
          </div>

          <button class="mobile-toggle" id="mobileMenuToggle" aria-label="Toggle navigation menu" aria-expanded="false" aria-controls="primaryNavMenu">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
          </button>
        </div>
      </nav>
    </div>
  </header>

  <main id="mainContent" style="padding-top: 2rem; padding-bottom: 4rem;">
