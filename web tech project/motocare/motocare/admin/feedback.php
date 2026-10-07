<?php
/**
 * ============================================================================
 * MotoCare – Two-Wheeler Service & Mechanic Shop Management System
 * File: admin/feedback.php
 * Stage 2: Admin Feedback Moderation & Quality Insights Control Center
 * ============================================================================
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/feedback-helper.php';

requireAdminLogin();
$admin = getCurrentAdmin();
$adminId = (int)$admin['id'];

$pageTitle = 'Feedback Moderation – Admin Control – MotoCare';
$currentPage = 'feedback.php';

$successMsg = trim($_GET['msg'] ?? '');
$errorMsg   = trim($_GET['err'] ?? '');

// Filter & Search parameters
$statusFilter = trim($_GET['status'] ?? 'All');
$ratingFilter = isset($_GET['rating']) ? (int)$_GET['rating'] : 0;
$search       = trim($_GET['q'] ?? '');

// Handle Quick Moderation Action from query or POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $fbId = (int)($_POST['feedback_id'] ?? 0);
    $action = trim($_POST['action'] ?? '');
    $respText = trim($_POST['admin_response'] ?? '');

    if ($action === 'approve') {
        $res = moderateFeedback($pdo, $fbId, $adminId, 'Approved', $respText ?: null);
        if ($res['success']) {
            header("Location: feedback.php?msg=" . urlencode("Review #{$fbId} successfully approved!"));
            exit;
        } else {
            $errorMsg = $res['error'];
        }
    } elseif ($action === 'reject') {
        $res = moderateFeedback($pdo, $fbId, $adminId, 'Rejected', $respText ?: null);
        if ($res['success']) {
            header("Location: feedback.php?msg=" . urlencode("Review #{$fbId} marked as rejected."));
            exit;
        } else {
            $errorMsg = $res['error'];
        }
    }
}

// Fetch feedback stats
$stats = getFeedbackStats($pdo);

// Query filtered feedback list
$feedbackList = [];
if ($pdo) {
    try {
        $where = [];
        $params = [];

        // Moderation status filter
        if (in_array($statusFilter, ['Pending', 'Approved', 'Rejected'])) {
            $where[] = "f.moderation_status = :mstat";
            $params[':mstat'] = $statusFilter;
        }

        // Star rating filter
        if ($ratingFilter >= 1 && $ratingFilter <= 5) {
            $where[] = "f.rating = :rating";
            $params[':rating'] = $ratingFilter;
        }

        // Search query
        if (!empty($search)) {
            $where[] = "(c.full_name LIKE :q OR b.booking_code LIKE :q OR v.registration_number LIKE :q OR f.comments LIKE :q)";
            $params[':q'] = "%{$search}%";
        }

        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        $query = "
            SELECT 
                f.*,
                c.full_name AS customer_name,
                c.email AS customer_email,
                c.phone AS customer_phone,
                b.booking_code,
                v.brand, v.model, v.registration_number,
                s.service_name,
                m.full_name AS mechanic_name,
                adm.username AS moderator_name
            FROM feedback f
            JOIN customers c ON f.customer_id = c.id
            JOIN bookings b ON f.booking_id = b.id
            JOIN vehicles v ON b.vehicle_id = v.id
            JOIN services s ON b.service_id = s.id
            LEFT JOIN mechanics m ON b.mechanic_id = m.id
            LEFT JOIN admin_users adm ON f.moderator_id = adm.id
            {$whereClause}
            ORDER BY 
                CASE WHEN f.moderation_status = 'Pending' THEN 1 ELSE 2 END,
                f.created_at DESC
        ";
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $feedbackList = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $errorMsg = "Database error: " . $e->getMessage();
    }
}

include __DIR__ . '/../includes/admin-header.php';
?>

<div class="container" style="padding-top: 1rem; padding-bottom: 4rem;">

  <!-- Header Banner -->
  <div style="background: linear-gradient(135deg, var(--bg-card), var(--bg-surface)); border: 1px solid var(--border-card); border-radius: var(--radius-xl); padding: 2rem 2.25rem; margin-bottom: 2rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1.5rem;">
    <div>
      <span class="service-tag" style="background: rgba(251,191,36,0.15); color: #fbbf24; border-color: rgba(251,191,36,0.3); margin-bottom: 0.5rem; display: inline-block;">
        Quality Assurance &amp; Voice of Customer
      </span>
      <h1 style="font-size: 2rem; margin-bottom: 0.25rem; color: #ffffff; font-family: var(--font-heading);">
        Customer Reviews &amp; Moderation
      </h1>
      <p style="color: var(--text-muted); font-size: 0.95rem; margin: 0;">
        Monitor customer ratings, approve verified testimonials, and respond to workshop service feedback.
      </p>
    </div>

    <!-- Average Rating Pill -->
    <div style="background: var(--bg-surface); padding: 1rem 1.5rem; border-radius: var(--radius-lg); border: 1px solid var(--border-subtle); display: flex; align-items: center; gap: 1.25rem;">
      <div style="font-size: 2.5rem; font-weight: 800; color: #fbbf24; font-family: var(--font-heading); line-height: 1;">
        ★ <?php echo $stats['average_rating'] > 0 ? number_format($stats['average_rating'], 1) : '0.0'; ?>
      </div>
      <div>
        <div style="font-size: 0.9rem; font-weight: 700; color: #ffffff;">Overall Workshop Rating</div>
        <div style="font-size: 0.8rem; color: var(--text-muted);">
          <?php echo $stats['approved_reviews']; ?> approved &bull; <?php echo $stats['total_reviews']; ?> total review(s)
        </div>
      </div>
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

  <!-- Summary KPI Metrics Cards (Section 8 & 12) -->
  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1.25rem; margin-bottom: 2rem;">
    <!-- Total Reviews -->
    <a href="feedback.php" style="text-decoration: none; color: inherit;">
      <div style="background: var(--bg-card); padding: 1.25rem 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); border-top: 3px solid #38bdf8;">
        <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Total Reviews</div>
        <div style="font-size: 1.85rem; font-weight: 800; color: #ffffff; font-family: var(--font-heading); margin-top: 0.25rem;">
          <?php echo $stats['total_reviews']; ?>
        </div>
      </div>
    </a>

    <!-- Pending Moderation -->
    <a href="feedback.php?status=Pending" style="text-decoration: none; color: inherit;">
      <div style="background: var(--bg-card); padding: 1.25rem 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); border-top: 3px solid #fbbf24;">
        <div style="font-size: 0.75rem; color: #fbbf24; text-transform: uppercase; font-weight: 600;">Pending Reviews</div>
        <div style="font-size: 1.85rem; font-weight: 800; color: #fbbf24; font-family: var(--font-heading); margin-top: 0.25rem;">
          <?php echo $stats['pending_reviews']; ?>
        </div>
      </div>
    </a>

    <!-- Approved Reviews -->
    <a href="feedback.php?status=Approved" style="text-decoration: none; color: inherit;">
      <div style="background: var(--bg-card); padding: 1.25rem 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); border-top: 3px solid #10b981;">
        <div style="font-size: 0.75rem; color: #10b981; text-transform: uppercase; font-weight: 600;">Approved (Public)</div>
        <div style="font-size: 1.85rem; font-weight: 800; color: #10b981; font-family: var(--font-heading); margin-top: 0.25rem;">
          <?php echo $stats['approved_reviews']; ?>
        </div>
      </div>
    </a>

    <!-- Rejected Reviews -->
    <a href="feedback.php?status=Rejected" style="text-decoration: none; color: inherit;">
      <div style="background: var(--bg-card); padding: 1.25rem 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); border-top: 3px solid #ef4444;">
        <div style="font-size: 0.75rem; color: #ef4444; text-transform: uppercase; font-weight: 600;">Rejected Reviews</div>
        <div style="font-size: 1.85rem; font-weight: 800; color: #ef4444; font-family: var(--font-heading); margin-top: 0.25rem;">
          <?php echo $stats['rejected_reviews']; ?>
        </div>
      </div>
    </a>
  </div>

  <!-- Search & Filter Controls (Section 8) -->
  <div class="form-card" style="padding: 1.5rem 2rem; margin-bottom: 2rem;">
    <form method="GET" action="feedback.php" style="display: flex; flex-direction: column; gap: 1rem;">
      <div style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 1rem; align-items: end;">
        
        <!-- Search Query -->
        <div>
          <label for="q" class="form-label" style="font-size: 0.8rem;">Search Reviews</label>
          <input 
            type="text" 
            id="q" 
            name="q" 
            class="form-control" 
            placeholder="Search customer, booking code, vehicle reg, comments..."
            value="<?php echo htmlspecialchars($search); ?>"
          >
        </div>

        <!-- Status Filter -->
        <div>
          <label for="status" class="form-label" style="font-size: 0.8rem;">Moderation Status</label>
          <select id="status" name="status" class="form-select">
            <option value="All" <?php echo ($statusFilter === 'All') ? 'selected' : ''; ?>>All Statuses</option>
            <option value="Pending" <?php echo ($statusFilter === 'Pending') ? 'selected' : ''; ?>>Pending (<?php echo $stats['pending_reviews']; ?>)</option>
            <option value="Approved" <?php echo ($statusFilter === 'Approved') ? 'selected' : ''; ?>>Approved (<?php echo $stats['approved_reviews']; ?>)</option>
            <option value="Rejected" <?php echo ($statusFilter === 'Rejected') ? 'selected' : ''; ?>>Rejected (<?php echo $stats['rejected_reviews']; ?>)</option>
          </select>
        </div>

        <!-- Rating Filter -->
        <div>
          <label for="rating" class="form-label" style="font-size: 0.8rem;">Star Rating</label>
          <select id="rating" name="rating" class="form-select">
            <option value="0" <?php echo ($ratingFilter === 0) ? 'selected' : ''; ?>>All Stars</option>
            <option value="5" <?php echo ($ratingFilter === 5) ? 'selected' : ''; ?>>★★★★★ (5 Stars)</option>
            <option value="4" <?php echo ($ratingFilter === 4) ? 'selected' : ''; ?>>★★★★☆ (4 Stars)</option>
            <option value="3" <?php echo ($ratingFilter === 3) ? 'selected' : ''; ?>>★★★☆☆ (3 Stars)</option>
            <option value="2" <?php echo ($ratingFilter === 2) ? 'selected' : ''; ?>>★★☆☆☆ (2 Stars)</option>
            <option value="1" <?php echo ($ratingFilter === 1) ? 'selected' : ''; ?>>★☆☆☆☆ (1 Star)</option>
          </select>
        </div>

        <!-- Submit & Reset -->
        <div style="display: flex; gap: 0.5rem;">
          <button type="submit" class="btn btn-primary" style="padding: 0.65rem 1.25rem;">
            Filter
          </button>
          <a href="feedback.php" class="btn btn-secondary" style="padding: 0.65rem 1rem;">
            Reset
          </a>
        </div>
      </div>
    </form>
  </div>

  <!-- Reviews Moderation Table (Section 8) -->
  <div class="dashboard-card" style="padding: 0; overflow: hidden; background: var(--bg-card); border-radius: var(--radius-lg); border: 1px solid var(--border-subtle);">
    <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border-subtle); display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02);">
      <h3 style="font-size: 1.1rem; color: #ffffff; margin: 0;">
        Customer Reviews Ledger (<?php echo count($feedbackList); ?>)
      </h3>
      <span class="badge" style="background: rgba(255,107,0,0.15); color: var(--accent-orange); border: 1px solid rgba(255,107,0,0.3);">
        Moderation Queue
      </span>
    </div>

    <?php if (empty($feedbackList)): ?>
      <div style="padding: 3.5rem 2rem; text-align: center; color: var(--text-muted);">
        <div style="font-size: 2.5rem; margin-bottom: 0.75rem;">★</div>
        <p style="font-size: 1rem; color: #ffffff; margin-bottom: 0.25rem;">No customer reviews match your active filters.</p>
        <p style="font-size: 0.85rem; margin: 0;">Try clearing filters or search criteria.</p>
      </div>
    <?php else: ?>
      <div style="overflow-x: auto;">
        <table class="data-table" style="width: 100%; border-collapse: collapse; text-align: left;">
          <thead>
            <tr style="border-bottom: 1px solid var(--border-subtle); background: var(--bg-surface); font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em;">
              <th style="padding: 1rem;">ID</th>
              <th style="padding: 1rem;">Booking Code</th>
              <th style="padding: 1rem;">Customer</th>
              <th style="padding: 1rem;">Vehicle</th>
              <th style="padding: 1rem;">Service</th>
              <th style="padding: 1rem;">Rating</th>
              <th style="padding: 1rem; min-width: 280px;">Review / Feedback</th>
              <th style="padding: 1rem;">Status</th>
              <th style="padding: 1rem;">Date</th>
              <th style="padding: 1rem; text-align: right;">Actions</th>
            </tr>
          </thead>
          <tbody style="font-size: 0.9rem;">
            <?php foreach ($feedbackList as $fb): 
              $mstat = $fb['moderation_status'];
              $mCol = '#fbbf24'; $mBg = 'rgba(251,191,36,0.15)';
              if ($mstat === 'Approved') { $mCol = '#10b981'; $mBg = 'rgba(16,185,129,0.15)'; }
              elseif ($mstat === 'Rejected') { $mCol = '#f87171'; $mBg = 'rgba(239,68,68,0.15)'; }
              $rNum = (int)$fb['rating'];
            ?>
              <tr style="border-bottom: 1px solid var(--border-subtle);">
                
                <!-- ID -->
                <td style="padding: 1rem; font-weight: 700; color: var(--text-muted);">
                  #<?php echo (int)$fb['id']; ?>
                </td>

                <!-- Booking Code -->
                <td style="padding: 1rem; font-weight: 700; font-family: monospace; color: var(--accent-orange);">
                  <a href="booking-details.php?id=<?php echo (int)$fb['booking_id']; ?>" style="color: inherit; text-decoration: none;">
                    <?php echo htmlspecialchars($fb['booking_code']); ?>
                  </a>
                </td>

                <!-- Customer -->
                <td style="padding: 1rem;">
                  <div style="font-weight: 600; color: #ffffff;"><?php echo htmlspecialchars($fb['customer_name']); ?></div>
                  <div style="font-size: 0.75rem; color: var(--text-muted);"><?php echo htmlspecialchars($fb['customer_phone']); ?></div>
                </td>

                <!-- Vehicle -->
                <td style="padding: 1rem;">
                  <div style="color: #ffffff;"><?php echo htmlspecialchars($fb['brand'] . ' ' . $fb['model']); ?></div>
                  <div style="font-family: monospace; font-size: 0.75rem; color: var(--accent-orange);"><?php echo htmlspecialchars($fb['registration_number']); ?></div>
                </td>

                <!-- Service -->
                <td style="padding: 1rem;">
                  <div style="color: #ffffff;"><?php echo htmlspecialchars($fb['service_name']); ?></div>
                  <?php if (!empty($fb['mechanic_name'])): ?>
                    <div style="font-size: 0.75rem; color: #60a5fa;">🔧 <?php echo htmlspecialchars($fb['mechanic_name']); ?></div>
                  <?php endif; ?>
                </td>

                <!-- Rating -->
                <td style="padding: 1rem;">
                  <div style="color: #fbbf24; font-size: 1.1rem; letter-spacing: 1px; white-space: nowrap;">
                    <?php echo str_repeat('★', $rNum) . str_repeat('☆', 5 - $rNum); ?>
                  </div>
                  <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700;">
                    <?php echo $rNum; ?> / 5 Stars
                  </div>
                </td>

                <!-- Review / Comments -->
                <td style="padding: 1rem;">
                  <p style="margin: 0; color: var(--text-secondary); line-height: 1.4; font-style: italic;">
                    "<?php echo htmlspecialchars($fb['comments']); ?>"
                  </p>
                  <?php if (!empty($fb['admin_response'])): ?>
                    <div style="font-size: 0.75rem; color: var(--accent-orange); margin-top: 0.35rem;">
                      ↳ Resp: <?php echo htmlspecialchars(mb_strimwidth($fb['admin_response'], 0, 60, '...')); ?>
                    </div>
                  <?php endif; ?>
                </td>

                <!-- Status -->
                <td style="padding: 1rem; white-space: nowrap;">
                  <span class="badge" style="background: <?php echo $mBg; ?>; color: <?php echo $mCol; ?>; border: 1px solid <?php echo $mCol; ?>; font-size: 0.75rem; padding: 0.25rem 0.55rem;">
                    <?php echo htmlspecialchars($mstat); ?>
                  </span>
                </td>

                <!-- Date -->
                <td style="padding: 1rem; color: var(--text-muted); font-size: 0.8rem; white-space: nowrap;">
                  <?php echo formatDisplayDate($fb['created_at']); ?>
                </td>

                <!-- Actions -->
                <td style="padding: 1rem; text-align: right; white-space: nowrap;">
                  <div style="display: flex; gap: 0.35rem; justify-content: flex-end;">
                    <a href="feedback-details.php?id=<?php echo (int)$fb['id']; ?>" class="btn btn-outline btn-sm" style="padding: 0.3rem 0.65rem; font-size: 0.8rem;">
                      Inspect →
                    </a>
                    <?php if ($mstat !== 'Approved'): ?>
                      <form method="POST" action="feedback.php" style="display: inline;" onsubmit="return confirm('Approve review #<?php echo (int)$fb['id']; ?> for public display?');">
                        <input type="hidden" name="feedback_id" value="<?php echo (int)$fb['id']; ?>">
                        <input type="hidden" name="action" value="approve">
                        <button type="submit" class="btn btn-secondary btn-sm" style="padding: 0.3rem 0.65rem; font-size: 0.8rem; color: #10b981; border-color: #10b981;">
                          ✓
                        </button>
                      </form>
                    <?php endif; ?>
                    <?php if ($mstat !== 'Rejected'): ?>
                      <form method="POST" action="feedback.php" style="display: inline;" onsubmit="return confirm('Reject review #<?php echo (int)$fb['id']; ?>?');">
                        <input type="hidden" name="feedback_id" value="<?php echo (int)$fb['id']; ?>">
                        <input type="hidden" name="action" value="reject">
                        <button type="submit" class="btn btn-secondary btn-sm" style="padding: 0.3rem 0.65rem; font-size: 0.8rem; color: #f87171; border-color: #f87171;">
                          ✕
                        </button>
                      </form>
                    <?php endif; ?>
                  </div>
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
