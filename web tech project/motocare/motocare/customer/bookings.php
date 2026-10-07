<?php
/**
 * ============================================================================
 * MotoCare – Two-Wheeler Service & Mechanic Shop Management System
 * File: customer/bookings.php
 * Stage 2: Customer Booking History (Dynamic MySQL Queries)
 * ============================================================================
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

requireCustomerLogin();
$customer = getCurrentCustomer();
$customerId = (int)$customer['id'];

$pageTitle = 'My Bookings – MotoCare Customer Portal';
$currentPage = 'bookings.php';

$filterVehicleId = isset($_GET['vehicle_id']) ? (int)$_GET['vehicle_id'] : 0;
$bookings = [];
$errorMsg = '';

if ($pdo) {
    try {
        $sql = "
            SELECT 
                b.*,
                v.brand, v.model, v.registration_number, v.vehicle_type,
                s.service_name, s.price AS service_price, s.category AS service_category,
                m.full_name AS mechanic_name
            FROM bookings b
            JOIN vehicles v ON b.vehicle_id = v.id
            JOIN services s ON b.service_id = s.id
            LEFT JOIN mechanics m ON b.mechanic_id = m.id
            WHERE b.customer_id = :cid
        ";

        $params = [':cid' => $customerId];

        if ($filterVehicleId > 0) {
            $sql .= " AND b.vehicle_id = :vid";
            $params[':vid'] = $filterVehicleId;
        }

        $sql .= " ORDER BY b.created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $bookings = $stmt->fetchAll();
    } catch (PDOException $e) {
        $errorMsg = 'Could not load booking history: ' . $e->getMessage();
    }
} else {
    // Offline demo fallback
    $bookings = [
        [
            'id' => 1,
            'booking_code' => 'MC-2026-1001',
            'brand' => 'Royal Enfield',
            'model' => 'Classic 350',
            'registration_number' => 'TN-07-AB-1234',
            'vehicle_type' => 'Motorcycle',
            'service_name' => 'General Service',
            'service_price' => 500.00,
            'preferred_date' => '2026-10-10',
            'preferred_time' => '10:00 AM - 01:00 PM',
            'mechanic_name' => 'Arun Kumar',
            'status' => 'Confirmed',
            'created_at' => '2026-10-05 10:30:00'
        ],
        [
            'id' => 2,
            'booking_code' => 'MC-2026-1002',
            'brand' => 'Honda',
            'model' => 'Activa 6G',
            'registration_number' => 'TN-09-CD-5678',
            'vehicle_type' => 'Scooter',
            'service_name' => 'Oil Change',
            'service_price' => 450.00,
            'preferred_date' => '2026-10-12',
            'preferred_time' => '08:00 AM - 10:00 AM',
            'mechanic_name' => null,
            'status' => 'Pending',
            'created_at' => '2026-10-05 14:00:00'
        ]
    ];
}

require_once __DIR__ . '/../includes/customer-header.php';
?>

<div class="container" style="padding-top: 1rem; padding-bottom: 3rem;">
  
  <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
    <div>
      <span class="service-tag" style="background: var(--accent-glow-subtle); color: var(--accent-orange); margin-bottom: 0.5rem; display: inline-block;">
        Appointments Ledger
      </span>
      <h1 style="font-size: 2rem; margin-bottom: 0.25rem;">My Service Bookings</h1>
      <p style="color: var(--text-muted); font-size: 0.9rem;">
        Track live workshop progress, scheduled dates, and service history (<?php echo count($bookings); ?> bookings found).
      </p>
    </div>

    <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
      <?php if ($filterVehicleId > 0): ?>
        <a href="bookings.php" class="btn btn-outline">
          Clear Vehicle Filter
        </a>
      <?php endif; ?>
      <a href="book-service.php" class="btn btn-primary">
        <span>+ Book New Service</span>
      </a>
    </div>
  </div>

  <?php if ($errorMsg): ?>
    <div class="form-alert form-alert-error" style="margin-bottom: 1.5rem;">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
      <span><?php echo htmlspecialchars($errorMsg); ?></span>
    </div>
  <?php endif; ?>

  <?php if (empty($bookings)): ?>
    <div class="form-card" style="padding: 3.5rem 2rem; text-align: center;">
      <div style="width: 64px; height: 64px; margin: 0 auto 1.5rem; border-radius: 50%; background: rgba(255,94,20,0.1); display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255,94,20,0.3);">
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="var(--accent-orange)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
      </div>
      <h3 style="font-size: 1.4rem; margin-bottom: 0.5rem; color: #ffffff;">No Service Bookings Found</h3>
      <p style="color: var(--text-muted); max-width: 460px; margin: 0 auto 1.75rem; font-size: 0.95rem;">
        You haven't scheduled any maintenance appointments yet. Select your vehicle and book your first workshop slot today.
      </p>
      <a href="book-service.php" class="btn btn-primary btn-lg">
        + Book Your First Service Slot
      </a>
    </div>
  <?php else: ?>

    <div class="form-card" style="padding: 1.5rem; overflow-x: auto;">
      <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
        <thead>
          <tr style="border-bottom: 2px solid var(--border-card); color: var(--text-main);">
            <th style="padding: 0.85rem 1rem;">Booking Code</th>
            <th style="padding: 0.85rem 1rem;">Vehicle</th>
            <th style="padding: 0.85rem 1rem;">Service Package</th>
            <th style="padding: 0.85rem 1rem;">Preferred Date &amp; Time</th>
            <th style="padding: 0.85rem 1rem;">Assigned Mechanic</th>
            <th style="padding: 0.85rem 1rem;">Status</th>
            <th style="padding: 0.85rem 1rem;">Booked On</th>
            <th style="padding: 0.85rem 1rem; text-align: right;">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($bookings as $bk): ?>
            <tr style="border-bottom: 1px solid var(--border-subtle);">
              
              <!-- 1. Booking Code -->
              <td style="padding: 1rem;">
                <a href="booking-details.php?code=<?php echo urlencode($bk['booking_code']); ?>" style="font-family: var(--font-heading); font-weight: 700; color: var(--accent-orange); text-decoration: none;">
                  <?php echo htmlspecialchars($bk['booking_code']); ?>
                </a>
              </td>

              <!-- 2. Vehicle Model & Reg No -->
              <td style="padding: 1rem;">
                <div style="font-weight: 600; color: #ffffff;">
                  <?php echo htmlspecialchars($bk['brand'] . ' ' . $bk['model']); ?>
                </div>
                <div style="font-family: monospace; font-size: 0.8rem; color: var(--accent-orange); font-weight: 600;">
                  <?php echo htmlspecialchars($bk['registration_number']); ?>
                </div>
                <div style="font-size: 0.75rem; color: var(--text-muted);">
                  <?php echo htmlspecialchars($bk['vehicle_type']); ?>
                </div>
              </td>

              <!-- 3. Service Package -->
              <td style="padding: 1rem;">
                <div style="font-weight: 600; color: #ffffff;">
                  <?php echo htmlspecialchars($bk['service_name']); ?>
                </div>
                <div style="font-size: 0.8rem; color: var(--text-secondary);">
                  <?php echo formatCurrency($bk['service_price']); ?>
                </div>
              </td>

              <!-- 4. Preferred Date & Time -->
              <td style="padding: 1rem;">
                <div style="font-weight: 600; color: #ffffff;">
                  <?php echo formatDisplayDate($bk['preferred_date']); ?>
                </div>
                <div style="font-size: 0.8rem; color: var(--text-muted);">
                  <?php echo htmlspecialchars($bk['preferred_time']); ?>
                </div>
              </td>

              <!-- 5. Assigned Mechanic -->
              <td style="padding: 1rem;">
                <?php if (!empty($bk['mechanic_name'])): ?>
                  <span style="color: #60a5fa; font-size: 0.85rem; font-weight: 600;">
                    🔧 <?php echo htmlspecialchars($bk['mechanic_name']); ?>
                  </span>
                <?php else: ?>
                  <span style="color: #fbbf24; font-size: 0.85rem; font-style: italic;">
                    Waiting for mechanic assignment
                  </span>
                <?php endif; ?>
              </td>

              <!-- 6. Status Badge -->
              <td style="padding: 1rem;">
                <?php 
                  $status = $bk['status'];
                  $tagClass = 'badge';
                  $bg = 'rgba(250,204,21,0.15)';
                  $color = '#fbbf24';
                  if ($status === 'Confirmed') {
                      $bg = 'rgba(56,189,248,0.15)';
                      $color = '#38bdf8';
                  } elseif ($status === 'Vehicle Received') {
                      $bg = 'rgba(250,204,21,0.15)';
                      $color = '#fbbf24';
                  } elseif ($status === 'Inspection' || $status === 'Service In Progress') {
                      $bg = 'rgba(255,94,20,0.15)';
                      $color = 'var(--accent-orange)';
                  } elseif ($status === 'Quality Check') {
                      $bg = 'rgba(168,85,247,0.15)';
                      $color = '#a855f7';
                  } elseif ($status === 'Ready for Delivery') {
                      $bg = 'rgba(16,185,129,0.15)';
                      $color = '#34d399';
                  } elseif ($status === 'Completed') {
                      $bg = 'rgba(16,185,129,0.15)';
                      $color = 'var(--color-green)';
                  } elseif ($status === 'Cancelled') {
                      $bg = 'rgba(239,68,68,0.15)';
                      $color = '#f87171';
                  }
                ?>
                <span class="service-tag" style="background: <?php echo $bg; ?>; color: <?php echo $color; ?>; border-color: <?php echo $color; ?>; font-size: 0.8rem; padding: 0.3rem 0.65rem;">
                  <?php echo htmlspecialchars($status); ?>
                </span>
              </td>

              <!-- 7. Created Date -->
              <td style="padding: 1rem; color: var(--text-muted); font-size: 0.8rem; white-space: nowrap;">
                <?php echo formatDisplayDate($bk['created_at']); ?>
              </td>

              <!-- 8. Action Link -->
              <td style="padding: 1rem; text-align: right;">
                <div style="display: inline-flex; gap: 0.5rem;">
                  <a href="booking-details.php?code=<?php echo urlencode($bk['booking_code']); ?>" class="btn btn-outline btn-sm">
                    View Details
                  </a>
                </div>
              </td>

            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

  <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
