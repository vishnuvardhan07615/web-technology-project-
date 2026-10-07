<?php
/**
 * ============================================================================
 * MotoCare – Two-Wheeler Service & Mechanic Shop Management System
 * File: includes/header.php
 * Stage 2: Public Website Header Template
 * ============================================================================
 */

// Load core dependencies
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/auth.php';

// Set default page variables if not set by parent page
$pageTitle = $pageTitle ?? 'MotoCare – Two-Wheeler Service & Mechanic Shop Management System';
$pageDescription = $pageDescription ?? 'Professional Two-Wheeler Service & Mechanic Shop Management System. Expert repairs for motorcycles, scooters, and electric two-wheelers.';
$currentPage = $currentPage ?? basename($_SERVER['PHP_SELF']);

// Determine relative path to root for assets (css, js, images)
$rootPath = './';
if (strpos($_SERVER['PHP_SELF'], '/customer/') !== false || 
    strpos($_SERVER['PHP_SELF'], '/admin/') !== false || 
    strpos($_SERVER['PHP_SELF'], '/mechanic/') !== false) {
    $rootPath = '../';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="<?php echo sanitizeInput($pageDescription); ?>">
  <meta name="keywords" content="bike service, motorcycle repair, scooter mechanic, two wheeler service, EV bike repair, MotoCare">
  <meta name="theme-color" content="#090d16">
  <title><?php echo sanitizeInput($pageTitle); ?></title>

  <!-- Google Fonts: Outfit & Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700;800;900&display=swap" rel="stylesheet">

  <!-- Favicon -->
  <link rel="icon" type="image/svg+xml" href="<?php echo $rootPath; ?>images/logo/favicon.svg">

  <!-- Core Stylesheet -->
  <link rel="stylesheet" href="<?php echo $rootPath; ?>css/style.css">
</head>
<body>

  <!-- ========================================================================
       Header & Navigation Bar
       ======================================================================== -->
  <header class="site-header" id="siteHeader">
    <div class="container">
      <nav class="navbar" aria-label="Main Navigation">
        <!-- Logo -->
        <a href="<?php echo $rootPath; ?>index.php" class="brand-logo" title="MotoCare Home">
          <img src="<?php echo $rootPath; ?>images/logo/logo.svg" alt="MotoCare Logo" width="180" height="42">
        </a>

        <!-- Desktop Navigation Menu -->
        <?php $bookServiceUrl = getBookServiceUrl($rootPath); ?>
        <ul class="nav-menu" id="primaryNavMenu">
          <li><a href="<?php echo $rootPath; ?>index.php" class="nav-link <?php echo ($currentPage === 'index.php') ? 'active' : ''; ?>">Home</a></li>
          <li><a href="<?php echo $rootPath; ?>about.php" class="nav-link <?php echo ($currentPage === 'about.php') ? 'active' : ''; ?>">About</a></li>
          <li><a href="<?php echo $rootPath; ?>services.php" class="nav-link <?php echo ($currentPage === 'services.php') ? 'active' : ''; ?>">Services</a></li>
          <li><a href="<?php echo $bookServiceUrl; ?>" class="nav-link <?php echo ($currentPage === 'booking.php' || $currentPage === 'book-service.php') ? 'active' : ''; ?>">Book Service</a></li>
          <li><a href="<?php echo $rootPath; ?>contact.php" class="nav-link <?php echo ($currentPage === 'contact.php') ? 'active' : ''; ?>">Contact</a></li>
          
          <?php if (isCustomerLoggedIn()): ?>
            <li><a href="<?php echo $rootPath; ?>customer/dashboard.php" class="nav-link text-orange">Dashboard</a></li>
            <li><a href="<?php echo $rootPath; ?>portal-login.php" class="nav-link <?php echo ($currentPage === 'portal-login.php') ? 'active' : ''; ?>">Portal Login</a></li>
          <?php elseif (isAdminLoggedIn()): ?>
            <li><a href="<?php echo $rootPath; ?>admin/dashboard.php" class="nav-link" style="color: #f87171;">Admin Panel</a></li>
            <li><a href="<?php echo $rootPath; ?>portal-login.php" class="nav-link <?php echo ($currentPage === 'portal-login.php') ? 'active' : ''; ?>">Portal Login</a></li>
          <?php elseif (isMechanicLoggedIn()): ?>
            <li><a href="<?php echo $rootPath; ?>mechanic/dashboard.php" class="nav-link" style="color: var(--color-green);">Workshop Bay</a></li>
            <li><a href="<?php echo $rootPath; ?>portal-login.php" class="nav-link <?php echo ($currentPage === 'portal-login.php') ? 'active' : ''; ?>">Portal Login</a></li>
          <?php else: ?>
            <li><a href="<?php echo $rootPath; ?>portal-login.php" class="nav-link <?php echo ($currentPage === 'portal-login.php') ? 'active' : ''; ?>" style="color: var(--color-blue);">Portal Login</a></li>
          <?php endif; ?>

          <li class="mobile-cta">
            <a href="<?php echo $bookServiceUrl; ?>" class="btn btn-primary btn-block">
              <span>Book a Service</span>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"></path><path d="m12 5 7 7-7 7"></path></svg>
            </a>
          </li>
        </ul>

        <!-- Navigation Actions -->
        <div class="nav-actions">
          <?php if (isCustomerLoggedIn()): ?>
            <a href="<?php echo $rootPath; ?>customer/dashboard.php" class="btn btn-secondary btn-sm" style="display: flex; align-items: center; gap: 0.4rem;">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
              <span><?php echo htmlspecialchars(explode(' ', getCurrentCustomer()['name'])[0]); ?></span>
            </a>
          <?php elseif (isAdminLoggedIn()): ?>
            <a href="<?php echo $rootPath; ?>admin/dashboard.php" class="btn btn-secondary btn-sm" style="display: flex; align-items: center; gap: 0.4rem; border-color: rgba(239,68,68,0.4);">
              <span class="pulse-dot" style="background-color: #ef4444;"></span>
              <span>Admin</span>
            </a>
          <?php elseif (isMechanicLoggedIn()): ?>
            <a href="<?php echo $rootPath; ?>mechanic/dashboard.php" class="btn btn-secondary btn-sm" style="display: flex; align-items: center; gap: 0.4rem; border-color: rgba(16,185,129,0.4);">
              <span class="pulse-dot" style="background-color: var(--color-green);"></span>
              <span>Technician</span>
            </a>
          <?php endif; ?>

          <a href="<?php echo $bookServiceUrl; ?>" class="btn btn-primary btn-desktop">
            <span>Book a Service</span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"></path><path d="m12 5 7 7-7 7"></path></svg>
          </a>

          <!-- Mobile Toggle Button -->
          <button class="mobile-toggle" id="mobileMenuToggle" aria-label="Toggle navigation menu" aria-expanded="false" aria-controls="primaryNavMenu">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <line x1="3" y1="12" x2="21" y2="12"></line>
              <line x1="3" y1="6" x2="21" y2="6"></line>
              <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
          </button>
        </div>
      </nav>
    </div>
  </header>

  <main id="mainContent">
