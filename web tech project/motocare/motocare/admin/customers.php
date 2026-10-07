<?php
/**
 * ============================================================================
 * MotoCare – Two-Wheeler Service & Mechanic Shop Management System
 * File: admin/customers.php
 * Stage 2: Customer Directory Management
 * ============================================================================
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

requireAdminLogin();

$pageTitle = 'Manage Customers – MotoCare Admin';
$currentPage = 'customers.php';
require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="container">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
    <div>
      <span class="service-tag" style="background: rgba(239,68,68,0.15); color: #f87171; border-color: rgba(239,68,68,0.3); margin-bottom: 0.5rem; display: inline-block;">
        Customer Database
      </span>
      <h1 style="font-size: 2rem; margin-bottom: 0.25rem;">Registered Customers</h1>
      <p style="color: var(--text-muted); font-size: 0.9rem;">View vehicle owners, contact phone numbers, and active service histories.</p>
    </div>
  </div>

  <div class="form-card" style="padding: 1.5rem; overflow-x: auto;">
    <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
      <thead>
        <tr style="border-bottom: 2px solid var(--border-card); color: var(--text-main);">
          <th style="padding: 0.75rem 1rem;">ID</th>
          <th style="padding: 0.75rem 1rem;">Customer Name</th>
          <th style="padding: 0.75rem 1rem;">Email Address</th>
          <th style="padding: 0.75rem 1rem;">Phone</th>
          <th style="padding: 0.75rem 1rem;">Address</th>
          <th style="padding: 0.75rem 1rem;">Registered</th>
        </tr>
      </thead>
      <tbody>
        <tr style="border-bottom: 1px solid var(--border-subtle);">
          <td style="padding: 1rem; font-weight: 700;">#1</td>
          <td style="padding: 1rem; font-weight: 600; color: #ffffff;">Ramesh Kumar</td>
          <td style="padding: 1rem;">ramesh@example.com</td>
          <td style="padding: 1rem;">9876543210</td>
          <td style="padding: 1rem; color: var(--text-muted);">#14, Anna Nagar 2nd St, Chennai</td>
          <td style="padding: 1rem; color: var(--text-dim);"><?php echo date('d M Y'); ?></td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
