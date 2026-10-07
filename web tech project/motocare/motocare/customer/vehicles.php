<?php
/**
 * ============================================================================
 * MotoCare – Two-Wheeler Service & Mechanic Shop Management System
 * File: customer/vehicles.php
 * Stage 2: Customer Registered Vehicles (Dynamic Fleet View)
 * ============================================================================
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

requireCustomerLogin();
$customer = getCurrentCustomer();
$customerId = (int)$customer['id'];

$pageTitle = 'My Vehicles – MotoCare Customer Portal';
$currentPage = 'vehicles.php';

$vehicles = [];
$errorMsg = '';
$infoMsg = '';

if (isset($_GET['msg']) && $_GET['msg'] === 'added') {
    $infoMsg = 'Vehicle successfully added to your MotoCare garage!';
}

if ($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT v.*,
                   (SELECT COUNT(*) FROM bookings b WHERE b.vehicle_id = v.id) AS total_bookings,
                   (SELECT b.status FROM bookings b WHERE b.vehicle_id = v.id ORDER BY b.created_at DESC LIMIT 1) AS latest_status
            FROM vehicles v
            WHERE v.customer_id = :cid
            ORDER BY v.created_at DESC
        ");
        $stmt->execute([':cid' => $customerId]);
        $vehicles = $stmt->fetchAll();
    } catch (PDOException $e) {
        $errorMsg = 'Could not load registered vehicles: ' . $e->getMessage();
    }
} else {
    // Offline demo fallback
    $vehicles = [
        [
            'id' => 1,
            'customer_id' => $customerId,
            'vehicle_type' => 'Motorcycle',
            'brand' => 'Royal Enfield',
            'model' => 'Classic 350',
            'registration_number' => 'TN-07-AB-1234',
            'total_bookings' => 1,
            'latest_status' => 'Confirmed'
        ],
        [
            'id' => 2,
            'customer_id' => $customerId,
            'vehicle_type' => 'Scooter',
            'brand' => 'Honda',
            'model' => 'Activa 6G',
            'registration_number' => 'TN-09-CD-5678',
            'total_bookings' => 1,
            'latest_status' => 'Pending'
        ]
    ];
}

require_once __DIR__ . '/../includes/customer-header.php';
?>

<div class="container" style="padding-top: 1rem; padding-bottom: 3rem;">
  <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
    <div>
      <span class="service-tag" style="background: var(--accent-glow-subtle); color: var(--accent-orange); margin-bottom: 0.5rem; display: inline-block;">
        Garage Fleet
      </span>
      <h1 style="font-size: 2rem; margin-bottom: 0.25rem;">My Two-Wheelers</h1>
      <p style="color: var(--text-muted); font-size: 0.9rem;">
        Registered bikes, scooters, and EVs linked to your account (<?php echo count($vehicles); ?> registered).
      </p>
    </div>
    <a href="add-vehicle.php" class="btn btn-primary">
      <span>+ Add New Two-Wheeler</span>
    </a>
  </div>

  <?php if ($infoMsg): ?>
    <div class="form-alert form-alert-success" style="margin-bottom: 1.5rem;">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
      <span><?php echo htmlspecialchars($infoMsg); ?></span>
    </div>
  <?php endif; ?>

  <?php if ($errorMsg): ?>
    <div class="form-alert form-alert-error" style="margin-bottom: 1.5rem;">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
      <span><?php echo htmlspecialchars($errorMsg); ?></span>
    </div>
  <?php endif; ?>

  <?php if (empty($vehicles)): ?>
    <div class="form-card" style="padding: 3.5rem 2rem; text-align: center;">
      <div style="width: 64px; height: 64px; margin: 0 auto 1.5rem; border-radius: 50%; background: rgba(255,94,20,0.1); display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255,94,20,0.3);">
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="var(--accent-orange)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
      </div>
      <h3 style="font-size: 1.4rem; margin-bottom: 0.5rem; color: #ffffff;">No Two-Wheelers in Your Garage Yet</h3>
      <p style="color: var(--text-muted); max-width: 460px; margin: 0 auto 1.75rem; font-size: 0.95rem;">
        Add your motorcycle, scooter, or EV to quickly book maintenance slots, view service invoices, and track workshop job cards.
      </p>
      <a href="add-vehicle.php" class="btn btn-primary btn-lg">
        + Register Your First Two-Wheeler
      </a>
    </div>
  <?php else: ?>
    <div class="services-grid" style="grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));">
      <?php foreach ($vehicles as $veh): ?>
        <div class="form-card" style="padding: 1.75rem; display: flex; flex-direction: column; justify-content: space-between;">
          <div>
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
              <span class="service-tag">
                <?php echo htmlspecialchars($veh['vehicle_type']); ?>
              </span>
              <span style="font-family: var(--font-heading); font-weight: 700; color: var(--accent-orange); letter-spacing: 0.5px;">
                <?php echo htmlspecialchars($veh['registration_number']); ?>
              </span>
            </div>

            <h3 style="font-size: 1.3rem; margin-bottom: 0.35rem; color: #ffffff;">
              <?php echo htmlspecialchars($veh['brand'] . ' ' . $veh['model']); ?>
            </h3>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.25rem;">
              Manufacturer: <strong style="color: var(--text-secondary);"><?php echo htmlspecialchars($veh['brand']); ?></strong> &bull;
              Model: <strong style="color: var(--text-secondary);"><?php echo htmlspecialchars($veh['model']); ?></strong>
            </p>

            <?php if (!empty($veh['latest_status'])): ?>
              <div style="margin-bottom: 1.25rem; font-size: 0.8rem; background: var(--bg-surface); padding: 0.5rem 0.75rem; border-radius: var(--radius-sm); border: 1px solid var(--border-subtle); display: flex; justify-content: space-between; align-items: center;">
                <span style="color: var(--text-muted);">Latest Service:</span>
                <span class="badge" style="background: rgba(255,94,20,0.15); color: var(--accent-orange); font-size: 0.75rem;">
                  <?php echo htmlspecialchars($veh['latest_status']); ?>
                </span>
              </div>
            <?php endif; ?>
          </div>
          
          <div style="display: flex; gap: 0.75rem; margin-top: 0.5rem;">
            <a href="book-service.php?vehicle_id=<?php echo urlencode($veh['id']); ?>" class="btn btn-primary btn-sm" style="flex: 1;">
              Book Service →
            </a>
            <a href="bookings.php?vehicle_id=<?php echo urlencode($veh['id']); ?>" class="btn btn-secondary btn-sm" title="View bookings for this vehicle">
              Bookings
            </a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
