<?php
/**
 * ============================================================================
 * MotoCare – Two-Wheeler Service & Mechanic Shop Management System
 * File: includes/customer-header.php
 * Stage 2: Customer Portal Header & Navigation Template
 * ============================================================================
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/auth.php';

$pageTitle = $pageTitle ?? 'Customer Portal – MotoCare';
$currentPage = $currentPage ?? basename($_SERVER['PHP_SELF']);
$customer = getCurrentCustomer();
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
      <nav class="navbar" aria-label="Customer Portal Navigation">
        <a href="<?php echo $rootPath; ?>customer/dashboard.php" class="brand-logo" title="MotoCare Customer Portal">
          <img src="<?php echo $rootPath; ?>images/logo/logo.svg" alt="MotoCare Logo" width="160" height="38">
          <span class="service-tag" style="background: var(--accent-glow-subtle); color: var(--accent-orange); border-color: rgba(255,94,20,0.3); font-size: 0.7rem;">Customer Portal</span>
        </a>

        <ul class="nav-menu" id="primaryNavMenu">
          <li><a href="<?php echo $rootPath; ?>customer/dashboard.php" class="nav-link <?php echo ($currentPage === 'dashboard.php') ? 'active' : ''; ?>">Dashboard</a></li>
          <li><a href="<?php echo $rootPath; ?>customer/bookings.php" class="nav-link <?php echo ($currentPage === 'bookings.php') ? 'active' : ''; ?>">My Bookings</a></li>
          <li><a href="<?php echo $rootPath; ?>customer/bills.php" class="nav-link <?php echo ($currentPage === 'bills.php' || $currentPage === 'bill-details.php') ? 'active' : ''; ?>">My Invoices</a></li>
          <li><a href="<?php echo $rootPath; ?>customer/feedback-history.php" class="nav-link <?php echo ($currentPage === 'feedback-history.php' || $currentPage === 'feedback.php') ? 'active' : ''; ?>">Reviews</a></li>
          <li><a href="<?php echo $rootPath; ?>customer/book-service.php" class="nav-link <?php echo ($currentPage === 'book-service.php') ? 'active' : ''; ?>">Book Service</a></li>
          <li><a href="<?php echo $rootPath; ?>customer/vehicles.php" class="nav-link <?php echo ($currentPage === 'vehicles.php' || $currentPage === 'add-vehicle.php') ? 'active' : ''; ?>">My Vehicles</a></li>
          <li><a href="<?php echo $rootPath; ?>customer/service-history.php" class="nav-link <?php echo ($currentPage === 'service-history.php') ? 'active' : ''; ?>">Service History</a></li>
          <li><a href="<?php echo $rootPath; ?>customer/profile.php" class="nav-link <?php echo ($currentPage === 'profile.php') ? 'active' : ''; ?>">Profile</a></li>
          <li class="mobile-cta">
            <a href="<?php echo $rootPath; ?>customer/logout.php" class="btn btn-secondary btn-block">Sign Out</a>
          </li>
        </ul>

        <div class="nav-actions">
          <div style="display: flex; align-items: center; gap: 0.75rem;">
            <a href="<?php echo $rootPath; ?>customer/profile.php" style="display: flex; align-items: center; gap: 0.5rem; color: #ffffff; font-size: 0.9rem;">
              <span class="customer-avatar" style="width: 34px; height: 34px; font-size: 0.85rem;"><?php echo strtoupper(substr($customer['name'] ?? 'C', 0, 1)); ?></span>
              <span class="btn-desktop" style="font-weight: 600;"><?php echo htmlspecialchars(explode(' ', $customer['name'] ?? 'Customer')[0]); ?></span>
            </a>
            <a href="<?php echo $rootPath; ?>customer/logout.php" class="btn btn-outline btn-sm btn-desktop">Sign Out</a>
            <a href="<?php echo $rootPath; ?>index.php" class="btn btn-secondary btn-sm btn-desktop" title="Visit public site">Public Site</a>
          </div>

          <button class="mobile-toggle" id="mobileMenuToggle" aria-label="Toggle navigation menu" aria-expanded="false" aria-controls="primaryNavMenu">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
          </button>
        </div>
      </nav>
    </div>
  </header>

  <main id="mainContent" style="padding-top: 2rem; padding-bottom: 4rem;">
