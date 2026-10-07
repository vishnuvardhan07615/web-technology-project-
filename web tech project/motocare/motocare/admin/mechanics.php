<?php
/**
 * ============================================================================
 * MotoCare – Two-Wheeler Service & Mechanic Shop Management System
 * File: admin/mechanics.php
 * Stage 2: Mechanic Staff Roster & Duty Allocation
 * ============================================================================
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

requireAdminLogin();

$pageTitle = 'Mechanics Staff Roster – MotoCare Admin';
$currentPage = 'mechanics.php';
require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="container">
  <div style="margin-bottom: 2rem;">
    <span class="service-tag" style="background: rgba(239,68,68,0.15); color: #f87171; border-color: rgba(239,68,68,0.3); margin-bottom: 0.5rem; display: inline-block;">
      Workshop Technicians
    </span>
    <h1 style="font-size: 2rem; margin-bottom: 0.25rem;">Mechanic Staff Roster</h1>
    <p style="color: var(--text-muted); font-size: 0.9rem;">Manage certified technician credentials, bay assignments, and duty statuses.</p>
  </div>

  <div class="form-card" style="padding: 1.5rem; overflow-x: auto;">
    <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
      <thead>
        <tr style="border-bottom: 2px solid var(--border-card); color: var(--text-main);">
          <th style="padding: 0.75rem 1rem;">ID</th>
          <th style="padding: 0.75rem 1rem;">Technician Name</th>
          <th style="padding: 0.75rem 1rem;">Email / Login</th>
          <th style="padding: 0.75rem 1rem;">Phone</th>
          <th style="padding: 0.75rem 1rem;">Specialization</th>
          <th style="padding: 0.75rem 1rem;">Status</th>
        </tr>
      </thead>
      <tbody>
        <tr style="border-bottom: 1px solid var(--border-subtle);">
          <td style="padding: 1rem; font-weight: 700;">#1</td>
          <td style="padding: 1rem; font-weight: 600; color: #ffffff;">Arun Kumar</td>
          <td style="padding: 1rem;">arun@motocare.com</td>
          <td style="padding: 1rem;">9876543201</td>
          <td style="padding: 1rem; color: var(--text-muted);">Royal Enfield &amp; Cruiser Specialist</td>
          <td style="padding: 1rem;"><span class="service-tag" style="background: rgba(16,185,129,0.15); color: var(--color-green); border-color: rgba(16,185,129,0.3);">Available</span></td>
        </tr>
        <tr style="border-bottom: 1px solid var(--border-subtle);">
          <td style="padding: 1rem; font-weight: 700;">#2</td>
          <td style="padding: 1rem; font-weight: 600; color: #ffffff;">Karthik</td>
          <td style="padding: 1rem;">karthik@motocare.com</td>
          <td style="padding: 1rem;">9876543202</td>
          <td style="padding: 1rem; color: var(--text-muted);">Engine Overhaul &amp; Transmission</td>
          <td style="padding: 1rem;"><span class="service-tag" style="background: rgba(16,185,129,0.15); color: var(--color-green); border-color: rgba(16,185,129,0.3);">Available</span></td>
        </tr>
        <tr style="border-bottom: 1px solid var(--border-subtle);">
          <td style="padding: 1rem; font-weight: 700;">#3</td>
          <td style="padding: 1rem; font-weight: 600; color: #ffffff;">Sanjay</td>
          <td style="padding: 1rem;">sanjay@motocare.com</td>
          <td style="padding: 1rem;">9876543203</td>
          <td style="padding: 1rem; color: var(--text-muted);">Electrical Diagnostics &amp; EV Powertrains</td>
          <td style="padding: 1rem;"><span class="service-tag" style="background: rgba(16,185,129,0.15); color: var(--color-green); border-color: rgba(16,185,129,0.3);">Available</span></td>
        </tr>
        <tr style="border-bottom: 1px solid var(--border-subtle);">
          <td style="padding: 1rem; font-weight: 700;">#4</td>
          <td style="padding: 1rem; font-weight: 600; color: #ffffff;">Praveen</td>
          <td style="padding: 1rem;">praveen@motocare.com</td>
          <td style="padding: 1rem;">9876543204</td>
          <td style="padding: 1rem; color: var(--text-muted);">Suspension Tuning &amp; Brake Systems</td>
          <td style="padding: 1rem;"><span class="service-tag" style="background: rgba(16,185,129,0.15); color: var(--color-green); border-color: rgba(16,185,129,0.3);">Available</span></td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
