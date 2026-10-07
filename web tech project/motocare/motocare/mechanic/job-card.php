<?php
/**
 * ============================================================================
 * MotoCare – Two-Wheeler Service & Mechanic Shop Management System
 * File: mechanic/job-card.php
 * Stage 2: Digital Job Card, Workshop Execution & Spare Parts Tracker
 * ============================================================================
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

requireMechanicLogin();
$mechanic = getCurrentMechanic();
$mechanicId = (int)$mechanic['id'];

$bookingId   = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$bookingCode = trim($_GET['code'] ?? '');

$booking = null;
$record  = null;
$recordParts  = [];
$catalogParts = [];
$errorMsg   = trim($_GET['err'] ?? '');
$successMsg = trim($_GET['msg'] ?? '');

if ($bookingId <= 0 && empty($bookingCode)) {
    header('Location: dashboard.php');
    exit;
}

if ($pdo) {
    try {
        // Query booking details with customer, vehicle, and service JOINs
        $stmtB = $pdo->prepare("
            SELECT 
                b.*,
                c.full_name AS customer_name,
                c.phone AS customer_phone,
                c.email AS customer_email,
                v.brand, v.model, v.registration_number, v.vehicle_type,
                s.service_name, s.category AS service_category, s.price AS service_price, s.estimated_duration,
                m.full_name AS mechanic_name
            FROM bookings b
            JOIN customers c ON b.customer_id = c.id
            JOIN vehicles v ON b.vehicle_id = v.id
            JOIN services s ON b.service_id = s.id
            LEFT JOIN mechanics m ON b.mechanic_id = m.id
            WHERE (b.id = :bid OR b.booking_code = :bcode)
            LIMIT 1
        ");
        $stmtB->execute([
            ':bid'   => $bookingId,
            ':bcode' => $bookingCode
        ]);
        $booking = $stmtB->fetch();

        if (!$booking) {
            $errorMsg = 'Service booking ticket not found.';
        } else {
            $bookingId = (int)$booking['id'];

            // SECTION 3, 19, 21, 22: Strict Anti-IDOR Authorization Check
            if ((int)$booking['mechanic_id'] !== $mechanicId) {
                $booking = null; // Deny access
                $errorMsg = 'Access Denied: You are not authorized to access or modify this service job card. It is assigned to another mechanic.';
            } else {
                // Fetch existing service record if created
                $stmtR = $pdo->prepare("SELECT * FROM service_records WHERE booking_id = :bid LIMIT 1");
                $stmtR->execute([':bid' => $bookingId]);
                $record = $stmtR->fetch();

                // Fetch relational parts usage for this job card (Section 13)
                if ($record && !empty($record['id'])) {
                    $stmtRP = $pdo->prepare("
                        SELECT srp.*, sp.part_name, sp.part_number, sp.brand, sp.stock_quantity AS current_stock
                        FROM service_record_parts srp
                        JOIN spare_parts sp ON srp.spare_part_id = sp.id
                        WHERE srp.service_record_id = :srid
                        ORDER BY srp.id ASC
                    ");
                    $stmtRP->execute([':srid' => $record['id']]);
                    $recordParts = $stmtRP->fetchAll();
                }

                // Fetch active catalog spare parts for the selection dropdown (Section 8)
                $stmtCatalog = $pdo->query("
                    SELECT id, part_name, part_number, brand, category, price, stock_quantity, minimum_stock, status
                    FROM spare_parts
                    WHERE status = 'Active'
                    ORDER BY part_name ASC
                ");
                $catalogParts = $stmtCatalog->fetchAll();
            }
        }
    } catch (PDOException $e) {
        $errorMsg = 'Database error: ' . $e->getMessage();
    }
} else {
    // Offline demo fallback
    $booking = [
        'id' => 1,
        'booking_code' => !empty($bookingCode) ? $bookingCode : 'MC-2026-1001',
        'customer_name' => 'Ramesh Kumar',
        'customer_phone' => '9876543210',
        'customer_email' => 'ramesh@example.com',
        'brand' => 'Royal Enfield',
        'model' => 'Classic 350',
        'registration_number' => 'TN-07-AB-1234',
        'vehicle_type' => 'Motorcycle',
        'service_name' => 'General Service',
        'service_category' => 'General Service',
        'service_price' => 500.00,
        'estimated_duration' => '2 - 3 Hours',
        'preferred_date' => '2026-10-10',
        'preferred_time' => '10:00 AM - 01:00 PM',
        'problem_description' => 'Periodic 5000 km general service and slight front disc squeak.',
        'mechanic_id' => $mechanicId,
        'mechanic_name' => $mechanic['name'],
        'status' => 'Confirmed'
    ];

    $record = [
        'id' => 1,
        'inspection_notes' => 'Spark plug electrode cleaned. Caliper slide pins dry.',
        'work_done' => 'Tappet clearance tuned. Engine oil drained and refilled.',
        'labor_hours' => 1.50,
        'technician_notes' => 'Test ride smooth. Brakes bedded in.',
        'parts_used' => 'Fully Synthetic 15W-50 Engine Oil (1L) (1)'
    ];

    $catalogParts = [
        ['id' => 1, 'part_name' => 'Fully Synthetic 15W-50 Engine Oil (1L)', 'part_number' => 'OIL-SYN-15W50', 'brand' => 'Motul', 'price' => 850.00, 'stock_quantity' => 45],
        ['id' => 3, 'part_name' => 'Sintered Front Disc Brake Pad Set', 'part_number' => 'BRK-PAD-DS01', 'brand' => 'Bosch', 'price' => 380.00, 'stock_quantity' => 8]
    ];
}

// Stage Progression Map
$stageSteps = [
    'Confirmed',
    'Vehicle Received',
    'Inspection',
    'Service In Progress',
    'Quality Check',
    'Ready for Delivery',
    'Completed'
];

$allowedNextTransitions = [
    'Confirmed'           => 'Vehicle Received',
    'Vehicle Received'    => 'Inspection',
    'Inspection'          => 'Service In Progress',
    'Service In Progress' => 'Quality Check',
    'Quality Check'       => 'Ready for Delivery',
    'Ready for Delivery'  => 'Completed'
];

$pageTitle = 'Digital Job Card – ' . htmlspecialchars($booking['booking_code'] ?? 'Workshop Bay') . ' – MotoCare';
$currentPage = 'dashboard.php';
require_once __DIR__ . '/../includes/mechanic-header.php';
?>

<div class="container" style="max-width: 960px; padding-top: 1rem; padding-bottom: 3rem;">
  
  <div style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
    <a href="dashboard.php" class="btn btn-outline btn-sm">
      ← Back to Workshop Bay
    </a>
    <span style="font-size: 0.85rem; color: var(--text-muted);">
      Assigned Technician: <strong style="color: #ffffff;"><?php echo htmlspecialchars($mechanic['name']); ?></strong>
    </span>
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

  <?php if ($booking): 
    $currentStatus = $booking['status'];
    $nextStatus = $allowedNextTransitions[$currentStatus] ?? null;
    $isCompleted = ($currentStatus === 'Completed');
  ?>
    <div class="form-card" style="padding: 2.5rem 2rem;">
      
      <!-- Top Title and Status Header -->
      <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2rem; border-bottom: 1px solid var(--border-subtle); padding-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <div>
          <span class="service-tag" style="background: rgba(16,185,129,0.15); color: var(--color-green); border-color: rgba(16,185,129,0.3); margin-bottom: 0.5rem; display: inline-block;">
            Digital Workshop Job Card
          </span>
          <h1 style="font-size: 1.85rem; margin-bottom: 0.25rem; color: #ffffff; font-family: var(--font-heading);">
            Job Card: <span style="color: var(--accent-orange);"><?php echo htmlspecialchars($booking['booking_code']); ?></span>
          </h1>
          <p style="color: var(--text-muted); font-size: 0.875rem;">
            Slot Scheduled: <?php echo formatDisplayDate($booking['preferred_date']); ?> &bull; <?php echo htmlspecialchars($booking['preferred_time']); ?>
          </p>
        </div>

        <div>
          <?php 
            $bColor = '#38bdf8';
            $bBg = 'rgba(56,189,248,0.15)';
            if ($currentStatus === 'Confirmed') { $bColor = '#38bdf8'; $bBg = 'rgba(56,189,248,0.15)'; }
            elseif ($currentStatus === 'Vehicle Received') { $bColor = '#fbbf24'; $bBg = 'rgba(250,204,21,0.15)'; }
            elseif ($currentStatus === 'Inspection' || $currentStatus === 'Service In Progress') { $bColor = 'var(--accent-orange)'; $bBg = 'rgba(255,94,20,0.15)'; }
            elseif ($currentStatus === 'Quality Check' || $currentStatus === 'Ready for Delivery') { $bColor = '#a855f7'; $bBg = 'rgba(168,85,247,0.15)'; }
            elseif ($currentStatus === 'Completed') { $bColor = 'var(--color-green)'; $bBg = 'rgba(16,185,129,0.15)'; }
          ?>
          <span class="service-tag" style="background: <?php echo $bBg; ?>; color: <?php echo $bColor; ?>; border-color: <?php echo $bColor; ?>; font-size: 0.9rem; padding: 0.4rem 0.9rem; font-weight: 700;">
            Stage: <?php echo htmlspecialchars($currentStatus); ?>
          </span>
        </div>
      </div>

      <!-- Stage Progression Flowchart Bar -->
      <div style="background: var(--bg-surface); border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 1.25rem; margin-bottom: 2rem; overflow-x: auto;">
        <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.75rem;">
          Workshop Stage Progression Pipeline:
        </div>
        <div style="display: flex; align-items: center; gap: 0.5rem; min-width: 680px;">
          <?php 
            $passedCurrent = false;
            foreach ($stageSteps as $idx => $step): 
              $isCurrent = ($step === $currentStatus);
              if ($isCurrent) $passedCurrent = true;
              $isPast = !$passedCurrent;
          ?>
            <div style="display: flex; align-items: center; gap: 0.5rem; flex: 1;">
              <div style="display: flex; align-items: center; gap: 0.35rem; font-size: 0.78rem; font-weight: 600; color: <?php echo $isCurrent ? '#ffffff' : ($isPast ? 'var(--color-green)' : 'var(--text-muted)'); ?>; background: <?php echo $isCurrent ? 'var(--accent-orange)' : ($isPast ? 'rgba(16,185,129,0.15)' : 'var(--bg-card)'); ?>; padding: 0.35rem 0.65rem; border-radius: var(--radius-sm); border: 1px solid <?php echo $isCurrent ? 'var(--accent-orange)' : 'var(--border-subtle)'; ?>;">
                <span><?php echo ($isPast ? '✓' : ($idx + 1) . '.'); ?></span>
                <span><?php echo htmlspecialchars($step); ?></span>
              </div>
              <?php if ($idx < count($stageSteps) - 1): ?>
                <span style="color: var(--border-subtle); font-size: 0.8rem;">→</span>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Information Overview Grid (Customer, Vehicle, Service, Problem) -->
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem; font-size: 0.95rem;">
        
        <!-- Two-Wheeler Details -->
        <div style="background: var(--bg-surface); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
          <h4 style="color: var(--text-muted); text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.05em; margin-bottom: 0.5rem;">
            Two-Wheeler
          </h4>
          <p style="color: #ffffff; font-weight: 700; font-size: 1.2rem; margin-bottom: 0.25rem;">
            <?php echo htmlspecialchars($booking['brand'] . ' ' . $booking['model']); ?>
          </p>
          <div style="font-family: monospace; font-size: 0.95rem; color: var(--accent-orange); font-weight: 700; margin-bottom: 0.35rem;">
            Reg #: <?php echo htmlspecialchars($booking['registration_number']); ?>
          </div>
          <span class="badge" style="background: rgba(255,255,255,0.06); color: var(--text-secondary); font-size: 0.75rem;">
            <?php echo htmlspecialchars($booking['vehicle_type']); ?>
          </span>
        </div>

        <!-- Customer Contact Details -->
        <div style="background: var(--bg-surface); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
          <h4 style="color: var(--text-muted); text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.05em; margin-bottom: 0.5rem;">
            Customer Contact
          </h4>
          <p style="color: #ffffff; font-weight: 700; font-size: 1.2rem; margin-bottom: 0.25rem;">
            <?php echo htmlspecialchars($booking['customer_name']); ?>
          </p>
          <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 0.25rem;">
            Phone: <a href="tel:<?php echo htmlspecialchars($booking['customer_phone']); ?>" style="color: var(--accent-orange); text-decoration: none; font-weight: 600;"><?php echo htmlspecialchars($booking['customer_phone']); ?></a>
          </p>
          <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0;">
            Email: <?php echo htmlspecialchars($booking['customer_email']); ?>
          </p>
        </div>

        <!-- Service Package Booked -->
        <div style="background: var(--bg-surface); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
          <h4 style="color: var(--text-muted); text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.05em; margin-bottom: 0.5rem;">
            Service Package Booked
          </h4>
          <p style="color: #ffffff; font-weight: 700; font-size: 1.15rem; margin-bottom: 0.25rem;">
            <?php echo htmlspecialchars($booking['service_name']); ?>
          </p>
          <p style="color: var(--accent-orange); font-weight: 700; font-size: 1.05rem; margin-bottom: 0.25rem;">
            Base Rate: <?php echo formatCurrency($booking['service_price']); ?>
          </p>
          <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0;">
            Est. Duration: <?php echo htmlspecialchars($booking['estimated_duration'] ?? 'N/A'); ?>
          </p>
        </div>

        <!-- Customer Problem Description -->
        <div style="background: var(--bg-surface); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
          <h4 style="color: var(--text-muted); text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.05em; margin-bottom: 0.5rem;">
            Customer Problem / Symptoms
          </h4>
          <p style="color: var(--text-secondary); font-size: 0.9rem; margin: 0; line-height: 1.5; font-style: <?php echo empty($booking['problem_description']) ? 'italic' : 'normal'; ?>;">
            <?php echo !empty($booking['problem_description']) ? nl2br(htmlspecialchars($booking['problem_description'])) : 'No specific complaints reported. Routine maintenance requested.'; ?>
          </p>
        </div>

      </div>

      <!-- ======================================================================
           DIGITAL JOB CARD FORM WITH INTEGRATED SPARE PARTS
           ====================================================================== -->
      <div style="background: var(--bg-surface); border: 1px solid var(--border-card); border-radius: var(--radius-lg); padding: 2rem; margin-bottom: 2rem;">
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-subtle); padding-bottom: 1rem;">
          <div>
            <h3 style="font-size: 1.25rem; color: #ffffff; margin: 0 0 0.25rem 0;">
              Technician Workshop Observations &amp; Labor Logs
            </h3>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0;">
              Record diagnostics, repair work performed, labor hours, and technical remarks.
            </p>
          </div>
          <?php if ($record && !empty($record['service_start'])): ?>
            <span style="font-size: 0.8rem; color: var(--text-muted);">
              Started: <?php echo formatDisplayDate($record['service_start']); ?>
            </span>
          <?php endif; ?>
        </div>

        <form method="POST" action="update-status.php" id="jobCardForm">
          <input type="hidden" name="booking_id" value="<?php echo (int)$booking['id']; ?>">
          <input type="hidden" name="has_parts_form" value="1">

          <!-- 1. Diagnostic Observations (Inspection Findings) -->
          <div class="form-group" style="margin-bottom: 1.5rem;">
            <label for="inspection_notes" class="form-label">
              1. Diagnostic Observations &amp; Inspection Findings
              <?php if ($currentStatus === 'Inspection'): ?>
                <span class="required-dot">* (Required to start service)</span>
              <?php endif; ?>
            </label>
            <textarea id="inspection_notes" name="inspection_notes" class="form-textarea" rows="3"
                      placeholder="e.g. Engine tappet tick heard; brake fluid discolored; drive chain slack at 45mm; front tyre tread depth 3mm..."
                      <?php echo $isCompleted ? 'readonly' : ''; ?>><?php echo htmlspecialchars($record['inspection_notes'] ?? ''); ?></textarea>
            <small style="color: var(--text-muted); font-size: 0.75rem; margin-top: 0.35rem; display: block;">
              Mechanical, electrical, suspension, and brake diagnostics observed during physical checkup.
            </small>
          </div>

          <!-- 2. Work Performed & Repairs Executed -->
          <div class="form-group" style="margin-bottom: 1.5rem;">
            <label for="work_done" class="form-label">
              2. Work Performed &amp; Maintenance Operations Executed
              <?php if ($currentStatus === 'Ready for Delivery'): ?>
                <span class="required-dot">* (Required before completion)</span>
              <?php endif; ?>
            </label>
            <textarea id="work_done" name="work_done" class="form-textarea" rows="3"
                      placeholder="e.g. Drained & refilled 10W-40 oil; replaced OEM oil filter; adjusted valve clearances to 0.08mm; lubricated & tensioned drive chain..."
                      <?php echo $isCompleted ? 'readonly' : ''; ?>><?php echo htmlspecialchars($record['work_done'] ?? ''); ?></textarea>
          </div>

          <!-- 3. Labor Hours & Technician Remarks Grid -->
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
            
            <!-- Labor Hours -->
            <div class="form-group">
              <label for="labor_hours" class="form-label">
                3. Total Labor Hours (hrs)
              </label>
              <input type="number" step="0.25" min="0" max="100" id="labor_hours" name="labor_hours" class="form-input"
                     placeholder="e.g. 1.50" 
                     value="<?php echo htmlspecialchars($record['labor_hours'] ?? '1.00'); ?>"
                     <?php echo $isCompleted ? 'readonly' : ''; ?>>
              <small style="color: var(--text-muted); font-size: 0.75rem; margin-top: 0.35rem; display: block;">
                Shop floor hours spent on maintenance.
              </small>
            </div>

            <!-- Technician Remarks / QC Notes -->
            <div class="form-group">
              <label for="technician_notes" class="form-label">
                4. Technician Remarks &amp; Quality Check Notes
              </label>
              <input type="text" id="technician_notes" name="technician_notes" class="form-input"
                     placeholder="e.g. Test ride completed, idle RPM steady at 1400, no leaks."
                     value="<?php echo htmlspecialchars($record['technician_notes'] ?? ''); ?>"
                     <?php echo $isCompleted ? 'readonly' : ''; ?>>
            </div>

          </div>

          <!-- ==================================================================
               5. SPARE PARTS USED SECTION (Sections 7, 8, 9, 10, 14, 18)
               ================================================================== -->
          <div style="background: var(--bg-card); border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 1.5rem; margin-bottom: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px solid var(--border-subtle); padding-bottom: 0.75rem;">
              <div>
                <h4 style="font-size: 1.1rem; color: #ffffff; margin: 0 0 0.2rem 0;">
                  5. Spare Parts &amp; Consumables Replaced
                </h4>
                <p style="color: var(--text-muted); font-size: 0.8rem; margin: 0;">
                  Select warehouse parts checked out for this two-wheeler. Stock is automatically deducted on save.
                </p>
              </div>

              <?php if (!$isCompleted): ?>
                <button type="button" class="btn btn-outline btn-sm" onclick="addPartRow();" style="font-size: 0.8rem; padding: 0.35rem 0.75rem;">
                  + Add Part
                </button>
              <?php endif; ?>
            </div>

            <!-- Parts Table / List -->
            <div style="overflow-x: auto;">
              <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;" id="partsTable">
                <thead>
                  <tr style="border-bottom: 1px solid var(--border-subtle); color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em;">
                    <th style="padding: 0.6rem 0.5rem;">Spare Part</th>
                    <th style="padding: 0.6rem 0.5rem; width: 120px;">Available</th>
                    <th style="padding: 0.6rem 0.5rem; width: 120px;">Quantity</th>
                    <th style="padding: 0.6rem 0.5rem; width: 120px;">Unit Price</th>
                    <th style="padding: 0.6rem 0.5rem; width: 120px;">Total</th>
                    <?php if (!$isCompleted): ?>
                      <th style="padding: 0.6rem 0.5rem; text-align: right; width: 60px;">Action</th>
                    <?php endif; ?>
                  </tr>
                </thead>
                <tbody id="partsTableBody">
                  <?php if (!empty($recordParts)): 
                    $totalPartsValue = 0;
                    foreach ($recordParts as $idx => $rp): 
                      $lineTotal = $rp['quantity'] * $rp['unit_price'];
                      $totalPartsValue += $lineTotal;
                  ?>
                    <tr class="part-row" style="border-bottom: 1px solid var(--border-subtle);" data-index="<?php echo $idx; ?>">
                      <!-- Part select/display -->
                      <td style="padding: 0.75rem 0.5rem;">
                        <?php if ($isCompleted): ?>
                          <div style="font-weight: 600; color: #ffffff;"><?php echo htmlspecialchars($rp['part_name']); ?></div>
                          <div style="font-size: 0.75rem; color: var(--text-muted);"><?php echo htmlspecialchars($rp['part_number']); ?> &bull; <?php echo htmlspecialchars($rp['brand']); ?></div>
                        <?php else: ?>
                          <select name="parts[<?php echo $idx; ?>][part_id]" class="form-select part-select" required onchange="handlePartChange(this);">
                            <option value="<?php echo (int)$rp['spare_part_id']; ?>" 
                                    data-price="<?php echo (float)$rp['unit_price']; ?>" 
                                    data-stock="<?php echo (int)($rp['current_stock'] + $rp['quantity']); ?>" 
                                    selected>
                              <?php echo htmlspecialchars($rp['part_name']); ?> (<?php echo htmlspecialchars($rp['part_number']); ?>) - In Stock: <?php echo (int)($rp['current_stock'] + $rp['quantity']); ?>
                            </option>
                            <?php foreach ($catalogParts as $cp): 
                              if ((int)$cp['id'] === (int)$rp['spare_part_id']) continue;
                            ?>
                              <option value="<?php echo (int)$cp['id']; ?>" 
                                      data-price="<?php echo (float)$cp['price']; ?>" 
                                      data-stock="<?php echo (int)$cp['stock_quantity']; ?>"
                                      <?php echo ($cp['stock_quantity'] <= 0) ? 'disabled' : ''; ?>>
                                <?php echo htmlspecialchars($cp['part_name']); ?> - In Stock: <?php echo (int)$cp['stock_quantity']; ?><?php echo ($cp['stock_quantity'] <= 0) ? ' [Out of Stock]' : ''; ?>
                              </option>
                            <?php endforeach; ?>
                          </select>
                        <?php endif; ?>
                      </td>

                      <!-- Available stock hint -->
                      <td style="padding: 0.75rem 0.5rem; color: var(--text-secondary);">
                        <span class="stock-avail-text" style="font-family: monospace; font-weight: 700; color: #34d399;">
                          <?php echo (int)($rp['current_stock'] + ($isCompleted ? 0 : $rp['quantity'])); ?> pcs
                        </span>
                      </td>

                      <!-- Quantity input -->
                      <td style="padding: 0.75rem 0.5rem;">
                        <?php if ($isCompleted): ?>
                          <span style="font-weight: 700; color: #ffffff; font-family: monospace;"><?php echo (int)$rp['quantity']; ?></span>
                        <?php else: ?>
                          <input type="number" name="parts[<?php echo $idx; ?>][quantity]" class="form-input part-qty" 
                                 min="1" step="1" value="<?php echo (int)$rp['quantity']; ?>" required oninput="calculateTotals();">
                        <?php endif; ?>
                      </td>

                      <!-- Unit price -->
                      <td style="padding: 0.75rem 0.5rem; font-weight: 600; color: #ffffff;">
                        <span class="unit-price-display"><?php echo formatCurrency($rp['unit_price']); ?></span>
                      </td>

                      <!-- Line total -->
                      <td style="padding: 0.75rem 0.5rem; font-weight: 700; color: var(--accent-orange);">
                        <span class="line-total-display"><?php echo formatCurrency($lineTotal); ?></span>
                      </td>

                      <!-- Remove action -->
                      <?php if (!$isCompleted): ?>
                        <td style="padding: 0.75rem 0.5rem; text-align: right;">
                          <button type="button" class="btn btn-outline btn-sm" onclick="removePartRow(this);" style="padding: 0.25rem 0.5rem; color: #f87171; border-color: rgba(239,68,68,0.3);" title="Remove this part">
                            ✕
                          </button>
                        </td>
                      <?php endif; ?>
                    </tr>
                  <?php endforeach; ?>
                  <?php else: ?>
                    <tr id="noPartsRow">
                      <td colspan="<?php echo $isCompleted ? 5 : 6; ?>" style="text-align: center; color: var(--text-muted); padding: 1.5rem;">
                        No spare parts recorded on this job card yet. Click "<strong>+ Add Part</strong>" to attach parts drawn from warehouse.
                      </td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>

            <!-- Informational Total Parts Value (Section 18) -->
            <div style="display: flex; justify-content: flex-end; align-items: center; gap: 1rem; border-top: 1px solid var(--border-subtle); padding-top: 1rem; margin-top: 1rem;">
              <span style="color: var(--text-muted); font-size: 0.9rem;">
                Total Parts Value (Informational):
              </span>
              <span id="grandPartsTotal" style="font-size: 1.25rem; font-weight: 800; color: var(--accent-orange); font-family: var(--font-heading);">
                <?php 
                  $computedTotal = 0;
                  if (!empty($recordParts)) {
                      foreach ($recordParts as $p) {
                          $computedTotal += ($p['quantity'] * $p['unit_price']);
                      }
                  }
                  echo formatCurrency($computedTotal);
                ?>
              </span>
            </div>
          </div>

          <!-- Contextual Action Buttons (Section 20 Workflow) -->
          <?php if (!$isCompleted): ?>
            <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border-subtle); padding-top: 1.5rem; flex-wrap: wrap; gap: 1rem;">
              
              <!-- Save Notes Button -->
              <button type="submit" name="action" value="save_notes_only" class="btn btn-secondary">
                💾 Save Job Card &amp; Update Parts
              </button>

              <!-- Next Progression Button -->
              <?php if ($nextStatus): ?>
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                  <input type="hidden" name="next_status" value="<?php echo htmlspecialchars($nextStatus); ?>">
                  
                  <?php if ($currentStatus === 'Confirmed'): ?>
                    <button type="submit" name="action" value="advance_status" class="btn btn-primary" style="background: #38bdf8; border-color: #38bdf8;">
                      Mark Vehicle Received →
                    </button>
                  <?php elseif ($currentStatus === 'Vehicle Received'): ?>
                    <button type="submit" name="action" value="advance_status" class="btn btn-primary" style="background: var(--accent-orange); border-color: var(--accent-orange);">
                      Start Inspection →
                    </button>
                  <?php elseif ($currentStatus === 'Inspection'): ?>
                    <button type="submit" name="action" value="advance_status" class="btn btn-primary" style="background: var(--accent-orange); border-color: var(--accent-orange);">
                      Start Service →
                    </button>
                  <?php elseif ($currentStatus === 'Service In Progress'): ?>
                    <button type="submit" name="action" value="advance_status" class="btn btn-primary" style="background: #a855f7; border-color: #a855f7;">
                      Send to Quality Check →
                    </button>
                  <?php elseif ($currentStatus === 'Quality Check'): ?>
                    <button type="submit" name="action" value="advance_status" class="btn btn-primary" style="background: #a855f7; border-color: #a855f7;">
                      Pass Quality Check →
                    </button>
                  <?php elseif ($currentStatus === 'Ready for Delivery'): ?>
                    <button type="submit" name="action" value="advance_status" class="btn btn-primary" style="background: var(--color-green); border-color: var(--color-green);">
                      Mark Completed →
                    </button>
                  <?php endif; ?>
                </div>
              <?php endif; ?>

            </div>
          <?php else: ?>
            <div style="background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.3); border-radius: var(--radius-md); padding: 1rem 1.5rem; display: flex; justify-content: space-between; align-items: center;">
              <span style="color: var(--color-green); font-weight: 700; font-size: 0.95rem;">
                ✓ Service Completed &amp; Workshop Bay Released
              </span>
              <span style="color: var(--text-muted); font-size: 0.85rem;">
                Completed on: <?php echo formatDisplayDate($record['service_end'] ?? date('Y-m-d')); ?>
              </span>
            </div>
          <?php endif; ?>

        </form>

      </div>

    </div>
  <?php endif; ?>

</div>

<!-- Catalog Template for dynamic JS insertion -->
<template id="partRowTemplate">
  <tr class="part-row" style="border-bottom: 1px solid var(--border-subtle);">
    <td style="padding: 0.75rem 0.5rem;">
      <select name="parts[__INDEX__][part_id]" class="form-select part-select" required onchange="handlePartChange(this);">
        <option value="" disabled selected>-- Select Spare Part --</option>
        <?php foreach ($catalogParts as $cp): ?>
          <option value="<?php echo (int)$cp['id']; ?>" 
                  data-price="<?php echo (float)$cp['price']; ?>" 
                  data-stock="<?php echo (int)$cp['stock_quantity']; ?>"
                  <?php echo ($cp['stock_quantity'] <= 0) ? 'disabled' : ''; ?>>
            <?php echo htmlspecialchars($cp['part_name']); ?> (<?php echo htmlspecialchars($cp['part_number']); ?>) - In Stock: <?php echo (int)$cp['stock_quantity']; ?><?php echo ($cp['stock_quantity'] <= 0) ? ' [Out of Stock]' : ''; ?>
          </option>
        <?php endforeach; ?>
      </select>
    </td>
    <td style="padding: 0.75rem 0.5rem; color: var(--text-secondary);">
      <span class="stock-avail-text" style="font-family: monospace; font-weight: 700; color: #34d399;">-</span>
    </td>
    <td style="padding: 0.75rem 0.5rem;">
      <input type="number" name="parts[__INDEX__][quantity]" class="form-input part-qty" min="1" step="1" value="1" required oninput="calculateTotals();">
    </td>
    <td style="padding: 0.75rem 0.5rem; font-weight: 600; color: #ffffff;">
      <span class="unit-price-display">₹ 0.00</span>
    </td>
    <td style="padding: 0.75rem 0.5rem; font-weight: 700; color: var(--accent-orange);">
      <span class="line-total-display">₹ 0.00</span>
    </td>
    <td style="padding: 0.75rem 0.5rem; text-align: right;">
      <button type="button" class="btn btn-outline btn-sm" onclick="removePartRow(this);" style="padding: 0.25rem 0.5rem; color: #f87171; border-color: rgba(239,68,68,0.3);" title="Remove this part">
        ✕
      </button>
    </td>
  </tr>
</template>

<script>
var partRowIndex = <?php echo count($recordParts); ?> + 10;

function addPartRow() {
  var noPartsRow = document.getElementById('noPartsRow');
  if (noPartsRow) {
    noPartsRow.style.display = 'none';
  }
  var tbody = document.getElementById('partsTableBody');
  var template = document.getElementById('partRowTemplate').innerHTML;
  var rendered = template.replace(/__INDEX__/g, partRowIndex++);
  var tempDiv = document.createElement('tbody');
  tempDiv.innerHTML = rendered;
  var newRow = tempDiv.firstElementChild;
  tbody.appendChild(newRow);
  calculateTotals();
}

function removePartRow(btn) {
  var row = btn.closest('.part-row');
  if (row) {
    row.remove();
  }
  var remainingRows = document.querySelectorAll('#partsTableBody .part-row');
  if (remainingRows.length === 0) {
    var noPartsRow = document.getElementById('noPartsRow');
    if (noPartsRow) noPartsRow.style.display = '';
  }
  calculateTotals();
}

function handlePartChange(selectElem) {
  var row = selectElem.closest('.part-row');
  var selected = selectElem.options[selectElem.selectedIndex];
  var stockText = row.querySelector('.stock-avail-text');
  var priceDisplay = row.querySelector('.unit-price-display');
  
  if (selected && selected.dataset.price !== undefined) {
    var price = parseFloat(selected.dataset.price) || 0;
    var stock = parseInt(selected.dataset.stock) || 0;
    
    stockText.textContent = stock + ' pcs';
    priceDisplay.textContent = '₹ ' + price.toFixed(2);
  } else {
    stockText.textContent = '-';
    priceDisplay.textContent = '₹ 0.00';
  }
  calculateTotals();
}

function calculateTotals() {
  var rows = document.querySelectorAll('#partsTableBody .part-row');
  var grandTotal = 0;
  
  rows.forEach(function(row) {
    var select = row.querySelector('.part-select');
    var qtyInput = row.querySelector('.part-qty');
    var lineDisplay = row.querySelector('.line-total-display');
    var priceDisplay = row.querySelector('.unit-price-display');
    
    var unitPrice = 0;
    if (select) {
      var opt = select.options[select.selectedIndex];
      if (opt && opt.dataset.price !== undefined) {
        unitPrice = parseFloat(opt.dataset.price) || 0;
      }
    } else {
      // Read-only row
      var priceText = priceDisplay ? priceDisplay.textContent.replace(/[^0-9.]/g, '') : '0';
      unitPrice = parseFloat(priceText) || 0;
    }
    
    var qty = qtyInput ? (parseInt(qtyInput.value) || 0) : 0;
    var lineTotal = unitPrice * qty;
    grandTotal += lineTotal;
    
    if (lineDisplay) {
      lineDisplay.textContent = '₹ ' + lineTotal.toFixed(2);
    }
  });
  
  var grandElem = document.getElementById('grandPartsTotal');
  if (grandElem) {
    grandElem.textContent = '₹ ' + grandTotal.toFixed(2);
  }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
