<?php
/**
 * ============================================================================
 * MotoCare – Two-Wheeler Service & Mechanic Shop Management System
 * File: admin/vehicles.php
 * Stage 2: Vehicle Inventory & Registry
 * ============================================================================
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

requireAdminLogin();

$pageTitle = 'Manage Vehicles – MotoCare Admin';
$currentPage = 'vehicles.php';
require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="container">
  <div style="margin-bottom: 2rem;">
    <span class="service-tag" style="background: rgba(239,68,68,0.15); color: #f87171; border-color: rgba(239,68,68,0.3); margin-bottom: 0.5rem; display: inline-block;">
      Vehicle Registry
    </span>
    <h1 style="font-size: 2rem; margin-bottom: 0.25rem;">Two-Wheeler Fleet Database</h1>
    <p style="color: var(--text-muted); font-size: 0.9rem;">Catalog of all motorcycles, scooters, and electric bikes serviced at MotoCare.</p>
  </div>

  <div class="form-card" style="padding: 1.5rem; overflow-x: auto;">
    <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
      <thead>
        <tr style="border-bottom: 2px solid var(--border-card); color: var(--text-main);">
          <th style="padding: 0.75rem 1rem;">ID</th>
          <th style="padding: 0.75rem 1rem;">Registration No.</th>
          <th style="padding: 0.75rem 1rem;">Type</th>
          <th style="padding: 0.75rem 1rem;">Brand &amp; Model</th>
          <th style="padding: 0.75rem 1rem;">Owner</th>
          <th style="padding: 0.75rem 1rem;">Created</th>
        </tr>
      </thead>
      <tbody>
        <tr style="border-bottom: 1px solid var(--border-subtle);">
          <td style="padding: 1rem; font-weight: 700;">#1</td>
          <td style="padding: 1rem; font-weight: 700; color: var(--accent-orange);">TN-07-AB-1234</td>
          <td style="padding: 1rem;"><span class="service-tag">Motorcycle</span></td>
          <td style="padding: 1rem; color: #ffffff; font-weight: 600;">Royal Enfield Classic 350</td>
          <td style="padding: 1rem;">Ramesh Kumar</td>
          <td style="padding: 1rem; color: var(--text-dim);"><?php echo date('d M Y'); ?></td>
        </tr>
        <tr style="border-bottom: 1px solid var(--border-subtle);">
          <td style="padding: 1rem; font-weight: 700;">#2</td>
          <td style="padding: 1rem; font-weight: 700; color: var(--accent-orange);">TN-09-CD-5678</td>
          <td style="padding: 1rem;"><span class="service-tag">Scooter</span></td>
          <td style="padding: 1rem; color: #ffffff; font-weight: 600;">Honda Activa 6G</td>
          <td style="padding: 1rem;">Ramesh Kumar</td>
          <td style="padding: 1rem; color: var(--text-dim);"><?php echo date('d M Y'); ?></td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
