<?php
/**
 * ============================================================================
 * MotoCare – Two-Wheeler Service & Mechanic Shop Management System
 * File: admin/booking-details.php
 * Stage 2: Admin Booking Inspection & Mechanic Assignment Interface
 * ============================================================================
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

requireAdminLogin();

$bookingId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$bookingCode = trim($_GET['code'] ?? '');

$booking = null;
$mechanics = [];
$errorMsg = trim($_GET['err'] ?? '');
$successMsg = '';

// Handle success messages from assignment action
if (isset($_GET['msg'])) {
    $mechName = htmlspecialchars($_GET['mech'] ?? 'Technician');
    if ($_GET['msg'] === 'assigned') {
        $successMsg = "Mechanic {$mechName} successfully assigned! Booking status changed to Confirmed.";
    } elseif ($_GET['msg'] === 'reassigned') {
        $successMsg = "Booking successfully reassigned to {$mechName}. Status confirmed.";
    }
}

if ($bookingId <= 0 && empty($bookingCode)) {
    header('Location: bookings.php');
    exit;
}

if ($pdo) {
    try {
        // Query full booking with customer, vehicle, service, and assigned mechanic
        $stmt = $pdo->prepare("
            SELECT 
                b.*,
                c.full_name AS customer_name,
                c.phone AS customer_phone,
                c.email AS customer_email,
                c.address AS customer_address,
                v.brand, v.model, v.registration_number, v.vehicle_type,
                s.service_name, s.category AS service_category, s.description AS service_description,
                s.price AS service_price, s.estimated_duration,
                m.id AS current_mechanic_id,
                m.full_name AS mechanic_name,
                m.phone AS mechanic_phone,
                m.specialization AS mechanic_specialization,
                m.status AS mechanic_status,
                sr.id AS record_id,
                sr.inspection_notes,
                sr.work_done,
                sr.labor_hours,
                sr.technician_notes,
                sr.parts_used,
                sr.service_start,
                sr.service_end
            FROM bookings b
            JOIN customers c ON b.customer_id = c.id
            JOIN vehicles v ON b.vehicle_id = v.id
            JOIN services s ON b.service_id = s.id
            LEFT JOIN mechanics m ON b.mechanic_id = m.id
            LEFT JOIN service_records sr ON b.id = sr.booking_id
            WHERE (b.id = :id OR b.booking_code = :code)
            LIMIT 1
        ");
        $stmt->execute([
            ':id'   => $bookingId,
            ':code' => $bookingCode
        ]);
        $booking = $stmt->fetch();

        if (!$booking) {
            $errorMsg = 'Booking record not found in database.';
        } else {
            $bookingId = (int)$booking['id'];

            // Query active mechanics list from mechanics table
            $stmtMech = $pdo->query("
                SELECT id, full_name, email, phone, specialization, status 
                FROM mechanics 
                ORDER BY 
                    CASE WHEN status = 'Available' THEN 1 WHEN status = 'Busy' THEN 2 ELSE 3 END,
                    full_name ASC
            ");
            $mechanics = $stmtMech->fetchAll();

            // Fetch spare parts used on this job card
            $recordParts = [];
            $totalPartsCost = 0.00;
            if (!empty($booking['record_id'])) {
                $stmtParts = $pdo->prepare("
                    SELECT srp.*, sp.part_name, sp.part_number, sp.category
                    FROM service_record_parts srp
                    JOIN spare_parts sp ON srp.spare_part_id = sp.id
                    WHERE srp.service_record_id = :srid
                    ORDER BY srp.id ASC
                ");
                $stmtParts->execute([':srid' => (int)$booking['record_id']]);
                $recordParts = $stmtParts->fetchAll();
                foreach ($recordParts as $p) {
                    $totalPartsCost += ((float)$p['unit_price'] * (int)$p['quantity']);
                }
            }

            // Check for associated Tax Invoice (Stage 2 Billing)
            $existingBill = null;
            $stmtBill = $pdo->prepare("
                SELECT id, invoice_number, total_amount, amount_paid, balance_due, payment_status, invoice_date 
                FROM bills 
                WHERE booking_id = :bid 
                LIMIT 1
            ");
            $stmtBill->execute([':bid' => $bookingId]);
            $existingBill = $stmtBill->fetch();

            // Check for customer feedback (Stage 2 Feedback)
            $existingFeedback = null;
            $stmtFb = $pdo->prepare("SELECT * FROM feedback WHERE booking_id = :bid LIMIT 1");
            $stmtFb->execute([':bid' => $bookingId]);
            $existingFeedback = $stmtFb->fetch();
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
        'customer_address' => '#14, Anna Nagar 2nd Street, Chennai - 600040',
        'brand' => 'Royal Enfield',
        'model' => 'Classic 350',
        'registration_number' => 'TN-07-AB-1234',
        'vehicle_type' => 'Motorcycle',
        'service_name' => 'General Service',
        'service_category' => 'General Service',
        'service_description' => '36-point diagnostic inspection, oil top-up, spark plug cleaning, throttle tuning, and foam wash.',
        'service_price' => 500.00,
        'estimated_duration' => '2 - 3 Hours',
        'preferred_date' => '2026-10-10',
        'preferred_time' => '10:00 AM - 01:00 PM',
        'problem_description' => 'Periodic 5000 km general service and slight front disc squeak.',
        'mechanic_id' => 1,
        'current_mechanic_id' => 1,
        'mechanic_name' => 'Arun Kumar',
        'mechanic_phone' => '9876543201',
        'mechanic_specialization' => 'Senior Mechanic & Cruiser Specialist',
        'mechanic_status' => 'Available',
        'status' => 'Confirmed',
        'created_at' => date('Y-m-d H:i:s')
    ];

    $mechanics = [
        ['id' => 1, 'full_name' => 'Arun Kumar', 'specialization' => 'Cruiser & Royal Enfield Specialist', 'phone' => '9876543201', 'status' => 'Available'],
        ['id' => 2, 'full_name' => 'Karthik', 'specialization' => 'Engine & Transmission Specialist', 'phone' => '9876543202', 'status' => 'Available'],
        ['id' => 3, 'full_name' => 'Sanjay', 'specialization' => 'EV Powertrains & Electrical Diagnostics', 'phone' => '9876543203', 'status' => 'Available'],
        ['id' => 4, 'full_name' => 'Praveen', 'specialization' => 'Suspension & Brake Systems', 'phone' => '9876543204', 'status' => 'Available']
    ];
}

$pageTitle = 'Booking #' . htmlspecialchars($booking['booking_code'] ?? 'Inspection') . ' – MotoCare Admin';
$currentPage = 'bookings.php';
require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="container" style="max-width: 880px; padding-top: 1rem; padding-bottom: 3rem;">
  
  <div style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
    <a href="bookings.php" class="btn btn-outline btn-sm">
      ← Back to Bookings Ledger
    </a>
    <div style="display: flex; gap: 0.5rem;">
      <a href="bookings.php?status=Pending" class="btn btn-secondary btn-sm">
        View Pending Queue
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

  <?php if ($booking): ?>
    <div class="form-card" style="padding: 2.5rem 2rem;">
      
      <!-- Top Title and Status Header -->
      <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2rem; border-bottom: 1px solid var(--border-subtle); padding-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <div>
          <span class="service-tag" style="background: rgba(239,68,68,0.15); color: #f87171; border-color: rgba(239,68,68,0.3); margin-bottom: 0.5rem; display: inline-block;">
            Job Order #<?php echo htmlspecialchars($booking['booking_code']); ?>
          </span>
          <h1 style="font-size: 1.85rem; margin-bottom: 0.25rem; color: #ffffff; font-family: var(--font-heading);">
            Customer Booking Details
          </h1>
          <p style="color: var(--text-muted); font-size: 0.875rem;">
            Received on: <?php echo formatDisplayDate($booking['created_at']); ?>
          </p>
        </div>

        <div>
          <?php 
            $status = $booking['status'];
            $badgeBg = 'rgba(250,204,21,0.15)';
            $badgeColor = '#fbbf24';
            if ($status === 'Confirmed') {
                $badgeBg = 'rgba(56,189,248,0.15)';
                $badgeColor = '#38bdf8';
            } elseif ($status === 'Vehicle Received') {
                $badgeBg = 'rgba(250,204,21,0.15)';
                $badgeColor = '#fbbf24';
            } elseif ($status === 'Service In Progress' || $status === 'Inspection') {
                $badgeBg = 'rgba(255,94,20,0.15)';
                $badgeColor = 'var(--accent-orange)';
            } elseif ($status === 'Quality Check') {
                $badgeBg = 'rgba(168,85,247,0.15)';
                $badgeColor = '#a855f7';
            } elseif ($status === 'Ready for Delivery') {
                $badgeBg = 'rgba(16,185,129,0.15)';
                $badgeColor = '#34d399';
            } elseif ($status === 'Completed') {
                $badgeBg = 'rgba(16,185,129,0.15)';
                $badgeColor = 'var(--color-green)';
            } elseif ($status === 'Cancelled') {
                $badgeBg = 'rgba(239,68,68,0.15)';
                $badgeColor = '#f87171';
            }
          ?>
          <span class="service-tag" style="background: <?php echo $badgeBg; ?>; color: <?php echo $badgeColor; ?>; border-color: <?php echo $badgeColor; ?>; font-size: 0.85rem; padding: 0.4rem 0.85rem;">
            Status: <?php echo htmlspecialchars($status); ?>
          </span>
        </div>
      </div>

      <!-- Information Overview Grid -->
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.75rem; margin-bottom: 1.75rem; font-size: 0.95rem;">
        
        <!-- 1. Customer Information Panel -->
        <div style="background: var(--bg-surface); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
          <h4 style="color: var(--text-muted); text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.05em; margin-bottom: 0.75rem;">
            Customer Information
          </h4>
          <p style="color: #ffffff; font-weight: 700; font-size: 1.15rem; margin-bottom: 0.35rem;">
            <?php echo htmlspecialchars($booking['customer_name']); ?>
          </p>
          <p style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 0.25rem;">
            Phone: <a href="tel:<?php echo htmlspecialchars($booking['customer_phone']); ?>" style="color: var(--accent-orange); font-weight: 600; text-decoration: none;"><?php echo htmlspecialchars($booking['customer_phone']); ?></a>
          </p>
          <p style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 0.25rem;">
            Email: <strong style="color: #ffffff;"><?php echo htmlspecialchars($booking['customer_email']); ?></strong>
          </p>
          <?php if (!empty($booking['customer_address'])): ?>
            <p style="color: var(--text-muted); font-size: 0.8rem; margin: 0.5rem 0 0;">
              Address: <?php echo htmlspecialchars($booking['customer_address']); ?>
            </p>
          <?php endif; ?>
        </div>

        <!-- 2. Vehicle Information Panel -->
        <div style="background: var(--bg-surface); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
          <h4 style="color: var(--text-muted); text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.05em; margin-bottom: 0.75rem;">
            Two-Wheeler Information
          </h4>
          <p style="color: #ffffff; font-weight: 700; font-size: 1.15rem; margin-bottom: 0.35rem;">
            <?php echo htmlspecialchars($booking['brand'] . ' ' . $booking['model']); ?>
          </p>
          <div style="font-family: monospace; font-size: 0.95rem; color: var(--accent-orange); font-weight: 700; margin-bottom: 0.5rem;">
            Registration: <?php echo htmlspecialchars($booking['registration_number']); ?>
          </div>
          <p style="color: var(--text-secondary); font-size: 0.85rem; margin: 0;">
            Category: <strong style="color: #ffffff;"><?php echo htmlspecialchars($booking['vehicle_type']); ?></strong>
          </p>
        </div>

      </div>

      <!-- Service Package & Appointment Schedule -->
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.75rem; margin-bottom: 1.75rem; font-size: 0.95rem;">
        
        <!-- 3. Service Package Panel -->
        <div style="background: var(--bg-surface); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
          <h4 style="color: var(--text-muted); text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.05em; margin-bottom: 0.75rem;">
            Service Package Booked
          </h4>
          <p style="color: #ffffff; font-weight: 700; font-size: 1.15rem; margin-bottom: 0.35rem;">
            <?php echo htmlspecialchars($booking['service_name']); ?>
          </p>
          <p style="color: var(--accent-orange); font-weight: 700; font-size: 1.1rem; margin-bottom: 0.25rem;">
            Standard Rate: <?php echo formatCurrency($booking['service_price']); ?>
          </p>
          <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 0.5rem;">
            Category: <strong style="color: var(--text-secondary);"><?php echo htmlspecialchars($booking['service_category']); ?></strong> &bull;
            Est. Time: <?php echo htmlspecialchars($booking['estimated_duration'] ?? 'N/A'); ?>
          </p>
          <?php if (!empty($booking['service_description'])): ?>
            <p style="color: var(--text-muted); font-size: 0.8rem; margin: 0; line-height: 1.4;">
              <?php echo htmlspecialchars($booking['service_description']); ?>
            </p>
          <?php endif; ?>
        </div>

        <!-- 4. Appointment Schedule Panel -->
        <div style="background: var(--bg-surface); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
          <h4 style="color: var(--text-muted); text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.05em; margin-bottom: 0.75rem;">
            Preferred Appointment Slot
          </h4>
          <p style="color: #ffffff; font-weight: 700; font-size: 1.15rem; margin-bottom: 0.35rem;">
            📅 <?php echo formatDisplayDate($booking['preferred_date']); ?>
          </p>
          <p style="color: var(--text-secondary); font-size: 0.95rem; margin-bottom: 0.5rem;">
            ⏰ Slot: <strong style="color: #ffffff;"><?php echo htmlspecialchars($booking['preferred_time']); ?></strong>
          </p>
          <p style="color: var(--text-muted); font-size: 0.8rem; margin: 0;">
            Booking Code: <strong style="color: var(--accent-orange); font-family: monospace;"><?php echo htmlspecialchars($booking['booking_code']); ?></strong>
          </p>
        </div>

      </div>

      <!-- Problem Description Notes -->
      <div style="background: var(--bg-surface); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); margin-bottom: 2rem;">
        <h4 style="color: var(--text-muted); text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.05em; margin-bottom: 0.5rem;">
          Reported Problem / Symptoms
        </h4>
        <p style="color: var(--text-secondary); font-size: 0.95rem; margin: 0; line-height: 1.6; font-style: <?php echo empty($booking['problem_description']) ? 'italic' : 'normal'; ?>;">
          <?php echo !empty($booking['problem_description']) ? nl2br(htmlspecialchars($booking['problem_description'])) : 'No specific complaints noted by customer. Standard scheduled maintenance.'; ?>
        </p>
      </div>

      <!-- ======================================================================
           Digital Job Card Status (Section 16 Requirement)
           ====================================================================== -->
      <div style="background: var(--bg-surface); padding: 1.75rem 2rem; border-radius: var(--radius-lg); border: 1px solid var(--border-card); margin-bottom: 2rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px solid var(--border-subtle); padding-bottom: 0.75rem; flex-wrap: wrap; gap: 0.5rem;">
          <div>
            <h3 style="font-size: 1.2rem; color: #ffffff; margin: 0 0 0.25rem 0;">
              Digital Job Card &amp; Workshop Log
            </h3>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0;">
              Technician diagnostics, recorded labor hours, and maintenance observations.
            </p>
          </div>
          <?php if (!empty($booking['record_id'])): ?>
            <span class="badge" style="background: rgba(16,185,129,0.15); color: var(--color-green); border: 1px solid rgba(16,185,129,0.3); padding: 0.35rem 0.75rem; font-weight: 700;">
              ✓ Job Card Active (ID #<?php echo (int)$booking['record_id']; ?>)
            </span>
          <?php else: ?>
            <span class="badge" style="background: rgba(250,204,21,0.1); color: #fbbf24; border: 1px dashed rgba(250,204,21,0.3); padding: 0.35rem 0.75rem;">
              Job Card Not Initiated Yet
            </span>
          <?php endif; ?>
        </div>

        <?php if (!empty($booking['record_id'])): ?>
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; font-size: 0.9rem;">
            <div style="background: var(--bg-card); padding: 1rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
              <strong style="color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase;">Diagnostic Observations:</strong>
              <p style="margin: 0.35rem 0 0; color: #ffffff;">
                <?php echo !empty($booking['inspection_notes']) ? nl2br(htmlspecialchars($booking['inspection_notes'])) : '<em style="color: var(--text-muted);">No diagnostic notes logged yet.</em>'; ?>
              </p>
            </div>
            <div style="background: var(--bg-card); padding: 1rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
              <strong style="color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase;">Work Performed:</strong>
              <p style="margin: 0.35rem 0 0; color: #ffffff;">
                <?php echo !empty($booking['work_done']) ? nl2br(htmlspecialchars($booking['work_done'])) : '<em style="color: var(--text-muted);">No repair logs entered yet.</em>'; ?>
              </p>
            </div>
            <div style="background: var(--bg-card); padding: 1rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
              <strong style="color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase;">Recorded Labor Hours:</strong>
              <p style="margin: 0.35rem 0 0; font-family: monospace; font-size: 1.1rem; color: var(--accent-orange); font-weight: 700;">
                <?php echo htmlspecialchars($booking['labor_hours'] ?? '0.00'); ?> hrs
              </p>
            </div>
            <div style="background: var(--bg-card); padding: 1rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
              <strong style="color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase;">Technician Remarks:</strong>
              <p style="margin: 0.35rem 0 0; color: #ffffff;">
                <?php echo !empty($booking['technician_notes']) ? htmlspecialchars($booking['technician_notes']) : '<em style="color: var(--text-muted);">None</em>'; ?>
              </p>
            </div>
          </div>
        <?php else: ?>
          <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0; font-style: italic;">
            Digital job card will be automatically populated once assigned technician marks vehicle received and logs inspection findings.
          </p>
        <?php endif; ?>
      </div>

      <!-- ======================================================================
           Spare Parts Used Section (Section 19 Requirement)
           ====================================================================== -->
      <div style="background: var(--bg-surface); padding: 1.75rem 2rem; border-radius: var(--radius-lg); border: 1px solid var(--border-card); margin-bottom: 2rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px solid var(--border-subtle); padding-bottom: 0.75rem; flex-wrap: wrap; gap: 0.5rem;">
          <div>
            <h3 style="font-size: 1.2rem; color: #ffffff; margin: 0 0 0.25rem 0;">
              Spare Parts &amp; Consumables Used
            </h3>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0;">
              Inventory deducted from warehouse stock by workshop technician for this job order.
            </p>
          </div>
          <?php if (!empty($recordParts)): ?>
            <span class="badge" style="background: rgba(56,189,248,0.15); color: #38bdf8; border: 1px solid rgba(56,189,248,0.3); padding: 0.35rem 0.75rem; font-weight: 700;">
              <?php echo count($recordParts); ?> Part(s) Recorded
            </span>
          <?php endif; ?>
        </div>

        <?php if (!empty($recordParts)): ?>
          <div style="overflow-x: auto;">
            <table class="table" style="width: 100%; margin: 0; font-size: 0.9rem;">
              <thead>
                <tr style="border-bottom: 1px solid var(--border-subtle); text-align: left;">
                  <th style="padding: 0.75rem 0.5rem; color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase;">Part Description</th>
                  <th style="padding: 0.75rem 0.5rem; color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase; text-align: center;">Qty</th>
                  <th style="padding: 0.75rem 0.5rem; color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase; text-align: right;">Unit Price (Snapshot)</th>
                  <th style="padding: 0.75rem 0.5rem; color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase; text-align: right;">Total Amount</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recordParts as $part): 
                  $lineTotal = (float)$part['unit_price'] * (int)$part['quantity'];
                ?>
                  <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                    <td style="padding: 0.85rem 0.5rem;">
                      <div style="font-weight: 600; color: #ffffff;">
                        <?php echo htmlspecialchars($part['part_name']); ?>
                      </div>
                      <div style="font-size: 0.75rem; color: var(--text-muted); font-family: monospace;">
                        SKU: <?php echo htmlspecialchars($part['part_number']); ?> &bull; <?php echo htmlspecialchars($part['category']); ?>
                      </div>
                    </td>
                    <td style="padding: 0.85rem 0.5rem; text-align: center; font-weight: 700; color: #ffffff;">
                      <?php echo (int)$part['quantity']; ?>
                    </td>
                    <td style="padding: 0.85rem 0.5rem; text-align: right; color: var(--text-secondary);">
                      <?php echo formatCurrency($part['unit_price']); ?>
                    </td>
                    <td style="padding: 0.85rem 0.5rem; text-align: right; font-weight: 700; color: var(--accent-orange);">
                      <?php echo formatCurrency($lineTotal); ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
              <tfoot>
                <tr style="border-top: 2px solid var(--border-subtle);">
                  <td colspan="3" style="padding: 1rem 0.5rem; text-align: right; font-weight: 700; color: var(--text-muted); text-transform: uppercase; font-size: 0.85rem;">
                    Total Parts Value (Informational):
                  </td>
                  <td style="padding: 1rem 0.5rem; text-align: right; font-weight: 800; font-size: 1.1rem; color: var(--accent-orange);">
                    <?php echo formatCurrency($totalPartsCost); ?>
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>
          <p style="color: var(--text-muted); font-size: 0.75rem; margin: 0.75rem 0 0; font-style: italic;">
            * Note: Parts total is derived from historical snapshot pricing stored on job card creation.
          </p>
        <?php else: ?>
          <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0; font-style: italic;">
            No spare parts or consumables logged for this service order yet.
          </p>
        <?php endif; ?>
      </div>

      <!-- ======================================================================
           STAGE 2 BILLING & INVOICE MANAGEMENT SECTION
           ====================================================================== -->
      <div style="background: var(--bg-surface); padding: 2rem; border-radius: var(--radius-lg); border: 1px solid var(--border-card); margin-bottom: 2rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 0.75rem;">
          <div>
            <h3 style="font-size: 1.25rem; color: #ffffff; margin: 0 0 0.25rem 0; display: flex; align-items: center; gap: 0.5rem;">
              <span>💳</span> Tax Invoice & Payment Status
            </h3>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0;">
              Billing generation, GST calculation, and customer payment tracking
            </p>
          </div>
          <?php if (!empty($existingBill)): ?>
            <span class="badge" style="background: rgba(16,185,129,0.15); color: var(--color-green); border: 1px solid rgba(16,185,129,0.3); padding: 0.4rem 0.85rem; font-weight: 700;">
              Invoice Active: <?php echo htmlspecialchars($existingBill['invoice_number']); ?>
            </span>
          <?php endif; ?>
        </div>

        <?php if (!empty($existingBill)): ?>
          <div style="background: var(--bg-card); border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1.25rem;">
            <div>
              <div style="font-size: 1.1rem; font-weight: 700; color: #ffffff; font-family: monospace;">
                <?php echo htmlspecialchars($existingBill['invoice_number']); ?>
              </div>
              <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.25rem;">
                Date: <?php echo formatDisplayDate($existingBill['invoice_date']); ?> &bull; 
                Total: <strong style="color: #ffffff;"><?php echo formatCurrency($existingBill['total_amount']); ?></strong> &bull; 
                Paid: <strong style="color: #10b981;"><?php echo formatCurrency($existingBill['amount_paid']); ?></strong> &bull; 
                Balance: <strong style="color: <?php echo ((float)$existingBill['balance_due'] > 0) ? '#ef4444' : '#10b981'; ?>;"><?php echo formatCurrency($existingBill['balance_due']); ?></strong>
              </div>
              <div style="margin-top: 0.5rem;">
                <?php 
                  $ps = $existingBill['payment_status'];
                  $psColor = '#fbbf24'; $psBg = 'rgba(251,191,36,0.15)';
                  if ($ps === 'Paid') { $psColor = '#10b981'; $psBg = 'rgba(16,185,129,0.15)'; }
                  elseif ($ps === 'Partially Paid') { $psColor = '#f59e0b'; $psBg = 'rgba(245,158,11,0.15)'; }
                ?>
                <span class="badge" style="background: <?php echo $psBg; ?>; color: <?php echo $psColor; ?>; border: 1px solid <?php echo $psColor; ?>; font-size: 0.8rem; padding: 0.25rem 0.65rem;">
                  Payment: <?php echo htmlspecialchars($ps); ?>
                </span>
              </div>
            </div>

            <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
              <a href="bill-details.php?id=<?php echo (int)$existingBill['id']; ?>" class="btn btn-primary btn-sm">
                View Invoice Details →
              </a>
              <a href="invoice-pdf.php?id=<?php echo (int)$existingBill['id']; ?>" class="btn btn-outline btn-sm" target="_blank">
                Download PDF
              </a>
              <?php if ((float)$existingBill['balance_due'] > 0): ?>
                <a href="record-payment.php?bill_id=<?php echo (int)$existingBill['id']; ?>" class="btn btn-secondary btn-sm" style="border-color: #10b981; color: #10b981;">
                  + Record Payment
                </a>
              <?php endif; ?>
            </div>
          </div>
        <?php elseif ($booking['status'] === 'Completed'): ?>
          <div style="background: rgba(16,185,129,0.08); border: 1px solid rgba(16,185,129,0.25); border-radius: var(--radius-md); padding: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
              <div style="font-weight: 700; color: #10b981; font-size: 1.05rem;">
                ✓ Service Completed — Ready for Billing
              </div>
              <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.25rem;">
                All workshop tasks and inventory parts have been recorded. You can generate the official GST Tax Invoice now.
              </div>
            </div>
            <a href="generate-bill.php?booking_id=<?php echo (int)$booking['id']; ?>" class="btn btn-primary">
              Generate Tax Invoice →
            </a>
          </div>
        <?php else: ?>
          <div style="background: var(--bg-card); border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 1.25rem;">
            <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0;">
              Billing will become available once the workshop service status reaches <strong>Completed</strong>. Current status: <span class="badge" style="background: rgba(255,255,255,0.08); color: #ffffff;"><?php echo htmlspecialchars($booking['status']); ?></span>
            </p>
          </div>
        <?php endif; ?>
      </div>

      <?php if (!empty($existingFeedback)): 
        $fbStat = $existingFeedback['moderation_status'];
        $fbBg = 'rgba(251,191,36,0.15)'; $fbCol = '#fbbf24';
        if ($fbStat === 'Approved') { $fbBg = 'rgba(16,185,129,0.15)'; $fbCol = '#10b981'; }
        elseif ($fbStat === 'Rejected') { $fbBg = 'rgba(239,68,68,0.15)'; $fbCol = '#f87171'; }
        $rStar = (int)$existingFeedback['rating'];
      ?>
        <!-- Customer Service Feedback Card (Stage 2 Section 7 & 8) -->
        <div style="background: var(--bg-surface); padding: 2rem; border-radius: var(--radius-lg); border: 1px solid var(--border-card); margin-bottom: 2rem;">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 0.75rem;">
            <div>
              <h3 style="font-size: 1.25rem; color: #ffffff; margin: 0 0 0.25rem 0; display: flex; align-items: center; gap: 0.5rem;">
                <span>★</span> Customer Rating &amp; Review
              </h3>
              <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0;">
                Verified customer feedback submitted following completed workshop ticket
              </p>
            </div>
            <span class="badge" style="background: <?php echo $fbBg; ?>; color: <?php echo $fbCol; ?>; border: 1px solid <?php echo $fbCol; ?>; padding: 0.35rem 0.75rem; font-weight: 700;">
              ● Moderation: <?php echo htmlspecialchars($fbStat); ?>
            </span>
          </div>

          <div style="background: var(--bg-card); border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
              <div style="color: #fbbf24; font-size: 1.35rem; letter-spacing: 2px;">
                <?php echo str_repeat('★', $rStar) . str_repeat('☆', 5 - $rStar); ?>
                <span style="font-size: 0.95rem; font-weight: 700; color: #ffffff; margin-left: 0.5rem;">
                  <?php echo $rStar; ?> / 5 Stars
                </span>
              </div>
              <p style="color: var(--text-secondary); font-size: 0.95rem; line-height: 1.5; margin: 0.5rem 0 0; font-style: italic;">
                "<?php echo htmlspecialchars($existingFeedback['comments']); ?>"
              </p>
              <?php if (!empty($existingFeedback['admin_response'])): ?>
                <div style="font-size: 0.8rem; color: var(--accent-orange); margin-top: 0.35rem;">
                  ↳ Supervisor Response: <?php echo htmlspecialchars($existingFeedback['admin_response']); ?>
                </div>
              <?php endif; ?>
            </div>

            <a href="feedback-details.php?id=<?php echo (int)$existingFeedback['id']; ?>" class="btn btn-outline btn-sm" style="white-space: nowrap;">
              Moderate Review →
            </a>
          </div>
        </div>
      <?php endif; ?>

      <!-- ======================================================================
           5. MECHANIC ASSIGNMENT SECTION (Sections 4, 5, 7, 8)
           ====================================================================== -->
      <div style="background: var(--bg-surface); padding: 2rem; border-radius: var(--radius-lg); border: 1px solid var(--border-card); margin-bottom: 2rem;">
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 0.75rem;">
          <div>
            <h3 style="font-size: 1.25rem; color: #ffffff; margin: 0 0 0.25rem 0;">
              Workshop Mechanic Allocation
            </h3>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0;">
              Assign or reassign an available technician to confirm this service booking.
            </p>
          </div>
          
          <?php if (!empty($booking['mechanic_name'])): ?>
            <span class="badge" style="background: rgba(56,189,248,0.15); color: #38bdf8; border: 1px solid rgba(56,189,248,0.3); padding: 0.4rem 0.85rem;">
              ● Currently Assigned
            </span>
          <?php else: ?>
            <span class="badge" style="background: rgba(250,204,21,0.15); color: #fbbf24; border: 1px solid rgba(250,204,21,0.3); padding: 0.4rem 0.85rem;">
              ● Waiting for mechanic assignment
            </span>
          <?php endif; ?>
        </div>

        <?php if (!empty($booking['mechanic_name'])): ?>
          <!-- Current Mechanic Display Card -->
          <div style="background: var(--bg-card); border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 1.25rem; margin-bottom: 1.5rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
            <div style="display: flex; align-items: center; gap: 1rem;">
              <div style="width: 48px; height: 48px; border-radius: 50%; background: rgba(56,189,248,0.15); display: flex; align-items: center; justify-content: center; font-size: 1.35rem; border: 1px solid rgba(56,189,248,0.3);">
                🔧
              </div>
              <div>
                <div style="font-size: 1.1rem; font-weight: 700; color: #ffffff;">
                  <?php echo htmlspecialchars($booking['mechanic_name']); ?>
                </div>
                <div style="font-size: 0.85rem; color: var(--text-muted);">
                  Specialization: <strong style="color: var(--text-secondary);"><?php echo htmlspecialchars($booking['mechanic_specialization'] ?? 'Master Technician'); ?></strong>
                </div>
                <?php if (!empty($booking['mechanic_phone'])): ?>
                  <div style="font-size: 0.8rem; color: var(--text-muted);">
                    Contact: <strong style="color: var(--accent-orange);"><?php echo htmlspecialchars($booking['mechanic_phone']); ?></strong>
                  </div>
                <?php endif; ?>
              </div>
            </div>

            <button type="button" class="btn btn-outline btn-sm" id="toggleReassignBtn" onclick="document.getElementById('reassignFormContainer').style.display = (document.getElementById('reassignFormContainer').style.display === 'none' ? 'block' : 'none');">
              Reassign Mechanic ↻
            </button>
          </div>
        <?php endif; ?>

        <!-- Mechanic Assignment Form -->
        <div id="reassignFormContainer" style="<?php echo !empty($booking['mechanic_name']) ? 'display: none;' : ''; ?> background: var(--bg-card); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
          
          <h4 style="font-size: 1rem; color: #ffffff; margin-bottom: 1rem;">
            <?php echo !empty($booking['mechanic_name']) ? 'Reassign to Another Technician' : 'Select Technician for Assignment'; ?>
          </h4>

          <?php if (in_array($booking['status'], ['Completed', 'Cancelled'])): ?>
            <p style="color: var(--text-muted); font-size: 0.9rem; margin: 0;">
              This booking is marked as <strong><?php echo htmlspecialchars($booking['status']); ?></strong>. Mechanic assignment cannot be modified.
            </p>
          <?php else: ?>
            <form method="POST" action="assign-mechanic.php" id="assignMechanicForm">
              <input type="hidden" name="booking_id" value="<?php echo (int)$booking['id']; ?>">
              <input type="hidden" name="is_reassign" value="<?php echo !empty($booking['mechanic_name']) ? '1' : '0'; ?>">

              <div class="form-group" style="margin-bottom: 1.25rem;">
                <label for="mechanic_id" class="form-label">
                  Available Workshop Mechanics <span class="required-dot">*</span>
                </label>
                
                <select id="mechanic_id" name="mechanic_id" class="form-select" required>
                  <option value="" disabled <?php echo empty($booking['mechanic_id']) ? 'selected' : ''; ?>>
                    -- Select Certified Mechanic --
                  </option>
                  <?php foreach ($mechanics as $mech): 
                    $isAssigned = ((int)$booking['mechanic_id'] === (int)$mech['id']);
                    $isOnLeave = (isset($mech['status']) && $mech['status'] === 'On Leave');
                  ?>
                    <option value="<?php echo (int)$mech['id']; ?>" 
                            <?php echo $isAssigned ? 'selected' : ''; ?>
                            <?php echo $isOnLeave ? 'disabled' : ''; ?>>
                      <?php 
                        echo htmlspecialchars($mech['full_name']); 
                        if (!empty($mech['specialization'])) {
                            echo " (" . htmlspecialchars($mech['specialization']) . ")";
                        }
                        if (!empty($mech['status'])) {
                            echo " - [" . htmlspecialchars($mech['status']) . "]";
                        }
                        if ($isOnLeave) {
                            echo " (Unavailable)";
                        }
                      ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                
                <small style="color: var(--text-muted); font-size: 0.75rem; margin-top: 0.35rem; display: block;">
                  Upon saving, the mechanic is assigned to this vehicle and the booking status changes to <strong>Confirmed</strong>.
                </small>
              </div>

              <div style="display: flex; gap: 1rem; align-items: center;">
                <button type="submit" class="btn btn-primary">
                  <?php echo !empty($booking['mechanic_name']) ? 'Confirm Reassignment →' : 'Assign Mechanic & Confirm Booking →'; ?>
                </button>
                <?php if (!empty($booking['mechanic_name'])): ?>
                  <button type="button" class="btn btn-secondary" onclick="document.getElementById('reassignFormContainer').style.display='none';">
                    Cancel
                  </button>
                <?php endif; ?>
              </div>
            </form>
          <?php endif; ?>

        </div>

      </div>

      <!-- Bottom Navigation Links -->
      <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border-subtle); padding-top: 1.5rem;">
        <a href="bookings.php" class="btn btn-secondary">
          ← Back to All Bookings
        </a>
        <div style="font-size: 0.85rem; color: var(--text-muted);">
          Booking ID: #<?php echo (int)$booking['id']; ?>
        </div>
      </div>

    </div>
  <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
