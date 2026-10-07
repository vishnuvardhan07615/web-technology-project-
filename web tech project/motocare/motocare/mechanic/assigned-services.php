<?php
/**
 * ============================================================================
 * MotoCare – Two-Wheeler Service & Mechanic Shop Management System
 * File: mechanic/assigned-services.php
 * Stage 2: Technician Assigned Service Jobs List
 * ============================================================================
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/constants.php';

requireMechanicLogin();

$mechanic = getCurrentMechanic();
$mechanicId = (int)$mechanic['id'];

$pageTitle = 'Assigned Two-Wheelers – Workshop Bay – MotoCare';
$currentPage = 'assigned-services.php';

$filterStatus = $_GET['status'] ?? 'all';
$bookings = [];
$errorMsg = '';

if ($pdo) {
    try {
        $sql = "
            SELECT 
                b.*,
                c.full_name AS customer_name,
                c.phone AS customer_phone,
                v.brand, v.model, v.registration_number, v.vehicle_type,
                s.service_name, s.estimated_duration
            FROM bookings b
            JOIN customers c ON b.customer_id = c.id
            JOIN vehicles v ON b.vehicle_id = v.id
            JOIN services s ON b.service_id = s.id
            WHERE b.mechanic_id = :mechanic_id
        ";
        
        $params = [':mechanic_id' => $mechanicId];
        
        if ($filterStatus === 'active') {
            $sql .= " AND b.status IN ('Vehicle Received', 'Inspection', 'Service In Progress', 'Quality Check')";
        } elseif ($filterStatus === 'completed') {
            $sql .= " AND b.status IN ('Ready for Delivery', 'Completed')";
        }

        $sql .= " ORDER BY b.preferred_date DESC, b.id DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $bookings = $stmt->fetchAll();
    } catch (PDOException $e) {
        $errorMsg = "Database error: " . $e->getMessage();
    }
} else {
    // Offline viva mock data
    $bookings = [
        [
            'id' => 1,
            'booking_code' => 'MC-2026-0001',
            'customer_name' => 'Rajesh Kumar',
            'customer_phone' => '9876543210',
            'brand' => 'Royal Enfield',
            'model' => 'Classic 350',
            'registration_number' => 'TN-09-BK-4521',
            'vehicle_type' => 'Motorcycle',
            'service_name' => 'General Periodic Maintenance',
            'preferred_date' => '2026-10-06',
            'preferred_time' => '09:00:00',
            'status' => 'Service In Progress',
            'problem_description' => 'Engine tappet noise and hard front brake pull.'
        ],
        [
            'id' => 3,
            'booking_code' => 'MC-2026-0003',
            'customer_name' => 'Vignesh Sundaram',
            'customer_phone' => '9443215678',
            'brand' => 'Yamaha',
            'model' => 'R15 V4',
            'registration_number' => 'TN-14-R1-5004',
            'vehicle_type' => 'Motorcycle',
            'service_name' => 'Chain Drive Maintenance & Sprocket Replacement',
            'preferred_date' => '2026-10-07',
            'preferred_time' => '11:00:00',
            'status' => 'Inspection',
            'problem_description' => 'Chain slack slapping against swingarm guard.'
        ]
    ];
}

include __DIR__ . '/../includes/mechanic-header.php';
?>

<div class="container">
  
  <div class="section-header" style="text-align: left; margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 1rem;">
    <div>
      <span class="section-tagline">Bay Work Queue</span>
      <h1 class="section-title" style="font-size: 2rem;">Assigned Service Jobs</h1>
      <p class="section-description">Manage job tickets allocated to you by shop floor administration.</p>
    </div>
    
    <!-- Filter Pills -->
    <div style="display: flex; gap: 0.5rem; background: var(--bg-card); padding: 0.35rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
      <a href="assigned-services.php?status=all" class="btn btn-sm <?php echo ($filterStatus === 'all') ? 'btn-primary' : 'btn-outline'; ?>" style="padding: 0.35rem 0.85rem; font-size: 0.8rem; <?php echo ($filterStatus === 'all') ? 'background: var(--color-green); border-color: var(--color-green);' : ''; ?>">All Jobs</a>
      <a href="assigned-services.php?status=active" class="btn btn-sm <?php echo ($filterStatus === 'active') ? 'btn-primary' : 'btn-outline'; ?>" style="padding: 0.35rem 0.85rem; font-size: 0.8rem; <?php echo ($filterStatus === 'active') ? 'background: var(--color-green); border-color: var(--color-green);' : ''; ?>">In Progress</a>
      <a href="assigned-services.php?status=completed" class="btn btn-sm <?php echo ($filterStatus === 'completed') ? 'btn-primary' : 'btn-outline'; ?>" style="padding: 0.35rem 0.85rem; font-size: 0.8rem; <?php echo ($filterStatus === 'completed') ? 'background: var(--color-green); border-color: var(--color-green);' : ''; ?>">Completed</a>
    </div>
  </div>

  <?php if ($errorMsg): ?>
    <div class="form-alert form-alert-error" style="margin-bottom: 1.5rem;">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
      <span><?php echo htmlspecialchars($errorMsg); ?></span>
    </div>
  <?php endif; ?>

  <div class="dashboard-card" style="padding: 0; overflow: hidden; background: var(--bg-card); border-radius: var(--radius-lg); border: 1px solid var(--border-subtle);">
    <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border-subtle); display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02);">
      <h3 style="font-size: 1.1rem; color: #ffffff; margin: 0;">Service Queue (<?php echo count($bookings); ?> bikes)</h3>
      <span class="badge" style="background: rgba(16,185,129,0.1); color: var(--color-green); border: 1px solid rgba(16,185,129,0.3);">Technician Bay</span>
    </div>

    <?php if (empty($bookings)): ?>
      <div style="padding: 3rem; text-align: center; color: var(--text-muted);">
        <p>No job tickets found matching your filter selection.</p>
      </div>
    <?php else: ?>
      <div style="overflow-x: auto;">
        <table class="data-table" style="width: 100%; border-collapse: collapse; text-align: left;">
          <thead>
            <tr style="border-bottom: 1px solid var(--border-subtle); background: var(--bg-surface); font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em;">
              <th style="padding: 1rem 1.5rem;">Booking Code</th>
              <th style="padding: 1rem 1.5rem;">Two-Wheeler</th>
              <th style="padding: 1rem 1.5rem;">Customer Details</th>
              <th style="padding: 1rem 1.5rem;">Package</th>
              <th style="padding: 1rem 1.5rem;">Status</th>
              <th style="padding: 1rem 1.5rem; text-align: right;">Action</th>
            </tr>
          </thead>
          <tbody style="font-size: 0.95rem;">
            <?php foreach ($bookings as $bk): ?>
              <tr style="border-bottom: 1px solid var(--border-subtle);">
                <td style="padding: 1rem 1.5rem;">
                  <div style="font-family: monospace; font-weight: 700; color: var(--accent-orange);"><?php echo htmlspecialchars($bk['booking_code']); ?></div>
                  <div style="font-size: 0.8rem; color: var(--text-muted);"><?php echo formatDisplayDate($bk['preferred_date']); ?></div>
                </td>
                <td style="padding: 1rem 1.5rem;">
                  <div style="font-weight: 600; color: #ffffff;"><?php echo htmlspecialchars($bk['brand'] . ' ' . $bk['model']); ?></div>
                  <div style="font-size: 0.8rem; color: var(--accent-orange); font-family: monospace;"><?php echo htmlspecialchars($bk['registration_number']); ?></div>
                  <div style="font-size: 0.75rem; color: var(--text-muted);"><?php echo htmlspecialchars($bk['vehicle_type']); ?></div>
                </td>
                <td style="padding: 1rem 1.5rem;">
                  <div style="font-weight: 500; color: #ffffff;"><?php echo htmlspecialchars($bk['customer_name']); ?></div>
                  <div style="font-size: 0.8rem; color: var(--text-muted);">📞 <?php echo htmlspecialchars($bk['customer_phone']); ?></div>
                </td>
                <td style="padding: 1rem 1.5rem;">
                  <div style="font-weight: 600; color: #ffffff; font-size: 0.9rem;"><?php echo htmlspecialchars($bk['service_name']); ?></div>
                  <div style="font-size: 0.8rem; color: var(--text-muted);">Est: <?php echo htmlspecialchars($bk['estimated_duration'] ?? '60 mins'); ?></div>
                </td>
                <td style="padding: 1rem 1.5rem;">
                  <span class="badge" style="background: rgba(16,185,129,0.15); color: #34d399; border: 1px solid rgba(16,185,129,0.3);">
                    <?php echo htmlspecialchars($bk['status']); ?>
                  </span>
                </td>
                <td style="padding: 1rem 1.5rem; text-align: right;">
                  <a href="job-card.php?id=<?php echo (int)$bk['id']; ?>" class="btn btn-primary btn-sm" style="background: var(--color-green); border-color: var(--color-green); padding: 0.35rem 0.85rem; font-size: 0.85rem;">
                    Open Job Card →
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
