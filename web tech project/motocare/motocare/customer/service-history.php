<?php
/**
 * ============================================================================
 * MotoCare – Two-Wheeler Service & Mechanic Shop Management System
 * File: customer/service-history.php
 * Stage 2: Vehicle Service Logs & Job Cards
 * ============================================================================
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

requireCustomerLogin();
$customer = getCurrentCustomer();

$pageTitle = 'Service History – MotoCare Customer Portal';
$currentPage = 'service-history.php';
require_once __DIR__ . '/../includes/customer-header.php';
?>

<div class="container">
  <div style="margin-bottom: 2rem;">
    <span class="service-tag" style="background: var(--accent-glow-subtle); color: var(--accent-orange); margin-bottom: 0.5rem; display: inline-block;">
      Maintenance Logs
    </span>
    <h1 style="font-size: 2rem; margin-bottom: 0.25rem;">Vehicle Service History</h1>
    <p style="color: var(--text-muted); font-size: 0.9rem;">Complete historical archive of past workshop job cards and spare part replacements.</p>
  </div>

  <div class="form-card" style="padding: 2rem; margin-bottom: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-subtle); padding-bottom: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 0.5rem;">
      <div>
        <h3 style="font-size: 1.25rem; margin-bottom: 0.2rem;">Royal Enfield Classic 350 <span style="font-size: 0.85rem; color: var(--text-dim);">(TN-07-AB-1234)</span></h3>
        <p style="font-size: 0.85rem; color: var(--text-muted);">Serviced by: <strong>Arun Kumar (Lead Mechanic)</strong> &bull; Completed: 15 Aug 2026</p>
      </div>
      <span class="service-tag" style="background: rgba(16,185,129,0.15); color: var(--color-green); border-color: rgba(16,185,129,0.3);">
        Completed &bull; Delivered
      </span>
    </div>

    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; font-size: 0.9rem; margin-bottom: 1.5rem;">
      <div>
        <span style="color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase;">Service Type</span>
        <p style="color: #ffffff; font-weight: 600;">General Service + Brake Pad Renewal</p>
      </div>
      <div>
        <span style="color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase;">Parts Replaced</span>
        <p style="color: #ffffff; font-weight: 600;">Motul 15W-50 Engine Oil, Front Disc Pad Set</p>
      </div>
      <div>
        <span style="color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase;">Total Billed</span>
        <p style="color: var(--accent-orange); font-weight: 700;">₹ 1,230.00</p>
      </div>
    </div>

    <div style="background: var(--bg-surface); padding: 1rem; border-radius: var(--radius-md); font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 1.25rem;">
      <strong>Mechanic Inspection Summary:</strong> Decarb completed on spark plug. Chain slack adjusted to 25mm. Front caliper piston cleaned and lubricated with silicone grease. Next recommended service at 8,000 km.
    </div>

    <div style="display: flex; gap: 0.75rem;">
      <a href="bill.php?id=1" class="btn btn-outline btn-sm">Download Invoice</a>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
