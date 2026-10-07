<?php
/**
 * ============================================================================
 * MotoCare – Two-Wheeler Service & Mechanic Shop Management System
 * File: admin/services.php
 * Stage 2: Service Packages Catalog & Pricing Management
 * ============================================================================
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/constants.php';

requireAdminLogin();

$pageTitle = 'Service Catalog – Admin Control – MotoCare';
$currentPage = 'services.php';

$successMsg = '';
$errorMsg = '';

// Handle quick toggle or add service in Stage 2 foundation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'toggle_status' && isset($_POST['service_id'])) {
        $svcId = (int)$_POST['service_id'];
        $newStatus = ($_POST['current_status'] === 'Active') ? 'Inactive' : 'Active';
        
        if ($pdo) {
            try {
                $stmt = $pdo->prepare("UPDATE services SET status = :status WHERE id = :id");
                $stmt->execute([':status' => $newStatus, ':id' => $svcId]);
                $successMsg = "Service package status updated to {$newStatus}.";
            } catch (PDOException $e) {
                $errorMsg = "Database error: " . $e->getMessage();
            }
        } else {
            $successMsg = "Demo: Status updated to {$newStatus}.";
        }
    }
}

// Fetch services
$services = [];
if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM services ORDER BY category ASC, price ASC");
        $services = $stmt->fetchAll();
    } catch (PDOException $e) {
        $errorMsg = "Could not load services: " . $e->getMessage();
    }
} else {
    // Mock services fallback for offline viva presentation
    $services = [
        ['id' => 1, 'service_name' => 'General Periodic Maintenance', 'category' => 'General', 'description' => 'Complete 40-point inspection and fluids check', 'price' => 799.00, 'estimated_duration' => '90 mins', 'status' => 'Active'],
        ['id' => 2, 'service_name' => 'Synthetic Engine Oil Flush & Refill', 'category' => 'Engine', 'description' => 'Premium 10W-40/15W-50 oil replacement with OEM filter', 'price' => 549.00, 'estimated_duration' => '45 mins', 'status' => 'Active'],
        ['id' => 3, 'service_name' => 'Brake Pad & Hydraulic Fluid Flush', 'category' => 'Brakes', 'description' => 'Caliper inspection, disc rotor cleaning, DOT4 fluid flush', 'price' => 399.00, 'estimated_duration' => '45 mins', 'status' => 'Active'],
        ['id' => 4, 'service_name' => 'EV Battery & BMS Diagnostics', 'category' => 'Electrical', 'description' => 'State of charge/health scan, wiring insulation check', 'price' => 649.00, 'estimated_duration' => '60 mins', 'status' => 'Active'],
    ];
}

include __DIR__ . '/../includes/admin-header.php';
?>

<div class="container">
  <div class="section-header" style="text-align: left; margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 1rem;">
    <div>
      <span class="section-tagline">Service Portfolio</span>
      <h1 class="section-title" style="font-size: 2rem;">Service Packages &amp; Labor Rates</h1>
      <p class="section-description">Manage rates, categories, and availability of all workshop repair packages.</p>
    </div>
    <div style="display: flex; gap: 0.75rem;">
      <a href="<?php echo $rootPath; ?>services.php" target="_blank" class="btn btn-outline btn-sm">Preview Public Page ↗</a>
    </div>
  </div>

  <?php if ($successMsg): ?>
    <div class="form-alert form-alert-success" style="margin-bottom: 1.5rem;">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
      <span><?php echo htmlspecialchars($successMsg); ?></span>
    </div>
  <?php endif; ?>

  <?php if ($errorMsg): ?>
    <div class="form-alert form-alert-error" style="margin-bottom: 1.5rem;">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
      <span><?php echo htmlspecialchars($errorMsg); ?></span>
    </div>
  <?php endif; ?>

  <div class="dashboard-card" style="padding: 0; overflow: hidden; background: var(--bg-card); border-radius: var(--radius-lg); border: 1px solid var(--border-subtle);">
    <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border-subtle); display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02);">
      <h3 style="font-size: 1.1rem; color: #ffffff; margin: 0;">Master Service Catalog (<?php echo count($services); ?> items)</h3>
      <span class="badge" style="background: rgba(255,107,0,0.1); color: var(--accent-orange); border: 1px solid rgba(255,107,0,0.3);">Stage 2 Foundation</span>
    </div>

    <div style="overflow-x: auto;">
      <table class="data-table" style="width: 100%; border-collapse: collapse; text-align: left;">
        <thead>
          <tr style="border-bottom: 1px solid var(--border-subtle); background: var(--bg-surface); font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em;">
            <th style="padding: 1rem 1.5rem;">ID</th>
            <th style="padding: 1rem 1.5rem;">Service Name</th>
            <th style="padding: 1rem 1.5rem;">Category</th>
            <th style="padding: 1rem 1.5rem;">Base Price</th>
            <th style="padding: 1rem 1.5rem;">Est. Duration</th>
            <th style="padding: 1rem 1.5rem;">Status</th>
            <th style="padding: 1rem 1.5rem; text-align: right;">Action</th>
          </tr>
        </thead>
        <tbody style="font-size: 0.95rem;">
          <?php foreach ($services as $svc): ?>
            <tr style="border-bottom: 1px solid var(--border-subtle);">
              <td style="padding: 1rem 1.5rem; color: var(--text-muted); font-family: monospace;">#<?php echo htmlspecialchars($svc['id']); ?></td>
              <td style="padding: 1rem 1.5rem;">
                <div style="font-weight: 600; color: #ffffff;"><?php echo htmlspecialchars($svc['service_name']); ?></div>
                <div style="font-size: 0.8rem; color: var(--text-muted); max-width: 320px;"><?php echo htmlspecialchars($svc['description'] ?? ''); ?></div>
              </td>
              <td style="padding: 1rem 1.5rem;">
                <span class="badge" style="background: rgba(255,255,255,0.06); color: var(--text-secondary); border: 1px solid var(--border-subtle);">
                  <?php echo htmlspecialchars($svc['category'] ?? 'General'); ?>
                </span>
              </td>
              <td style="padding: 1rem 1.5rem; font-weight: 700; color: var(--accent-orange);">
                <?php echo formatCurrency($svc['price']); ?>
              </td>
              <td style="padding: 1rem 1.5rem; color: var(--text-secondary);">
                <?php echo htmlspecialchars($svc['estimated_duration'] ?? 'N/A'); ?>
              </td>
              <td style="padding: 1rem 1.5rem;">
                <?php if (($svc['status'] ?? 'Active') === 'Active'): ?>
                  <span class="badge" style="background: rgba(16,185,129,0.15); color: #34d399; border: 1px solid rgba(16,185,129,0.3);">Active</span>
                <?php else: ?>
                  <span class="badge" style="background: rgba(239,68,68,0.15); color: #f87171; border: 1px solid rgba(239,68,68,0.3);">Inactive</span>
                <?php endif; ?>
              </td>
              <td style="padding: 1rem 1.5rem; text-align: right;">
                <form method="POST" style="display: inline;">
                  <input type="hidden" name="action" value="toggle_status">
                  <input type="hidden" name="service_id" value="<?php echo htmlspecialchars($svc['id']); ?>">
                  <input type="hidden" name="current_status" value="<?php echo htmlspecialchars($svc['status'] ?? 'Active'); ?>">
                  <button type="submit" class="btn btn-outline btn-sm" style="padding: 0.3rem 0.7rem; font-size: 0.8rem;">
                    Toggle
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
