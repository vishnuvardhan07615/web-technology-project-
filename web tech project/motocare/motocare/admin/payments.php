<?php
/**
 * ============================================================================
 * MotoCare – Two-Wheeler Service & Mechanic Shop Management System
 * File: admin/payments.php
 * Stage 2: Master Payment Ledger & Transactions Control
 * ============================================================================
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/billing-helper.php';

requireAdminLogin();

$pageTitle = 'Payments Ledger – Admin Control – MotoCare';
$currentPage = 'payments.php';

$search = trim($_GET['q'] ?? '');
$methodFilter = trim($_GET['method'] ?? '');

$payments = [];
$totalCollected = 0.00;

if ($pdo) {
    try {
        $where = [];
        $params = [];

        if (!empty($methodFilter)) {
            $where[] = "p.payment_method = :method";
            $params[':method'] = $methodFilter;
        }

        if (!empty($search)) {
            $where[] = "(b.invoice_number LIKE :q OR c.full_name LIKE :q OR p.transaction_reference LIKE :q OR p.notes LIKE :q)";
            $params[':q'] = "%{$search}%";
        }

        $whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        $stmt = $pdo->prepare("
            SELECT 
                p.*,
                b.id AS bill_id,
                b.invoice_number,
                b.total_amount,
                b.balance_due,
                b.payment_status,
                c.full_name AS customer_name
            FROM payments p
            JOIN bills b ON p.bill_id = b.id
            JOIN customers c ON b.customer_id = c.id
            {$whereSql}
            ORDER BY p.payment_date DESC, p.id DESC
        ");
        $stmt->execute($params);
        $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmtSum = $pdo->query("SELECT COALESCE(SUM(amount), 0.00) FROM payments");
        $totalCollected = (float)$stmtSum->fetchColumn();

    } catch (PDOException $e) {
        $errorMsg = 'Error loading payments: ' . $e->getMessage();
    }
}

include __DIR__ . '/../includes/admin-header.php';
?>

<div class="container" style="padding-top: 1rem; padding-bottom: 4rem;">

  <!-- Header Banner -->
  <div style="background: linear-gradient(135deg, var(--bg-card), var(--bg-surface)); border: 1px solid var(--border-card); border-radius: var(--radius-xl); padding: 2rem 2.25rem; margin-bottom: 2rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1.5rem;">
    <div>
      <span class="service-tag" style="background: rgba(16,185,129,0.15); color: #10b981; margin-bottom: 0.5rem; display: inline-block;">
        Financial Accounting
      </span>
      <h1 style="font-size: 1.85rem; color: #ffffff; font-family: var(--font-heading); margin-bottom: 0.25rem;">
        Payment Transactions Ledger
      </h1>
      <p style="color: var(--text-muted); font-size: 0.95rem; margin: 0;">
        Complete transaction log of all settlements, multi-installment payments, and reference tracking.
      </p>
    </div>

    <!-- Quick Stats -->
    <div style="background: var(--bg-surface); padding: 1rem 1.5rem; border-radius: var(--radius-lg); border: 1px solid var(--border-subtle); display: flex; align-items: center; gap: 1.25rem;">
      <div style="font-size: 2rem; font-weight: 800; color: #10b981; font-family: monospace;">
        ₹<?php echo number_format($totalCollected, 2); ?>
      </div>
      <div>
        <div style="font-size: 0.85rem; font-weight: 700; color: #ffffff;">Total Collected</div>
        <div style="font-size: 0.75rem; color: var(--text-muted);"><?php echo count($payments); ?> transaction(s) recorded</div>
      </div>
    </div>
  </div>

  <!-- Filter & Search Bar -->
  <div class="filter-card" style="background: var(--bg-card); padding: 1.25rem 1.5rem; border-radius: var(--radius-lg); border: 1px solid var(--border-card); margin-bottom: 2rem;">
    <form method="GET" action="payments.php" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
      <div style="flex: 1; min-width: 220px;">
        <input 
          type="text" 
          name="q" 
          class="form-control" 
          placeholder="Search by Invoice, Customer, Reference..." 
          value="<?php echo htmlspecialchars($search); ?>"
        >
      </div>

      <div style="min-width: 160px;">
        <select name="method" class="form-control" onchange="this.form.submit()">
          <option value="">All Payment Modes</option>
          <option value="Cash" <?php echo $methodFilter === 'Cash' ? 'selected' : ''; ?>>Cash</option>
          <option value="UPI" <?php echo $methodFilter === 'UPI' ? 'selected' : ''; ?>>UPI</option>
          <option value="Card" <?php echo $methodFilter === 'Card' ? 'selected' : ''; ?>>Card</option>
          <option value="Bank Transfer" <?php echo $methodFilter === 'Bank Transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
        </select>
      </div>

      <button type="submit" class="btn btn-primary btn-sm">Filter</button>
      <?php if (!empty($search) || !empty($methodFilter)): ?>
        <a href="payments.php" class="btn btn-outline btn-sm">Clear</a>
      <?php endif; ?>
      <a href="bills.php" class="btn btn-secondary btn-sm" style="margin-left: auto;">View Invoices →</a>
    </form>
  </div>

  <!-- Payments Table -->
  <div style="background: var(--bg-card); border: 1px solid var(--border-card); border-radius: var(--radius-lg); overflow: hidden;">
    <div style="overflow-x: auto;">
      <table class="data-table" style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
        <thead>
          <tr style="background: var(--bg-surface); border-bottom: 1px solid var(--border-subtle); color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase;">
            <th style="padding: 1rem 1.25rem;">Payment Date</th>
            <th style="padding: 1rem 1.25rem;">Invoice #</th>
            <th style="padding: 1rem 1.25rem;">Customer</th>
            <th style="padding: 1rem 1.25rem;">Method</th>
            <th style="padding: 1rem 1.25rem;">Reference</th>
            <th style="padding: 1rem 1.25rem; text-align: right;">Amount Paid</th>
            <th style="padding: 1rem 1.25rem;">Notes</th>
            <th style="padding: 1rem 1.25rem; text-align: right;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($payments)): ?>
            <tr>
              <td colspan="8" style="padding: 3rem; text-align: center; color: var(--text-muted);">
                No payment transactions found.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($payments as $pay): ?>
              <tr style="border-bottom: 1px solid var(--border-subtle);">
                <td style="padding: 1rem 1.25rem; color: #ffffff;">
                  <?php echo formatDisplayDate($pay['payment_date']); ?>
                </td>
                <td style="padding: 1rem 1.25rem;">
                  <a href="bill-details.php?id=<?php echo (int)$pay['bill_id']; ?>" style="color: var(--accent-orange); font-weight: 700; text-decoration: none; font-family: monospace;">
                    <?php echo htmlspecialchars($pay['invoice_number']); ?>
                  </a>
                </td>
                <td style="padding: 1rem 1.25rem; color: #ffffff; font-weight: 600;">
                  <?php echo htmlspecialchars($pay['customer_name']); ?>
                </td>
                <td style="padding: 1rem 1.25rem;">
                  <span class="badge" style="background: rgba(56,189,248,0.15); color: #38bdf8; font-size: 0.75rem;">
                    <?php echo htmlspecialchars($pay['payment_method']); ?>
                  </span>
                </td>
                <td style="padding: 1rem 1.25rem; font-family: monospace; font-size: 0.8rem; color: var(--text-secondary);">
                  <?php echo htmlspecialchars($pay['transaction_reference'] ?: '—'); ?>
                </td>
                <td style="padding: 1rem 1.25rem; text-align: right; color: #10b981; font-weight: 700; font-family: monospace;">
                  ₹<?php echo number_format((float)$pay['amount'], 2); ?>
                </td>
                <td style="padding: 1rem 1.25rem; color: var(--text-muted); font-size: 0.8rem; max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                  <?php echo htmlspecialchars($pay['notes'] ?: '—'); ?>
                </td>
                <td style="padding: 1rem 1.25rem; text-align: right;">
                  <a href="bill-details.php?id=<?php echo (int)$pay['bill_id']; ?>" class="btn btn-outline btn-sm" style="padding: 0.35rem 0.65rem; font-size: 0.8rem;">
                    View Invoice
                  </a>
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
