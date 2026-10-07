<?php
/**
 * ============================================================================
 * MotoCare – Two-Wheeler Service & Mechanic Shop Management System
 * File: customer/bill-details.php
 * Stage 2: Customer Invoice View (Strict IDOR Protected)
 * ============================================================================
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/billing-helper.php';

requireCustomerLogin();
$customer = getCurrentCustomer();
$customerId = (int)$customer['id'];

$billId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$bookingCode = trim($_GET['code'] ?? '');

$bill = null;
$errorMsg = '';

if ($pdo) {
    try {
        // Enforce strict customer ownership check to completely prevent IDOR
        $query = "
            SELECT 
                b.*,
                bk.booking_code, bk.preferred_date, bk.preferred_time,
                c.full_name AS customer_name, c.phone AS customer_phone, c.email AS customer_email, c.address AS customer_address,
                v.brand, v.model, v.registration_number, v.vehicle_type,
                s.service_name, s.category AS service_category,
                m.full_name AS mechanic_name
            FROM bills b
            JOIN bookings bk ON b.booking_id = bk.id
            JOIN customers c ON b.customer_id = c.id
            JOIN vehicles v ON (b.vehicle_id = v.id OR bk.vehicle_id = v.id)
            JOIN services s ON bk.service_id = s.id
            LEFT JOIN mechanics m ON bk.mechanic_id = m.id
            WHERE (b.id = :id OR bk.booking_code = :code)
              AND b.customer_id = :cid
            LIMIT 1
        ";
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            ':id'   => $billId,
            ':code' => $bookingCode,
            ':cid'  => $customerId
        ]);
        $bill = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$bill) {
            $errorMsg = 'Invoice not found or you do not have permission to view this billing record.';
        } else {
            // Customer view: parts used without internal supplier or warehouse stock data (Section 10 & 20)
            $stmtParts = $pdo->prepare("
                SELECT srp.quantity, srp.unit_price, sp.part_name, sp.part_number
                FROM service_record_parts srp
                JOIN spare_parts sp ON srp.spare_part_id = sp.id
                WHERE srp.service_record_id = :srid
                ORDER BY srp.id ASC
            ");
            $stmtParts->execute([':srid' => (int)($bill['service_record_id'] ?? 0)]);
            $bill['parts'] = $stmtParts->fetchAll(PDO::FETCH_ASSOC);

            // Fetch payment history
            $stmtPay = $pdo->prepare("
                SELECT amount, payment_method, transaction_reference, payment_date 
                FROM payments 
                WHERE bill_id = :bid 
                ORDER BY payment_date ASC, id ASC
            ");
            $stmtPay->execute([':bid' => (int)$bill['id']]);
            $bill['payments'] = $stmtPay->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        $errorMsg = 'Database error: ' . $e->getMessage();
    }
} else {
    $errorMsg = 'Database offline.';
}

$pageTitle = $bill ? "Invoice #{$bill['invoice_number']} – MotoCare" : "Invoice Not Found";
$currentPage = 'bills.php';

include __DIR__ . '/../includes/customer-header.php';
?>

<style>
@media print {
  body {
    background: #ffffff !important;
    color: #111111 !important;
  }
  .site-header, .site-footer, .no-print, .btn, .nav-actions, .mobile-toggle {
    display: none !important;
  }
  .container {
    max-width: 100% !important;
    padding: 0 !important;
    margin: 0 !important;
  }
  .invoice-card {
    background: #ffffff !important;
    border: none !important;
    box-shadow: none !important;
    color: #111111 !important;
    padding: 0 !important;
  }
  .invoice-card h1, .invoice-card h2, .invoice-card h3, .invoice-card h4, .invoice-card p, .invoice-card td, .invoice-card th {
    color: #111111 !important;
  }
}
</style>

<div class="container" style="max-width: 860px; padding-top: 1rem; padding-bottom: 4rem;">

  <div class="no-print" style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
    <a href="bills.php" class="btn btn-outline btn-sm">
      ← Back to My Invoices
    </a>

    <?php if ($bill): ?>
      <div style="display: flex; gap: 0.75rem;">
        <button type="button" class="btn btn-secondary btn-sm" onclick="window.print();">
          🖨️ Print Slip
        </button>
        <a href="invoice-pdf.php?id=<?php echo (int)$bill['id']; ?>" class="btn btn-primary btn-sm" target="_blank">
          📄 Download PDF
        </a>
      </div>
    <?php endif; ?>
  </div>

  <?php if ($errorMsg): ?>
    <div class="form-alert form-alert-error" style="margin-bottom: 1.5rem;">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
      <span><?php echo htmlspecialchars($errorMsg); ?></span>
    </div>
    <div class="form-card" style="padding: 2.5rem; text-align: center;">
      <p style="color: var(--text-muted); margin-bottom: 1.5rem;">The requested billing receipt cannot be accessed.</p>
      <a href="bills.php" class="btn btn-primary">Return to Invoices List</a>
    </div>
  <?php else: ?>

    <div class="form-card invoice-card" style="padding: 3rem 2.5rem; background: var(--bg-card); border: 1px solid var(--border-card);">
      
      <!-- Top Branding -->
      <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid var(--border-subtle); padding-bottom: 2rem; margin-bottom: 2rem; flex-wrap: wrap; gap: 1.5rem;">
        <div>
          <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
            <span style="font-size: 1.85rem; font-weight: 900; color: #ffffff; letter-spacing: -0.02em; font-family: var(--font-heading);">
              MOTO<span style="color: var(--accent-orange);">CARE</span>
            </span>
            <span class="service-tag" style="background: rgba(255,107,0,0.15); color: var(--accent-orange); font-size: 0.7rem;">TAX INVOICE</span>
          </div>
          <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.5; margin: 0;">
            <strong><?php echo COMPANY_NAME; ?></strong><br>
            <?php echo COMPANY_ADDRESS; ?><br>
            GSTIN: <?php echo COMPANY_GSTIN; ?>
          </p>
        </div>

        <div style="text-align: right;">
          <?php 
            $pStatus = $bill['payment_status'];
            $statusBg = 'rgba(239,68,68,0.15)';
            $statusColor = '#f87171';
            if ($pStatus === 'Paid') {
                $statusBg = 'rgba(16,185,129,0.15)';
                $statusColor = 'var(--color-green)';
            } elseif ($pStatus === 'Partially Paid') {
                $statusBg = 'rgba(245,158,11,0.15)';
                $statusColor = '#fbbf24';
            }
          ?>
          <span class="badge" style="background: <?php echo $statusBg; ?>; color: <?php echo $statusColor; ?>; border: 1px solid <?php echo $statusColor; ?>; padding: 0.35rem 0.75rem; font-size: 0.85rem; font-weight: 700; margin-bottom: 0.5rem; display: inline-block;">
            Payment: <?php echo htmlspecialchars($pStatus); ?>
          </span>
          <h2 style="font-size: 1.5rem; color: #ffffff; margin: 0.25rem 0; font-family: monospace;">
            <?php echo htmlspecialchars($bill['invoice_number']); ?>
          </h2>
          <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0;">
            Date: <?php echo date('d M Y', strtotime($bill['bill_date'])); ?>
          </p>
          <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0.2rem 0 0;">
            Token: <strong style="color: var(--accent-orange); font-family: monospace;"><?php echo htmlspecialchars($bill['booking_code']); ?></strong>
          </p>
        </div>
      </div>

      <!-- Info Grid -->
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2.5rem; font-size: 0.9rem;">
        <div style="background: var(--bg-surface); padding: 1.25rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
          <span style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700;">Customer Details</span>
          <h4 style="font-size: 1.1rem; color: #ffffff; margin: 0.25rem 0;"><?php echo htmlspecialchars($bill['customer_name']); ?></h4>
          <p style="color: var(--text-muted); margin: 0;">Phone: <?php echo htmlspecialchars($bill['customer_phone']); ?></p>
          <p style="color: var(--text-muted); margin: 0;">Email: <?php echo htmlspecialchars($bill['customer_email']); ?></p>
        </div>

        <div style="background: var(--bg-surface); padding: 1.25rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
          <span style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700;">Vehicle Information</span>
          <h4 style="font-size: 1.1rem; color: #ffffff; margin: 0.25rem 0;"><?php echo htmlspecialchars($bill['brand'] . ' ' . $bill['model']); ?></h4>
          <p style="color: var(--accent-orange); font-weight: 700; margin: 0; font-family: monospace;"><?php echo htmlspecialchars($bill['registration_number']); ?></p>
          <p style="color: var(--text-muted); margin: 0;">Service Bay: MotoCare Main Hub</p>
        </div>
      </div>

      <!-- Itemized Table -->
      <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem; margin-bottom: 2rem;">
        <thead>
          <tr style="border-bottom: 2px solid var(--border-subtle); color: var(--text-muted); font-size: 0.78rem; text-transform: uppercase;">
            <th style="padding: 0.75rem 0.5rem;">Description</th>
            <th style="padding: 0.75rem 0.5rem; text-align: center;">Qty</th>
            <th style="padding: 0.75rem 0.5rem; text-align: right;">Rate</th>
            <th style="padding: 0.75rem 0.5rem; text-align: right;">Amount</th>
          </tr>
        </thead>
        <tbody>
          <!-- Service Charge -->
          <tr style="border-bottom: 1px solid var(--border-subtle);">
            <td style="padding: 1rem 0.5rem;">
              <div style="font-weight: 600; color: #ffffff;"><?php echo htmlspecialchars($bill['service_name']); ?> (Workshop Service &amp; Labor)</div>
            </td>
            <td style="padding: 1rem 0.5rem; text-align: center; color: #ffffff;">1</td>
            <td style="padding: 1rem 0.5rem; text-align: right; color: var(--text-secondary);"><?php echo formatCurrency($bill['service_charge']); ?></td>
            <td style="padding: 1rem 0.5rem; text-align: right; font-weight: 700; color: #ffffff;"><?php echo formatCurrency($bill['service_charge']); ?></td>
          </tr>

          <!-- Parts Used -->
          <?php if (!empty($bill['parts'])): ?>
            <?php foreach ($bill['parts'] as $p): 
              $pTot = (float)$p['unit_price'] * (int)$p['quantity'];
            ?>
              <tr style="border-bottom: 1px solid var(--border-subtle);">
                <td style="padding: 1rem 0.5rem;">
                  <div style="font-weight: 600; color: #ffffff;">🔧 <?php echo htmlspecialchars($p['part_name']); ?></div>
                  <div style="font-size: 0.78rem; color: var(--text-muted); font-family: monospace;">SKU: <?php echo htmlspecialchars($p['part_number']); ?></div>
                </td>
                <td style="padding: 1rem 0.5rem; text-align: center; color: #ffffff; font-weight: 700;"><?php echo (int)$p['quantity']; ?></td>
                <td style="padding: 1rem 0.5rem; text-align: right; color: var(--text-secondary);"><?php echo formatCurrency($p['unit_price']); ?></td>
                <td style="padding: 1rem 0.5rem; text-align: right; font-weight: 700; color: #ffffff;"><?php echo formatCurrency($pTot); ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>

      <!-- Totals & Taxes Breakdown -->
      <div style="display: flex; justify-content: flex-end; margin-bottom: 2rem;">
        <div style="width: 100%; max-width: 340px;">
          <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
            <tr>
              <td style="padding: 0.35rem 0; color: var(--text-muted);">Gross Subtotal:</td>
              <td style="padding: 0.35rem 0; text-align: right; color: #ffffff; font-weight: 600;"><?php echo formatCurrency($bill['subtotal']); ?></td>
            </tr>
            <?php if ((float)$bill['discount'] > 0): ?>
              <tr>
                <td style="padding: 0.35rem 0; color: var(--color-green);">Promotional Discount:</td>
                <td style="padding: 0.35rem 0; text-align: right; color: var(--color-green);">- <?php echo formatCurrency($bill['discount']); ?></td>
              </tr>
            <?php endif; ?>
            <tr>
              <td style="padding: 0.35rem 0; color: var(--text-muted);">Taxable Amount:</td>
              <td style="padding: 0.35rem 0; text-align: right; color: #ffffff;"><?php echo formatCurrency($bill['taxable_amount']); ?></td>
            </tr>
            <tr>
              <td style="padding: 0.35rem 0; color: var(--text-muted);">CGST (9%):</td>
              <td style="padding: 0.35rem 0; text-align: right; color: var(--text-secondary);"><?php echo formatCurrency($bill['cgst']); ?></td>
            </tr>
            <tr>
              <td style="padding: 0.35rem 0; color: var(--text-muted);">SGST (9%):</td>
              <td style="padding: 0.35rem 0; text-align: right; color: var(--text-secondary);"><?php echo formatCurrency($bill['sgst']); ?></td>
            </tr>
            <tr style="border-top: 2px solid var(--border-subtle); border-bottom: 2px solid var(--border-subtle);">
              <td style="padding: 0.75rem 0; font-size: 1.15rem; font-weight: 800; color: #ffffff;">Grand Total:</td>
              <td style="padding: 0.75rem 0; text-align: right; font-size: 1.25rem; font-weight: 900; color: var(--accent-orange);">
                <?php echo formatCurrency($bill['total_amount']); ?>
              </td>
            </tr>
            <tr>
              <td style="padding: 0.4rem 0; color: var(--color-green); font-weight: 600;">Amount Settled:</td>
              <td style="padding: 0.4rem 0; text-align: right; color: var(--color-green); font-weight: 700;"><?php echo formatCurrency($bill['amount_paid']); ?></td>
            </tr>
            <tr>
              <td style="padding: 0.4rem 0; color: <?php echo ((float)$bill['balance_due'] > 0) ? '#f87171' : 'var(--text-muted)'; ?>; font-weight: 700;">Balance Due:</td>
              <td style="padding: 0.4rem 0; text-align: right; color: <?php echo ((float)$bill['balance_due'] > 0) ? '#f87171' : 'var(--text-muted)'; ?>; font-weight: 800; font-size: 1.1rem;">
                <?php echo formatCurrency($bill['balance_due']); ?>
              </td>
            </tr>
          </table>
        </div>
      </div>

      <!-- Payment Receipts History -->
      <?php if (!empty($bill['payments'])): ?>
        <div style="background: var(--bg-surface); padding: 1.25rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); margin-bottom: 1.5rem;">
          <h4 style="font-size: 0.85rem; text-transform: uppercase; color: var(--text-muted); margin: 0 0 0.75rem 0;">Payment Transaction Acknowledgements</h4>
          <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.5rem; font-size: 0.85rem;">
            <?php foreach ($bill['payments'] as $py): ?>
              <li style="display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0.75rem; background: var(--bg-card); border-radius: var(--radius-sm); border: 1px solid var(--border-subtle);">
                <span>
                  💳 Paid via <strong><?php echo htmlspecialchars($py['payment_method']); ?></strong> on <?php echo date('d M Y', strtotime($py['payment_date'])); ?>
                  <?php if (!empty($py['transaction_reference'])): ?>
                    <span style="color: var(--text-muted); font-family: monospace;">(Ref: <?php echo htmlspecialchars($py['transaction_reference']); ?>)</span>
                  <?php endif; ?>
                </span>
                <strong style="color: var(--color-green);"><?php echo formatCurrency($py['amount']); ?></strong>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <p style="color: var(--text-muted); font-size: 0.8rem; margin: 0; text-align: center; border-top: 1px solid var(--border-subtle); padding-top: 1rem;">
        Thank you for servicing with MotoCare! For customer support, call <?php echo COMPANY_PHONE; ?>.
      </p>

    </div>
  <?php endif; ?>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
