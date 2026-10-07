<?php
/**
 * ============================================================================
 * MotoCare – Two-Wheeler Service & Mechanic Shop Management System
 * File: customer/dashboard.php
 * Stage 2: Dynamic Customer Dashboard (MySQL Live Metrics & Quick Actions)
 * ============================================================================
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

requireCustomerLogin();
$customer = getCurrentCustomer();
$customerId = (int)$customer['id'];

$pageTitle = 'Customer Dashboard – MotoCare';
$currentPage = 'dashboard.php';

// Default metrics
$metrics = [
    'total_vehicles'     => 0,
    'active_bookings'    => 0,
    'completed_services' => 0,
    'pending_bookings'   => 0
];

$latestBooking = null;
$recentBookings = [];

if ($pdo) {
    try {
        // 1. Total Vehicles
        $stmtVeh = $pdo->prepare("SELECT COUNT(*) FROM vehicles WHERE customer_id = :cid");
        $stmtVeh->execute([':cid' => $customerId]);
        $metrics['total_vehicles'] = (int)$stmtVeh->fetchColumn();

        // 2. Active Bookings (In progress, confirmed, inspection, etc.)
        $stmtAct = $pdo->prepare("
            SELECT COUNT(*) FROM bookings 
            WHERE customer_id = :cid 
              AND status IN ('Confirmed', 'Vehicle Received', 'Inspection', 'Service In Progress', 'Quality Check', 'Ready for Delivery')
        ");
        $stmtAct->execute([':cid' => $customerId]);
        $metrics['active_bookings'] = (int)$stmtAct->fetchColumn();

        // 3. Completed Services
        $stmtComp = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE customer_id = :cid AND status = 'Completed'");
        $stmtComp->execute([':cid' => $customerId]);
        $metrics['completed_services'] = (int)$stmtComp->fetchColumn();

        // 4. Pending Bookings
        $stmtPend = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE customer_id = :cid AND status = 'Pending'");
        $stmtPend->execute([':cid' => $customerId]);
        $metrics['pending_bookings'] = (int)$stmtPend->fetchColumn();

        // 5. Customer Invoicing & Billing Metrics (Stage 2 Section 16)
        $billingMetrics = [
            'total_billed'        => 0.00,
            'total_paid'          => 0.00,
            'outstanding_balance' => 0.00,
            'recent_invoice'      => null
        ];

        $stmtBill = $pdo->prepare("
            SELECT 
                COALESCE(SUM(total_amount), 0.00) AS total_billed,
                COALESCE(SUM(amount_paid), 0.00) AS total_paid,
                COALESCE(SUM(balance_due), 0.00) AS outstanding_balance
            FROM bills 
            WHERE customer_id = :cid
        ");
        $stmtBill->execute([':cid' => $customerId]);
        $bm = $stmtBill->fetch(PDO::FETCH_ASSOC);
        if ($bm) {
            $billingMetrics['total_billed']        = (float)$bm['total_billed'];
            $billingMetrics['total_paid']          = (float)$bm['total_paid'];
            $billingMetrics['outstanding_balance'] = (float)$bm['outstanding_balance'];
        }

        $stmtRecInv = $pdo->prepare("
            SELECT id, invoice_number, total_amount, payment_status, bill_date, balance_due 
            FROM bills 
            WHERE customer_id = :cid 
            ORDER BY id DESC 
            LIMIT 1
        ");
        $stmtRecInv->execute([':cid' => $customerId]);
        $billingMetrics['recent_invoice'] = $stmtRecInv->fetch(PDO::FETCH_ASSOC);

        // 6. Fetch most recent ongoing / active booking for status tracking
        $stmtRecent = $pdo->prepare("
            SELECT 
                b.*,
                v.brand, v.model, v.registration_number, v.vehicle_type,
                s.service_name, s.price AS service_price,
                m.full_name AS mechanic_name
            FROM bookings b
            JOIN vehicles v ON b.vehicle_id = v.id
            JOIN services s ON b.service_id = s.id
            LEFT JOIN mechanics m ON b.mechanic_id = m.id
            WHERE b.customer_id = :cid
            ORDER BY 
                CASE 
                    WHEN b.status IN ('Service In Progress', 'Inspection', 'Vehicle Received', 'Quality Check', 'Confirmed', 'Pending') THEN 1 
                    ELSE 2 
                END,
                b.created_at DESC
            LIMIT 1
        ");
        $stmtRecent->execute([':cid' => $customerId]);
        $latestBooking = $stmtRecent->fetch();

        // 7. Completed booking pending customer feedback (Stage 2 Section 6)
        $pendingReviewBooking = null;
        $stmtPendingRev = $pdo->prepare("
            SELECT b.id, b.booking_code, b.preferred_date, s.service_name, v.brand, v.model, v.registration_number
            FROM bookings b
            JOIN services s ON b.service_id = s.id
            JOIN vehicles v ON b.vehicle_id = v.id
            LEFT JOIN feedback f ON b.id = f.booking_id
            WHERE b.customer_id = :cid 
              AND b.status = 'Completed' 
              AND f.id IS NULL
            ORDER BY b.id DESC
            LIMIT 1
        ");
        $stmtPendingRev->execute([':cid' => $customerId]);
        $pendingReviewBooking = $stmtPendingRev->fetch(PDO::FETCH_ASSOC);

        // Count customer's submitted reviews
        $stmtRevCnt = $pdo->prepare("SELECT COUNT(*) FROM feedback WHERE customer_id = :cid");
        $stmtRevCnt->execute([':cid' => $customerId]);
        $totalCustomerReviews = (int)$stmtRevCnt->fetchColumn();

    } catch (PDOException $e) {
        // Log silently or fallback
    }
} else {
    // Offline demo fallback
    $metrics = [
        'total_vehicles'     => 2,
        'active_bookings'    => 1,
        'completed_services' => 3,
        'pending_bookings'   => 1
    ];
    $billingMetrics = [
        'total_billed'        => 590.00,
        'total_paid'          => 590.00,
        'outstanding_balance' => 0.00,
        'recent_invoice'      => [
            'id' => 1,
            'invoice_number' => 'MC-INV-2026-1001',
            'total_amount' => 590.00,
            'payment_status' => 'Paid',
            'balance_due' => 0.00
        ]
    ];
}

require_once __DIR__ . '/../includes/customer-header.php';
?>

<div class="container" style="padding-top: 1rem; padding-bottom: 3rem;">
  
  <!-- Welcome Strip -->
  <div style="background: linear-gradient(135deg, var(--bg-card), var(--bg-surface)); border: 1px solid var(--border-card); border-radius: var(--radius-xl); padding: 2.25rem 2rem; margin-bottom: 2.5rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1.5rem;">
    <div>
      <span class="service-tag" style="background: var(--accent-glow-subtle); color: var(--accent-orange); margin-bottom: 0.5rem; display: inline-block;">
        Customer Control Center
      </span>
      <h1 style="font-size: 2rem; margin-bottom: 0.25rem; color: #ffffff; font-family: var(--font-heading);">
        Welcome back, <?php echo htmlspecialchars($customer['name']); ?>!
      </h1>
      <p style="color: var(--text-muted); font-size: 0.95rem; margin: 0;">
        Account: <strong style="color: var(--text-secondary);"><?php echo htmlspecialchars($customer['email']); ?></strong> &bull;
        Phone: <strong style="color: var(--text-secondary);"><?php echo htmlspecialchars($customer['phone']); ?></strong>
      </p>
    </div>
    <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
      <a href="book-service.php" class="btn btn-primary btn-lg">
        <span>+ Book a Service</span>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"></path><path d="m12 5 7 7-7 7"></path></svg>
      </a>
      <a href="bills.php" class="btn btn-outline btn-lg">
        <span>💳 My Invoices</span>
      </a>
      <a href="feedback-history.php" class="btn btn-secondary btn-lg" style="border-color: #fbbf24; color: #fbbf24;">
        <span>★ My Reviews (<?php echo $totalCustomerReviews ?? 0; ?>)</span>
      </a>
    </div>
  </div>

  <?php if (!empty($pendingReviewBooking)): ?>
    <!-- Stage 2 Section 6: Rate Your Recent Service / Feedback Pending Banner -->
    <div style="background: rgba(251,191,36,0.1); border: 1px solid rgba(251,191,36,0.35); border-radius: var(--radius-lg); padding: 1.35rem 1.75rem; margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
      <div style="display: flex; align-items: center; gap: 1rem;">
        <span style="font-size: 2rem; color: #fbbf24;">★</span>
        <div>
          <h4 style="color: #fbbf24; font-size: 1.1rem; margin: 0 0 0.25rem 0; font-family: var(--font-heading);">
            Rate Your Recent Service &mdash; Feedback Pending
          </h4>
          <p style="color: var(--text-muted); font-size: 0.9rem; margin: 0;">
            Your service <strong style="color: #ffffff;"><?php echo htmlspecialchars($pendingReviewBooking['booking_code']); ?></strong> 
            (<?php echo htmlspecialchars($pendingReviewBooking['service_name']); ?> for <?php echo htmlspecialchars($pendingReviewBooking['brand'] . ' ' . $pendingReviewBooking['model']); ?>) 
            is completed. Tell us how our mechanics performed!
          </p>
        </div>
      </div>
      <a href="feedback.php?booking_id=<?php echo (int)$pendingReviewBooking['id']; ?>" class="btn btn-primary" style="white-space: nowrap;">
        ★ Rate Service Now →
      </a>
    </div>
  <?php endif; ?>

  <!-- Dynamic Summary Metric Counters (Section 10 Requirements) -->
  <div class="stats-grid" style="margin-bottom: 3rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem;">
    
    <!-- 1. Total Vehicles -->
    <a href="vehicles.php" style="text-decoration: none; color: inherit;">
      <div class="stat-item" style="background: var(--bg-card); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); border-left: 4px solid var(--accent-orange); transition: transform 0.2s ease;">
        <span class="stat-number" style="color: var(--accent-orange); font-size: 2.25rem; font-weight: 800; font-family: var(--font-heading);">
          <?php echo $metrics['total_vehicles']; ?>
        </span>
        <span class="stat-label" style="display: block; font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-top: 0.25rem;">
          Total Vehicles
        </span>
      </div>
    </a>

    <!-- 2. Active Bookings -->
    <a href="bookings.php" style="text-decoration: none; color: inherit;">
      <div class="stat-item" style="background: var(--bg-card); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); border-left: 4px solid #38bdf8; transition: transform 0.2s ease;">
        <span class="stat-number" style="color: #38bdf8; font-size: 2.25rem; font-weight: 800; font-family: var(--font-heading);">
          <?php echo $metrics['active_bookings']; ?>
        </span>
        <span class="stat-label" style="display: block; font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-top: 0.25rem;">
          Active Bookings
        </span>
      </div>
    </a>

    <!-- 3. Completed Services -->
    <a href="service-history.php" style="text-decoration: none; color: inherit;">
      <div class="stat-item" style="background: var(--bg-card); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); border-left: 4px solid var(--color-green); transition: transform 0.2s ease;">
        <span class="stat-number" style="color: var(--color-green); font-size: 2.25rem; font-weight: 800; font-family: var(--font-heading);">
          <?php echo $metrics['completed_services']; ?>
        </span>
        <span class="stat-label" style="display: block; font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-top: 0.25rem;">
          Completed Services
        </span>
      </div>
    </a>

    <!-- 4. Pending Bookings -->
    <a href="bookings.php" style="text-decoration: none; color: inherit;">
      <div class="stat-item" style="background: var(--bg-card); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); border-left: 4px solid #fbbf24; transition: transform 0.2s ease;">
        <span class="stat-number" style="color: #fbbf24; font-size: 2.25rem; font-weight: 800; font-family: var(--font-heading);">
          <?php echo $metrics['pending_bookings']; ?>
        </span>
        <span class="stat-label" style="display: block; font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-top: 0.25rem;">
          Pending Bookings
        </span>
      </div>
    </a>

  </div>

  <!-- Customer Billing Overview Banner (Stage 2 Section 16 Requirement) -->
  <div style="background: var(--bg-card); border: 1px solid var(--border-card); border-radius: var(--radius-lg); padding: 1.5rem 2rem; margin-bottom: 2.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1.5rem;">
    <div style="display: flex; align-items: center; gap: 1.25rem;">
      <div style="width: 48px; height: 48px; border-radius: 50%; background: rgba(56,189,248,0.15); display: flex; align-items: center; justify-content: center; font-size: 1.35rem; border: 1px solid rgba(56,189,248,0.3);">
        💳
      </div>
      <div>
        <h4 style="font-size: 1.1rem; color: #ffffff; margin: 0 0 0.25rem 0;">
          Service Billing &amp; Invoices
        </h4>
        <div style="font-size: 0.85rem; color: var(--text-muted); display: flex; gap: 1rem; flex-wrap: wrap;">
          <span>Total Invoiced: <strong style="color: #ffffff;"><?php echo formatCurrency($billingMetrics['total_billed']); ?></strong></span>
          <span>&bull;</span>
          <span>Settled: <strong style="color: var(--color-green);"><?php echo formatCurrency($billingMetrics['total_paid']); ?></strong></span>
          <?php if ($billingMetrics['outstanding_balance'] > 0): ?>
            <span>&bull;</span>
            <span>Due: <strong style="color: #f87171;"><?php echo formatCurrency($billingMetrics['outstanding_balance']); ?></strong></span>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div style="display: flex; gap: 0.75rem; align-items: center;">
      <?php if (!empty($billingMetrics['recent_invoice'])): ?>
        <a href="bill-details.php?id=<?php echo (int)$billingMetrics['recent_invoice']['id']; ?>" class="btn btn-outline btn-sm">
          Recent Invoice #<?php echo htmlspecialchars($billingMetrics['recent_invoice']['invoice_number']); ?> ↗
        </a>
      <?php endif; ?>
      <a href="bills.php" class="btn btn-secondary btn-sm">
        My Invoices Ledger →
      </a>
    </div>
  </div>

  <!-- Main Grid: Active Service Progress & Quick Action Cards -->
  <div style="display: grid; grid-template-columns: 1.35fr 0.65fr; gap: 2rem;">
    
    <!-- Left Column: Active Service Status Card -->
    <div class="form-card" style="padding: 2rem;">
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-subtle); padding-bottom: 1rem;">
        <h3 style="font-size: 1.3rem; margin: 0; color: #ffffff;">Latest Service Status</h3>
        <a href="bookings.php" style="font-size: 0.85rem; color: var(--accent-orange); font-weight: 600; text-decoration: none;">
          View All Bookings &rarr;
        </a>
      </div>

      <?php if ($latestBooking): ?>
        <div style="border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 1.5rem; background: var(--bg-surface); margin-bottom: 1rem;">
          
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
            <a href="booking-details.php?code=<?php echo urlencode($latestBooking['booking_code']); ?>" style="font-family: var(--font-heading); font-weight: 700; color: var(--accent-orange); font-size: 1.2rem; text-decoration: none;">
              <?php echo htmlspecialchars($latestBooking['booking_code']); ?>
            </a>
            
            <?php 
              $st = $latestBooking['status'];
              $bColor = '#fbbf24';
              $bBg = 'rgba(250,204,21,0.15)';
              if ($st === 'Confirmed') { $bColor = '#38bdf8'; $bBg = 'rgba(56,189,248,0.15)'; }
              elseif ($st === 'Vehicle Received') { $bColor = '#fbbf24'; $bBg = 'rgba(250,204,21,0.15)'; }
              elseif ($st === 'Service In Progress' || $st === 'Inspection') { $bColor = 'var(--accent-orange)'; $bBg = 'rgba(255,94,20,0.15)'; }
              elseif ($st === 'Quality Check') { $bColor = '#a855f7'; $bBg = 'rgba(168,85,247,0.15)'; }
              elseif ($st === 'Ready for Delivery') { $bColor = '#34d399'; $bBg = 'rgba(16,185,129,0.15)'; }
              elseif ($st === 'Completed') { $bColor = 'var(--color-green)'; $bBg = 'rgba(16,185,129,0.15)'; }
              elseif ($st === 'Cancelled') { $bColor = '#f87171'; $bBg = 'rgba(239,68,68,0.15)'; }
            ?>
            <span class="service-tag" style="background: <?php echo $bBg; ?>; color: <?php echo $bColor; ?>; border-color: <?php echo $bColor; ?>; font-size: 0.8rem; padding: 0.3rem 0.7rem;">
              <?php echo htmlspecialchars($st); ?>
            </span>
          </div>

          <h4 style="font-size: 1.15rem; margin-bottom: 0.35rem; color: #ffffff;">
            <?php echo htmlspecialchars($latestBooking['brand'] . ' ' . $latestBooking['model']); ?>
            <span style="font-family: monospace; font-size: 0.85rem; color: var(--accent-orange); font-weight: 600;">(<?php echo htmlspecialchars($latestBooking['registration_number']); ?>)</span>
          </h4>

          <div style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 1.25rem;">
            Package: <strong style="color: #ffffff;"><?php echo htmlspecialchars($latestBooking['service_name']); ?></strong> &bull;
            Date: <strong style="color: var(--text-secondary);"><?php echo formatDisplayDate($latestBooking['preferred_date']); ?></strong> (<?php echo htmlspecialchars($latestBooking['preferred_time']); ?>)
          </div>

          <div style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 1.25rem; background: var(--bg-card); padding: 0.75rem 1rem; border-radius: var(--radius-sm); border: 1px solid var(--border-subtle);">
            Mechanic: 
            <?php if (!empty($latestBooking['mechanic_name'])): ?>
              <strong style="color: #60a5fa;">🔧 <?php echo htmlspecialchars($latestBooking['mechanic_name']); ?></strong>
            <?php else: ?>
              <span style="color: #fbbf24; font-style: italic;">Waiting for mechanic assignment</span>
            <?php endif; ?>
          </div>

          <div style="display: flex; gap: 0.75rem;">
            <a href="booking-details.php?code=<?php echo urlencode($latestBooking['booking_code']); ?>" class="btn btn-outline btn-sm">
              View Booking Details
            </a>
            <a href="bill.php?booking_code=<?php echo urlencode($latestBooking['booking_code']); ?>" class="btn btn-secondary btn-sm">
              View Digital Bill
            </a>
          </div>

        </div>
      <?php else: ?>
        <div style="padding: 2.5rem 1rem; text-align: center; color: var(--text-muted);">
          <p style="margin-bottom: 1.25rem;">No active services currently scheduled.</p>
          <a href="book-service.php" class="btn btn-primary btn-sm">
            Book a Workshop Slot Now →
          </a>
        </div>
      <?php endif; ?>

    </div>

    <!-- Right Column: Quick Action Cards (Section 10 Requirements) -->
    <div class="form-card" style="padding: 2rem;">
      <h3 style="font-size: 1.3rem; margin-bottom: 1.25rem; color: #ffffff; border-bottom: 1px solid var(--border-subtle); padding-bottom: 1rem;">
        Quick Action Cards
      </h3>

      <div style="display: flex; flex-direction: column; gap: 0.85rem;">
        <!-- Card 1: Book a Service -->
        <a href="book-service.php" class="btn btn-primary btn-block" style="text-align: left; padding: 0.9rem 1.25rem; display: flex; align-items: center; justify-content: space-between;">
          <span>⚡ Book a Service</span>
          <span>→</span>
        </a>

        <!-- Card 2: My Vehicles -->
        <a href="vehicles.php" class="btn btn-secondary btn-block" style="text-align: left; padding: 0.9rem 1.25rem; display: flex; align-items: center; justify-content: space-between;">
          <span>🛵 My Vehicles (<?php echo $metrics['total_vehicles']; ?>)</span>
          <span>→</span>
        </a>

        <!-- Card 3: My Bookings -->
        <a href="bookings.php" class="btn btn-secondary btn-block" style="text-align: left; padding: 0.9rem 1.25rem; display: flex; align-items: center; justify-content: space-between;">
          <span>📋 My Bookings</span>
          <span>→</span>
        </a>

        <!-- Card 4: Service History -->
        <a href="service-history.php" class="btn btn-secondary btn-block" style="text-align: left; padding: 0.9rem 1.25rem; display: flex; align-items: center; justify-content: space-between;">
          <span>📜 Service History</span>
          <span>→</span>
        </a>

        <!-- Card 5: My Invoices (Section 16 Requirement) -->
        <a href="bills.php" class="btn btn-secondary btn-block" style="text-align: left; padding: 0.9rem 1.25rem; display: flex; align-items: center; justify-content: space-between;">
          <span>💳 My Invoices &amp; Receipts</span>
          <span>→</span>
        </a>

        <!-- Card 6: Add New Vehicle -->
        <a href="add-vehicle.php" class="btn btn-outline btn-block" style="text-align: left; padding: 0.85rem 1.25rem; font-size: 0.85rem; margin-top: 0.5rem;">
          + Register New Two-Wheeler
        </a>
      </div>
    </div>

  </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
