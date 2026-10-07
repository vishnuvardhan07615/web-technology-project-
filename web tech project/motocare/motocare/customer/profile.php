<?php
/**
 * ============================================================================
 * MotoCare – Two-Wheeler Service & Mechanic Shop Management System
 * File: customer/profile.php
 * Stage 2: Customer Profile Foundation
 * ============================================================================
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

requireCustomerLogin();
$customer = getCurrentCustomer();

$pageTitle = 'My Profile – MotoCare Customer Portal';
$currentPage = 'profile.php';
require_once __DIR__ . '/../includes/customer-header.php';
?>

<div class="container" style="max-width: 720px;">
  <div class="form-card" style="padding: 2.5rem 2rem;">
    <div style="margin-bottom: 2rem;">
      <span class="service-tag" style="background: var(--accent-glow-subtle); color: var(--accent-orange); margin-bottom: 0.5rem; display: inline-block;">
        Account Settings
      </span>
      <h1 style="font-size: 1.85rem; margin-bottom: 0.25rem;">Personal Profile</h1>
      <p style="color: var(--text-muted); font-size: 0.9rem;">
        Keep your vehicle registration phone number and address up to date for SMS alerts and invoicing.
      </p>
    </div>

    <form method="POST" action="profile.php">
      <div class="form-grid">
        <div class="form-group full-width">
          <label class="form-label">Full Name</label>
          <input type="text" class="form-input" value="<?php echo htmlspecialchars($customer['name']); ?>" required>
        </div>

        <div class="form-group">
          <label class="form-label">Email Address (Login ID)</label>
          <input type="email" class="form-input" value="<?php echo htmlspecialchars($customer['email']); ?>" readonly style="opacity: 0.8;">
        </div>

        <div class="form-group">
          <label class="form-label">Mobile Number</label>
          <input type="tel" class="form-input" value="<?php echo htmlspecialchars($customer['phone']); ?>" required>
        </div>

        <div class="form-group full-width">
          <label class="form-label">Address</label>
          <textarea class="form-textarea" placeholder="Update your address...">#14, Anna Nagar 2nd Street, Chennai - 600040</textarea>
        </div>

        <div class="form-group full-width" style="margin-top: 1rem;">
          <button type="button" class="btn btn-primary" onclick="alert('Profile update foundation active. Database integration ready.');">
            Save Profile Changes
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
