<?php
/**
 * ============================================================================
 * MotoCare – Two-Wheeler Service & Mechanic Shop Management System
 * File: admin/feedback-details.php
 * Stage 2: Feedback Inspection & Supervisor Moderation Interface
 * ============================================================================
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/feedback-helper.php';

requireAdminLogin();
$admin = getCurrentAdmin();
$adminId = (int)$admin['id'];

$feedbackId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($feedbackId <= 0) {
    header("Location: feedback.php?err=" . urlencode("Invalid review ID specified."));
    exit;
}

$feedback = null;
$errorMsg = '';
$successMsg = '';

// Handle Moderation Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim($_POST['action'] ?? '');
    $adminResponse = trim($_POST['admin_response'] ?? '');

    $targetStatus = 'Pending';
    if ($action === 'approve') {
        $targetStatus = 'Approved';
    } elseif ($action === 'reject') {
        $targetStatus = 'Rejected';
    }

    if (in_array($targetStatus, ['Approved', 'Rejected'])) {
        $res = moderateFeedback($pdo, $feedbackId, $adminId, $targetStatus, $adminResponse ?: null);
        if ($res['success']) {
            $successMsg = "Feedback #{$feedbackId} successfully updated to {$targetStatus}!";
        } else {
            $errorMsg = $res['error'];
        }
    }
}

// Fetch Feedback Record with Customer, Booking, Vehicle, Service, Mechanic, Invoice, and Moderator details
if ($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                f.*,
                c.full_name AS customer_name,
                c.email AS customer_email,
                c.phone AS customer_phone,
                c.address AS customer_address,
                b.id AS booking_id,
                b.booking_code,
                b.preferred_date,
                b.preferred_time,
                b.status AS booking_status,
                v.brand, v.model, v.registration_number, v.vehicle_type,
                s.service_name, s.price AS service_price,
                m.full_name AS mechanic_name,
                m.specialization AS mechanic_specialization,
                bi.id AS bill_id,
                bi.invoice_number,
                bi.total_amount AS invoice_total,
                bi.payment_status AS invoice_payment_status,
                adm.username AS moderator_name
            FROM feedback f
            JOIN customers c ON f.customer_id = c.id
            JOIN bookings b ON f.booking_id = b.id
            JOIN vehicles v ON b.vehicle_id = v.id
            JOIN services s ON b.service_id = s.id
            LEFT JOIN mechanics m ON b.mechanic_id = m.id
            LEFT JOIN bills bi ON b.id = bi.booking_id
            LEFT JOIN admin_users adm ON f.moderator_id = adm.id
            WHERE f.id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $feedbackId]);
        $feedback = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$feedback) {
            $errorMsg = "Feedback record not found in system.";
        }
    } catch (PDOException $e) {
        $errorMsg = "Database error: " . $e->getMessage();
    }
}

$pageTitle = 'Review #' . $feedbackId . ' – Admin Moderation – MotoCare';
$currentPage = 'feedback.php';
include __DIR__ . '/../includes/admin-header.php';
?>

<div class="container" style="max-width: 860px; padding-top: 1rem; padding-bottom: 4rem;">

  <!-- Navigation -->
  <div style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
    <a href="feedback.php" class="btn btn-outline btn-sm">
      ← Back to Reviews Queue
    </a>
    <div style="display: flex; gap: 0.5rem;">
      <a href="booking-details.php?id=<?php echo (int)($feedback['booking_id'] ?? 0); ?>" class="btn btn-secondary btn-sm">
        View Service Booking
      </a>
      <?php if (!empty($feedback['bill_id'])): ?>
        <a href="bill-details.php?id=<?php echo (int)$feedback['bill_id']; ?>" class="btn btn-secondary btn-sm">
          View Tax Invoice
        </a>
      <?php endif; ?>
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

  <?php if ($feedback): 
    $mstat = $feedback['moderation_status'];
    $mCol = '#fbbf24'; $mBg = 'rgba(251,191,36,0.15)';
    if ($mstat === 'Approved') { $mCol = '#10b981'; $mBg = 'rgba(16,185,129,0.15)'; }
    elseif ($mstat === 'Rejected') { $mCol = '#f87171'; $mBg = 'rgba(239,68,68,0.15)'; }
    $rNum = (int)$feedback['rating'];
  ?>
    <div class="form-card" style="padding: 2.5rem 2rem;">
      
      <!-- Top Title and Status Header -->
      <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2rem; border-bottom: 1px solid var(--border-subtle); padding-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <div>
          <span class="service-tag" style="background: rgba(251,191,36,0.15); color: #fbbf24; border-color: rgba(251,191,36,0.3); margin-bottom: 0.5rem; display: inline-block;">
            Review Verification #<?php echo $feedbackId; ?>
          </span>
          <h1 style="font-size: 1.85rem; margin-bottom: 0.25rem; color: #ffffff; font-family: var(--font-heading);">
            Customer Review &amp; Moderation
          </h1>
          <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0;">
            Submitted on: <?php echo formatDisplayDate($feedback['created_at']); ?>
          </p>
        </div>

        <div style="text-align: right;">
          <span class="badge" style="background: <?php echo $mBg; ?>; color: <?php echo $mCol; ?>; border: 1px solid <?php echo $mCol; ?>; font-size: 0.85rem; padding: 0.35rem 0.75rem; font-weight: 700;">
            ● Moderation: <?php echo htmlspecialchars($mstat); ?>
          </span>
          <?php if (!empty($feedback['moderator_name'])): ?>
            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.35rem;">
              Moderated by: <strong style="color: #ffffff;"><?php echo htmlspecialchars($feedback['moderator_name']); ?></strong> 
              (<?php echo formatDisplayDate($feedback['moderated_at']); ?>)
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Rating Display Banner -->
      <div style="background: rgba(251,191,36,0.08); border: 1px solid rgba(251,191,36,0.25); border-radius: var(--radius-md); padding: 1.5rem; margin-bottom: 2rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
        <div style="display: flex; align-items: center; gap: 1rem;">
          <div style="font-size: 2.5rem; color: #fbbf24; letter-spacing: 3px;">
            <?php echo str_repeat('★', $rNum) . str_repeat('☆', 5 - $rNum); ?>
          </div>
          <div>
            <div style="font-size: 1.25rem; font-weight: 800; color: #ffffff; font-family: var(--font-heading);">
              <?php echo $rNum; ?> out of 5 Stars
            </div>
            <div style="font-size: 0.85rem; color: var(--text-muted);">
              Customer Rating Assessment
            </div>
          </div>
        </div>

        <div>
          <span class="service-tag" style="background: <?php echo $mBg; ?>; color: <?php echo $mCol; ?>;">
            <?php echo $mstat === 'Approved' ? 'Publicly Visible on Homepage' : 'Hidden from Public Website'; ?>
          </span>
        </div>
      </div>

      <!-- Customer Comments Card -->
      <div style="background: var(--bg-surface); border: 1px solid var(--border-card); border-radius: var(--radius-md); padding: 1.75rem; margin-bottom: 2rem;">
        <h4 style="font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin: 0 0 0.75rem 0;">
          Customer Review Comments
        </h4>
        <p style="color: var(--text-main); font-size: 1.05rem; line-height: 1.6; margin: 0; font-style: italic;">
          "<?php echo nl2br(htmlspecialchars($feedback['comments'])); ?>"
        </p>
      </div>

      <!-- Service & Workshop Context Grid -->
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        
        <!-- Customer Info -->
        <div style="background: var(--bg-card); padding: 1.25rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
          <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.35rem;">Customer</div>
          <div style="font-weight: 700; color: #ffffff; font-size: 1rem;"><?php echo htmlspecialchars($feedback['customer_name']); ?></div>
          <div style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 0.15rem;"><?php echo htmlspecialchars($feedback['customer_phone']); ?></div>
          <div style="font-size: 0.8rem; color: var(--text-muted);"><?php echo htmlspecialchars($feedback['customer_email']); ?></div>
        </div>

        <!-- Vehicle Info -->
        <div style="background: var(--bg-card); padding: 1.25rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
          <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.35rem;">Vehicle</div>
          <div style="font-weight: 700; color: #ffffff; font-size: 1rem;"><?php echo htmlspecialchars($feedback['brand'] . ' ' . $feedback['model']); ?></div>
          <div style="font-family: monospace; font-size: 0.85rem; color: var(--accent-orange); margin-top: 0.15rem;"><?php echo htmlspecialchars($feedback['registration_number']); ?></div>
          <div style="font-size: 0.8rem; color: var(--text-muted);"><?php echo htmlspecialchars($feedback['vehicle_type']); ?></div>
        </div>

        <!-- Service & Mechanic -->
        <div style="background: var(--bg-card); padding: 1.25rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
          <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.35rem;">Service &amp; Mechanic</div>
          <div style="font-weight: 700; color: #ffffff; font-size: 1rem;"><?php echo htmlspecialchars($feedback['service_name']); ?></div>
          <div style="font-size: 0.85rem; color: #60a5fa; margin-top: 0.15rem;">
            🔧 <?php echo htmlspecialchars($feedback['mechanic_name'] ?? 'Senior Mechanic'); ?>
          </div>
          <div style="font-size: 0.8rem; color: var(--text-muted);">
            Booking: <a href="booking-details.php?id=<?php echo (int)$feedback['booking_id']; ?>" style="color: var(--accent-orange); font-family: monospace; text-decoration: none;"><?php echo htmlspecialchars($feedback['booking_code']); ?></a>
          </div>
        </div>

      </div>

      <!-- Moderation Form Section (Section 9 & 16) -->
      <div style="background: var(--bg-surface); padding: 2rem; border-radius: var(--radius-lg); border: 1px solid var(--border-card);">
        <h3 style="font-size: 1.15rem; color: #ffffff; margin: 0 0 1rem 0;">
          Supervisor Moderation Actions
        </h3>

        <form method="POST" action="feedback-details.php?id=<?php echo $feedbackId; ?>">
          
          <div class="form-group" style="margin-bottom: 1.5rem;">
            <label for="admin_response" class="form-label">
              Official Workshop Response (Optional)
            </label>
            <textarea 
              id="admin_response" 
              name="admin_response" 
              rows="3" 
              class="form-control" 
              placeholder="Enter public supervisor response (e.g., Thank you for trusting MotoCare! We have shared your feedback with Arun...)"
            ><?php echo htmlspecialchars($feedback['admin_response'] ?? ''); ?></textarea>
            <small style="color: var(--text-muted); font-size: 0.75rem; margin-top: 0.35rem; display: block;">
              If provided, this response is displayed alongside the customer review on their dashboard and public testimonials.
            </small>
          </div>

          <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
            <button type="submit" name="action" value="approve" class="btn btn-primary" style="background: #10b981; border-color: #10b981;">
              ✓ Approve Review (Publish)
            </button>
            <button type="submit" name="action" value="reject" class="btn btn-secondary" style="color: #f87171; border-color: #f87171;" onclick="return confirm('Are you sure you want to reject this review? It will be hidden from public display.');">
              ✕ Reject Review
            </button>
            <a href="feedback.php" class="btn btn-outline" style="margin-left: auto;">
              Back to Queue
            </a>
          </div>
        </form>
      </div>

    </div>
  <?php endif; ?>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
