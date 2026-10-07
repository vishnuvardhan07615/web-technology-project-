<?php
/**
 * ============================================================================
 * MotoCare – Two-Wheeler Service & Mechanic Shop Management System
 * File: booking.php
 * Stage 2: Public Booking Gateway & Informational Page
 * ============================================================================
 */

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/auth.php';

// Role-based routing: authenticated users are routed directly to their respective portals
if (isCustomerLoggedIn()) {
    $queryString = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
    header('Location: ' . BASE_URL . '/customer/book-service.php' . $queryString);
    exit;
} elseif (isAdminLoggedIn()) {
    header('Location: ' . BASE_URL . '/admin/dashboard.php');
    exit;
} elseif (isMechanicLoggedIn()) {
    header('Location: ' . BASE_URL . '/mechanic/dashboard.php');
    exit;
}

$pageTitle = 'Book a Service – Customer Login Required | MotoCare';
$pageDescription = 'Customer authentication is required to book a two-wheeler service appointment at MotoCare. Sign in to select your registered vehicle and reserve a workshop bay.';
$currentPage = 'booking.php';

require_once __DIR__ . '/includes/header.php';
?>

<!-- ======================================================================
     Informational Hero Section
     ====================================================================== -->
<section class="section-padding" style="background: radial-gradient(circle at 50% 0%, rgba(255,94,20,0.15) 0%, transparent 65%); padding-top: 3.5rem; padding-bottom: 2rem;">
  <div class="container">
    <div class="section-header" style="text-align: center; max-width: 720px; margin: 0 auto;">
      <span class="section-badge">Authenticated Booking Workflow</span>
      <h1 class="section-title">Book Your <span class="text-gradient">Two-Wheeler Service</span></h1>
      <p class="section-subtitle">
        To ensure guaranteed bay reservation, technician assignment, and digital job card tracking, all service appointments are booked securely inside the Customer Portal.
      </p>
    </div>
  </div>
</section>

<!-- ======================================================================
     Informational Gateway Card
     ====================================================================== -->
<section class="section-padding" style="padding-top: 1rem; padding-bottom: 5rem;">
  <div class="container" style="max-width: 780px;">
    
    <div class="card" style="background: var(--bg-card); border: 1px solid rgba(255,94,20,0.3); border-radius: var(--radius-lg); padding: 3rem 2.5rem; box-shadow: var(--shadow-lg); text-align: center;">
      
      <!-- Icon -->
      <div style="width: 72px; height: 72px; margin: 0 auto 1.5rem; border-radius: 50%; background: rgba(255,94,20,0.12); border: 2px solid rgba(255,94,20,0.35); display: flex; align-items: center; justify-content: center; color: var(--accent-orange);">
        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
          <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
        </svg>
      </div>

      <!-- Heading -->
      <h2 style="font-size: 1.85rem; color: #ffffff; margin-bottom: 0.75rem;">
        Customer login is required to book a service.
      </h2>
      <p style="color: var(--text-muted); font-size: 1rem; max-width: 580px; margin: 0 auto 2rem; line-height: 1.6;">
        Please sign in to your customer account to select your registered vehicle, choose from available service packages, pick your preferred date and time slot, and receive an instant booking token.
      </p>

      <!-- Primary Action Buttons -->
      <div style="display: flex; justify-content: center; gap: 1rem; flex-wrap: wrap; margin-bottom: 2.5rem;">
        <a href="customer/login.php?redirect=book-service" class="btn btn-primary btn-lg" style="min-width: 200px; justify-content: center;">
          <span>Customer Login</span>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width: 18px; height: 18px;"><path d="M5 12h14"></path><path d="m12 5 7 7-7 7"></path></svg>
        </a>
        <a href="customer/register.php" class="btn btn-outline btn-lg" style="min-width: 200px; justify-content: center;">
          <span>Create Customer Account</span>
        </a>
      </div>

      <!-- Demo Credentials Quick Helper -->
      <div style="background: var(--bg-surface); padding: 1.25rem; border-radius: var(--radius-md); border: 1px dashed var(--border-subtle); max-width: 460px; margin: 0 auto 2.5rem; font-size: 0.85rem; color: var(--text-muted);">
        <strong style="color: var(--text-primary); display: block; margin-bottom: 0.35rem;">Demo Customer Account for Viva:</strong>
        Email: <code style="color: var(--color-blue); font-weight: 600;">ramesh@example.com</code> &nbsp;|&nbsp; 
        Password: <code style="color: var(--accent-orange); font-weight: 600;">customer123</code>
      </div>

      <!-- 3-Step Process Guide -->
      <div style="border-top: 1px solid var(--border-subtle); padding-top: 2rem; text-align: left;">
        <h3 style="font-size: 1.1rem; color: #ffffff; margin-bottom: 1.25rem; text-align: center;">How Customer Booking Works</h3>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
          
          <div style="background: var(--bg-surface); padding: 1.25rem; border-radius: var(--radius-md); border-left: 3px solid var(--accent-orange);">
            <div style="font-size: 0.8rem; font-weight: 700; color: var(--accent-orange); margin-bottom: 0.35rem;">STEP 1</div>
            <div style="font-size: 0.95rem; font-weight: 600; color: #ffffff; margin-bottom: 0.25rem;">Sign In / Register</div>
            <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0;">Authenticate your customer profile and register your two-wheelers.</p>
          </div>

          <div style="background: var(--bg-surface); padding: 1.25rem; border-radius: var(--radius-md); border-left: 3px solid var(--color-blue);">
            <div style="font-size: 0.8rem; font-weight: 700; color: var(--color-blue); margin-bottom: 0.35rem;">STEP 2</div>
            <div style="font-size: 0.95rem; font-weight: 600; color: #ffffff; margin-bottom: 0.25rem;">Select Bike &amp; Service</div>
            <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0;">Pick your vehicle from your garage, choose standard or custom packages.</p>
          </div>

          <div style="background: var(--bg-surface); padding: 1.25rem; border-radius: var(--radius-md); border-left: 3px solid var(--color-green);">
            <div style="font-size: 0.8rem; font-weight: 700; color: var(--color-green); margin-bottom: 0.35rem;">STEP 3</div>
            <div style="font-size: 0.95rem; font-weight: 600; color: #ffffff; margin-bottom: 0.25rem;">Instant Token</div>
            <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0;">Get your unique booking code (e.g., MC-2026-XXXX) and track progress.</p>
          </div>

        </div>
      </div>

      <!-- Multi-Role Portal Link -->
      <div style="margin-top: 2rem; font-size: 0.85rem; color: var(--text-muted);">
        Looking for Admin or Mechanic access? <a href="portal-login.php" style="color: var(--accent-orange); text-decoration: underline;">Open Portal Login Gateway →</a>
      </div>

    </div>

  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
