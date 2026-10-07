<?php
/**
 * ============================================================================
 * MotoCare – Two-Wheeler Service & Mechanic Shop Management System
 * File: customer/booking-details.php
 * Stage 2: Customer Booking Details View (Security Protected)
 * ============================================================================
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

requireCustomerLogin();
$customer = getCurrentCustomer();
$customerId = (int)$customer['id'];

$bookingCode = trim($_GET['code'] ?? '');
$bookingId = (int)($_GET['id'] ?? 0);

$booking = null;
$errorMsg = '';

if (empty($bookingCode) && $bookingId <= 0) {
    header('Location: bookings.php');
    exit;
}

if ($pdo) {
    try {
        // Query booking strictly filtered by logged-in customer ID (Prevents IDOR / unauthorized access)
        $sql = "
            SELECT 
                b.*,
                c.full_name AS customer_name,
                c.email AS customer_email,
                c.phone AS customer_phone,
                v.brand, v.model, v.registration_number, v.vehicle_type,
                s.service_name, s.category AS service_category, s.price AS service_price, s.estimated_duration,
                m.full_name AS mechanic_name,
                m.specialization AS mechanic_specialization,
                m.phone AS mechanic_phone
            FROM bookings b
            JOIN customers c ON b.customer_id = c.id
            JOIN vehicles v ON b.vehicle_id = v.id
            JOIN services s ON b.service_id = s.id
            LEFT JOIN mechanics m ON b.mechanic_id = m.id
            WHERE (b.booking_code = :code OR b.id = :id) 
              AND b.customer_id = :cid
            LIMIT 1
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':code' => $bookingCode,
            ':id'   => $bookingId,
            ':cid'  => $customerId
        ]);
        $booking = $stmt->fetch();

        $customerPartsUsed = [];
        if (!$booking) {
            $errorMsg = 'Booking not found or you do not have permission to view this reservation.';
        } else {
            // Customer view: only part name and quantity (Section 20: NO internal warehouse stock, reorder levels, or cost)
            $stmtP = $pdo->prepare("
                SELECT sp.part_name, srp.quantity
                FROM service_records sr
                JOIN service_record_parts srp ON sr.id = srp.service_record_id
                JOIN spare_parts sp ON srp.spare_part_id = sp.id
                WHERE sr.booking_id = :bid
                ORDER BY srp.id ASC
            ");
            $stmtP->execute([':bid' => (int)$booking['id']]);
            $customerPartsUsed = $stmtP->fetchAll();

            // Stage 2: Check for existing Customer Feedback (Section 7)
            $existingFeedback = null;
            if ($booking['status'] === 'Completed') {
                $stmtFb = $pdo->prepare("
                    SELECT id, rating, comments, moderation_status, admin_response, created_at 
                    FROM feedback 
                    WHERE booking_id = :bid AND customer_id = :cid 
                    LIMIT 1
                ");
                $stmtFb->execute([':bid' => (int)$booking['id'], ':cid' => $customerId]);
                $existingFeedback = $stmtFb->fetch();
            }
        }
    } catch (PDOException $e) {
        $errorMsg = 'Database error: ' . $e->getMessage();
    }
} else {
    // Offline demo fallback for presentation
    $booking = [
        'id' => 1,
        'booking_code' => !empty($bookingCode) ? $bookingCode : 'MC-2026-1001',
        'customer_name' => $customer['name'],
        'customer_email' => $customer['email'],
        'customer_phone' => $customer['phone'],
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
        'mechanic_name' => null, // Waiting for assignment
        'status' => 'Pending',
        'created_at' => date('Y-m-d H:i:s')
    ];
}

$pageTitle = 'Booking Details ' . htmlspecialchars($booking['booking_code'] ?? '') . ' – MotoCare';
$currentPage = 'bookings.php';
require_once __DIR__ . '/../includes/customer-header.php';
?>

<div class="container" style="max-width: 820px; padding-top: 1rem; padding-bottom: 3rem;">
  
  <div style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
    <a href="bookings.php" class="btn btn-outline btn-sm">
      ← Back to My Bookings
    </a>
    <a href="book-service.php" class="btn btn-secondary btn-sm">
      + Book Another Service
    </a>
  </div>

  <?php if ($errorMsg): ?>
    <div class="form-alert form-alert-error" style="margin-bottom: 2rem;">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
      <span><?php echo htmlspecialchars($errorMsg); ?></span>
    </div>
    <div class="form-card" style="padding: 2.5rem; text-align: center;">
      <p style="color: var(--text-muted); margin-bottom: 1.5rem;">The requested booking cannot be displayed.</p>
      <a href="bookings.php" class="btn btn-primary">Return to Your Bookings List</a>
    </div>
  <?php else: ?>

    <div class="form-card" style="padding: 2.5rem 2rem;">
      
      <!-- Header Banner with Status Badge -->
      <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2rem; border-bottom: 1px solid var(--border-subtle); padding-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <div>
          <span class="service-tag" style="background: var(--accent-glow-subtle); color: var(--accent-orange); margin-bottom: 0.5rem; display: inline-block;">
            Service Appointment
          </span>
          <h1 style="font-size: 1.85rem; margin-bottom: 0.25rem; font-family: var(--font-heading); color: #ffffff;">
            Booking <span style="color: var(--accent-orange);"><?php echo htmlspecialchars($booking['booking_code']); ?></span>
          </h1>
          <p style="color: var(--text-muted); font-size: 0.875rem;">
            Booked on: <?php echo formatDisplayDate($booking['created_at']); ?>
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

      <!-- Information Grid -->
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem; font-size: 0.95rem;">
        
        <!-- Two-Wheeler Details -->
        <div style="background: var(--bg-surface); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
          <h4 style="color: var(--text-muted); text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.05em; margin-bottom: 0.75rem;">
            Two-Wheeler Details
          </h4>
          <p style="color: #ffffff; font-weight: 700; font-size: 1.15rem; margin-bottom: 0.35rem;">
            <?php echo htmlspecialchars($booking['brand'] . ' ' . $booking['model']); ?>
          </p>
          <div style="font-family: monospace; font-size: 0.95rem; color: var(--accent-orange); margin-bottom: 0.5rem; font-weight: 700;">
            Registration: <?php echo htmlspecialchars($booking['registration_number']); ?>
          </div>
          <p style="color: var(--text-secondary); font-size: 0.85rem; margin: 0;">
            Vehicle Type: <strong style="color: #ffffff;"><?php echo htmlspecialchars($booking['vehicle_type']); ?></strong>
          </p>
        </div>

        <!-- Customer & Contact Info -->
        <div style="background: var(--bg-surface); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
          <h4 style="color: var(--text-muted); text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.05em; margin-bottom: 0.75rem;">
            Customer Contact
          </h4>
          <p style="color: #ffffff; font-weight: 700; font-size: 1.15rem; margin-bottom: 0.35rem;">
            <?php echo htmlspecialchars($booking['customer_name']); ?>
          </p>
          <p style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 0.25rem;">
            Phone: <strong style="color: #ffffff;"><?php echo htmlspecialchars($booking['customer_phone']); ?></strong>
          </p>
          <p style="color: var(--text-secondary); font-size: 0.85rem; margin: 0;">
            Email: <strong style="color: #ffffff;"><?php echo htmlspecialchars($booking['customer_email']); ?></strong>
          </p>
        </div>

      </div>

      <!-- Service Package & Appointment Schedule -->
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem; font-size: 0.95rem;">
        
        <!-- Package Specs -->
        <div style="background: var(--bg-surface); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
          <h4 style="color: var(--text-muted); text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.05em; margin-bottom: 0.75rem;">
            Selected Service Package
          </h4>
          <p style="color: #ffffff; font-weight: 700; font-size: 1.15rem; margin-bottom: 0.35rem;">
            <?php echo htmlspecialchars($booking['service_name']); ?>
          </p>
          <p style="color: var(--accent-orange); font-weight: 700; font-size: 1.1rem; margin-bottom: 0.25rem;">
            Base Price: <?php echo formatCurrency($booking['service_price']); ?>
          </p>
          <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0;">
            Estimated Duration: <?php echo htmlspecialchars($booking['estimated_duration'] ?? 'N/A'); ?>
          </p>
        </div>

        <!-- Appointment Slot -->
        <div style="background: var(--bg-surface); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
          <h4 style="color: var(--text-muted); text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.05em; margin-bottom: 0.75rem;">
            Scheduled Date &amp; Time
          </h4>
          <p style="color: #ffffff; font-weight: 700; font-size: 1.15rem; margin-bottom: 0.35rem;">
            📅 <?php echo formatDisplayDate($booking['preferred_date']); ?>
          </p>
          <p style="color: var(--text-secondary); font-size: 0.95rem; margin-bottom: 0.25rem;">
            ⏰ Slot: <strong style="color: #ffffff;"><?php echo htmlspecialchars($booking['preferred_time']); ?></strong>
          </p>
          <p style="color: var(--text-muted); font-size: 0.8rem; margin: 0;">
            Workshop location: MotoCare Main Hub
          </p>
        </div>

      </div>

      <!-- Mechanic Assignment Status (Section 9 Requirement) -->
      <div style="background: var(--bg-surface); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); margin-bottom: 2rem;">
        <h4 style="color: var(--text-muted); text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.05em; margin-bottom: 0.75rem;">
          Assigned Workshop Mechanic
        </h4>
        <?php if (!empty($booking['mechanic_name'])): ?>
          <div style="display: flex; align-items: center; gap: 1rem;">
            <div style="width: 44px; height: 44px; border-radius: 50%; background: rgba(56,189,248,0.15); display: flex; align-items: center; justify-content: center; border: 1px solid rgba(56,189,248,0.3); font-size: 1.2rem;">
              🔧
            </div>
            <div>
              <div style="font-weight: 700; color: #ffffff; font-size: 1.05rem;">
                <?php echo htmlspecialchars($booking['mechanic_name']); ?>
              </div>
              <div style="color: var(--text-muted); font-size: 0.85rem;">
                <?php echo htmlspecialchars($booking['mechanic_specialization'] ?? 'Master Two-Wheeler Technician'); ?>
              </div>
            </div>
          </div>
        <?php else: ?>
          <div style="display: flex; align-items: center; gap: 0.75rem; color: #fbbf24; font-size: 0.95rem;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
            <span>Waiting for mechanic assignment</span>
          </div>
          <small style="color: var(--text-muted); font-size: 0.8rem; margin-top: 0.35rem; display: block;">
            The workshop floor supervisor will assign a specialized mechanic when your vehicle arrives at the service bay.
          </small>
        <?php endif; ?>
      </div>

      <!-- Problem Description Notes -->
      <div style="background: var(--bg-surface); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); margin-bottom: 2rem;">
        <h4 style="color: var(--text-muted); text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.05em; margin-bottom: 0.5rem;">
          Reported Problem / Symptoms
        </h4>
        <p style="color: var(--text-secondary); font-size: 0.95rem; margin: 0; line-height: 1.6; font-style: <?php echo empty($booking['problem_description']) ? 'italic' : 'normal'; ?>;">
          <?php echo !empty($booking['problem_description']) ? nl2br(htmlspecialchars($booking['problem_description'])) : 'No specific complaints noted. Routine periodic maintenance requested.'; ?>
        </p>
      </div>

      <?php if (!empty($customerPartsUsed)): ?>
        <!-- Spare Parts Replaced (Section 20: Only part name & quantity, NO warehouse/stock/cost info) -->
        <div style="background: var(--bg-surface); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); margin-bottom: 2rem;">
          <h4 style="color: var(--text-muted); text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.05em; margin-bottom: 0.75rem;">
            Parts Replaced During Service
          </h4>
          <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.5rem;">
            <?php foreach ($customerPartsUsed as $cpu): ?>
              <li style="display: flex; justify-content: space-between; align-items: center; padding: 0.65rem 0.85rem; background: var(--bg-card); border-radius: var(--radius-sm); border: 1px solid var(--border-subtle);">
                <span style="color: #ffffff; font-weight: 500;">🔧 <?php echo htmlspecialchars($cpu['part_name']); ?></span>
                <span style="color: var(--text-secondary); font-size: 0.85rem;">Quantity: <strong style="color: var(--accent-orange);"><?php echo (int)$cpu['quantity']; ?></strong></span>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <?php if ($booking['status'] === 'Completed'): ?>
        <!-- Stage 2 Section 7: Service Feedback Status / Action -->
        <div style="background: var(--bg-surface); padding: 1.75rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); margin-bottom: 2rem;">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.75rem;">
            <h4 style="color: #ffffff; font-size: 1.05rem; margin: 0; display: flex; align-items: center; gap: 0.5rem;">
              <span>★</span> Service Feedback &amp; Rating
            </h4>
            <?php if (!empty($existingFeedback)): 
              $mstat = $existingFeedback['moderation_status'];
              $mbadgeBg = 'rgba(251,191,36,0.15)'; $mbadgeCol = '#fbbf24';
              if ($mstat === 'Approved') { $mbadgeBg = 'rgba(16,185,129,0.15)'; $mbadgeCol = '#10b981'; }
              elseif ($mstat === 'Rejected') { $mbadgeBg = 'rgba(239,68,68,0.15)'; $mbadgeCol = '#f87171'; }
            ?>
              <span class="badge" style="background: <?php echo $mbadgeBg; ?>; color: <?php echo $mbadgeCol; ?>; border: 1px solid <?php echo $mbadgeCol; ?>; font-size: 0.75rem; padding: 0.25rem 0.65rem;">
                Status: <?php echo htmlspecialchars($mstat); ?>
              </span>
            <?php endif; ?>
          </div>

          <?php if (!empty($existingFeedback)): 
            $rVal = (int)$existingFeedback['rating'];
          ?>
            <div style="background: var(--bg-card); padding: 1.25rem; border-radius: var(--radius-sm); border: 1px solid var(--border-card);">
              <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;">
                <span style="color: #fbbf24; font-size: 1.35rem; letter-spacing: 2px;">
                  <?php echo str_repeat('★', $rVal) . str_repeat('☆', 5 - $rVal); ?>
                </span>
                <span style="color: #ffffff; font-weight: 700; font-size: 0.95rem;">
                  Your Rating: <?php echo $rVal; ?> / 5 Stars
                </span>
              </div>

              <p style="color: var(--text-secondary); font-size: 0.95rem; line-height: 1.6; margin: 0 0 0.75rem 0; font-style: italic;">
                "<?php echo nl2br(htmlspecialchars($existingFeedback['comments'])); ?>"
              </p>

              <?php if (!empty($existingFeedback['admin_response'])): ?>
                <div style="background: var(--bg-surface); border-left: 3px solid var(--accent-orange); padding: 0.85rem 1rem; border-radius: var(--radius-sm); margin-top: 0.75rem;">
                  <div style="font-size: 0.75rem; font-weight: 700; color: var(--accent-orange); text-transform: uppercase;">
                    Workshop Supervisor Response:
                  </div>
                  <p style="color: var(--text-main); font-size: 0.85rem; margin: 0.2rem 0 0; line-height: 1.5;">
                    <?php echo nl2br(htmlspecialchars($existingFeedback['admin_response'])); ?>
                  </p>
                </div>
              <?php endif; ?>

              <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.75rem;">
                Submitted on: <?php echo formatDisplayDate($existingFeedback['created_at']); ?>
              </div>
            </div>
          <?php else: ?>
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; background: rgba(255,107,0,0.06); padding: 1.25rem; border-radius: var(--radius-sm); border: 1px solid rgba(255,107,0,0.2);">
              <div>
                <div style="font-weight: 600; color: #ffffff; font-size: 0.95rem;">
                  How was your workshop service experience?
                </div>
                <div style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.2rem;">
                  Share your 5-star rating and feedback to help us maintain top workshop quality.
                </div>
              </div>
              <a href="feedback.php?booking_id=<?php echo (int)$booking['id']; ?>" class="btn btn-primary btn-sm" style="white-space: nowrap;">
                ★ Rate This Service →
              </a>
            </div>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <!-- Actions -->
      <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
        <a href="bookings.php" class="btn btn-secondary">
          ← Back to All Bookings
        </a>
        <a href="bill.php?booking_code=<?php echo urlencode($booking['booking_code']); ?>" class="btn btn-outline">
          View Invoice / Bill Slip ↗
        </a>
        <a href="book-service.php" class="btn btn-primary" style="margin-left: auto;">
          Book Another Service →
        </a>
      </div>

    </div>

  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
