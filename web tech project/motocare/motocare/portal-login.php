<?php
/**
 * ============================================================================
 * MotoCare – Two-Wheeler Service & Mechanic Shop Management System
 * File: portal-login.php
 * Stage 2: Central Role-Based Portal Gateway
 * ============================================================================
 */

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/auth.php';

// If a user is already authenticated in any role, offer quick redirect
if (isCustomerLoggedIn()) {
    $currentRole = 'Customer';
    $dashUrl = 'customer/dashboard.php';
} elseif (isAdminLoggedIn()) {
    $currentRole = 'Administrator';
    $dashUrl = 'admin/dashboard.php';
} elseif (isMechanicLoggedIn()) {
    $currentRole = 'Mechanic';
    $dashUrl = 'mechanic/dashboard.php';
} else {
    $currentRole = null;
    $dashUrl = null;
}

$pageTitle = 'Portal Login – Role Selection Gateway | MotoCare';
$pageDescription = 'Access MotoCare role-based portals for Customers, Workshop Administrators, and Service Mechanics.';
$currentPage = 'portal-login.php';

require_once __DIR__ . '/includes/header.php';
?>

<section class="section-padding" style="min-height: 80vh; display: flex; align-items: center;">
  <div class="container">
    
    <!-- Section Header -->
    <div class="section-header" style="text-align: center; max-width: 700px; margin: 0 auto 3rem;">
      <span class="section-badge">Secure Access Gateway</span>
      <h1 class="section-title">Select Your <span class="text-gradient">Portal</span></h1>
      <p class="section-subtitle">
        MotoCare provides distinct workspaces for vehicle owners, workshop supervisors, and garage technicians. Choose your portal below to sign in.
      </p>

      <?php if ($currentRole): ?>
        <div style="margin-top: 1.5rem; display: inline-flex; align-items: center; gap: 0.75rem; background: rgba(255,94,20,0.12); border: 1px solid var(--accent-orange); padding: 0.75rem 1.25rem; border-radius: var(--radius-md);">
          <span class="pulse-dot" style="background-color: var(--color-green);"></span>
          <span style="font-size: 0.9rem; color: #fff;">Currently logged in as <strong><?php echo htmlspecialchars($currentRole); ?></strong></span>
          <a href="<?php echo $dashUrl; ?>" class="btn btn-primary btn-sm" style="padding: 0.35rem 0.85rem; font-size: 0.8rem;">Go to Dashboard →</a>
        </div>
      <?php endif; ?>
    </div>

    <!-- Portal Cards Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(310px, 1fr)); gap: 2rem; max-width: 1100px; margin: 0 auto;">
      
      <!-- 1. CUSTOMER PORTAL -->
      <div class="card" style="background: var(--bg-card); border: 1px solid rgba(255,94,20,0.3); border-radius: var(--radius-lg); padding: 2.25rem; display: flex; flex-direction: column; justify-content: space-between; position: relative; transition: transform 0.25s ease, box-shadow 0.25s ease;">
        <div>
          <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
            <div style="width: 52px; height: 52px; border-radius: var(--radius-md); background: rgba(255,94,20,0.12); border: 1px solid rgba(255,94,20,0.3); display: flex; align-items: center; justify-content: center; color: var(--accent-orange);">
              <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
            </div>
            <span class="service-tag" style="background: rgba(255,94,20,0.15); color: var(--accent-orange); border-color: rgba(255,94,20,0.3);">
              Vehicle Owners
            </span>
          </div>

          <h2 style="font-size: 1.45rem; margin-bottom: 0.5rem; color: #fff;">CUSTOMER PORTAL</h2>
          <p style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 1.5rem;">
            Online booking and self-service maintenance center for two-wheeler owners.
          </p>

          <ul style="list-style: none; padding: 0; margin: 0 0 2rem 0; display: flex; flex-direction: column; gap: 0.65rem; font-size: 0.885rem; color: var(--text-secondary);">
            <li style="display: flex; align-items: center; gap: 0.5rem;">
              <span style="color: var(--accent-orange); font-weight: bold;">✓</span> Book services
            </li>
            <li style="display: flex; align-items: center; gap: 0.5rem;">
              <span style="color: var(--accent-orange); font-weight: bold;">✓</span> Manage vehicles
            </li>
            <li style="display: flex; align-items: center; gap: 0.5rem;">
              <span style="color: var(--accent-orange); font-weight: bold;">✓</span> View bookings
            </li>
            <li style="display: flex; align-items: center; gap: 0.5rem;">
              <span style="color: var(--accent-orange); font-weight: bold;">✓</span> View invoices
            </li>
            <li style="display: flex; align-items: center; gap: 0.5rem;">
              <span style="color: var(--accent-orange); font-weight: bold;">✓</span> Submit reviews
            </li>
          </ul>
        </div>

        <div>
          <a href="customer/login.php" class="btn btn-primary btn-block btn-lg" style="margin-bottom: 1rem; text-align: center; justify-content: center;">
            Customer Login →
          </a>
          <div style="background: var(--bg-surface); padding: 0.75rem; border-radius: var(--radius-sm); border: 1px dashed var(--border-subtle); font-size: 0.775rem; color: var(--text-muted); text-align: center;">
            <strong style="color: var(--text-primary);">Demo Account for Viva:</strong><br>
            <code>ramesh@example.com</code> / <code>customer123</code>
          </div>
        </div>
      </div>

      <!-- 2. ADMIN PORTAL -->
      <div class="card" style="background: var(--bg-card); border: 1px solid rgba(239,68,68,0.35); border-radius: var(--radius-lg); padding: 2.25rem; display: flex; flex-direction: column; justify-content: space-between; position: relative; transition: transform 0.25s ease, box-shadow 0.25s ease;">
        <div>
          <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
            <div style="width: 52px; height: 52px; border-radius: var(--radius-md); background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.3); display: flex; align-items: center; justify-content: center; color: #f87171;">
              <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
            </div>
            <span class="service-tag" style="background: rgba(239,68,68,0.15); color: #f87171; border-color: rgba(239,68,68,0.3);">
              Workshop Control
            </span>
          </div>

          <h2 style="font-size: 1.45rem; margin-bottom: 0.5rem; color: #fff;">ADMIN PORTAL</h2>
          <p style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 1.5rem;">
            Administrative operations, dispatch, inventory control &amp; revenue billing.
          </p>

          <ul style="list-style: none; padding: 0; margin: 0 0 2rem 0; display: flex; flex-direction: column; gap: 0.65rem; font-size: 0.885rem; color: var(--text-secondary);">
            <li style="display: flex; align-items: center; gap: 0.5rem;">
              <span style="color: #f87171; font-weight: bold;">✓</span> Manage bookings
            </li>
            <li style="display: flex; align-items: center; gap: 0.5rem;">
              <span style="color: #f87171; font-weight: bold;">✓</span> Assign mechanics
            </li>
            <li style="display: flex; align-items: center; gap: 0.5rem;">
              <span style="color: #f87171; font-weight: bold;">✓</span> Manage inventory
            </li>
            <li style="display: flex; align-items: center; gap: 0.5rem;">
              <span style="color: #f87171; font-weight: bold;">✓</span> Manage invoices
            </li>
            <li style="display: flex; align-items: center; gap: 0.5rem;">
              <span style="color: #f87171; font-weight: bold;">✓</span> Manage payments
            </li>
            <li style="display: flex; align-items: center; gap: 0.5rem;">
              <span style="color: #f87171; font-weight: bold;">✓</span> Moderate feedback
            </li>
          </ul>
        </div>

        <div>
          <a href="admin/login.php" class="btn btn-block btn-lg" style="margin-bottom: 1rem; text-align: center; justify-content: center; background: linear-gradient(135deg, #ef4444, #b91c1c); color: #fff;">
            Admin Login →
          </a>
          <div style="background: var(--bg-surface); padding: 0.75rem; border-radius: var(--radius-sm); border: 1px dashed var(--border-subtle); font-size: 0.775rem; color: var(--text-muted); text-align: center;">
            <strong style="color: var(--text-primary);">Demo Account for Viva:</strong><br>
            <code>admin</code> / <code>admin123</code>
          </div>
        </div>
      </div>

      <!-- 3. MECHANIC PORTAL -->
      <div class="card" style="background: var(--bg-card); border: 1px solid rgba(16,185,129,0.35); border-radius: var(--radius-lg); padding: 2.25rem; display: flex; flex-direction: column; justify-content: space-between; position: relative; transition: transform 0.25s ease, box-shadow 0.25s ease;">
        <div>
          <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
            <div style="width: 52px; height: 52px; border-radius: var(--radius-md); background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.3); display: flex; align-items: center; justify-content: center; color: var(--color-green);">
              <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>
            </div>
            <span class="service-tag" style="background: rgba(16,185,129,0.15); color: var(--color-green); border-color: rgba(16,185,129,0.3);">
              Workshop Bay
            </span>
          </div>

          <h2 style="font-size: 1.45rem; margin-bottom: 0.5rem; color: #fff;">MECHANIC PORTAL</h2>
          <p style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 1.5rem;">
            Digital workshop job cards, parts usage recording, and live status progression.
          </p>

          <ul style="list-style: none; padding: 0; margin: 0 0 2rem 0; display: flex; flex-direction: column; gap: 0.65rem; font-size: 0.885rem; color: var(--text-secondary);">
            <li style="display: flex; align-items: center; gap: 0.5rem;">
              <span style="color: var(--color-green); font-weight: bold;">✓</span> View assigned services
            </li>
            <li style="display: flex; align-items: center; gap: 0.5rem;">
              <span style="color: var(--color-green); font-weight: bold;">✓</span> Manage job cards
            </li>
            <li style="display: flex; align-items: center; gap: 0.5rem;">
              <span style="color: var(--color-green); font-weight: bold;">✓</span> Update service status
            </li>
            <li style="display: flex; align-items: center; gap: 0.5rem;">
              <span style="color: var(--color-green); font-weight: bold;">✓</span> Record parts used
            </li>
          </ul>
        </div>

        <div>
          <a href="mechanic/login.php" class="btn btn-block btn-lg" style="margin-bottom: 1rem; text-align: center; justify-content: center; background: linear-gradient(135deg, #10b981, #059669); color: #fff;">
            Mechanic Login →
          </a>
          <div style="background: var(--bg-surface); padding: 0.75rem; border-radius: var(--radius-sm); border: 1px dashed var(--border-subtle); font-size: 0.775rem; color: var(--text-muted); text-align: center;">
            <strong style="color: var(--text-primary);">Demo Account for Viva:</strong><br>
            <code>arun@motocare.com</code> / <code>mechanic123</code>
          </div>
        </div>
      </div>

    </div>

    <!-- Return to Website Link -->
    <div style="text-align: center; margin-top: 3rem;">
      <a href="index.php" style="color: var(--text-muted); font-size: 0.9rem; text-decoration: underline;">
        ← Return to MotoCare Public Homepage
      </a>
    </div>

  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
