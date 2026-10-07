<?php
/**
 * ============================================================================
 * MotoCare – Two-Wheeler Service & Mechanic Shop Management System
 * File: mechanic/service-details.php
 * Stage 2: Technician Job Card & Service Progress Logger
 * ============================================================================
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/constants.php';

requireMechanicLogin();

$mechanic = getCurrentMechanic();

$bookingCode = trim($_GET['code'] ?? '');
$bookingId   = (int)($_GET['id'] ?? 0);

if (!empty($bookingCode)) {
    header('Location: job-card.php?code=' . urlencode($bookingCode));
    exit;
} elseif ($bookingId > 0) {
    header('Location: job-card.php?id=' . $bookingId);
    exit;
} else {
    header('Location: dashboard.php');
    exit;
}
$errorMsg = '';
$booking = null;
$record = null;

// Handle Job Card Update Form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_job_card') {
    $newStatus = trim($_POST['status'] ?? '');
    $inspectionNotes = trim($_POST['inspection_notes'] ?? '');
    $workDone = trim($_POST['work_done'] ?? '');
    $partsUsed = trim($_POST['parts_used'] ?? '');

    if ($pdo) {
        try {
            $pdo->beginTransaction();

            // 1. Update booking status
            $stmt1 = $pdo->prepare("UPDATE bookings SET status = :status WHERE booking_code = :code");
            $stmt1->execute([':status' => $newStatus, ':code' => $bookingCode]);

            // 2. Fetch booking ID
            $stmtId = $pdo->prepare("SELECT id FROM bookings WHERE booking_code = :code LIMIT 1");
            $stmtId->execute([':code' => $bookingCode]);
            $bRow = $stmtId->fetch();

            if ($bRow) {
                $bId = $bRow['id'];
                // Check if service_record exists
                $stmtCheck = $pdo->prepare("SELECT id FROM service_records WHERE booking_id = :bid LIMIT 1");
                $stmtCheck->execute([':bid' => $bId]);
                $recRow = $stmtCheck->fetch();

                if ($recRow) {
                    $stmtRec = $pdo->prepare("
                        UPDATE service_records 
                        SET mechanic_id = :mid,
                            inspection_notes = :notes,
                            work_done = :work,
                            parts_used = :parts,
                            service_status = :status,
                            service_end = CASE WHEN :is_completed = 1 THEN NOW() ELSE service_end END
                        WHERE id = :rid
                    ");
                    $stmtRec->execute([
                        ':mid' => $mechanicId,
                        ':notes' => $inspectionNotes,
                        ':work' => $workDone,
                        ':parts' => $partsUsed,
                        ':status' => $newStatus,
                        ':is_completed' => ($newStatus === 'Completed' || $newStatus === 'Ready for Delivery') ? 1 : 0,
                        ':rid' => $recRow['id']
                    ]);
                } else {
                    $stmtRec = $pdo->prepare("
                        INSERT INTO service_records (booking_id, mechanic_id, inspection_notes, work_done, parts_used, service_start, service_status, created_at)
                        VALUES (:bid, :mid, :notes, :work, :parts, NOW(), :status, NOW())
                    ");
                    $stmtRec->execute([
                        ':bid' => $bId,
                        ':mid' => $mechanicId,
                        ':notes' => $inspectionNotes,
                        ':work' => $workDone,
                        ':parts' => $partsUsed,
                        ':status' => $newStatus
                    ]);
                }
            }

            $pdo->commit();
            $successMsg = "Job card successfully updated to: {$newStatus}";
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errorMsg = "Database error: " . $e->getMessage();
        }
    } else {
        $successMsg = "Demo: Job card updated to '{$newStatus}' (Mock update)";
    }
}

// Fetch booking & record data
if ($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                b.*,
                c.full_name AS customer_name,
                c.phone AS customer_phone,
                c.email AS customer_email,
                v.brand, v.model, v.registration_number, v.vehicle_type,
                s.service_name, s.price AS service_price, s.estimated_duration,
                sr.id AS record_id,
                sr.inspection_notes,
                sr.work_done,
                sr.parts_used,
                sr.service_start,
                sr.service_end
            FROM bookings b
            JOIN customers c ON b.customer_id = c.id
            JOIN vehicles v ON b.vehicle_id = v.id
            JOIN services s ON b.service_id = s.id
            LEFT JOIN service_records sr ON b.id = sr.booking_id
            WHERE b.booking_code = :code
            LIMIT 1
        ");
        $stmt->execute([':code' => $bookingCode]);
        $booking = $stmt->fetch();
    } catch (PDOException $e) {
        $errorMsg = "Database error: " . $e->getMessage();
    }
}

// Fallback mock data if offline viva
if (!$booking) {
    $booking = [
        'booking_code' => $bookingCode,
        'customer_name' => 'Rajesh Kumar',
        'customer_phone' => '9876543210',
        'customer_email' => 'rajesh.kumar@example.com',
        'brand' => 'Royal Enfield',
        'model' => 'Classic 350',
        'registration_number' => 'TN-09-BK-4521',
        'vehicle_type' => 'Motorcycle',
        'service_name' => 'General Periodic Maintenance',
        'service_price' => 799.00,
        'estimated_duration' => '90 mins',
        'preferred_date' => '2026-10-06',
        'preferred_time' => '09:00:00',
        'status' => 'Service In Progress',
        'problem_description' => 'Engine tappet noise and hard front brake pull.',
        'inspection_notes' => 'Engine tappet noise detected; front brake pad 80% worn out.',
        'work_done' => 'Oil replacement, oil filter change, spark plug cleanup, chain slack tensioning.',
        'parts_used' => 'Motul 7100 15W-50 (2.5L), OEM Oil Filter, Spark Plug NGK'
    ];
}

include __DIR__ . '/../includes/mechanic-header.php';
?>

<div class="container">
  
  <div style="margin-bottom: 1.5rem;">
    <a href="assigned-services.php" class="btn btn-outline btn-sm">← Back to Assigned Bikes</a>
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

  <div style="display: grid; grid-template-columns: 1fr 340px; gap: 2rem; align-items: start;">
    
    <!-- Left Column: Technician Action Form -->
    <div class="dashboard-card" style="background: var(--bg-card); border: 1px solid var(--border-subtle); border-radius: var(--radius-lg); padding: 2rem;">
      
      <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-subtle); padding-bottom: 1.25rem; margin-bottom: 1.5rem;">
        <div>
          <span class="badge" style="background: rgba(16,185,129,0.15); color: var(--color-green); border: 1px solid rgba(16,185,129,0.3);">
            Active Job Card
          </span>
          <h2 style="font-size: 1.5rem; color: #ffffff; margin-top: 0.5rem; margin-bottom: 0;">
            Update Service Log &amp; Progress
          </h2>
        </div>
        <div style="text-align: right;">
          <div style="font-family: monospace; font-size: 1.2rem; font-weight: 700; color: var(--accent-orange);">
            <?php echo htmlspecialchars($booking['booking_code']); ?>
          </div>
          <div style="font-size: 0.8rem; color: var(--text-muted);">
            Slot: <?php echo formatDisplayDate($booking['preferred_date']); ?>
          </div>
        </div>
      </div>

      <form method="POST" action="service-details.php?code=<?php echo urlencode($bookingCode); ?>" class="booking-form" style="display: flex; flex-direction: column; gap: 1.5rem;">
        <input type="hidden" name="action" value="update_job_card">

        <!-- Status selector -->
        <div class="form-group">
          <label for="status" class="form-label">Service Stage / Status <span class="required">*</span></label>
          <select id="status" name="status" class="form-select" required>
            <?php 
              $stages = [
                'Vehicle Received' => 'Vehicle Received (In Bay)',
                'Inspection' => 'Initial Inspection & Diagnostics',
                'Service In Progress' => 'Service In Progress (On Lift)',
                'Quality Check' => 'Quality Check & Road Test',
                'Ready for Delivery' => 'Ready for Delivery',
                'Completed' => 'Completed & Closed'
              ];
              foreach ($stages as $key => $lbl):
            ?>
              <option value="<?php echo $key; ?>" <?php echo ($booking['status'] === $key) ? 'selected' : ''; ?>>
                <?php echo $lbl; ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Inspection notes -->
        <div class="form-group">
          <label for="inspection_notes" class="form-label">Technician Inspection &amp; Diagnostic Notes</label>
          <textarea id="inspection_notes" name="inspection_notes" class="form-textarea" rows="3" placeholder="e.g. Compression test normal, brake fluid degraded, chain tension loose..."><?php echo htmlspecialchars($booking['inspection_notes'] ?? ''); ?></textarea>
        </div>

        <!-- Work done -->
        <div class="form-group">
          <label for="work_done" class="form-label">Work Done &amp; Repairs Executed</label>
          <textarea id="work_done" name="work_done" class="form-textarea" rows="3" placeholder="e.g. Engine oil replaced, tappets adjusted, spark plug cleaned, drive chain lubricated..."><?php echo htmlspecialchars($booking['work_done'] ?? ''); ?></textarea>
        </div>

        <!-- Parts used -->
        <div class="form-group">
          <label for="parts_used" class="form-label">Spare Parts &amp; Consumables Consumed</label>
          <input type="text" id="parts_used" name="parts_used" class="form-input" 
                 placeholder="e.g. Motul 7100 15W-50 (2.5L), Oil Filter #OF-44, Spark Plug NGK" 
                 value="<?php echo htmlspecialchars($booking['parts_used'] ?? ''); ?>">
          <small style="color: var(--text-muted); font-size: 0.75rem; margin-top: 0.35rem; display: block;">
            List OEM spare parts drawn from inventory to be billed to customer.
          </small>
        </div>

        <button type="submit" class="btn btn-primary btn-block" style="background: var(--color-green); border-color: var(--color-green); padding: 0.9rem;">
          Save Job Card Updates →
        </button>
      </form>

    </div>

    <!-- Right Column: Vehicle & Booking Information Summary -->
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
      
      <div class="dashboard-card" style="background: var(--bg-card); border: 1px solid var(--border-subtle); border-radius: var(--radius-lg); padding: 1.5rem;">
        <h3 style="font-size: 1.1rem; color: #ffffff; margin-bottom: 1rem; border-bottom: 1px solid var(--border-subtle); padding-bottom: 0.5rem;">
          Vehicle Information
        </h3>
        <div style="font-size: 0.9rem; line-height: 1.8;">
          <div><strong style="color: var(--text-muted);">Vehicle:</strong> <span style="color: #ffffff;"><?php echo htmlspecialchars($booking['brand'] . ' ' . $booking['model']); ?></span></div>
          <div><strong style="color: var(--text-muted);">Reg #:</strong> <span style="font-family: monospace; color: var(--accent-orange); font-weight: 700;"><?php echo htmlspecialchars($booking['registration_number']); ?></span></div>
          <div><strong style="color: var(--text-muted);">Type:</strong> <span style="color: #ffffff;"><?php echo htmlspecialchars($booking['vehicle_type']); ?></span></div>
        </div>
      </div>

      <div class="dashboard-card" style="background: var(--bg-card); border: 1px solid var(--border-subtle); border-radius: var(--radius-lg); padding: 1.5rem;">
        <h3 style="font-size: 1.1rem; color: #ffffff; margin-bottom: 1rem; border-bottom: 1px solid var(--border-subtle); padding-bottom: 0.5rem;">
          Customer &amp; Request
        </h3>
        <div style="font-size: 0.9rem; line-height: 1.8;">
          <div><strong style="color: var(--text-muted);">Customer:</strong> <span style="color: #ffffff;"><?php echo htmlspecialchars($booking['customer_name']); ?></span></div>
          <div><strong style="color: var(--text-muted);">Phone:</strong> <a href="tel:<?php echo htmlspecialchars($booking['customer_phone']); ?>" style="color: var(--accent-orange);"><?php echo htmlspecialchars($booking['customer_phone']); ?></a></div>
          <div style="margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px dashed var(--border-subtle);">
            <strong style="color: var(--text-muted);">Customer Complaint:</strong>
            <p style="margin: 0.35rem 0 0; color: var(--text-secondary); font-size: 0.85rem; font-style: italic;">
              "<?php echo htmlspecialchars($booking['problem_description'] ?? 'Standard maintenance.'); ?>"
            </p>
          </div>
        </div>
      </div>

      <div class="dashboard-card" style="background: var(--bg-card); border: 1px solid var(--border-subtle); border-radius: var(--radius-lg); padding: 1.5rem;">
        <h3 style="font-size: 1.1rem; color: #ffffff; margin-bottom: 1rem; border-bottom: 1px solid var(--border-subtle); padding-bottom: 0.5rem;">
          Package Specs
        </h3>
        <div style="font-size: 0.9rem; line-height: 1.8;">
          <div><strong style="color: var(--text-muted);">Package:</strong> <span style="color: #ffffff;"><?php echo htmlspecialchars($booking['service_name']); ?></span></div>
          <div><strong style="color: var(--text-muted);">Duration:</strong> <span style="color: #ffffff;"><?php echo htmlspecialchars($booking['estimated_duration'] ?? '60 mins'); ?></span></div>
          <div><strong style="color: var(--text-muted);">Base Cost:</strong> <span style="color: var(--accent-orange); font-weight: 700;"><?php echo formatCurrency($booking['service_price']); ?></span></div>
        </div>
      </div>

    </div>

  </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
