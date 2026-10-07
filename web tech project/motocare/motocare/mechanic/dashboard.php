<?php
/**
 * ============================================================================
 * MotoCare – Two-Wheeler Service & Mechanic Shop Management System
 * File: mechanic/dashboard.php
 * Stage 2: Technician Workshop Bay Dashboard & Assigned Bookings Execution Queue
 * ============================================================================
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

requireMechanicLogin();

$mechanic = getCurrentMechanic();
$mechanicId = (int)$mechanic['id'];

$pageTitle = 'Workshop Bay Dashboard – MotoCare';
$currentPage = 'dashboard.php';

$filterStatus = trim($_GET['status'] ?? 'all');
$errorMsg = trim($_GET['err'] ?? '');
$successMsg = trim($_GET['msg'] ?? '');

// Initialize 8 Useful Metrics (Section 4 Requirement)
$metrics = [
    'total_assigned'      => 0,
    'confirmed'           => 0,
    'vehicle_received'    => 0,
    'inspection'          => 0,
    'service_in_progress' => 0,
    'quality_check'       => 0,
    'ready_for_delivery'  => 0,
    'completed'           => 0
];

$assignedBookings = [];

if ($pdo) {
    try {
        // Query ALL assigned bookings for this logged-in mechanic (Anti-IDOR)
        $sql = "
            SELECT 
                b.*,
                c.full_name AS customer_name,
                c.phone AS customer_phone,
                v.brand, v.model, v.registration_number, v.vehicle_type,
                s.service_name, s.estimated_duration, s.price AS service_price,
                sr.id AS service_record_id,
                sr.inspection_notes,
                sr.work_done,
                sr.labor_hours,
                sr.service_start,
                sr.service_end
            FROM bookings b
            JOIN customers c ON b.customer_id = c.id
            JOIN vehicles v ON b.vehicle_id = v.id
            JOIN services s ON b.service_id = s.id
            LEFT JOIN service_records sr ON b.id = sr.booking_id
            WHERE b.mechanic_id = :mechanic_id
            ORDER BY 
                CASE 
                    WHEN b.status = 'Service In Progress' THEN 1
                    WHEN b.status = 'Inspection' THEN 2
                    WHEN b.status = 'Vehicle Received' THEN 3
                    WHEN b.status = 'Confirmed' THEN 4
                    WHEN b.status = 'Quality Check' THEN 5
                    WHEN b.status = 'Ready for Delivery' THEN 6
                    WHEN b.status = 'Completed' THEN 7
                    ELSE 8
                END,
                b.preferred_date ASC,
                b.id DESC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':mechanic_id' => $mechanicId]);
        $allBookings = $stmt->fetchAll();

        // Calculate 8 Metrics dynamically
        $metrics['total_assigned'] = count($allBookings);
        foreach ($allBookings as $bk) {
            $st = $bk['status'];
            if ($st === 'Confirmed') {
                $metrics['confirmed']++;
            } elseif ($st === 'Vehicle Received') {
                $metrics['vehicle_received']++;
            } elseif ($st === 'Inspection') {
                $metrics['inspection']++;
            } elseif ($st === 'Service In Progress') {
                $metrics['service_in_progress']++;
            } elseif ($st === 'Quality Check') {
                $metrics['quality_check']++;
            } elseif ($st === 'Ready for Delivery') {
                $metrics['ready_for_delivery']++;
            } elseif ($st === 'Completed') {
                $metrics['completed_jobs'] = ($metrics['completed_jobs'] ?? 0) + 1;
                $metrics['completed']++;
            }
        }

        // Apply filter if specified
        if ($filterStatus !== 'all') {
            $assignedBookings = array_filter($allBookings, function($item) use ($filterStatus) {
                return $item['status'] === $filterStatus;
            });
        } else {
            $assignedBookings = $allBookings;
        }

    } catch (PDOException $e) {
        $errorMsg = 'Database error loading workshop bay queue: ' . $e->getMessage();
    }
} else {
    // Offline viva mock data (strictly for assigned technician)
    $metrics = [
        'total_assigned'      => 3,
        'confirmed'           => 1,
        'vehicle_received'    => 0,
        'inspection'          => 1,
        'service_in_progress' => 1,
        'quality_check'       => 0,
        'ready_for_delivery'  => 0,
        'completed'           => 0
    ];

    $assignedBookings = [
        [
            'id' => 1,
            'booking_code' => 'MC-2026-1001',
            'customer_name' => 'Ramesh Kumar',
            'customer_phone' => '9876543210',
            'brand' => 'Royal Enfield',
            'model' => 'Classic 350',
            'registration_number' => 'TN-07-AB-1234',
            'vehicle_type' => 'Motorcycle',
            'service_name' => 'General Service',
            'service_price' => 500.00,
            'estimated_duration' => '2 - 3 Hours',
            'preferred_date' => '2026-10-10',
            'preferred_time' => '10:00 AM - 01:00 PM',
            'status' => 'Confirmed',
            'problem_description' => 'Periodic 5000 km general service and slight front disc squeak.',
            'service_record_id' => 1
        ],
        [
            'id' => 3,
            'booking_code' => 'MC-2026-1003',
            'customer_name' => 'Vignesh Sundaram',
            'customer_phone' => '9443215678',
            'brand' => 'Yamaha',
            'model' => 'R15 V4',
            'registration_number' => 'TN-14-R1-5004',
            'vehicle_type' => 'Motorcycle',
            'service_name' => 'Chain Drive Maintenance & Sprocket Replacement',
            'service_price' => 450.00,
            'estimated_duration' => '45 mins',
            'preferred_date' => '2026-10-07',
            'preferred_time' => '11:00:00',
            'status' => 'Inspection',
            'problem_description' => 'Chain slack slapping against swingarm guard.',
            'service_record_id' => 2
        ],
        [
            'id' => 4,
            'booking_code' => 'MC-2026-1004',
            'customer_name' => 'Karthik Raja',
            'customer_phone' => '9884012345',
            'brand' => 'KTM',
            'model' => 'Duke 250',
            'registration_number' => 'TN-02-CD-9988',
            'vehicle_type' => 'Motorcycle',
            'service_name' => 'Brake Service & Fluid Flush',
            'service_price' => 350.00,
            'estimated_duration' => '1 Hour',
            'preferred_date' => '2026-10-08',
            'preferred_time' => '02:00 PM',
            'status' => 'Service In Progress',
            'problem_description' => 'Spongy rear brake pedal feel.',
            'service_record_id' => 3
        ]
    ];
}

include __DIR__ . '/../includes/mechanic-header.php';
?>

<div class="container" style="padding-top: 1rem; padding-bottom: 3rem;">
  
  <!-- Welcome Banner -->
  <div class="dashboard-banner" style="background: linear-gradient(135deg, rgba(16,185,129,0.15) 0%, rgba(18,22,29,0.9) 100%); border: 1px solid rgba(16,185,129,0.3); border-radius: var(--radius-lg); padding: 2rem; margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1.5rem;">
    <div>
      <span class="badge" style="background: rgba(16,185,129,0.2); color: var(--color-green); border: 1px solid rgba(16,185,129,0.4); margin-bottom: 0.75rem;">Workshop Technician Bay</span>
      <h1 style="font-size: 2rem; color: #ffffff; margin-bottom: 0.5rem; font-family: var(--font-heading);">
        Welcome, <?php echo htmlspecialchars($mechanic['name']); ?>!
      </h1>
      <p style="color: var(--text-secondary); margin: 0; max-width: 600px;">
        Specialization: <strong style="color: #ffffff;"><?php echo htmlspecialchars($mechanic['specialization'] ?? 'Master Technician'); ?></strong>
        &bull; Bay ID: <strong style="color: var(--accent-orange);">Bay #<?php echo $mechanicId; ?></strong>
      </p>
    </div>
    <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
      <span class="badge" style="background: rgba(16,185,129,0.2); color: #34d399; border: 1px solid rgba(16,185,129,0.3); font-size: 0.9rem; padding: 0.5rem 1rem;">
        ● Bay Status: <?php echo htmlspecialchars($mechanic['status'] ?? 'Available'); ?>
      </span>
      <a href="assigned-services.php" class="btn btn-primary" style="background: var(--color-green); border-color: var(--color-green);">
        Assigned Jobs View →
      </a>
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

  <!-- Useful Metrics Grid (Section 4: 8 Metrics) -->
  <div style="margin-bottom: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
      <h2 style="font-size: 1.15rem; color: #ffffff; text-transform: uppercase; letter-spacing: 0.05em; margin: 0;">
        Workshop Execution Metrics
      </h2>
      <span style="font-size: 0.8rem; color: var(--text-muted);">Real-time count of your allocated work</span>
    </div>

    <div class="metrics-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 1rem;">
      
      <!-- 1. Total Assigned -->
      <a href="dashboard.php?status=all" style="text-decoration: none; color: inherit;">
        <div class="dashboard-card" style="background: var(--bg-card); border: 1px solid <?php echo ($filterStatus === 'all') ? 'var(--accent-orange)' : 'var(--border-subtle)'; ?>; border-radius: var(--radius-md); padding: 1.15rem 1rem; text-align: center;">
          <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.35rem;">Assigned</div>
          <div style="font-size: 1.85rem; font-weight: 800; color: #ffffff; font-family: var(--font-heading); line-height: 1;">
            <?php echo $metrics['total_assigned']; ?>
          </div>
          <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 0.35rem;">Total Allocated</div>
        </div>
      </a>

      <!-- 2. Confirmed -->
      <a href="dashboard.php?status=Confirmed" style="text-decoration: none; color: inherit;">
        <div class="dashboard-card" style="background: var(--bg-card); border: 1px solid <?php echo ($filterStatus === 'Confirmed') ? '#38bdf8' : 'var(--border-subtle)'; ?>; border-radius: var(--radius-md); padding: 1.15rem 1rem; text-align: center;">
          <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.35rem;">Confirmed</div>
          <div style="font-size: 1.85rem; font-weight: 800; color: #38bdf8; font-family: var(--font-heading); line-height: 1;">
            <?php echo $metrics['confirmed']; ?>
          </div>
          <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 0.35rem;">Awaiting Arrival</div>
        </div>
      </a>

      <!-- 3. Vehicle Received -->
      <a href="dashboard.php?status=Vehicle+Received" style="text-decoration: none; color: inherit;">
        <div class="dashboard-card" style="background: var(--bg-card); border: 1px solid <?php echo ($filterStatus === 'Vehicle Received') ? '#fbbf24' : 'var(--border-subtle)'; ?>; border-radius: var(--radius-md); padding: 1.15rem 1rem; text-align: center;">
          <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.35rem;">Received</div>
          <div style="font-size: 1.85rem; font-weight: 800; color: #fbbf24; font-family: var(--font-heading); line-height: 1;">
            <?php echo $metrics['vehicle_received']; ?>
          </div>
          <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 0.35rem;">In Bay Lot</div>
        </div>
      </a>

      <!-- 4. Inspection -->
      <a href="dashboard.php?status=Inspection" style="text-decoration: none; color: inherit;">
        <div class="dashboard-card" style="background: var(--bg-card); border: 1px solid <?php echo ($filterStatus === 'Inspection') ? 'var(--accent-orange)' : 'var(--border-subtle)'; ?>; border-radius: var(--radius-md); padding: 1.15rem 1rem; text-align: center;">
          <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.35rem;">Inspection</div>
          <div style="font-size: 1.85rem; font-weight: 800; color: var(--accent-orange); font-family: var(--font-heading); line-height: 1;">
            <?php echo $metrics['inspection']; ?>
          </div>
          <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 0.35rem;">Diagnostics</div>
        </div>
      </a>

      <!-- 5. Service In Progress -->
      <a href="dashboard.php?status=Service+In+Progress" style="text-decoration: none; color: inherit;">
        <div class="dashboard-card" style="background: var(--bg-card); border: 1px solid <?php echo ($filterStatus === 'Service In Progress') ? 'var(--accent-orange)' : 'var(--border-subtle)'; ?>; border-radius: var(--radius-md); padding: 1.15rem 1rem; text-align: center;">
          <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.35rem;">In Progress</div>
          <div style="font-size: 1.85rem; font-weight: 800; color: #f97316; font-family: var(--font-heading); line-height: 1;">
            <?php echo $metrics['service_in_progress']; ?>
          </div>
          <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 0.35rem;">On Hydraulic Lift</div>
        </div>
      </a>

      <!-- 6. Quality Check -->
      <a href="dashboard.php?status=Quality+Check" style="text-decoration: none; color: inherit;">
        <div class="dashboard-card" style="background: var(--bg-card); border: 1px solid <?php echo ($filterStatus === 'Quality Check') ? '#a855f7' : 'var(--border-subtle)'; ?>; border-radius: var(--radius-md); padding: 1.15rem 1rem; text-align: center;">
          <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.35rem;">QC / Test</div>
          <div style="font-size: 1.85rem; font-weight: 800; color: #a855f7; font-family: var(--font-heading); line-height: 1;">
            <?php echo $metrics['quality_check']; ?>
          </div>
          <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 0.35rem;">Road Test</div>
        </div>
      </a>

      <!-- 7. Ready for Delivery -->
      <a href="dashboard.php?status=Ready+for+Delivery" style="text-decoration: none; color: inherit;">
        <div class="dashboard-card" style="background: var(--bg-card); border: 1px solid <?php echo ($filterStatus === 'Ready for Delivery') ? '#10b981' : 'var(--border-subtle)'; ?>; border-radius: var(--radius-md); padding: 1.15rem 1rem; text-align: center;">
          <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.35rem;">Ready</div>
          <div style="font-size: 1.85rem; font-weight: 800; color: #10b981; font-family: var(--font-heading); line-height: 1;">
            <?php echo $metrics['ready_for_delivery']; ?>
          </div>
          <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 0.35rem;">Washed &amp; Parked</div>
        </div>
      </a>

      <!-- 8. Completed -->
      <a href="dashboard.php?status=Completed" style="text-decoration: none; color: inherit;">
        <div class="dashboard-card" style="background: var(--bg-card); border: 1px solid <?php echo ($filterStatus === 'Completed') ? 'var(--color-green)' : 'var(--border-subtle)'; ?>; border-radius: var(--radius-md); padding: 1.15rem 1rem; text-align: center;">
          <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.35rem;">Completed</div>
          <div style="font-size: 1.85rem; font-weight: 800; color: var(--color-green); font-family: var(--font-heading); line-height: 1;">
            <?php echo $metrics['completed']; ?>
          </div>
          <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 0.35rem;">Closed Jobs</div>
        </div>
      </a>

    </div>
  </div>

  <!-- Section Header with Filter Controls -->
  <div class="section-header" style="text-align: left; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 1rem;">
    <div>
      <h2 style="font-size: 1.4rem; color: #ffffff; margin-bottom: 0.25rem;">My Workshop Queue</h2>
      <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0;">
        Two-wheelers assigned exclusively to your technician bay.
      </p>
    </div>

    <!-- Filter Pills -->
    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
      <?php if ($filterStatus !== 'all'): ?>
        <a href="dashboard.php" class="btn btn-outline btn-sm">
          Clear Filter (Showing: <?php echo htmlspecialchars($filterStatus); ?>)
        </a>
      <?php endif; ?>
      <a href="dashboard.php" class="btn btn-secondary btn-sm">
        ↻ Refresh Bay Queue
      </a>
    </div>
  </div>

  <!-- Workshop Queue Table (Section 4 & Section 20 Columns) -->
  <div class="form-card" style="padding: 1.5rem; overflow-x: auto;">
    <?php if (empty($assignedBookings)): ?>
      <div style="padding: 3.5rem 1rem; text-align: center; color: var(--text-muted);">
        <div style="font-size: 2rem; margin-bottom: 0.75rem;">🔧</div>
        <p style="font-size: 1.1rem; margin-bottom: 0.5rem; color: #ffffff;">No service bookings in your bay queue for this filter.</p>
        <p style="font-size: 0.85rem; margin-bottom: 1.25rem;">New bookings will appear here as shop floor admin allocates them to you.</p>
        <a href="dashboard.php" class="btn btn-secondary btn-sm">View All Assigned Tickets</a>
      </div>
    <?php else: ?>
      <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
        <thead>
          <tr style="border-bottom: 2px solid var(--border-card); color: var(--text-main);">
            <th style="padding: 0.85rem 0.75rem;">Booking Code</th>
            <th style="padding: 0.85rem 0.75rem;">Customer Name</th>
            <th style="padding: 0.85rem 0.75rem;">Vehicle</th>
            <th style="padding: 0.85rem 0.75rem;">Registration No.</th>
            <th style="padding: 0.85rem 0.75rem;">Service</th>
            <th style="padding: 0.85rem 0.75rem;">Appt. Date</th>
            <th style="padding: 0.85rem 0.75rem;">Appt. Time</th>
            <th style="padding: 0.85rem 0.75rem;">Current Status</th>
            <th style="padding: 0.85rem 0.75rem;">Job Card</th>
            <th style="padding: 0.85rem 0.75rem; text-align: right;">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($assignedBookings as $job): 
            $status = $job['status'];
            $hasJobCard = !empty($job['service_record_id']);
            
            // Badge color mapping
            $bColor = '#38bdf8';
            $bBg = 'rgba(56,189,248,0.15)';
            if ($status === 'Confirmed') { $bColor = '#38bdf8'; $bBg = 'rgba(56,189,248,0.15)'; }
            elseif ($status === 'Vehicle Received') { $bColor = '#fbbf24'; $bBg = 'rgba(250,204,21,0.15)'; }
            elseif ($status === 'Inspection' || $status === 'Service In Progress') { $bColor = 'var(--accent-orange)'; $bBg = 'rgba(255,94,20,0.15)'; }
            elseif ($status === 'Quality Check' || $status === 'Ready for Delivery') { $bColor = '#a855f7'; $bBg = 'rgba(168,85,247,0.15)'; }
            elseif ($status === 'Completed') { $bColor = 'var(--color-green)'; $bBg = 'rgba(16,185,129,0.15)'; }
          ?>
            <tr style="border-bottom: 1px solid var(--border-subtle);">
              
              <!-- 1. Booking Code -->
              <td style="padding: 0.9rem 0.75rem;">
                <a href="job-card.php?id=<?php echo (int)$job['id']; ?>" style="font-family: monospace; font-weight: 700; color: var(--accent-orange); text-decoration: none;">
                  <?php echo htmlspecialchars($job['booking_code']); ?>
                </a>
              </td>

              <!-- 2. Customer Name -->
              <td style="padding: 0.9rem 0.75rem;">
                <div style="font-weight: 600; color: #ffffff;">
                  <?php echo htmlspecialchars($job['customer_name']); ?>
                </div>
                <div style="font-size: 0.75rem; color: var(--text-muted);">
                  📞 <?php echo htmlspecialchars($job['customer_phone']); ?>
                </div>
              </td>

              <!-- 3. Vehicle -->
              <td style="padding: 0.9rem 0.75rem;">
                <div style="font-weight: 600; color: #ffffff;">
                  <?php echo htmlspecialchars($job['brand'] . ' ' . $job['model']); ?>
                </div>
                <div style="font-size: 0.72rem; color: var(--text-muted);">
                  <?php echo htmlspecialchars($job['vehicle_type']); ?>
                </div>
              </td>

              <!-- 4. Registration Number -->
              <td style="padding: 0.9rem 0.75rem; font-family: monospace; font-weight: 700; color: var(--accent-orange);">
                <?php echo htmlspecialchars($job['registration_number']); ?>
              </td>

              <!-- 5. Service -->
              <td style="padding: 0.9rem 0.75rem;">
                <div style="font-weight: 500; color: #ffffff;">
                  <?php echo htmlspecialchars($job['service_name']); ?>
                </div>
                <div style="font-size: 0.75rem; color: var(--text-muted);">
                  <?php echo htmlspecialchars($job['estimated_duration'] ?? '60 mins'); ?>
                </div>
              </td>

              <!-- 6. Appointment Date -->
              <td style="padding: 0.9rem 0.75rem; color: #ffffff; white-space: nowrap;">
                <?php echo formatDisplayDate($job['preferred_date']); ?>
              </td>

              <!-- 7. Appointment Time -->
              <td style="padding: 0.9rem 0.75rem; color: var(--text-muted); font-size: 0.85rem; white-space: nowrap;">
                <?php echo htmlspecialchars($job['preferred_time']); ?>
              </td>

              <!-- 8. Current Status -->
              <td style="padding: 0.9rem 0.75rem;">
                <span class="service-tag" style="background: <?php echo $bBg; ?>; color: <?php echo $bColor; ?>; border-color: <?php echo $bColor; ?>; font-size: 0.75rem; padding: 0.25rem 0.55rem; white-space: nowrap;">
                  <?php echo htmlspecialchars($status); ?>
                </span>
              </td>

              <!-- 9. Job Card Status (Section 4 Requirement) -->
              <td style="padding: 0.9rem 0.75rem;">
                <?php if ($hasJobCard): ?>
                  <span class="badge" style="background: rgba(16,185,129,0.15); color: var(--color-green); border: 1px solid rgba(16,185,129,0.3); font-size: 0.72rem; padding: 0.2rem 0.5rem;">
                    ✓ Recorded
                  </span>
                <?php else: ?>
                  <span class="badge" style="background: rgba(250,204,21,0.1); color: #fbbf24; border: 1px dashed rgba(250,204,21,0.3); font-size: 0.72rem; padding: 0.2rem 0.5rem;">
                    Pending
                  </span>
                <?php endif; ?>
              </td>

              <!-- 10. Action (Section 4 & Section 20 Contextual Progression) -->
              <td style="padding: 0.9rem 0.75rem; text-align: right; white-space: nowrap;">
                <div style="display: inline-flex; align-items: center; gap: 0.5rem;">
                  
                  <?php if ($status === 'Confirmed'): ?>
                    <a href="job-card.php?id=<?php echo (int)$job['id']; ?>" class="btn btn-primary btn-sm" style="background: #38bdf8; border-color: #38bdf8; padding: 0.35rem 0.65rem; font-size: 0.78rem;">
                      Mark Received →
                    </a>
                  <?php elseif ($status === 'Vehicle Received'): ?>
                    <a href="job-card.php?id=<?php echo (int)$job['id']; ?>" class="btn btn-primary btn-sm" style="background: var(--accent-orange); border-color: var(--accent-orange); padding: 0.35rem 0.65rem; font-size: 0.78rem;">
                      Start Inspection →
                    </a>
                  <?php elseif ($status === 'Inspection'): ?>
                    <a href="job-card.php?id=<?php echo (int)$job['id']; ?>" class="btn btn-primary btn-sm" style="background: var(--accent-orange); border-color: var(--accent-orange); padding: 0.35rem 0.65rem; font-size: 0.78rem;">
                      Start Service →
                    </a>
                  <?php elseif ($status === 'Service In Progress'): ?>
                    <a href="job-card.php?id=<?php echo (int)$job['id']; ?>" class="btn btn-primary btn-sm" style="background: #a855f7; border-color: #a855f7; padding: 0.35rem 0.65rem; font-size: 0.78rem;">
                      Send to QC →
                    </a>
                  <?php elseif ($status === 'Quality Check'): ?>
                    <a href="job-card.php?id=<?php echo (int)$job['id']; ?>" class="btn btn-primary btn-sm" style="background: #a855f7; border-color: #a855f7; padding: 0.35rem 0.65rem; font-size: 0.78rem;">
                      Pass QC →
                    </a>
                  <?php elseif ($status === 'Ready for Delivery'): ?>
                    <a href="job-card.php?id=<?php echo (int)$job['id']; ?>" class="btn btn-primary btn-sm" style="background: var(--color-green); border-color: var(--color-green); padding: 0.35rem 0.65rem; font-size: 0.78rem;">
                      Mark Completed →
                    </a>
                  <?php else: ?>
                    <a href="job-card.php?id=<?php echo (int)$job['id']; ?>" class="btn btn-outline btn-sm" style="padding: 0.35rem 0.65rem; font-size: 0.78rem;">
                      View Job Card ↗
                    </a>
                  <?php endif; ?>

                  <a href="job-card.php?id=<?php echo (int)$job['id']; ?>" class="btn btn-outline btn-sm" style="padding: 0.35rem 0.55rem; font-size: 0.78rem;" title="Open Job Card">
                    Open Job Card →
                  </a>

                </div>
              </td>

            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
