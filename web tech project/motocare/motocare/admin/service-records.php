<?php
/**
 * ============================================================================
 * MotoCare – Two-Wheeler Service & Mechanic Shop Management System
 * File: admin/service-records.php
 * Stage 2: Workshop Job Cards & Technician Service Logs
 * ============================================================================
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/constants.php';

requireAdminLogin();

$pageTitle = 'Workshop Job Cards – Admin Control – MotoCare';
$currentPage = 'service-records.php';

$records = [];
$errorMsg = '';

if ($pdo) {
    try {
        $query = "
            SELECT 
                sr.*,
                b.booking_code,
                b.preferred_date,
                b.problem_description,
                c.full_name AS customer_name,
                c.phone AS customer_phone,
                v.brand, v.model, v.registration_number,
                m.full_name AS mechanic_name
            FROM service_records sr
            JOIN bookings b ON sr.booking_id = b.id
            JOIN customers c ON b.customer_id = c.id
            JOIN vehicles v ON b.vehicle_id = v.id
            LEFT JOIN mechanics m ON sr.mechanic_id = m.id
            ORDER BY sr.created_at DESC
        ";
        $stmt = $pdo->query($query);
        $records = $stmt->fetchAll();
    } catch (PDOException $e) {
        $errorMsg = "Database error: " . $e->getMessage();
    }
} else {
    // Offline viva mock data
    $records = [
        [
            'id' => 1,
            'booking_code' => 'MC-2026-0001',
            'customer_name' => 'Rajesh Kumar',
            'customer_phone' => '9876543210',
            'brand' => 'Royal Enfield',
            'model' => 'Classic 350',
            'registration_number' => 'TN-09-BK-4521',
            'mechanic_name' => 'Arun Kumar',
            'inspection_notes' => 'Engine tappet noise detected; front brake pad 80% worn out.',
            'work_done' => 'Oil replacement, oil filter change, spark plug cleanup, chain slack tensioning.',
            'parts_used' => 'Motul 7100 15W-50 (2.5L), OEM Oil Filter, Spark Plug NGK',
            'service_start' => '2026-10-06 09:30:00',
            'service_end' => '2026-10-06 12:45:00',
            'service_status' => 'Completed',
            'created_at' => '2026-10-06 09:30:00'
        ],
        [
            'id' => 2,
            'booking_code' => 'MC-2026-0002',
            'customer_name' => 'Priya Sharma',
            'customer_phone' => '9845123456',
            'brand' => 'Ather',
            'model' => '450X Gen 3',
            'registration_number' => 'TN-22-EV-9811',
            'mechanic_name' => 'Karthik Raja',
            'inspection_notes' => 'BMS diagnostic diagnostic pass; regenerative braking sensor calibrated.',
            'work_done' => 'Belt tension calibrated, firmware health check, brake bleed.',
            'parts_used' => 'DOT4 Brake Fluid (250ml)',
            'service_start' => '2026-10-06 10:15:00',
            'service_end' => null,
            'service_status' => 'Service In Progress',
            'created_at' => '2026-10-06 10:15:00'
        ]
    ];
}

include __DIR__ . '/../includes/admin-header.php';
?>

<div class="container">
  <div class="section-header" style="text-align: left; margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 1rem;">
    <div>
      <span class="section-tagline">Workshop Operations</span>
      <h1 class="section-title" style="font-size: 2rem;">Service Records &amp; Job Cards</h1>
      <p class="section-description">Detailed technician notes, spare parts consumed, labor timestamps, and job completion stages.</p>
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
      <h3 style="font-size: 1.1rem; color: #ffffff; margin: 0;">Service Job Cards (<?php echo count($records); ?> records)</h3>
      <span class="badge" style="background: rgba(255,107,0,0.1); color: var(--accent-orange); border: 1px solid rgba(255,107,0,0.3);">Stage 2 Foundation</span>
    </div>

    <?php if (empty($records)): ?>
      <div style="padding: 3rem; text-align: center; color: var(--text-muted);">
        <p>No job card records logged yet.</p>
      </div>
    <?php else: ?>
      <div style="overflow-x: auto;">
        <table class="data-table" style="width: 100%; border-collapse: collapse; text-align: left;">
          <thead>
            <tr style="border-bottom: 1px solid var(--border-subtle); background: var(--bg-surface); font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em;">
              <th style="padding: 1rem 1.5rem;">Job Card #</th>
              <th style="padding: 1rem 1.5rem;">Vehicle &amp; Customer</th>
              <th style="padding: 1rem 1.5rem;">Assigned Mechanic</th>
              <th style="padding: 1rem 1.5rem;">Work Done &amp; Spares</th>
              <th style="padding: 1rem 1.5rem;">Status</th>
              <th style="padding: 1rem 1.5rem; text-align: right;">Action</th>
            </tr>
          </thead>
          <tbody style="font-size: 0.95rem;">
            <?php foreach ($records as $rec): ?>
              <tr style="border-bottom: 1px solid var(--border-subtle);">
                <td style="padding: 1rem 1.5rem;">
                  <div style="font-family: monospace; font-weight: 700; color: var(--accent-orange);">
                    <?php echo htmlspecialchars($rec['booking_code']); ?>
                  </div>
                  <div style="font-size: 0.8rem; color: var(--text-muted);">
                    ID: #<?php echo htmlspecialchars($rec['id']); ?>
                  </div>
                </td>
                <td style="padding: 1rem 1.5rem;">
                  <div style="font-weight: 600; color: #ffffff;">
                    <?php echo htmlspecialchars($rec['brand'] . ' ' . $rec['model']); ?>
                  </div>
                  <div style="font-size: 0.8rem; color: var(--accent-orange); font-family: monospace;">
                    <?php echo htmlspecialchars($rec['registration_number']); ?>
                  </div>
                  <div style="font-size: 0.8rem; color: var(--text-muted);">
                    Owner: <?php echo htmlspecialchars($rec['customer_name']); ?>
                  </div>
                </td>
                <td style="padding: 1rem 1.5rem;">
                  <span class="badge" style="background: rgba(59,130,246,0.15); color: #60a5fa; border: 1px solid rgba(59,130,246,0.3);">
                    🔧 <?php echo htmlspecialchars($rec['mechanic_name'] ?? 'Unassigned'); ?>
                  </span>
                </td>
                <td style="padding: 1rem 1.5rem; max-width: 320px;">
                  <div style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 0.35rem;">
                    <strong>Work:</strong> <?php echo htmlspecialchars($rec['work_done'] ?? 'Pending'); ?>
                  </div>
                  <?php if (!empty($rec['parts_used'])): ?>
                    <div style="font-size: 0.8rem; color: var(--text-muted);">
                      <strong>Parts:</strong> <?php echo htmlspecialchars($rec['parts_used']); ?>
                    </div>
                  <?php endif; ?>
                </td>
                <td style="padding: 1rem 1.5rem;">
                  <span class="badge" style="background: rgba(255,107,0,0.15); color: var(--accent-orange); border: 1px solid rgba(255,107,0,0.3);">
                    <?php echo htmlspecialchars($rec['service_status']); ?>
                  </span>
                </td>
                <td style="padding: 1rem 1.5rem; text-align: right;">
                  <a href="<?php echo $rootPath; ?>admin/booking-details.php?code=<?php echo urlencode($rec['booking_code']); ?>" class="btn btn-outline btn-sm">
                    View Details
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
