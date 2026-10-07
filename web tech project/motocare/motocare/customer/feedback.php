<?php
/**
 * ============================================================================
 * MotoCare – Two-Wheeler Service & Mechanic Shop Management System
 * File: customer/feedback.php
 * Stage 2: Customer Service Review & 5-Star Rating Submission
 * ============================================================================
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/feedback-helper.php';

requireCustomerLogin();
$customer = getCurrentCustomer();
$customerId = (int)$customer['id'];

$bookingId = isset($_REQUEST['booking_id']) ? (int)$_REQUEST['booking_id'] : 0;
$bookingCode = trim($_REQUEST['booking_code'] ?? '');

$errorMsg   = '';
$successMsg = '';
$booking    = null;
$existingFb = null;

if (!$pdo) {
    die("Database offline. Please start MySQL service.");
}

// Locate booking by ID or Code
if ($bookingId <= 0 && !empty($bookingCode)) {
    $stmtCode = $pdo->prepare("SELECT id FROM bookings WHERE booking_code = :code AND customer_id = :cid LIMIT 1");
    $stmtCode->execute([':code' => $bookingCode, ':cid' => $customerId]);
    $bookingId = (int)$stmtCode->fetchColumn();
}

if ($bookingId <= 0) {
    header("Location: bookings.php?err=" . urlencode("Please select a completed service booking to leave a review."));
    exit;
}

// Check eligibility
$check = canCustomerSubmitFeedback($pdo, $customerId, $bookingId);
$booking = $check['booking'];
$existingFb = $check['feedback'];

if (!$check['eligible'] && !$existingFb) {
    $errorMsg = $check['error'];
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($existingFb)) {
        $errorMsg = "You have already submitted a review for this completed service. Duplicate reviews are not permitted.";
    } elseif (empty($errorMsg)) {
        $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
        $comments = trim($_POST['comments'] ?? '');

        $result = submitFeedback($pdo, $customerId, $bookingId, $rating, $comments);
        if ($result['success']) {
            $successMsg = "Thank you! Your verified review and rating have been submitted successfully. It will be published after supervisor moderation.";
            // Refresh eligibility to show submitted feedback card
            $check = canCustomerSubmitFeedback($pdo, $customerId, $bookingId);
            $existingFb = $check['feedback'];
        } else {
            $errorMsg = $result['error'];
        }
    }
}

$pageTitle = 'Submit Service Feedback – MotoCare';
$currentPage = 'feedback.php';
require_once __DIR__ . '/../includes/customer-header.php';
?>

<div class="container" style="max-width: 780px; padding-top: 1rem; padding-bottom: 4rem;">

  <!-- Breadcrumb & Navigation -->
  <div style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
    <a href="booking-details.php?id=<?php echo $bookingId; ?>" class="btn btn-outline btn-sm">
      ← Back to Booking Details
    </a>
    <a href="feedback-history.php" class="btn btn-secondary btn-sm">
      ★ View My Reviews
    </a>
  </div>

  <?php if ($successMsg): ?>
    <div class="form-alert form-alert-success" style="margin-bottom: 2rem;">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
      <div>
        <h4 style="margin: 0 0 0.25rem 0; font-size: 1rem; font-weight: 700; color: #10b981;">Review Submitted!</h4>
        <p style="margin: 0; font-size: 0.9rem;"><?php echo htmlspecialchars($successMsg); ?></p>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($errorMsg): ?>
    <div class="form-alert form-alert-error" style="margin-bottom: 2rem;">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
      <span><?php echo htmlspecialchars($errorMsg); ?></span>
    </div>
  <?php endif; ?>

  <?php if ($booking): ?>
    <div class="form-card" style="padding: 2.5rem 2rem;">
      
      <!-- Card Title & Service Summary -->
      <div style="border-bottom: 1px solid var(--border-subtle); padding-bottom: 1.5rem; margin-bottom: 2rem;">
        <span class="service-tag" style="background: rgba(255,107,0,0.15); color: var(--accent-orange); margin-bottom: 0.5rem; display: inline-block;">
          MotoCare Customer Care
        </span>
        <h1 style="font-size: 1.85rem; color: #ffffff; font-family: var(--font-heading); margin-bottom: 0.5rem;">
          MotoCare Service Feedback
        </h1>
        <p style="color: var(--text-muted); font-size: 0.95rem; margin: 0;">
          Your feedback helps us recognize our certified technicians and maintain high workshop standards.
        </p>
      </div>

      <!-- Service Metadata Summary Grid -->
      <div style="background: var(--bg-surface); padding: 1.25rem 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border-card); margin-bottom: 2rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.25rem;">
        <div>
          <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Booking Reference</div>
          <div style="font-weight: 700; color: var(--accent-orange); font-family: monospace; font-size: 1rem; margin-top: 0.15rem;">
            <?php echo htmlspecialchars($booking['booking_code']); ?>
          </div>
        </div>

        <div>
          <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Serviced Vehicle</div>
          <div style="font-weight: 600; color: #ffffff; margin-top: 0.15rem;">
            <?php echo htmlspecialchars($booking['brand'] . ' ' . $booking['model']); ?>
          </div>
          <div style="font-size: 0.75rem; color: var(--text-muted); font-family: monospace;">
            <?php echo htmlspecialchars($booking['registration_number']); ?>
          </div>
        </div>

        <div>
          <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Service Performed</div>
          <div style="font-weight: 600; color: #ffffff; margin-top: 0.15rem;">
            <?php echo htmlspecialchars($booking['service_name']); ?>
          </div>
          <div style="font-size: 0.75rem; color: var(--text-muted);">
            Completed: <?php echo formatDisplayDate($booking['preferred_date']); ?>
          </div>
        </div>

        <div>
          <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Lead Technician</div>
          <div style="font-weight: 600; color: #60a5fa; margin-top: 0.15rem;">
            🔧 <?php echo htmlspecialchars($booking['mechanic_name'] ?? 'Senior Mechanic'); ?>
          </div>
          <?php if (!empty($booking['invoice_number'])): ?>
            <div style="font-size: 0.75rem; color: var(--text-muted); font-family: monospace;">
              Inv: <?php echo htmlspecialchars($booking['invoice_number']); ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <?php if (!empty($existingFb)): ?>
        <!-- Already Reviewed Card -->
        <div style="background: rgba(16,185,129,0.08); border: 1px solid rgba(16,185,129,0.25); border-radius: var(--radius-md); padding: 1.75rem; text-align: left;">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.5rem;">
            <div style="font-weight: 700; color: #ffffff; font-size: 1.1rem; display: flex; align-items: center; gap: 0.5rem;">
              <span>✓ Your Submitted Review</span>
            </div>
            <?php
              $mstat = $existingFb['moderation_status'];
              $mbadgeBg = 'rgba(251,191,36,0.15)'; $mbadgeCol = '#fbbf24';
              if ($mstat === 'Approved') { $mbadgeBg = 'rgba(16,185,129,0.15)'; $mbadgeCol = '#10b981'; }
              elseif ($mstat === 'Rejected') { $mbadgeBg = 'rgba(239,68,68,0.15)'; $mbadgeCol = '#f87171'; }
            ?>
            <span class="badge" style="background: <?php echo $mbadgeBg; ?>; color: <?php echo $mbadgeCol; ?>; border: 1px solid <?php echo $mbadgeCol; ?>; font-size: 0.75rem; padding: 0.25rem 0.65rem;">
              Status: <?php echo htmlspecialchars($mstat); ?>
            </span>
          </div>

          <!-- Star Rating Display -->
          <div style="color: #fbbf24; font-size: 1.75rem; letter-spacing: 3px; margin-bottom: 0.75rem;">
            <?php 
              $rVal = (int)$existingFb['rating'];
              echo str_repeat('★', $rVal) . str_repeat('☆', 5 - $rVal);
            ?>
            <span style="font-size: 1rem; color: #ffffff; font-weight: 700; margin-left: 0.5rem; font-family: var(--font-heading);">
              <?php echo $rVal; ?> / 5 Stars
            </span>
          </div>

          <p style="color: var(--text-secondary); font-size: 0.95rem; line-height: 1.6; margin: 0 0 1rem 0; font-style: italic;">
            "<?php echo nl2br(htmlspecialchars($existingFb['comments'])); ?>"
          </p>

          <?php if (!empty($existingFb['admin_response'])): ?>
            <!-- Workshop Manager Response -->
            <div style="background: var(--bg-card); border-left: 4px solid var(--accent-orange); padding: 1rem 1.25rem; border-radius: var(--radius-sm); margin-top: 1rem;">
              <div style="font-size: 0.8rem; font-weight: 700; color: var(--accent-orange); text-transform: uppercase;">
                Workshop Supervisor Response:
              </div>
              <p style="color: var(--text-main); font-size: 0.9rem; margin: 0.35rem 0 0; line-height: 1.5;">
                <?php echo nl2br(htmlspecialchars($existingFb['admin_response'])); ?>
              </p>
            </div>
          <?php endif; ?>

          <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 1rem;">
            Submitted on: <?php echo formatDisplayDate($existingFb['created_at']); ?>
          </div>
        </div>

      <?php else: ?>

        <!-- Active Review Submission Form -->
        <form method="POST" action="feedback.php" id="feedbackForm" onsubmit="return validateFeedbackForm();">
          <input type="hidden" name="booking_id" value="<?php echo (int)$booking['booking_id']; ?>">

          <!-- Star Rating Interactive Component (Section 5) -->
          <div class="form-group" style="margin-bottom: 2rem;">
            <label class="form-label" style="font-size: 1rem; margin-bottom: 0.75rem; display: block;">
              Overall Workshop Rating <span class="required-dot">*</span>
            </label>

            <style>
              .star-rating-widget {
                display: inline-flex;
                flex-direction: row-reverse;
                gap: 0.5rem;
                font-size: 2.25rem;
                cursor: pointer;
              }
              .star-rating-widget input[type="radio"] {
                display: none;
              }
              .star-rating-widget label.star-btn {
                color: rgba(255,255,255,0.2);
                transition: color 0.15s ease, transform 0.15s ease;
                cursor: pointer;
                user-select: none;
              }
              .star-rating-widget label.star-btn:hover,
              .star-rating-widget label.star-btn:hover ~ label.star-btn {
                color: #f59e0b;
                transform: scale(1.1);
              }
              .star-rating-widget input[type="radio"]:checked ~ label.star-btn {
                color: #fbbf24;
              }
              .rating-label-display {
                margin-left: 1rem;
                font-size: 1rem;
                font-weight: 700;
                color: #fbbf24;
                align-self: center;
              }
            </style>

            <div style="display: flex; align-items: center; flex-wrap: wrap; gap: 1rem;">
              <div class="star-rating-widget" role="radiogroup" aria-label="Rating from 1 to 5 stars">
                <input type="radio" id="star5" name="rating" value="5" required>
                <label for="star5" class="star-btn" title="5 Stars - Outstanding">★</label>

                <input type="radio" id="star4" name="rating" value="4">
                <label for="star4" class="star-btn" title="4 Stars - Very Good">★</label>

                <input type="radio" id="star3" name="rating" value="3">
                <label for="star3" class="star-btn" title="3 Stars - Good / Average">★</label>

                <input type="radio" id="star2" name="rating" value="2">
                <label for="star2" class="star-btn" title="2 Stars - Needs Improvement">★</label>

                <input type="radio" id="star1" name="rating" value="1">
                <label for="star1" class="star-btn" title="1 Star - Poor / Unsatisfactory">★</label>
              </div>

              <span id="ratingDescription" class="rating-label-display">Select rating</span>
            </div>
            <small style="color: var(--text-muted); font-size: 0.8rem; margin-top: 0.4rem; display: block;">
              Click a star to grade your service experience from 1 (Poor) to 5 (Outstanding).
            </small>
          </div>

          <!-- Review Comments -->
          <div class="form-group" style="margin-bottom: 2rem;">
            <label for="comments" class="form-label" style="font-size: 1rem; margin-bottom: 0.5rem; display: flex; justify-content: space-between;">
              <span>Detailed Review / Experience Comments <span class="required-dot">*</span></span>
              <span id="charCount" style="font-size: 0.8rem; color: var(--text-muted); font-weight: normal;">0 / 1000</span>
            </label>
            <textarea 
              id="comments" 
              name="comments" 
              rows="5" 
              class="form-control" 
              placeholder="Tell us about the vehicle performance, mechanic communication, timeliness, and billing transparency..."
              required
              minlength="5"
              maxlength="1000"
              style="resize: vertical; line-height: 1.5;"
              oninput="document.getElementById('charCount').textContent = this.value.length + ' / 1000';"
            ><?php echo htmlspecialchars($_POST['comments'] ?? ''); ?></textarea>
            <small style="color: var(--text-muted); font-size: 0.8rem; margin-top: 0.4rem; display: block;">
              Minimum 5 characters. Maximum 1000 characters. Please avoid personal sensitive data.
            </small>
          </div>

          <!-- Submit Buttons -->
          <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
            <button type="submit" class="btn btn-primary" id="submitFeedbackBtn">
              Submit Feedback
            </button>
            <a href="booking-details.php?id=<?php echo $bookingId; ?>" class="btn btn-secondary">
              Cancel
            </a>
          </div>
        </form>

        <script>
          const ratingLabels = {
            '5': '★★★★★ 5 Stars - Outstanding Experience',
            '4': '★★★★☆ 4 Stars - Very Good Service',
            '3': '★★★☆☆ 3 Stars - Average / Satisfactory',
            '2': '★★☆☆☆ 2 Stars - Needs Improvement',
            '1': '★☆☆☆☆ 1 Star - Unsatisfactory'
          };

          document.querySelectorAll('.star-rating-widget input[type="radio"]').forEach(radio => {
            radio.addEventListener('change', function() {
              const label = ratingLabels[this.value] || '';
              document.getElementById('ratingDescription').textContent = label;
            });
          });

          function validateFeedbackForm() {
            const checkedRating = document.querySelector('input[name="rating"]:checked');
            if (!checkedRating) {
              alert('Please select a star rating between 1 and 5.');
              return false;
            }
            const comments = document.getElementById('comments').value.trim();
            if (comments.length < 5) {
              alert('Please enter at least 5 characters for your review.');
              document.getElementById('comments').focus();
              return false;
            }
            return true;
          }
        </script>

      <?php endif; ?>

    </div>
  <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
