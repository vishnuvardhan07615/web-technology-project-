<?php
/**
 * ============================================================================
 * MotoCare – Two-Wheeler Service & Mechanic Shop Management System
 * File: admin/record-payment.php
 * Stage 2: Payment Recording Controller & Interface (Transaction-Safe)
 * ============================================================================
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/billing-helper.php';

requireAdminLogin();

$billId = isset($_REQUEST['bill_id']) ? (int)$_REQUEST['bill_id'] : 0;
$errorMsg = trim($_GET['err'] ?? '');
$successMsg = '';

if ($billId <= 0) {
    header('Location: bills.php?err=' . urlencode('Invalid invoice ID specified.'));
    exit;
}

$bill = null;
if ($pdo) {
    $bill = fetchFullBillDetails($pdo, $billId);
}

if (!$bill) {
    header('Location: bills.php?err=' . urlencode('Invoice record not found.'));
    exit;
}

// Handle POST payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount        = isset($_POST['amount']) ? (float)$_POST['amount'] : 0.00;
    $paymentMethod = trim($_POST['payment_method'] ?? 'Cash');
    $reference     = trim($_POST['transaction_reference'] ?? '');
    $paymentDate   = trim($_POST['payment_date'] ?? date('Y-m-d'));
    $notes         = trim($_POST['notes'] ?? '');

    if ($pdo) {
        $res = recordBillPayment($pdo, $billId, $amount, $paymentMethod, $reference, $notes, $paymentDate);
        if ($res['success']) {
            header("Location: bill-details.php?id={$billId}&msg=" . urlencode($res['message']));
            exit;
        } else {
            $errorMsg = $res['error'];
        }
    } else {
        $errorMsg = 'Database connection offline.';
    }
}

$pageTitle = "Record Payment – Invoice #{$bill['invoice_number']} – MotoCare";
$currentPage = 'bills.php';

include __DIR__ . '/../includes/admin-header.php';
?>

<div class="container" style="max-width: 650px; padding-top: 1rem; padding-bottom: 4rem;">

  <div style="margin-bottom: 1.5rem;">
    <a href="bill-details.php?id=<?php echo (int)$bill['id']; ?>" class="btn btn-outline btn-sm">
      ← Back to Invoice #<?php echo htmlspecialchars($bill['invoice_number']); ?>
    </a>
  </div>

  <?php if ($errorMsg): ?>
    <div class="form-alert form-alert-error" style="margin-bottom: 1.5rem;">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
      <span><?php echo htmlspecialchars($errorMsg); ?></span>
    </div>
  <?php endif; ?>

  <div class="form-card" style="padding: 2.5rem 2rem;">
    
    <div style="border-bottom: 1px solid var(--border-subtle); padding-bottom: 1.25rem; margin-bottom: 1.75rem;">
      <span class="service-tag" style="background: rgba(16,185,129,0.15); color: var(--color-green); border-color: rgba(16,185,129,0.3); margin-bottom: 0.5rem; display: inline-block;">
        Payment Settlement
      </span>
      <h2 style="font-size: 1.6rem; color: #ffffff; margin: 0 0 0.25rem 0;">
        Record Invoice Payment
      </h2>
      <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0;">
        Invoice <strong style="color: #ffffff; font-family: monospace;"><?php echo htmlspecialchars($bill['invoice_number']); ?></strong> &bull; Customer: <strong style="color: var(--accent-orange);"><?php echo htmlspecialchars($bill['customer_name']); ?></strong>
      </p>
    </div>

    <!-- Outstanding Balance Summary Box -->
    <div style="background: var(--bg-surface); padding: 1.25rem 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
      <div>
        <span style="font-size: 0.78rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700; letter-spacing: 0.05em;">
          Current Outstanding Balance
        </span>
        <div style="font-size: 1.75rem; font-weight: 800; color: #f87171; font-family: var(--font-heading); margin-top: 0.2rem;">
          <?php echo formatCurrency($bill['balance_due']); ?>
        </div>
      </div>
      <div style="text-align: right;">
        <span style="font-size: 0.8rem; color: var(--text-muted); display: block;">
          Total Invoiced: <?php echo formatCurrency($bill['total_amount']); ?>
        </span>
        <span style="font-size: 0.8rem; color: var(--color-green); display: block; font-weight: 600;">
          Already Paid: <?php echo formatCurrency($bill['amount_paid']); ?>
        </span>
      </div>
    </div>

    <?php if ((float)$bill['balance_due'] <= 0.00): ?>
      <div style="text-align: center; padding: 2rem; background: rgba(16,185,129,0.1); border-radius: var(--radius-md); border: 1px solid rgba(16,185,129,0.3);">
        <span style="font-size: 2rem;">✅</span>
        <h4 style="color: var(--color-green); margin: 0.5rem 0 0.25rem 0;">Fully Paid</h4>
        <p style="color: var(--text-muted); font-size: 0.88rem; margin: 0 0 1.25rem 0;">This invoice is already completely settled. No payment is required.</p>
        <a href="bill-details.php?id=<?php echo (int)$bill['id']; ?>" class="btn btn-outline btn-sm">Return to Invoice</a>
      </div>
    <?php else: ?>

      <form method="POST" action="record-payment.php?bill_id=<?php echo (int)$bill['id']; ?>">
        <input type="hidden" name="bill_id" value="<?php echo (int)$bill['id']; ?>">

        <div class="form-group" style="margin-bottom: 1.25rem;">
          <label for="amount" class="form-label">
            Payment Amount (<?php echo CURRENCY_SYMBOL; ?>) <span class="required-dot">*</span>
          </label>
          <div style="position: relative;">
            <input 
              type="number" 
              step="0.01" 
              min="0.01" 
              max="<?php echo htmlspecialchars($bill['balance_due']); ?>" 
              id="amount" 
              name="amount" 
              class="form-control" 
              value="<?php echo htmlspecialchars($bill['balance_due']); ?>" 
              required
              style="font-size: 1.15rem; font-weight: 700; color: #ffffff;"
            >
          </div>
          <small style="color: var(--text-muted); font-size: 0.75rem; margin-top: 0.35rem; display: block;">
            Maximum payable for this invoice: <strong><?php echo formatCurrency($bill['balance_due']); ?></strong>
          </small>
        </div>

        <div class="form-group" style="margin-bottom: 1.25rem;">
          <label for="payment_method" class="form-label">
            Payment Method <span class="required-dot">*</span>
          </label>
          <select id="payment_method" name="payment_method" class="form-select" required>
            <?php foreach (PAYMENT_METHODS as $pm): ?>
              <option value="<?php echo htmlspecialchars($pm); ?>">
                <?php echo htmlspecialchars($pm); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group" style="margin-bottom: 1.25rem;">
          <label for="transaction_reference" class="form-label">
            Transaction / UTR / Reference ID
          </label>
          <input 
            type="text" 
            id="transaction_reference" 
            name="transaction_reference" 
            class="form-control" 
            placeholder="e.g. UPI-123456789 or TXN-4402"
          >
          <small style="color: var(--text-muted); font-size: 0.75rem; margin-top: 0.35rem; display: block;">
            Optional for Cash; strongly recommended for UPI, Card, and Bank Transfer.
          </small>
        </div>

        <div class="form-group" style="margin-bottom: 1.25rem;">
          <label for="payment_date" class="form-label">
            Payment Date <span class="required-dot">*</span>
          </label>
          <input 
            type="date" 
            id="payment_date" 
            name="payment_date" 
            class="form-control" 
            value="<?php echo date('Y-m-d'); ?>" 
            required
          >
        </div>

        <div class="form-group" style="margin-bottom: 1.75rem;">
          <label for="notes" class="form-label">
            Internal Cashier / Audit Remarks
          </label>
          <textarea 
            id="notes" 
            name="notes" 
            class="form-control" 
            rows="2" 
            placeholder="e.g. Received full settlement at front desk counter"
          ></textarea>
        </div>

        <div style="display: flex; gap: 1rem; align-items: center;">
          <button type="submit" class="btn btn-primary">
            Confirm &amp; Record Payment →
          </button>
          <a href="bill-details.php?id=<?php echo (int)$bill['id']; ?>" class="btn btn-secondary">
            Cancel
          </a>
        </div>

      </form>
    <?php endif; ?>

  </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
