<?php
/**
 * ============================================================================
 * MotoCare – Two-Wheeler Service & Mechanic Shop Management System
 * File: admin/dashboard.php
 * Stage 2: Admin Dashboard (Dynamic Metrics & Mechanic Assignment Shortcuts)
 * ============================================================================
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

requireAdminLogin();
$admin = getCurrentAdmin();

$pageTitle = 'Admin Dashboard – MotoCare Management';
$currentPage = 'dashboard.php';

// Dynamic Metrics Default Array (Section 10 & Stage 2 Inventory & Billing & Feedback Requirements)
$metrics = [
    'total_bookings'       => 0,
    'pending_bookings'     => 0,
    'confirmed_bookings'   => 0,
    'completed_bookings'   => 0,
    'unassigned_bookings'  => 0,
    'total_parts'          => 0,
    'parts_in_stock'       => 0,
    'low_stock_parts'      => 0,
    'out_of_stock_parts'   => 0,
    'total_bills'          => 0,
    'paid_bills'           => 0,
    'partially_paid_bills' => 0,
    'pending_bills'        => 0,
    'total_billed'         => 0.00,
    'total_collected'      => 0.00,
    'outstanding_amount'   => 0.00,
    'total_reviews'        => 0,
    'pending_reviews'      => 0,
    'approved_reviews'     => 0,
    'average_rating'       => 0.0
];

$criticalParts = [];
$recentBookings = [];
$errorMsg = '';

if ($pdo) {
    try {
        // 1. Total Bookings
        $metrics['total_bookings'] = (int)$pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();

        // 2. Pending Bookings
        $metrics['pending_bookings'] = (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'Pending'")->fetchColumn();

        // 3. Confirmed Bookings
        $metrics['confirmed_bookings'] = (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'Confirmed'")->fetchColumn();

        // 4. Completed Bookings
        $metrics['completed_bookings'] = (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'Completed'")->fetchColumn();

        // 5. Unassigned Bookings (No mechanic allocated yet)
        $metrics['unassigned_bookings'] = (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE mechanic_id IS NULL AND status != 'Cancelled'")->fetchColumn();

        // 6. Inventory Metrics (Stage 2 Section 5)
        $metrics['total_parts'] = (int)$pdo->query("SELECT COUNT(*) FROM spare_parts WHERE status = 'Active'")->fetchColumn();
        $metrics['out_of_stock_parts'] = (int)$pdo->query("SELECT COUNT(*) FROM spare_parts WHERE status = 'Active' AND stock_quantity = 0")->fetchColumn();
        $metrics['low_stock_parts'] = (int)$pdo->query("SELECT COUNT(*) FROM spare_parts WHERE status = 'Active' AND stock_quantity > 0 AND stock_quantity <= minimum_stock")->fetchColumn();
        $metrics['parts_in_stock'] = (int)$pdo->query("SELECT COUNT(*) FROM spare_parts WHERE status = 'Active' AND stock_quantity > minimum_stock")->fetchColumn();

        // Critical low/out-of-stock items for alert banner (Stage 2 Section 6)
        $stmtCrit = $pdo->query("
            SELECT part_name, part_number, stock_quantity, minimum_stock 
            FROM spare_parts 
            WHERE status = 'Active' AND stock_quantity <= minimum_stock 
            ORDER BY stock_quantity ASC 
            LIMIT 4
        ");
        $criticalParts = $stmtCrit->fetchAll();

        // 7. Recent bookings list
        $stmtRecent = $pdo->query("
            SELECT 
                b.*,
                c.full_name AS customer_name,
                c.phone AS customer_phone,
                v.brand, v.model, v.registration_number,
                s.service_name, s.price AS service_price,
                m.full_name AS mechanic_name
            FROM bookings b
            JOIN customers c ON b.customer_id = c.id
            JOIN vehicles v ON b.vehicle_id = v.id
            JOIN services s ON b.service_id = s.id
            LEFT JOIN mechanics m ON b.mechanic_id = m.id
            ORDER BY b.created_at DESC
            LIMIT 5
        ");
        $recentBookings = $stmtRecent->fetchAll();

        // 8. Stage 2 Billing & Financial Metrics (Section 15 Requirements)
        $metrics['total_bills'] = (int)$pdo->query("SELECT COUNT(*) FROM bills")->fetchColumn();
        $metrics['paid_bills'] = (int)$pdo->query("SELECT COUNT(*) FROM bills WHERE payment_status = 'Paid'")->fetchColumn();
        $metrics['partially_paid_bills'] = (int)$pdo->query("SELECT COUNT(*) FROM bills WHERE payment_status = 'Partially Paid'")->fetchColumn();
        $metrics['pending_bills'] = (int)$pdo->query("SELECT COUNT(*) FROM bills WHERE payment_status = 'Pending'")->fetchColumn();

        $stmtFin = $pdo->query("
            SELECT 
                COALESCE(SUM(total_amount), 0) AS total_billed,
                COALESCE(SUM(amount_paid), 0) AS total_collected,
                COALESCE(SUM(balance_due), 0) AS outstanding_amount
            FROM bills
        ");
        $finRow = $stmtFin->fetch();
        if ($finRow) {
            $metrics['total_billed'] = (float)$finRow['total_billed'];
            $metrics['total_collected'] = (float)$finRow['total_collected'];
            $metrics['outstanding_amount'] = (float)$finRow['outstanding_amount'];
        }

        // 9. Feedback & Quality Metrics (Stage 2 Section 12 Requirements)
        require_once __DIR__ . '/../includes/feedback-helper.php';
        $fbStats = getFeedbackStats($pdo);
        $metrics['total_reviews']    = $fbStats['total_reviews'];
        $metrics['pending_reviews']  = $fbStats['pending_reviews'];
        $metrics['approved_reviews'] = $fbStats['approved_reviews'];
        $metrics['average_rating']   = $fbStats['average_rating'];

    } catch (PDOException $e) {
        $errorMsg = 'Database error loading dashboard: ' . $e->getMessage();
    }
} else {
    // Offline demo fallback
    $metrics = [
        'total_bookings'       => 2,
        'pending_bookings'     => 1,
        'confirmed_bookings'   => 1,
        'completed_bookings'   => 0,
        'unassigned_bookings'  => 1
    ];

    $recentBookings = [
        [
            'id' => 1,
            'booking_code' => 'MC-2026-1001',
            'customer_name' => 'Ramesh Kumar',
            'customer_phone' => '9876543210',
            'brand' => 'Royal Enfield',
            'model' => 'Classic 350',
            'registration_number' => 'TN-07-AB-1234',
            'service_name' => 'General Service',
            'preferred_date' => '2026-10-10',
            'preferred_time' => '10:00 AM - 01:00 PM',
            'mechanic_name' => 'Arun Kumar',
            'status' => 'Confirmed'
        ],
        [
            'id' => 2,
            'booking_code' => 'MC-2026-1002',
            'customer_name' => 'Ramesh Kumar',
            'customer_phone' => '9876543210',
            'brand' => 'Honda',
            'model' => 'Activa 6G',
            'registration_number' => 'TN-09-CD-5678',
            'service_name' => 'Oil Change',
            'preferred_date' => '2026-10-12',
            'preferred_time' => '08:00 AM - 10:00 AM',
            'mechanic_name' => null,
            'status' => 'Pending'
        ]
    ];
}

require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="container" style="padding-top: 1rem; padding-bottom: 3rem;">
  
  <!-- Top Welcome Banner -->
  <div style="background: linear-gradient(135deg, var(--bg-card), var(--bg-surface)); border: 1px solid var(--border-card); border-radius: var(--radius-xl); padding: 2.25rem 2rem; margin-bottom: 2rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1.5rem;">
    <div>
      <span class="service-tag" style="background: rgba(239,68,68,0.15); color: #f87171; border-color: rgba(239,68,68,0.3); margin-bottom: 0.5rem; display: inline-block;">
        Workshop Operations Center
      </span>
      <h1 style="font-size: 2rem; margin-bottom: 0.25rem; color: #ffffff; font-family: var(--font-heading);">
        Command Dashboard
      </h1>
      <p style="color: var(--text-muted); font-size: 0.95rem; margin: 0;">
        Administrator: <strong style="color: #ffffff;"><?php echo htmlspecialchars($admin['username']); ?></strong> &bull;
        Role: <span style="color: var(--accent-orange);">Workshop Supervisor</span>
      </p>
    </div>
    
    <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
      <a href="bookings.php" class="btn btn-primary">
        Manage Bookings
      </a>
      <a href="bills.php" class="btn btn-outline" style="border-color: #38bdf8; color: #38bdf8;">
        💳 Billing & Invoices
      </a>
      <a href="feedback.php" class="btn btn-outline" style="border-color: #fbbf24; color: #fbbf24;">
        ★ Reviews (<?php echo $metrics['total_reviews']; ?>)
      </a>
      <a href="mechanics.php" class="btn btn-secondary">
        Mechanic Roster
      </a>
    </div>
  </div>

  <?php if ($metrics['pending_reviews'] > 0): ?>
    <!-- Pending Feedback Moderation Shortcut Banner (Section 12 Requirement) -->
    <div style="background: rgba(251,191,36,0.1); border: 1px solid rgba(251,191,36,0.35); border-radius: var(--radius-lg); padding: 1.25rem 1.75rem; margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
      <div style="display: flex; align-items: center; gap: 1rem;">
        <span style="font-size: 1.75rem;">★</span>
        <div>
          <h4 style="color: #fbbf24; font-size: 1.1rem; margin: 0 0 0.2rem 0;">
            Pending Feedback Moderation (<?php echo $metrics['pending_reviews']; ?> awaiting supervisor review)
          </h4>
          <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0;">
            Customer service reviews have been submitted and are pending moderation before public display.
          </p>
        </div>
      </div>
      <a href="feedback.php?status=Pending" class="btn btn-primary btn-sm" style="padding: 0.6rem 1.25rem;">
        Moderate Reviews Now →
      </a>
    </div>
  <?php endif; ?>

  <?php if ($metrics['unassigned_bookings'] > 0 || $metrics['pending_bookings'] > 0): ?>
    <!-- Pending Mechanic Assignments Shortcut Banner (Section 10 Requirement) -->
    <div style="background: rgba(250,204,21,0.1); border: 1px solid rgba(250,204,21,0.3); border-radius: var(--radius-lg); padding: 1.25rem 1.75rem; margin-bottom: 2.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
      <div style="display: flex; align-items: center; gap: 1rem;">
        <span style="font-size: 1.75rem;">⚠️</span>
        <div>
          <h4 style="color: #fbbf24; font-size: 1.1rem; margin: 0 0 0.2rem 0;">
            Pending Mechanic Assignments (<?php echo $metrics['unassigned_bookings']; ?> unassigned)
          </h4>
          <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0;">
            There are <?php echo $metrics['pending_bookings']; ?> pending customer service reservations waiting for workshop technician allocation.
          </p>
        </div>
      </div>
      <a href="bookings.php?status=Pending" class="btn btn-primary btn-sm" style="padding: 0.6rem 1.25rem;">
        Assign Mechanics Now →
      </a>
    </div>
  <?php endif; ?>

  <?php if (!empty($criticalParts)): ?>
    <!-- Low Stock Alert Banner (Stage 2 Section 6 Requirement) -->
    <div style="background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); border-radius: var(--radius-lg); padding: 1.25rem 1.75rem; margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
      <div style="display: flex; align-items: center; gap: 1rem;">
        <span style="font-size: 1.75rem;">⚠️</span>
        <div>
          <h4 style="color: #f87171; font-size: 1.1rem; margin: 0 0 0.2rem 0;">
            Spare Parts Inventory Alert (<?php echo $metrics['out_of_stock_parts']; ?> Out of Stock, <?php echo $metrics['low_stock_parts']; ?> Low Stock)
          </h4>
          <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0;">
            <?php 
              $critList = [];
              foreach ($criticalParts as $cp) {
                  $critList[] = htmlspecialchars($cp['part_name']) . " &mdash; <strong style='color: " . ($cp['stock_quantity'] == 0 ? '#f87171' : '#fbbf24') . ";'>" . $cp['stock_quantity'] . " remaining</strong>";
              }
              echo implode(" &bull; ", $critList);
            ?>
          </p>
        </div>
      </div>
      <a href="spare-parts.php" class="btn btn-outline btn-sm" style="border-color: #f87171; color: #f87171;">
        Manage Inventory →
      </a>
    </div>
  <?php endif; ?>

  <!-- Dynamic Summary Metrics (Section 10 Requirements) -->
  <div class="stats-grid" style="margin-bottom: 3rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1.25rem;">
    
    <!-- 1. Total Bookings -->
    <a href="bookings.php" style="text-decoration: none; color: inherit;">
      <div class="stat-item" style="background: var(--bg-card); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); border-left: 4px solid var(--accent-orange);">
        <span class="stat-number" style="color: var(--accent-orange); font-size: 2.25rem; font-weight: 800; font-family: var(--font-heading);">
          <?php echo $metrics['total_bookings']; ?>
        </span>
        <span class="stat-label" style="display: block; font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-top: 0.25rem;">
          Total Bookings
        </span>
      </div>
    </a>

    <!-- 2. Pending Bookings -->
    <a href="bookings.php?status=Pending" style="text-decoration: none; color: inherit;">
      <div class="stat-item" style="background: var(--bg-card); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); border-left: 4px solid #fbbf24;">
        <span class="stat-number" style="color: #fbbf24; font-size: 2.25rem; font-weight: 800; font-family: var(--font-heading);">
          <?php echo $metrics['pending_bookings']; ?>
        </span>
        <span class="stat-label" style="display: block; font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-top: 0.25rem;">
          Pending Bookings
        </span>
      </div>
    </a>

    <!-- 3. Confirmed Bookings -->
    <a href="bookings.php?status=Confirmed" style="text-decoration: none; color: inherit;">
      <div class="stat-item" style="background: var(--bg-card); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); border-left: 4px solid #38bdf8;">
        <span class="stat-number" style="color: #38bdf8; font-size: 2.25rem; font-weight: 800; font-family: var(--font-heading);">
          <?php echo $metrics['confirmed_bookings']; ?>
        </span>
        <span class="stat-label" style="display: block; font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-top: 0.25rem;">
          Confirmed Bookings
        </span>
      </div>
    </a>

    <!-- 4. Completed Bookings -->
    <a href="bookings.php?status=Completed" style="text-decoration: none; color: inherit;">
      <div class="stat-item" style="background: var(--bg-card); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); border-left: 4px solid var(--color-green);">
        <span class="stat-number" style="color: var(--color-green); font-size: 2.25rem; font-weight: 800; font-family: var(--font-heading);">
          <?php echo $metrics['completed_bookings']; ?>
        </span>
        <span class="stat-label" style="display: block; font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-top: 0.25rem;">
          Completed Bookings
        </span>
      </div>
    </a>

    <!-- 5. Spares In Stock -->
    <a href="spare-parts.php" style="text-decoration: none; color: inherit;">
      <div class="stat-item" style="background: var(--bg-card); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); border-left: 4px solid #38bdf8;">
        <span class="stat-number" style="color: #38bdf8; font-size: 2.25rem; font-weight: 800; font-family: var(--font-heading);">
          <?php echo $metrics['parts_in_stock']; ?>
        </span>
        <span class="stat-label" style="display: block; font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-top: 0.25rem;">
          Spares In Stock
        </span>
      </div>
    </a>

    <!-- 6. Low/Out of Stock Spares -->
    <a href="spare-parts.php?filter=low" style="text-decoration: none; color: inherit;">
      <div class="stat-item" style="background: var(--bg-card); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); border-left: 4px solid #f87171;">
        <span class="stat-number" style="color: #f87171; font-size: 2.25rem; font-weight: 800; font-family: var(--font-heading);">
          <?php echo ($metrics['low_stock_parts'] + $metrics['out_of_stock_parts']); ?>
        </span>
        <span class="stat-label" style="display: block; font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-top: 0.25rem;">
          Low / Out Spares
        </span>
      </div>
    </a>

  </div>

  <!-- Stage 2 Section 15: Billing & Revenue Overview -->
  <div style="background: var(--bg-card); border: 1px solid var(--border-card); border-radius: var(--radius-lg); padding: 1.75rem 2rem; margin-bottom: 2.5rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 0.75rem;">
      <div>
        <h3 style="font-size: 1.25rem; color: #ffffff; margin: 0 0 0.25rem 0; display: flex; align-items: center; gap: 0.5rem;">
          <span>💳</span> Billing & Revenue Overview
        </h3>
        <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0;">
          Tax invoice generation, collection tracking, and outstanding balances
        </p>
      </div>
      <a href="bills.php" class="btn btn-outline btn-sm" style="border-color: #38bdf8; color: #38bdf8;">
        Open Billing Ledger &rarr;
      </a>
    </div>

    <!-- Financial Summary Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-bottom: 1.25rem;">
      <!-- Total Bills -->
      <a href="bills.php" style="text-decoration: none; color: inherit;">
        <div style="background: var(--bg-surface); padding: 1rem 1.25rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); border-top: 3px solid #38bdf8;">
          <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em;">Total Bills</div>
          <div style="font-size: 1.75rem; font-weight: 800; color: #ffffff; font-family: var(--font-heading); margin-top: 0.25rem;">
            <?php echo $metrics['total_bills']; ?>
          </div>
        </div>
      </a>

      <!-- Paid Bills -->
      <a href="bills.php?payment_status=Paid" style="text-decoration: none; color: inherit;">
        <div style="background: var(--bg-surface); padding: 1rem 1.25rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); border-top: 3px solid #10b981;">
          <div style="font-size: 0.75rem; color: #10b981; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600;">Paid Bills</div>
          <div style="font-size: 1.75rem; font-weight: 800; color: #10b981; font-family: var(--font-heading); margin-top: 0.25rem;">
            <?php echo $metrics['paid_bills']; ?>
          </div>
        </div>
      </a>

      <!-- Partially Paid Bills -->
      <a href="bills.php?payment_status=Partially+Paid" style="text-decoration: none; color: inherit;">
        <div style="background: var(--bg-surface); padding: 1rem 1.25rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); border-top: 3px solid #f59e0b;">
          <div style="font-size: 0.75rem; color: #f59e0b; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600;">Partially Paid</div>
          <div style="font-size: 1.75rem; font-weight: 800; color: #f59e0b; font-family: var(--font-heading); margin-top: 0.25rem;">
            <?php echo $metrics['partially_paid_bills']; ?>
          </div>
        </div>
      </a>

      <!-- Pending Bills -->
      <a href="bills.php?payment_status=Pending" style="text-decoration: none; color: inherit;">
        <div style="background: var(--bg-surface); padding: 1rem 1.25rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); border-top: 3px solid #ef4444;">
          <div style="font-size: 0.75rem; color: #ef4444; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600;">Pending Bills</div>
          <div style="font-size: 1.75rem; font-weight: 800; color: #ef4444; font-family: var(--font-heading); margin-top: 0.25rem;">
            <?php echo $metrics['pending_bills']; ?>
          </div>
        </div>
      </a>
    </div>

    <!-- Revenue & Collection Strip -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; background: rgba(0,0,0,0.2); padding: 1rem 1.25rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
      <div>
        <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Total Billed Amount</div>
        <div style="font-size: 1.35rem; font-weight: 800; color: #ffffff; font-family: monospace; margin-top: 0.15rem;">
          <?php echo formatCurrency($metrics['total_billed']); ?>
        </div>
      </div>
      <div>
        <div style="font-size: 0.75rem; color: #10b981; text-transform: uppercase; font-weight: 600;">Total Collected</div>
        <div style="font-size: 1.35rem; font-weight: 800; color: #10b981; font-family: monospace; margin-top: 0.15rem;">
          <?php echo formatCurrency($metrics['total_collected']); ?>
        </div>
      </div>
      <div>
        <div style="font-size: 0.75rem; color: #ef4444; text-transform: uppercase; font-weight: 600;">Outstanding Amount Due</div>
        <div style="font-size: 1.35rem; font-weight: 800; color: #ef4444; font-family: monospace; margin-top: 0.15rem;">
          <?php echo formatCurrency($metrics['outstanding_amount']); ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Stage 2 Section 12: Customer Feedback & Quality Insights Overview -->
  <div style="background: var(--bg-card); border: 1px solid var(--border-card); border-radius: var(--radius-lg); padding: 1.75rem 2rem; margin-bottom: 2.5rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 0.75rem;">
      <div>
        <h3 style="font-size: 1.25rem; color: #ffffff; margin: 0 0 0.25rem 0; display: flex; align-items: center; gap: 0.5rem;">
          <span>★</span> Customer Reviews &amp; Quality Metrics
        </h3>
        <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0;">
          5-star customer ratings, service satisfaction, and review moderation
        </p>
      </div>
      <div style="display: flex; gap: 0.5rem;">
        <?php if ($metrics['pending_reviews'] > 0): ?>
          <a href="feedback.php?status=Pending" class="btn btn-primary btn-sm" style="white-space: nowrap;">
            Pending Moderation (<?php echo $metrics['pending_reviews']; ?>) →
          </a>
        <?php endif; ?>
        <a href="feedback.php" class="btn btn-outline btn-sm" style="border-color: #fbbf24; color: #fbbf24;">
          All Reviews Ledger &rarr;
        </a>
      </div>
    </div>

    <!-- Review Metric Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem;">
      <!-- Total Reviews -->
      <a href="feedback.php" style="text-decoration: none; color: inherit;">
        <div style="background: var(--bg-surface); padding: 1rem 1.25rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); border-top: 3px solid #38bdf8;">
          <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Total Reviews</div>
          <div style="font-size: 1.75rem; font-weight: 800; color: #ffffff; font-family: var(--font-heading); margin-top: 0.25rem;">
            <?php echo $metrics['total_reviews']; ?>
          </div>
        </div>
      </a>

      <!-- Pending Moderation -->
      <a href="feedback.php?status=Pending" style="text-decoration: none; color: inherit;">
        <div style="background: var(--bg-surface); padding: 1rem 1.25rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); border-top: 3px solid #fbbf24;">
          <div style="font-size: 0.75rem; color: #fbbf24; text-transform: uppercase; font-weight: 600;">Pending Moderation</div>
          <div style="font-size: 1.75rem; font-weight: 800; color: #fbbf24; font-family: var(--font-heading); margin-top: 0.25rem;">
            <?php echo $metrics['pending_reviews']; ?>
          </div>
        </div>
      </a>

      <!-- Approved Reviews -->
      <a href="feedback.php?status=Approved" style="text-decoration: none; color: inherit;">
        <div style="background: var(--bg-surface); padding: 1rem 1.25rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); border-top: 3px solid #10b981;">
          <div style="font-size: 0.75rem; color: #10b981; text-transform: uppercase; font-weight: 600;">Approved (Published)</div>
          <div style="font-size: 1.75rem; font-weight: 800; color: #10b981; font-family: var(--font-heading); margin-top: 0.25rem;">
            <?php echo $metrics['approved_reviews']; ?>
          </div>
        </div>
      </a>

      <!-- Average Rating -->
      <div style="background: var(--bg-surface); padding: 1rem 1.25rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); border-top: 3px solid #f59e0b;">
        <div style="font-size: 0.75rem; color: #f59e0b; text-transform: uppercase; font-weight: 600;">Average Rating</div>
        <div style="display: flex; align-items: baseline; gap: 0.35rem; margin-top: 0.25rem;">
          <span style="font-size: 1.75rem; font-weight: 800; color: #fbbf24; font-family: var(--font-heading);">
            ★ <?php echo $metrics['average_rating'] > 0 ? number_format($metrics['average_rating'], 1) : '0.0'; ?>
          </span>
          <span style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600;">/ 5.0</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Recent Service Appointments Table -->
  <div class="form-card" style="padding: 2rem;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 0.5rem;">
      <div>
        <h3 style="font-size: 1.35rem; color: #ffffff; margin: 0 0 0.25rem 0;">Recent Service Appointments</h3>
        <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0;">Live feed of customer appointment bookings and bay allocations</p>
      </div>
      <a href="bookings.php" class="btn btn-outline btn-sm">Full Bookings Ledger &rarr;</a>
    </div>

    <div style="overflow-x: auto;">
      <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
        <thead>
          <tr style="border-bottom: 2px solid var(--border-card); color: var(--text-main);">
            <th style="padding: 0.75rem 1rem;">Code</th>
            <th style="padding: 0.75rem 1rem;">Customer</th>
            <th style="padding: 0.75rem 1rem;">Vehicle</th>
            <th style="padding: 0.75rem 1rem;">Service Required</th>
            <th style="padding: 0.75rem 1rem;">Preferred Slot</th>
            <th style="padding: 0.75rem 1rem;">Assigned Mechanic</th>
            <th style="padding: 0.75rem 1rem;">Status</th>
            <th style="padding: 0.75rem 1rem; text-align: right;">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recentBookings as $bk): ?>
            <tr style="border-bottom: 1px solid var(--border-subtle);">
              
              <!-- Code -->
              <td style="padding: 1rem; font-weight: 700; color: var(--accent-orange); font-family: monospace;">
                <a href="booking-details.php?id=<?php echo urlencode($bk['id']); ?>" style="color: inherit; text-decoration: none;">
                  <?php echo htmlspecialchars($bk['booking_code']); ?>
                </a>
              </td>

              <!-- Customer -->
              <td style="padding: 1rem;">
                <div style="font-weight: 600; color: #ffffff;"><?php echo htmlspecialchars($bk['customer_name']); ?></div>
                <div style="font-size: 0.8rem; color: var(--text-muted);"><?php echo htmlspecialchars($bk['customer_phone']); ?></div>
              </td>

              <!-- Vehicle -->
              <td style="padding: 1rem;">
                <div style="font-weight: 500; color: #ffffff;"><?php echo htmlspecialchars($bk['brand'] . ' ' . $bk['model']); ?></div>
                <div style="font-family: monospace; font-size: 0.8rem; color: var(--accent-orange);"><?php echo htmlspecialchars($bk['registration_number']); ?></div>
              </td>

              <!-- Service -->
              <td style="padding: 1rem;">
                <div style="color: #ffffff;"><?php echo htmlspecialchars($bk['service_name']); ?></div>
              </td>

              <!-- Preferred Slot -->
              <td style="padding: 1rem;">
                <div style="font-weight: 500; color: #ffffff;"><?php echo formatDisplayDate($bk['preferred_date']); ?></div>
                <div style="font-size: 0.8rem; color: var(--text-muted);"><?php echo htmlspecialchars($bk['preferred_time']); ?></div>
              </td>

              <!-- Assigned Mechanic -->
              <td style="padding: 1rem;">
                <?php if (!empty($bk['mechanic_name'])): ?>
                  <span style="color: #60a5fa; font-size: 0.85rem; font-weight: 600;">
                    🔧 <?php echo htmlspecialchars($bk['mechanic_name']); ?>
                  </span>
                <?php else: ?>
                  <span class="badge" style="background: rgba(250,204,21,0.1); color: #fbbf24; border: 1px dashed rgba(250,204,21,0.3); font-size: 0.75rem;">
                    Unassigned
                  </span>
                <?php endif; ?>
              </td>

              <!-- Status -->
              <td style="padding: 1rem;">
                <?php 
                  $status = $bk['status'];
                  $bg = 'rgba(250,204,21,0.15)';
                  $color = '#fbbf24';
                  if ($status === 'Confirmed') { $bg = 'rgba(56,189,248,0.15)'; $color = '#38bdf8'; }
                  elseif ($status === 'Service In Progress' || $status === 'Inspection') { $bg = 'rgba(255,94,20,0.15)'; $color = 'var(--accent-orange)'; }
                  elseif ($status === 'Completed') { $bg = 'rgba(16,185,129,0.15)'; $color = 'var(--color-green)'; }
                ?>
                <span class="service-tag" style="background: <?php echo $bg; ?>; color: <?php echo $color; ?>; border-color: <?php echo $color; ?>; font-size: 0.75rem; padding: 0.25rem 0.55rem;">
                  <?php echo htmlspecialchars($status); ?>
                </span>
              </td>

              <!-- Action -->
              <td style="padding: 1rem; text-align: right;">
                <a href="booking-details.php?id=<?php echo urlencode($bk['id']); ?>" class="btn btn-outline btn-sm">
                  <?php echo empty($bk['mechanic_name']) ? 'Assign Mechanic →' : 'Details / Reassign'; ?>
                </a>
              </td>

            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
