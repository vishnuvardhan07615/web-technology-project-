<?php
/**
 * ============================================================================
 * MotoCare – Two-Wheeler Service & Mechanic Shop Management System
 * File: admin/bills.php
 * Stage 2: Master Invoicing Ledger & Financial Metrics Center
 * ============================================================================
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/billing-helper.php';

requireAdminLogin();

$pageTitle = 'Billing & Invoices – Admin Control – MotoCare';
$currentPage = 'bills.php';

$successMsg = trim($_GET['msg'] ?? '');
$errorMsg   = trim($_GET['err'] ?? '');

// Filter parameters
$search        = trim($_GET['q'] ?? '');
$paymentStatus = trim($_GET['status'] ?? '');
$dateFilter    = trim($_GET['date'] ?? '');

$bills = [];
$metrics = [
    'total_invoices'      => 0,
    'paid_invoices'       => 0,
    'partial_invoices'    => 0,
    'pending_invoices'    => 0,
    'total_revenue'       => 0.00,
    'outstanding_balance' => 0.00
];

if ($pdo) {
    try {
        // 1. Overall Financial Summary Metrics (Section 6)
        $stmtMetrics = $pdo->query("
            SELECT 
                COUNT(*) AS total_invoices,
                COUNT(CASE WHEN payment_status = 'Paid' THEN 1 END) AS paid_invoices,
                COUNT(CASE WHEN payment_status = 'Partially Paid' THEN 1 END) AS partial_invoices,
                COUNT(CASE WHEN payment_status = 'Pending' THEN 1 END) AS pending_invoices,
                COALESCE(SUM(amount_paid), 0.00) AS total_revenue,
                COALESCE(SUM(balance_due), 0.00) AS outstanding_balance
            FROM bills
        ");
        $m = $stmtMetrics->fetch(PDO::FETCH_ASSOC);
        if ($m) {
            $metrics['total_invoices']      = (int)$m['total_invoices'];
            $metrics['paid_invoices']       = (int)$m['paid_invoices'];
            $metrics['partial_invoices']    = (int)$m['partial_invoices'];
            $metrics['pending_invoices']    = (int)$m['pending_invoices'];
            $metrics['total_revenue']       = (float)$m['total_revenue'];
            $metrics['outstanding_balance'] = (float)$m['outstanding_balance'];
        }

        // 2. Build filtered ledger query
        $whereClauses = [];
        $params = [];

        if (!empty($search)) {
            $whereClauses[] = "(
                b.invoice_number LIKE :q1 
                OR bk.booking_code LIKE :q2 
                OR c.full_name LIKE :q3 
                OR c.phone LIKE :q4 
                OR v.registration_number LIKE :q5
            )";
            $qWild = "%{$search}%";
            $params[':q1'] = $qWild;
            $params[':q2'] = $qWild;
            $params[':q3'] = $qWild;
            $params[':q4'] = $qWild;
            $params[':q5'] = $qWild;
        }

        if (!empty($paymentStatus) && in_array($paymentStatus, ['Paid', 'Partially Paid', 'Pending'])) {
            $whereClauses[] = "b.payment_status = :pstatus";
            $params[':pstatus'] = $paymentStatus;
        }

        if (!empty($dateFilter)) {
            $whereClauses[] = "DATE(b.bill_date) = :bdate";
            $params[':bdate'] = $dateFilter;
        }

        $sqlWhere = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

        $query = "
            SELECT 
                b.*,
                bk.booking_code,
                c.full_name AS customer_name,
                c.phone AS customer_phone,
                v.brand, v.model, v.registration_number,
                s.service_name
            FROM bills b
            JOIN bookings bk ON b.booking_id = bk.id
            JOIN customers c ON b.customer_id = c.id
            JOIN vehicles v ON (b.vehicle_id = v.id OR bk.vehicle_id = v.id)
            JOIN services s ON bk.service_id = s.id
            {$sqlWhere}
            ORDER BY b.id DESC
        ";

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $bills = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $errorMsg = "Database error: " . $e->getMessage();
    }
} else {
    // Offline viva mock data
    $bills = [
        [
            'id' => 1,
            'invoice_number' => 'MC-INV-2026-0001',
            'booking_id' => 1,
            'booking_code' => 'MC-2026-1001',
            'customer_name' => 'Ramesh Kumar',
            'customer_phone' => '9876543210',
            'brand' => 'Royal Enfield',
            'model' => 'Classic 350',
            'registration_number' => 'TN-07-AB-1234',
            'service_name' => 'General Service',
            'subtotal' => 880.00,
            'discount' => 0.00,
            'taxable_amount' => 880.00,
            'cgst' => 79.20,
            'sgst' => 79.20,
            'tax' => 158.40,
            'total_amount' => 1038.40,
            'payment_status' => 'Pending',
            'payment_method' => 'Cash',
            'amount_paid' => 0.00,
            'balance_due' => 1038.40,
            'bill_date' => date('Y-m-d H:i:s')
        ]
    ];
}

include __DIR__ . '/../includes/admin-header.php';
?>

<div class="container" style="padding-top: 1rem; padding-bottom: 3rem;">
  
  <!-- Header Title Strip -->
  <div class="section-header" style="text-align: left; margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 1rem;">
    <div>
      <span class="service-tag" style="background: rgba(16,185,129,0.15); color: var(--color-green); border-color: rgba(16,185,129,0.3); margin-bottom: 0.5rem; display: inline-block;">
        Financial Accounting &amp; Revenue
      </span>
      <h1 class="section-title" style="font-size: 2rem; margin-bottom: 0.25rem;">Billing, Invoices &amp; Payments</h1>
      <p class="section-description">Central tax invoice registry, GST compliance logs, and multi-mode payment settlements.</p>
    </div>

    <div>
      <a href="bookings.php?status=Completed" class="btn btn-primary">
        + Invoices for Completed Services →
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

  <!-- Dynamic Financial Summary Metrics Cards (Section 6 Requirements) -->
  <div class="stats-grid" style="margin-bottom: 2.5rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 1.25rem;">
    
    <!-- 1. Total Invoices -->
    <div class="stat-item" style="background: var(--bg-card); padding: 1.25rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); border-left: 4px solid var(--accent-orange);">
      <span class="stat-number" style="color: var(--accent-orange); font-size: 2rem; font-weight: 800; font-family: var(--font-heading);">
        <?php echo $metrics['total_invoices']; ?>
      </span>
      <span class="stat-label" style="display: block; font-size: 0.78rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-top: 0.25rem;">
        Total Invoices
      </span>
    </div>

    <!-- 2. Paid Invoices -->
    <div class="stat-item" style="background: var(--bg-card); padding: 1.25rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); border-left: 4px solid var(--color-green);">
      <span class="stat-number" style="color: var(--color-green); font-size: 2rem; font-weight: 800; font-family: var(--font-heading);">
        <?php echo $metrics['paid_invoices']; ?>
      </span>
      <span class="stat-label" style="display: block; font-size: 0.78rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-top: 0.25rem;">
        Paid In Full
      </span>
    </div>

    <!-- 3. Partially Paid -->
    <div class="stat-item" style="background: var(--bg-card); padding: 1.25rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); border-left: 4px solid #fbbf24;">
      <span class="stat-number" style="color: #fbbf24; font-size: 2rem; font-weight: 800; font-family: var(--font-heading);">
        <?php echo $metrics['partial_invoices']; ?>
      </span>
      <span class="stat-label" style="display: block; font-size: 0.78rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-top: 0.25rem;">
        Partially Paid
      </span>
    </div>

    <!-- 4. Pending Payment -->
    <div class="stat-item" style="background: var(--bg-card); padding: 1.25rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); border-left: 4px solid #f87171;">
      <span class="stat-number" style="color: #f87171; font-size: 2rem; font-weight: 800; font-family: var(--font-heading);">
        <?php echo $metrics['pending_invoices']; ?>
      </span>
      <span class="stat-label" style="display: block; font-size: 0.78rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-top: 0.25rem;">
        Pending Payment
      </span>
    </div>

    <!-- 5. Total Revenue Collected -->
    <div class="stat-item" style="background: var(--bg-card); padding: 1.25rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); border-left: 4px solid #38bdf8;">
      <span class="stat-number" style="color: #38bdf8; font-size: 1.85rem; font-weight: 800; font-family: var(--font-heading);">
        <?php echo formatCurrency($metrics['total_revenue']); ?>
      </span>
      <span class="stat-label" style="display: block; font-size: 0.78rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-top: 0.25rem;">
        Total Collected
      </span>
    </div>

    <!-- 6. Outstanding Balance -->
    <div class="stat-item" style="background: var(--bg-card); padding: 1.25rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); border-left: 4px solid #e11d48;">
      <span class="stat-number" style="color: #f43f5e; font-size: 1.85rem; font-weight: 800; font-family: var(--font-heading);">
        <?php echo formatCurrency($metrics['outstanding_balance']); ?>
      </span>
      <span class="stat-label" style="display: block; font-size: 0.78rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-top: 0.25rem;">
        Outstanding Due
      </span>
    </div>

  </div>

  <!-- Filter & Search Panel -->
  <div class="form-card" style="padding: 1.25rem 1.5rem; margin-bottom: 2rem;">
    <form method="GET" action="bills.php" style="display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap;">
      <div style="flex: 2; min-width: 200px;">
        <label for="q" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.25rem;">Search Invoices</label>
        <input type="text" id="q" name="q" class="form-control" placeholder="Invoice #, booking code, customer, reg no..." value="<?php echo htmlspecialchars($search); ?>">
      </div>

      <div style="flex: 1; min-width: 140px;">
        <label for="status" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.25rem;">Payment Status</label>
        <select id="status" name="status" class="form-select">
          <option value="">All Statuses</option>
          <option value="Paid" <?php echo ($paymentStatus === 'Paid') ? 'selected' : ''; ?>>Paid</option>
          <option value="Partially Paid" <?php echo ($paymentStatus === 'Partially Paid') ? 'selected' : ''; ?>>Partially Paid</option>
          <option value="Pending" <?php echo ($paymentStatus === 'Pending') ? 'selected' : ''; ?>>Pending</option>
        </select>
      </div>

      <div style="flex: 1; min-width: 130px;">
        <label for="date" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.25rem;">Bill Date</label>
        <input type="date" id="date" name="date" class="form-control" value="<?php echo htmlspecialchars($dateFilter); ?>">
      </div>

      <div style="display: flex; gap: 0.5rem;">
        <button type="submit" class="btn btn-primary btn-sm" style="padding: 0.65rem 1.25rem;">
          Filter Ledger
        </button>
        <?php if (!empty($search) || !empty($paymentStatus) || !empty($dateFilter)): ?>
          <a href="bills.php" class="btn btn-secondary btn-sm" style="padding: 0.65rem 1rem;">Reset</a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <!-- Invoices Table -->
  <div class="form-card" style="padding: 0; overflow: hidden;">
    <div style="overflow-x: auto;">
      <table class="table" style="width: 100%; border-collapse: collapse; text-align: left; margin: 0; font-size: 0.88rem;">
        <thead>
          <tr style="border-bottom: 1px solid var(--border-subtle); background: var(--bg-surface); font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em;">
            <th style="padding: 1rem 1.25rem;">Invoice #</th>
            <th style="padding: 1rem 1.25rem;">Order &amp; Customer</th>
            <th style="padding: 1rem 1.25rem;">Vehicle &amp; Service</th>
            <th style="padding: 1rem 1.25rem;">Bill Date</th>
            <th style="padding: 1rem 1.25rem; text-align: right;">Subtotal</th>
            <th style="padding: 1rem 1.25rem; text-align: right;">GST (18%)</th>
            <th style="padding: 1rem 1.25rem; text-align: right;">Grand Total</th>
            <th style="padding: 1rem 1.25rem; text-align: right;">Paid / Balance</th>
            <th style="padding: 1rem 1.25rem; text-align: center;">Status</th>
            <th style="padding: 1rem 1.25rem; text-align: right;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($bills)): ?>
            <tr>
              <td colspan="10" style="padding: 3rem; text-align: center; color: var(--text-muted);">
                <p style="font-size: 1.1rem; margin-bottom: 0.5rem;">No billing records found.</p>
                <small>Bills are generated once services reach Completed status.</small>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($bills as $bill): 
              $pStatus = $bill['payment_status'];
              $statusBadge = 'background: rgba(239,68,68,0.15); color: #f87171; border: 1px solid rgba(239,68,68,0.3);';
              if ($pStatus === 'Paid') {
                  $statusBadge = 'background: rgba(16,185,129,0.15); color: var(--color-green); border: 1px solid rgba(16,185,129,0.3);';
              } elseif ($pStatus === 'Partially Paid') {
                  $statusBadge = 'background: rgba(245,158,11,0.15); color: #fbbf24; border: 1px solid rgba(245,158,11,0.3);';
              }
            ?>
              <tr class="bill-row" style="border-bottom: 1px solid var(--border-subtle);">
                
                <!-- 1. Invoice Number -->
                <td style="padding: 1rem 1.25rem;">
                  <a href="bill-details.php?id=<?php echo (int)$bill['id']; ?>" style="font-weight: 700; color: #ffffff; text-decoration: none; font-family: monospace; font-size: 0.95rem;">
                    <?php echo htmlspecialchars($bill['invoice_number']); ?>
                  </a>
                </td>

                <!-- 2. Order & Customer -->
                <td style="padding: 1rem 1.25rem;">
                  <div style="font-weight: 600; color: #ffffff;">
                    <?php echo htmlspecialchars($bill['customer_name']); ?>
                  </div>
                  <div style="font-size: 0.78rem; color: var(--text-muted); font-family: monospace;">
                    Order #<?php echo htmlspecialchars($bill['booking_code']); ?>
                  </div>
                </td>

                <!-- 3. Vehicle & Service -->
                <td style="padding: 1rem 1.25rem;">
                  <div style="color: #ffffff; font-weight: 500;">
                    <?php echo htmlspecialchars($bill['service_name']); ?>
                  </div>
                  <div style="font-size: 0.78rem; color: var(--accent-orange); font-family: monospace;">
                    <?php echo htmlspecialchars($bill['registration_number']); ?> (<?php echo htmlspecialchars($bill['brand'] . ' ' . $bill['model']); ?>)
                  </div>
                </td>

                <!-- 4. Bill Date -->
                <td style="padding: 1rem 1.25rem; color: var(--text-muted); font-size: 0.85rem;">
                  <?php echo date('d M Y', strtotime($bill['bill_date'])); ?>
                </td>

                <!-- 5. Subtotal -->
                <td style="padding: 1rem 1.25rem; text-align: right; color: var(--text-secondary);">
                  <?php echo formatCurrency($bill['subtotal']); ?>
                </td>

                <!-- 6. GST -->
                <td style="padding: 1rem 1.25rem; text-align: right; color: var(--text-muted);">
                  <?php echo formatCurrency($bill['tax']); ?>
                </td>

                <!-- 7. Grand Total -->
                <td style="padding: 1rem 1.25rem; text-align: right; font-weight: 700; color: #ffffff; font-size: 0.95rem;">
                  <?php echo formatCurrency($bill['total_amount']); ?>
                </td>

                <!-- 8. Paid / Balance -->
                <td style="padding: 1rem 1.25rem; text-align: right;">
                  <div style="color: var(--color-green); font-size: 0.85rem; font-weight: 600;">
                    Paid: <?php echo formatCurrency($bill['amount_paid']); ?>
                  </div>
                  <?php if ((float)$bill['balance_due'] > 0): ?>
                    <div style="color: #f87171; font-size: 0.8rem; font-weight: 700;">
                      Due: <?php echo formatCurrency($bill['balance_due']); ?>
                    </div>
                  <?php endif; ?>
                </td>

                <!-- 9. Status Badge -->
                <td style="padding: 1rem 1.25rem; text-align: center;">
                  <span class="badge" style="<?php echo $statusBadge; ?> font-size: 0.75rem; padding: 0.3rem 0.65rem;">
                    <?php echo htmlspecialchars($pStatus); ?>
                  </span>
                </td>

                <!-- 10. Actions -->
                <td style="padding: 1rem 1.25rem; text-align: right;">
                  <div style="display: flex; gap: 0.4rem; justify-content: flex-end;">
                    <a href="bill-details.php?id=<?php echo (int)$bill['id']; ?>" class="btn btn-outline btn-sm" style="padding: 0.35rem 0.65rem; font-size: 0.8rem;" title="View Invoice">
                      View
                    </a>
                    <a href="invoice-pdf.php?id=<?php echo (int)$bill['id']; ?>" class="btn btn-secondary btn-sm" style="padding: 0.35rem 0.65rem; font-size: 0.8rem;" target="_blank" title="Download PDF">
                      PDF
                    </a>
                    <?php if ((float)$bill['balance_due'] > 0): ?>
                      <a href="record-payment.php?bill_id=<?php echo (int)$bill['id']; ?>" class="btn btn-primary btn-sm" style="padding: 0.35rem 0.65rem; font-size: 0.8rem;" title="Record Payment">
                        Pay
                      </a>
                    <?php endif; ?>
                  </div>
                </td>

              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
