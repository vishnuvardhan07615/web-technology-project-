<?php
/**
 * ============================================================================
 * MotoCare – Two-Wheeler Service & Mechanic Shop Management System
 * File: admin/bookings.php
 * Stage 2: Master Service Bookings Ledger (Upgraded with Search & Status Filters)
 * ============================================================================
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

requireAdminLogin();

$pageTitle = 'Manage Bookings – MotoCare Admin';
$currentPage = 'bookings.php';

$filterStatus = trim($_GET['status'] ?? 'all');
$searchTerm   = trim($_GET['search'] ?? '');

$bookings = [];
$errorMsg = trim($_GET['err'] ?? '');
$successMsg = '';

if (isset($_GET['msg']) && $_GET['msg'] === 'assigned') {
    $successMsg = 'Mechanic successfully assigned to service booking.';
}

if ($pdo) {
    try {
        $whereClauses = [];
        $params = [];

        // 1. Status Filter
        if ($filterStatus !== 'all') {
            if ($filterStatus === 'unassigned') {
                $whereClauses[] = "b.mechanic_id IS NULL";
            } elseif (in_array($filterStatus, BOOKING_STATUSES)) {
                $whereClauses[] = "b.status = :status";
                $params[':status'] = $filterStatus;
            }
        }

        // 2. Search Filter (Booking Code, Customer Name, Registration Number, Phone)
        if (!empty($searchTerm)) {
            $whereClauses[] = "(b.booking_code LIKE :search OR c.full_name LIKE :search OR v.registration_number LIKE :search OR c.phone LIKE :search)";
            $params[':search'] = '%' . $searchTerm . '%';
        }

        $whereSql = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

        // Query bookings with customer, vehicle, service, and mechanic JOINs
        $sql = "
            SELECT 
                b.*,
                c.full_name AS customer_name,
                c.phone AS customer_phone,
                c.email AS customer_email,
                v.brand, v.model, v.registration_number, v.vehicle_type,
                s.service_name, s.price AS service_price,
                m.full_name AS mechanic_name,
                sr.id AS service_record_id
            FROM bookings b
            JOIN customers c ON b.customer_id = c.id
            JOIN vehicles v ON b.vehicle_id = v.id
            JOIN services s ON b.service_id = s.id
            LEFT JOIN mechanics m ON b.mechanic_id = m.id
            LEFT JOIN service_records sr ON b.id = sr.booking_id
            {$whereSql}
            ORDER BY 
                CASE 
                    WHEN b.status = 'Pending' AND b.mechanic_id IS NULL THEN 1 
                    WHEN b.status = 'Confirmed' THEN 2 
                    ELSE 3 
                END,
                b.created_at DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $bookings = $stmt->fetchAll();

    } catch (PDOException $e) {
        $errorMsg = 'Database error loading bookings: ' . $e->getMessage();
    }
} else {
    // Offline demo fallback
    $bookings = [
        [
            'id' => 1,
            'booking_code' => 'MC-2026-1001',
            'customer_name' => 'Ramesh Kumar',
            'customer_phone' => '9876543210',
            'customer_email' => 'ramesh@example.com',
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
            'customer_name' => 'Ramesh Kumar',
            'customer_phone' => '9876543210',
            'customer_email' => 'ramesh@example.com',
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

// All filter buttons list (Section 3)
$allFilters = [
    'all'                 => 'All Bookings',
    'Pending'             => 'Pending',
    'unassigned'          => 'Unassigned',
    'Confirmed'           => 'Confirmed',
    'Vehicle Received'    => 'Vehicle Received',
    'Inspection'          => 'Inspection',
    'Service In Progress' => 'In Progress',
    'Quality Check'       => 'Quality Check',
    'Ready for Delivery'  => 'Ready for Delivery',
    'Completed'           => 'Completed',
    'Cancelled'           => 'Cancelled'
];

require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="container" style="padding-top: 1rem; padding-bottom: 3rem;">
  
  <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
    <div>
      <span class="service-tag" style="background: rgba(239,68,68,0.15); color: #f87171; border-color: rgba(239,68,68,0.3); margin-bottom: 0.5rem; display: inline-block;">
        Workshop Pipeline
      </span>
      <h1 style="font-size: 2rem; margin-bottom: 0.25rem;">Service Bookings Ledger</h1>
      <p style="color: var(--text-muted); font-size: 0.9rem;">
        Master log of all appointments, incoming customer requests, and mechanic assignments (<?php echo count($bookings); ?> bookings listed).
      </p>
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

  <!-- Search & Filter Bar (Section 3 Requirements) -->
  <div class="form-card" style="padding: 1.5rem; margin-bottom: 2rem; background: var(--bg-surface);">
    
    <!-- Search Form -->
    <form method="GET" action="bookings.php" style="display: flex; gap: 0.75rem; margin-bottom: 1.25rem; flex-wrap: wrap;">
      <input type="hidden" name="status" value="<?php echo htmlspecialchars($filterStatus); ?>">
      
      <div style="flex: 1; min-width: 280px; position: relative;">
        <input type="text" name="search" class="form-input" 
               placeholder="Search by Booking Code, Customer Name, License Plate, or Phone..." 
               value="<?php echo htmlspecialchars($searchTerm); ?>" 
               style="padding-left: 2.5rem;">
        <span style="position: absolute; left: 0.85rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 1rem;">
          🔍
        </span>
      </div>

      <button type="submit" class="btn btn-primary btn-sm" style="padding: 0.65rem 1.25rem;">
        Search Records
      </button>

      <?php if (!empty($searchTerm) || $filterStatus !== 'all'): ?>
        <a href="bookings.php" class="btn btn-outline btn-sm" style="padding: 0.65rem 1rem;">
          Reset Filters
        </a>
      <?php endif; ?>
    </form>

    <!-- Status Filter Buttons (Section 3 Requirements) -->
    <div style="display: flex; gap: 0.4rem; overflow-x: auto; padding-bottom: 0.35rem; scrollbar-width: thin;">
      <?php foreach ($allFilters as $stKey => $stLabel): 
        $isActive = ($filterStatus === $stKey);
      ?>
        <a href="bookings.php?status=<?php echo urlencode($stKey); ?><?php echo !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : ''; ?>" 
           class="btn btn-sm <?php echo $isActive ? 'btn-primary' : 'btn-outline'; ?>" 
           style="white-space: nowrap; padding: 0.35rem 0.75rem; font-size: 0.78rem;">
          <?php echo htmlspecialchars($stLabel); ?>
        </a>
      <?php endforeach; ?>
    </div>

  </div>

  <!-- Bookings Table (Section 3 Columns) -->
  <div class="form-card" style="padding: 1.5rem; overflow-x: auto;">
    <?php if (empty($bookings)): ?>
      <div style="padding: 3rem 1rem; text-align: center; color: var(--text-muted);">
        <p style="font-size: 1.1rem; margin-bottom: 0.75rem; color: #ffffff;">No service bookings match your search or filter criteria.</p>
        <a href="bookings.php" class="btn btn-secondary btn-sm">Clear All Filters</a>
      </div>
    <?php else: ?>
      <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
        <thead>
          <tr style="border-bottom: 2px solid var(--border-card); color: var(--text-main);">
            <th style="padding: 0.75rem 0.85rem;">Booking Code</th>
            <th style="padding: 0.75rem 0.85rem;">Customer Name</th>
            <th style="padding: 0.75rem 0.85rem;">Phone</th>
            <th style="padding: 0.75rem 0.85rem;">Vehicle</th>
            <th style="padding: 0.75rem 0.85rem;">Registration No.</th>
            <th style="padding: 0.75rem 0.85rem;">Service</th>
            <th style="padding: 0.75rem 0.85rem;">Appt. Date &amp; Time</th>
            <th style="padding: 0.75rem 0.85rem;">Status</th>
            <th style="padding: 0.75rem 0.85rem;">Job Card</th>
            <th style="padding: 0.75rem 0.85rem;">Assigned Mechanic</th>
            <th style="padding: 0.75rem 0.85rem; text-align: right;">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($bookings as $bk): ?>
            <tr style="border-bottom: 1px solid var(--border-subtle);">
              
              <!-- 1. Booking Code -->
              <td style="padding: 0.9rem 0.85rem; font-weight: 700; color: var(--accent-orange); font-family: monospace;">
                <a href="booking-details.php?id=<?php echo urlencode($bk['id']); ?>" style="color: var(--accent-orange); text-decoration: none;">
                  <?php echo htmlspecialchars($bk['booking_code']); ?>
                </a>
              </td>

              <!-- 2. Customer Name -->
              <td style="padding: 0.9rem 0.85rem; font-weight: 600; color: #ffffff;">
                <?php echo htmlspecialchars($bk['customer_name']); ?>
              </td>

              <!-- 3. Phone -->
              <td style="padding: 0.9rem 0.85rem; font-family: monospace; font-size: 0.85rem; color: var(--text-secondary);">
                <a href="tel:<?php echo htmlspecialchars($bk['customer_phone']); ?>" style="color: inherit; text-decoration: none;">
                  <?php echo htmlspecialchars($bk['customer_phone']); ?>
                </a>
              </td>

              <!-- 4. Vehicle (Brand + Model) -->
              <td style="padding: 0.9rem 0.85rem;">
                <div style="font-weight: 600; color: #ffffff;">
                  <?php echo htmlspecialchars($bk['brand'] . ' ' . $bk['model']); ?>
                </div>
                <div style="font-size: 0.75rem; color: var(--text-muted);">
                  <?php echo htmlspecialchars($bk['vehicle_type']); ?>
                </div>
              </td>

              <!-- 5. Registration Number -->
              <td style="padding: 0.9rem 0.85rem; font-family: monospace; font-weight: 600; color: var(--accent-orange);">
                <?php echo htmlspecialchars($bk['registration_number']); ?>
              </td>

              <!-- 6. Service -->
              <td style="padding: 0.9rem 0.85rem;">
                <div style="font-weight: 500; color: #ffffff;">
                  <?php echo htmlspecialchars($bk['service_name']); ?>
                </div>
                <div style="font-size: 0.8rem; color: var(--text-muted);">
                  <?php echo formatCurrency($bk['service_price']); ?>
                </div>
              </td>

              <!-- 7. Appointment Date & Time -->
              <td style="padding: 0.9rem 0.85rem;">
                <div style="font-weight: 600; color: #ffffff;">
                  <?php echo formatDisplayDate($bk['preferred_date']); ?>
                </div>
                <div style="font-size: 0.78rem; color: var(--text-muted);">
                  <?php echo htmlspecialchars($bk['preferred_time']); ?>
                </div>
              </td>

              <!-- 8. Status Badge (Latest Service Stage) -->
              <td style="padding: 0.9rem 0.85rem;">
                <?php 
                  $status = $bk['status'];
                  $bg = 'rgba(250,204,21,0.15)';
                  $color = '#fbbf24';
                  if ($status === 'Confirmed') {
                      $bg = 'rgba(56,189,248,0.15)';
                      $color = '#38bdf8';
                  } elseif ($status === 'Vehicle Received') {
                      $bg = 'rgba(250,204,21,0.15)';
                      $color = '#fbbf24';
                  } elseif ($status === 'Service In Progress' || $status === 'Inspection') {
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
                <span class="service-tag" style="background: <?php echo $bg; ?>; color: <?php echo $color; ?>; border-color: <?php echo $color; ?>; font-size: 0.78rem; padding: 0.25rem 0.55rem; white-space: nowrap;">
                  <?php echo htmlspecialchars($status); ?>
                </span>
              </td>

              <!-- 9. Job Card Existence (Section 16 Requirement) -->
              <td style="padding: 0.9rem 0.85rem;">
                <?php if (!empty($bk['service_record_id'])): ?>
                  <span class="badge" style="background: rgba(16,185,129,0.15); color: var(--color-green); border: 1px solid rgba(16,185,129,0.3); font-size: 0.72rem; padding: 0.2rem 0.5rem;">
                    ✓ Active
                  </span>
                <?php else: ?>
                  <span class="badge" style="background: rgba(255,255,255,0.05); color: var(--text-muted); border: 1px dashed var(--border-subtle); font-size: 0.72rem; padding: 0.2rem 0.5rem;">
                    None
                  </span>
                <?php endif; ?>
              </td>

              <!-- 10. Assigned Mechanic -->
              <td style="padding: 0.9rem 0.85rem;">
                <?php if (!empty($bk['mechanic_name'])): ?>
                  <span style="color: #60a5fa; font-size: 0.85rem; font-weight: 600;">
                    🔧 <?php echo htmlspecialchars($bk['mechanic_name']); ?>
                  </span>
                <?php else: ?>
                  <span class="badge" style="background: rgba(250,204,21,0.1); color: #fbbf24; border: 1px dashed rgba(250,204,21,0.3); font-size: 0.75rem; padding: 0.2rem 0.5rem;">
                    Unassigned
                  </span>
                <?php endif; ?>
              </td>

              <!-- 11. Action Button -->
              <td style="padding: 0.9rem 0.85rem; text-align: right;">
                <a href="booking-details.php?id=<?php echo urlencode($bk['id']); ?>" class="btn btn-outline btn-sm" style="padding: 0.35rem 0.7rem; font-size: 0.8rem; white-space: nowrap;">
                  <?php echo empty($bk['mechanic_name']) ? 'Assign Mechanic →' : ($bk['status'] === 'Completed' ? 'Invoice / Details →' : 'Inspect Details'); ?>
                </a>
              </td>

            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
