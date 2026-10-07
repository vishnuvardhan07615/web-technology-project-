<?php
/**
 * ============================================================================
 * MotoCare – Two-Wheeler Service & Mechanic Shop Management System
 * File: customer/feedback-history.php
 * Stage 2: Customer's Submitted Feedback & Star Ratings History
 * ============================================================================
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/feedback-helper.php';

requireCustomerLogin();
$customer = getCurrentCustomer();
$customerId = (int)$customer['id'];

$pageTitle = 'My Service Reviews – MotoCare';
$currentPage = 'feedback-history.php';

$reviews = [];
$errorMsg = '';

if ($pdo) {
    try {
        // Query only reviews submitted by this authenticated customer (Strict Session Bound)
        $stmt = $pdo->prepare("
            SELECT 
                f.id AS feedback_id,
                f.rating,
                f.comments,
                f.admin_response,
                f.moderation_status,
                f.created_at,
                f.moderated_at,
                b.id AS booking_id,
                b.booking_code,
                b.preferred_date,
                v.brand, v.model, v.registration_number,
                s.service_name,
                m.full_name AS mechanic_name
            FROM feedback f
            JOIN bookings b ON f.booking_id = b.id
            JOIN vehicles v ON b.vehicle_id = v.id
            JOIN services s ON b.service_id = s.id
            LEFT JOIN mechanics m ON b.mechanic_id = m.id
            WHERE f.customer_id = :cid
            ORDER BY f.created_at DESC
        ");
        $stmt->execute([':cid' => $customerId]);
        $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $errorMsg = 'Database error loading reviews: ' . $e->getMessage();
    }
}

require_once __DIR__ . '/../includes/customer-header.php';
?>

<div class="container" style="max-width: 900px; padding-top: 1rem; padding-bottom: 4rem;">

  <!-- Header Banner -->
  <div style="background: linear-gradient(135deg, var(--bg-card), var(--bg-surface)); border: 1px solid var(--border-card); border-radius: var(--radius-xl); padding: 2rem 2.25rem; margin-bottom: 2rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1.5rem;">
    <div>
      <span class="service-tag" style="background: rgba(255,107,0,0.15); color: var(--accent-orange); margin-bottom: 0.5rem; display: inline-block;">
        Customer Quality Voice
      </span>
      <h1 style="font-size: 1.85rem; color: #ffffff; font-family: var(--font-heading); margin-bottom: 0.25rem;">
        My Service Reviews
      </h1>
      <p style="color: var(--text-muted); font-size: 0.95rem; margin: 0;">
        History of all feedback, ratings, and workshop supervisor responses for your vehicle services.
      </p>
    </div>

    <div style="display: flex; gap: 0.75rem;">
      <a href="dashboard.php" class="btn btn-outline btn-sm">
        ← Dashboard
      </a>
      <a href="bookings.php" class="btn btn-primary btn-sm">
        View Bookings
      </a>
    </div>
  </div>

  <?php if ($errorMsg): ?>
    <div class="form-alert form-alert-error" style="margin-bottom: 1.5rem;">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
      <span><?php echo htmlspecialchars($errorMsg); ?></span>
    </div>
  <?php endif; ?>

  <?php if (empty($reviews)): ?>
    <div class="form-card" style="padding: 3.5rem 2rem; text-align: center;">
      <div style="font-size: 3rem; margin-bottom: 1rem;">★</div>
      <h3 style="color: #ffffff; font-size: 1.35rem; margin-bottom: 0.5rem;">No Reviews Submitted Yet</h3>
      <p style="color: var(--text-muted); max-width: 480px; margin: 0 auto 1.75rem; font-size: 0.95rem;">
        Once your scheduled two-wheeler service appointment is completed by our mechanics, you can rate your experience here.
      </p>
      <a href="bookings.php" class="btn btn-primary">
        Inspect Completed Bookings →
      </a>
    </div>
  <?php else: ?>

    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
      <?php foreach ($reviews as $rev): 
        $status = $rev['moderation_status'];
        $badgeBg = 'rgba(251,191,36,0.15)'; $badgeCol = '#fbbf24';
        if ($status === 'Approved') { $badgeBg = 'rgba(16,185,129,0.15)'; $badgeCol = '#10b981'; }
        elseif ($status === 'Rejected') { $badgeBg = 'rgba(239,68,68,0.15)'; $badgeCol = '#f87171'; }
        $ratingNum = (int)$rev['rating'];
      ?>
        <div class="form-card" style="padding: 1.75rem 2rem; border-left: 4px solid <?php echo $badgeCol; ?>;">
          
          <!-- Review Header -->
          <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.75rem;">
            <div>
              <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                <span style="color: #fbbf24; font-size: 1.25rem; letter-spacing: 2px;">
                  <?php echo str_repeat('★', $ratingNum) . str_repeat('☆', 5 - $ratingNum); ?>
                </span>
                <span style="font-weight: 700; color: #ffffff; font-size: 1rem;">
                  <?php echo $ratingNum; ?> / 5 Stars
                </span>
                <span class="badge" style="background: <?php echo $badgeBg; ?>; color: <?php echo $badgeCol; ?>; border: 1px solid <?php echo $badgeCol; ?>; font-size: 0.75rem; padding: 0.2rem 0.55rem;">
                  Status: <?php echo htmlspecialchars($status); ?>
                </span>
              </div>

              <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.35rem;">
                Service: <strong style="color: #ffffff;"><?php echo htmlspecialchars($rev['service_name']); ?></strong> &bull;
                Vehicle: <strong style="color: var(--text-secondary);"><?php echo htmlspecialchars($rev['brand'] . ' ' . $rev['model'] . ' (' . $rev['registration_number'] . ')'); ?></strong> &bull;
                Booking: <a href="booking-details.php?id=<?php echo (int)$rev['booking_id']; ?>" style="color: var(--accent-orange); font-family: monospace; text-decoration: none;"><?php echo htmlspecialchars($rev['booking_code']); ?></a>
              </div>
            </div>

            <div style="font-size: 0.8rem; color: var(--text-muted); white-space: nowrap;">
              Submitted: <?php echo formatDisplayDate($rev['created_at']); ?>
            </div>
          </div>

          <!-- Review Body -->
          <div style="background: var(--bg-surface); padding: 1.25rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); margin-bottom: 1rem;">
            <p style="color: var(--text-secondary); font-size: 0.95rem; line-height: 1.6; margin: 0; font-style: italic;">
              "<?php echo nl2br(htmlspecialchars($rev['comments'])); ?>"
            </p>
          </div>

          <!-- Workshop Supervisor Response (if any) -->
          <?php if (!empty($rev['admin_response'])): ?>
            <div style="background: var(--bg-card); border-left: 3px solid var(--accent-orange); padding: 1rem 1.25rem; border-radius: var(--radius-sm); margin-top: 0.75rem;">
              <div style="font-size: 0.8rem; font-weight: 700; color: var(--accent-orange); text-transform: uppercase;">
                Workshop Supervisor Response:
              </div>
              <p style="color: var(--text-main); font-size: 0.9rem; margin: 0.25rem 0 0; line-height: 1.5;">
                <?php echo nl2br(htmlspecialchars($rev['admin_response'])); ?>
              </p>
            </div>
          <?php endif; ?>

        </div>
      <?php endforeach; ?>
    </div>

  <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
