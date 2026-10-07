<?php
/**
 * ============================================================================
 * MotoCare – Two-Wheeler Service & Mechanic Shop Management System
 * File: includes/admin-header.php
 * Stage 2: Admin Control Center Header Template
 * ============================================================================
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/auth.php';

$pageTitle = $pageTitle ?? 'Admin Control Center – MotoCare';
$currentPage = $currentPage ?? basename($_SERVER['PHP_SELF']);
$admin = getCurrentAdmin();
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
  <style>
    .admin-subnav {
      background-color: var(--bg-surface);
      border-bottom: 1px solid var(--border-subtle);
      padding: 0.6rem 0;
      overflow-x: auto;
    }
    .admin-subnav-links {
      display: flex;
      align-items: center;
      gap: 1.5rem;
      white-space: nowrap;
    }
    .admin-subnav-links a {
      font-size: 0.85rem;
      font-weight: 500;
      color: var(--text-muted);
      padding: 0.35rem 0.65rem;
      border-radius: var(--radius-sm);
    }
    .admin-subnav-links a:hover, .admin-subnav-links a.active {
      color: #ffffff;
      background-color: var(--bg-card);
    }
    .admin-subnav-links a.active {
      color: var(--accent-orange);
      font-weight: 600;
    }
  </style>
</head>
<body>

  <header class="site-header" id="siteHeader">
    <div class="container">
      <nav class="navbar" aria-label="Admin Navigation">
        <a href="<?php echo $rootPath; ?>admin/dashboard.php" class="brand-logo" title="MotoCare Admin Dashboard">
          <img src="<?php echo $rootPath; ?>images/logo/logo.svg" alt="MotoCare Logo" width="160" height="38">
          <span class="service-tag" style="background: rgba(239,68,68,0.15); color: #f87171; border-color: rgba(239,68,68,0.3); font-size: 0.7rem;">Admin Panel</span>
        </a>

        <ul class="nav-menu" id="primaryNavMenu">
          <li><a href="<?php echo $rootPath; ?>admin/dashboard.php" class="nav-link <?php echo ($currentPage === 'dashboard.php') ? 'active' : ''; ?>">Dashboard</a></li>
          <li><a href="<?php echo $rootPath; ?>admin/bookings.php" class="nav-link <?php echo ($currentPage === 'bookings.php' || $currentPage === 'booking-details.php') ? 'active' : ''; ?>">Bookings</a></li>
          <li><a href="<?php echo $rootPath; ?>admin/mechanics.php" class="nav-link <?php echo ($currentPage === 'mechanics.php') ? 'active' : ''; ?>">Mechanics</a></li>
          <li><a href="<?php echo $rootPath; ?>admin/spare-parts.php" class="nav-link <?php echo ($currentPage === 'spare-parts.php') ? 'active' : ''; ?>">Spare Parts</a></li>
          <li><a href="<?php echo $rootPath; ?>admin/bills.php" class="nav-link <?php echo ($currentPage === 'bills.php' || $currentPage === 'bill-details.php') ? 'active' : ''; ?>">Bills</a></li>
          <li><a href="<?php echo $rootPath; ?>admin/payments.php" class="nav-link <?php echo ($currentPage === 'payments.php' || $currentPage === 'record-payment.php') ? 'active' : ''; ?>">Payments</a></li>
          <li><a href="<?php echo $rootPath; ?>admin/feedback.php" class="nav-link <?php echo ($currentPage === 'feedback.php' || $currentPage === 'feedback-details.php') ? 'active' : ''; ?>">Feedback</a></li>
          <li class="mobile-cta">
            <a href="<?php echo $rootPath; ?>admin/logout.php" class="btn btn-secondary btn-block">Logout (<?php echo htmlspecialchars($admin['username'] ?? 'Admin'); ?>)</a>
          </li>
        </ul>

        <div class="nav-actions">
          <div style="display: flex; align-items: center; gap: 0.75rem;">
            <span style="font-size: 0.85rem; color: var(--text-muted); display: flex; align-items: center; gap: 0.4rem;">
              <span class="pulse-dot" style="background-color: var(--color-green);"></span>
              <strong style="color: #ffffff;"><?php echo htmlspecialchars($admin['username'] ?? 'Admin'); ?></strong>
            </span>
            <a href="<?php echo $rootPath; ?>admin/logout.php" class="btn btn-outline btn-sm btn-desktop">Logout</a>
            <a href="<?php echo $rootPath; ?>index.php" class="btn btn-secondary btn-sm btn-desktop" title="Preview public website">Public Site</a>
          </div>

          <button class="mobile-toggle" id="mobileMenuToggle" aria-label="Toggle navigation menu" aria-expanded="false" aria-controls="primaryNavMenu">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
          </button>
        </div>
      </nav>
    </div>

    <!-- Secondary Admin Navigation Bar -->
    <div class="admin-subnav">
      <div class="container">
        <div class="admin-subnav-links">
          <a href="<?php echo $rootPath; ?>admin/dashboard.php" class="<?php echo ($currentPage === 'dashboard.php') ? 'active' : ''; ?>">Overview</a>
          <a href="<?php echo $rootPath; ?>admin/bookings.php" class="<?php echo ($currentPage === 'bookings.php' || $currentPage === 'booking-details.php') ? 'active' : ''; ?>">Bookings</a>
          <a href="<?php echo $rootPath; ?>admin/mechanics.php" class="<?php echo ($currentPage === 'mechanics.php') ? 'active' : ''; ?>">Mechanics</a>
          <a href="<?php echo $rootPath; ?>admin/spare-parts.php" class="<?php echo ($currentPage === 'spare-parts.php') ? 'active' : ''; ?>">Spare Parts</a>
          <a href="<?php echo $rootPath; ?>admin/bills.php" class="<?php echo ($currentPage === 'bills.php' || $currentPage === 'bill-details.php') ? 'active' : ''; ?>">Bills</a>
          <a href="<?php echo $rootPath; ?>admin/payments.php" class="<?php echo ($currentPage === 'payments.php' || $currentPage === 'record-payment.php') ? 'active' : ''; ?>">Payments</a>
          <a href="<?php echo $rootPath; ?>admin/feedback.php" class="<?php echo ($currentPage === 'feedback.php') ? 'active' : ''; ?>">Feedback</a>
          <a href="<?php echo $rootPath; ?>admin/customers.php" class="<?php echo ($currentPage === 'customers.php') ? 'active' : ''; ?>">Customers</a>
          <a href="<?php echo $rootPath; ?>admin/vehicles.php" class="<?php echo ($currentPage === 'vehicles.php') ? 'active' : ''; ?>">Vehicles</a>
          <a href="<?php echo $rootPath; ?>admin/service-records.php" class="<?php echo ($currentPage === 'service-records.php') ? 'active' : ''; ?>">Job Cards</a>
        </div>
      </div>
    </div>
  </header>

  <main id="mainContent" style="padding-top: 2rem; padding-bottom: 4rem;">
