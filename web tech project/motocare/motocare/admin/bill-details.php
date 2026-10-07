<?php
/**
 * ============================================================================
 * MotoCare – Two-Wheeler Service & Mechanic Shop Management System
 * File: admin/bill-details.php
 * Stage 2: Tax Invoice View, Itemized Breakdown & Payment Ledger
 * ============================================================================
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/billing-helper.php';

requireAdminLogin();

$billId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$bookingCode = trim($_GET['code'] ?? '');

$bill = null;
$errorMsg = trim($_GET['err'] ?? '');
$successMsg = trim($_GET['msg'] ?? '');

if ($pdo) {
    if ($billId > 0) {
        $bill = fetchFullBillDetails($pdo, $billId);
    } elseif (!empty($bookingCode)) {
        // Locate by booking code
        $stmtB = $pdo->prepare("SELECT id FROM bills WHERE booking_id = (SELECT id FROM bookings WHERE booking_code = :code LIMIT 1)");
        $stmtB->execute([':code' => $bookingCode]);
        $foundId = (int)$stmtB->fetchColumn();
        if ($foundId > 0) {
            $bill = fetchFullBillDetails($pdo, $foundId);
        }
    }
}

if (!$bill && empty($errorMsg)) {
    $errorMsg = 'Invoice record not found in database.';
}

$pageTitle = $bill ? "Invoice #{$bill['invoice_number']} – MotoCare Admin" : "Invoice Not Found";
$currentPage = 'bills.php';

include __DIR__ . '/../includes/admin-header.php';
?>

<style>
/* Print Styling (Section 17 Requirements) */
@media print {
  body {
    background: #ffffff !important;
    color: #111111 !important;
  }
  .site-header, .admin-subnav, .site-footer, .no-print, .btn, .nav-actions, .mobile-toggle {
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
  .invoice-card table {
    border-color: #dddddd !important;
  }
  .invoice-card tr {
    border-color: #e5e5e5 !important;
  }
  .print-only {
    display: block !important;
  }
}
</style>

<div class="container" style="max-width: 900px; padding-top: 1rem; padding-bottom: 4rem;">

  <!-- Back navigation and Actions Bar (Screen only) -->
  <div class="no-print" style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
    <a href="bills.php" class="btn btn-outline btn-sm">
      ← Back to Invoices Ledger
    </a>

    <?php if ($bill): ?>
      <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
        <button type="button" class="btn btn-secondary btn-sm" onclick="window.print();">
          🖨️ Print Invoice
        </button>
        <a href="invoice-pdf.php?id=<?php echo (int)$bill['id']; ?>" class="btn btn-outline btn-sm" target="_blank">
          📄 Download PDF
        </a>
        <?php if ((float)$bill['balance_due'] > 0): ?>
          <a href="record-payment.php?bill_id=<?php echo (int)$bill['id']; ?>" class="btn btn-primary btn-sm">
            💳 Record Payment →
          </a>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>

  <?php if ($successMsg): ?>
    <div class="form-alert form-alert-success no-print" style="margin-bottom: 1.5rem;">
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

  <?php if ($bill): ?>
    <div class="form-card invoice-card" style="padding: 3rem 2.5rem; background: var(--bg-card); border: 1px solid var(--border-card);">
      
      <!-- Top Invoice Branding Strip -->
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
            Phone: <?php echo COMPANY_PHONE; ?> &bull; Email: <?php echo COMPANY_EMAIL; ?><br>
            <strong>GSTIN: <?php echo COMPANY_GSTIN; ?></strong>
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
          <span class="badge" style="background: <?php echo $statusBg; ?>; color: <?php echo $statusColor; ?>; border: 1px solid <?php echo $statusColor; ?>; padding: 0.4rem 0.85rem; font-size: 0.85rem; font-weight: 700; margin-bottom: 0.5rem; display: inline-block;">
            ● Payment: <?php echo htmlspecialchars($pStatus); ?>
          </span>
          <h2 style="font-size: 1.6rem; color: #ffffff; margin: 0.25rem 0; font-family: monospace;">
            <?php echo htmlspecialchars($bill['invoice_number']); ?>
          </h2>
          <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0;">
            Invoice Date: <strong style="color: #ffffff;"><?php echo date('d M Y, h:i A', strtotime($bill['bill_date'])); ?></strong>
          </p>
          <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0.2rem 0 0;">
            Booking Token: <strong style="color: var(--accent-orange); font-family: monospace;"><?php echo htmlspecialchars($bill['booking_code']); ?></strong>
          </p>
        </div>
      </div>

      <!-- Customer, Vehicle & Workshop Metadata Grid -->
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.75rem; margin-bottom: 2.5rem; font-size: 0.9rem;">
        
        <!-- 1. Customer Details -->
        <div style="background: var(--bg-surface); padding: 1.25rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
          <span style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700; letter-spacing: 0.05em; display: block; margin-bottom: 0.5rem;">
            Billed To (Customer)
          </span>
          <h4 style="font-size: 1.15rem; color: #ffffff; margin: 0 0 0.35rem 0; font-weight: 700;">
            <?php echo htmlspecialchars($bill['customer_name']); ?>
          </h4>
          <p style="color: var(--text-secondary); margin: 0 0 0.2rem 0; font-size: 0.85rem;">
            Phone: <strong style="color: #ffffff;"><?php echo htmlspecialchars($bill['customer_phone']); ?></strong>
          </p>
          <p style="color: var(--text-secondary); margin: 0; font-size: 0.85rem;">
            Email: <?php echo htmlspecialchars($bill['customer_email']); ?>
          </p>
          <?php if (!empty($bill['customer_address'])): ?>
            <p style="color: var(--text-muted); margin: 0.35rem 0 0; font-size: 0.8rem;">
              <?php echo htmlspecialchars($bill['customer_address']); ?>
            </p>
          <?php endif; ?>
        </div>

        <!-- 2. Vehicle Information -->
        <div style="background: var(--bg-surface); padding: 1.25rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
          <span style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700; letter-spacing: 0.05em; display: block; margin-bottom: 0.5rem;">
            Vehicle Serviced
          </span>
          <h4 style="font-size: 1.15rem; color: #ffffff; margin: 0 0 0.35rem 0; font-weight: 700;">
            <?php echo htmlspecialchars($bill['brand'] . ' ' . $bill['model']); ?>
          </h4>
          <div style="font-family: monospace; font-size: 1rem; color: var(--accent-orange); font-weight: 800; margin-bottom: 0.2rem;">
            <?php echo htmlspecialchars($bill['registration_number']); ?>
          </div>
          <p style="color: var(--text-muted); margin: 0; font-size: 0.85rem;">
            Category: <strong style="color: #ffffff;"><?php echo htmlspecialchars($bill['vehicle_type']); ?></strong>
          </p>
        </div>

        <!-- 3. Service & Workshop Details -->
        <div style="background: var(--bg-surface); padding: 1.25rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
          <span style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700; letter-spacing: 0.05em; display: block; margin-bottom: 0.5rem;">
            Workshop Execution
          </span>
          <h4 style="font-size: 1.15rem; color: #ffffff; margin: 0 0 0.35rem 0; font-weight: 700;">
            <?php echo htmlspecialchars($bill['service_name']); ?>
          </h4>
          <p style="color: var(--text-secondary); margin: 0 0 0.2rem 0; font-size: 0.85rem;">
            Technician: <strong style="color: #ffffff;"><?php echo htmlspecialchars($bill['mechanic_name'] ?? 'Senior Mechanic'); ?></strong>
          </p>
          <p style="color: var(--text-muted); margin: 0; font-size: 0.85rem;">
            Labor Logged: <?php echo htmlspecialchars($bill['labor_hours'] ?? '0.00'); ?> hrs &bull; Job Card #<?php echo (int)($bill['record_id'] ?? 0); ?>
          </p>
        </div>

      </div>

      <!-- Itemized Billing Table (Labor + Spare Parts) -->
      <div style="margin-bottom: 2rem;">
        <h4 style="font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); margin-bottom: 0.75rem;">
          Itemized Services &amp; Replaced Parts
        </h4>

        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
          <thead>
            <tr style="border-bottom: 2px solid var(--border-subtle); color: var(--text-muted); font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.05em;">
              <th style="padding: 0.75rem 0.5rem;">#</th>
              <th style="padding: 0.75rem 0.5rem;">Item Description</th>
              <th style="padding: 0.75rem 0.5rem; text-align: center;">HSN / SKU</th>
              <th style="padding: 0.75rem 0.5rem; text-align: center;">Qty</th>
              <th style="padding: 0.75rem 0.5rem; text-align: right;">Rate</th>
              <th style="padding: 0.75rem 0.5rem; text-align: right;">Amount</th>
            </tr>
          </thead>
          <tbody>
            
            <!-- 1. Labor & Service Package -->
            <tr style="border-bottom: 1px solid var(--border-subtle);">
              <td style="padding: 1rem 0.5rem; color: var(--text-muted);">1</td>
              <td style="padding: 1rem 0.5rem;">
                <div style="font-weight: 700; color: #ffffff;">
                  <?php echo htmlspecialchars($bill['service_name']); ?> (Workshop Labor &amp; Service Charge)
                </div>
                <div style="font-size: 0.8rem; color: var(--text-muted);">
                  Category: <?php echo htmlspecialchars($bill['service_category']); ?> &bull; Diagnostic inspection &amp; service bay overhead
                </div>
              </td>
              <td style="padding: 1rem 0.5rem; text-align: center; font-family: monospace; color: var(--text-muted);">
                998729
              </td>
              <td style="padding: 1rem 0.5rem; text-align: center; color: #ffffff; font-weight: 600;">
                1
              </td>
              <td style="padding: 1rem 0.5rem; text-align: right; color: var(--text-secondary);">
                <?php echo formatCurrency($bill['service_charge']); ?>
              </td>
              <td style="padding: 1rem 0.5rem; text-align: right; font-weight: 700; color: #ffffff;">
                <?php echo formatCurrency($bill['service_charge']); ?>
              </td>
            </tr>

            <!-- 2. Spare Parts Used Line Items -->
            <?php 
              $itemIndex = 2;
              if (!empty($bill['parts'])):
                foreach ($bill['parts'] as $part):
                  $pTotal = (float)$part['unit_price'] * (int)$part['quantity'];
            ?>
              <tr style="border-bottom: 1px solid var(--border-subtle);">
                <td style="padding: 1rem 0.5rem; color: var(--text-muted);"><?php echo $itemIndex++; ?></td>
                <td style="padding: 1rem 0.5rem;">
                  <div style="font-weight: 600; color: #ffffff;">
                    🔧 <?php echo htmlspecialchars($part['part_name']); ?>
                  </div>
                  <div style="font-size: 0.78rem; color: var(--text-muted);">
                    Brand: <?php echo htmlspecialchars($part['part_brand'] ?? 'OEM'); ?> &bull; <?php echo htmlspecialchars($part['category']); ?>
                  </div>
                </td>
                <td style="padding: 1rem 0.5rem; text-align: center; font-family: monospace; color: var(--accent-orange); font-size: 0.82rem;">
                  <?php echo htmlspecialchars($part['part_number']); ?>
                </td>
                <td style="padding: 1rem 0.5rem; text-align: center; color: #ffffff; font-weight: 700;">
                  <?php echo (int)$part['quantity']; ?>
                </td>
                <td style="padding: 1rem 0.5rem; text-align: right; color: var(--text-secondary);">
                  <?php echo formatCurrency($part['unit_price']); ?>
                </td>
                <td style="padding: 1rem 0.5rem; text-align: right; font-weight: 700; color: #ffffff;">
                  <?php echo formatCurrency($pTotal); ?>
                </td>
              </tr>
            <?php 
                endforeach;
              endif; 
            ?>

          </tbody>
        </table>
      </div>

      <!-- Financial Calculation & Tax Summary (Sections 2, 3, 7) -->
      <div style="display: flex; justify-content: flex-end; margin-bottom: 2.5rem;">
        <div style="width: 100%; max-width: 360px;">
          <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
            <tr>
              <td style="padding: 0.4rem 0; color: var(--text-muted);">Service/Labor Charge:</td>
              <td style="padding: 0.4rem 0; text-align: right; color: #ffffff;"><?php echo formatCurrency($bill['service_charge']); ?></td>
            </tr>
            <tr>
              <td style="padding: 0.4rem 0; color: var(--text-muted);">Spare Parts Subtotal:</td>
              <td style="padding: 0.4rem 0; text-align: right; color: #ffffff;"><?php echo formatCurrency($bill['parts_subtotal']); ?></td>
            </tr>
            <tr style="border-top: 1px solid var(--border-subtle);">
              <td style="padding: 0.5rem 0; color: var(--text-secondary); font-weight: 600;">Gross Subtotal:</td>
              <td style="padding: 0.5rem 0; text-align: right; color: #ffffff; font-weight: 600;"><?php echo formatCurrency($bill['subtotal']); ?></td>
            </tr>
            <?php if ((float)$bill['discount'] > 0): ?>
              <tr>
                <td style="padding: 0.4rem 0; color: var(--color-green);">Promotional Discount:</td>
                <td style="padding: 0.4rem 0; text-align: right; color: var(--color-green);">- <?php echo formatCurrency($bill['discount']); ?></td>
              </tr>
            <?php endif; ?>
            <tr>
              <td style="padding: 0.4rem 0; color: var(--text-muted);">Taxable Value:</td>
              <td style="padding: 0.4rem 0; text-align: right; color: #ffffff;"><?php echo formatCurrency($bill['taxable_amount']); ?></td>
            </tr>
            <tr>
              <td style="padding: 0.4rem 0; color: var(--text-muted);">CGST (<?php echo number_format($bill['gst_rate'] / 2, 1); ?>%):</td>
              <td style="padding: 0.4rem 0; text-align: right; color: var(--text-secondary);"><?php echo formatCurrency($bill['cgst']); ?></td>
            </tr>
            <tr>
              <td style="padding: 0.4rem 0; color: var(--text-muted);">SGST (<?php echo number_format($bill['gst_rate'] / 2, 1); ?>%):</td>
              <td style="padding: 0.4rem 0; text-align: right; color: var(--text-secondary);"><?php echo formatCurrency($bill['sgst']); ?></td>
            </tr>
            <tr style="border-top: 2px solid var(--border-subtle); border-bottom: 2px solid var(--border-subtle);">
              <td style="padding: 0.75rem 0; color: #ffffff; font-size: 1.15rem; font-weight: 800; font-family: var(--font-heading);">Grand Total:</td>
              <td style="padding: 0.75rem 0; text-align: right; color: var(--accent-orange); font-size: 1.3rem; font-weight: 900; font-family: var(--font-heading);">
                <?php echo formatCurrency($bill['total_amount']); ?>
              </td>
            </tr>
            <tr>
              <td style="padding: 0.5rem 0; color: var(--color-green); font-weight: 600;">Amount Paid:</td>
              <td style="padding: 0.5rem 0; text-align: right; color: var(--color-green); font-weight: 700;"><?php echo formatCurrency($bill['amount_paid']); ?></td>
            </tr>
            <tr>
              <td style="padding: 0.5rem 0; color: <?php echo ((float)$bill['balance_due'] > 0) ? '#f87171' : 'var(--text-muted)'; ?>; font-weight: 700;">Balance Due:</td>
              <td style="padding: 0.5rem 0; text-align: right; color: <?php echo ((float)$bill['balance_due'] > 0) ? '#f87171' : 'var(--text-muted)'; ?>; font-weight: 800; font-size: 1.1rem;">
                <?php echo formatCurrency($bill['balance_due']); ?>
              </td>
            </tr>
          </table>
        </div>
      </div>

      <!-- Payment History Ledger (Section 8) -->
      <div style="background: var(--bg-surface); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); margin-bottom: 2rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px solid var(--border-subtle); padding-bottom: 0.5rem;">
          <h4 style="font-size: 1rem; color: #ffffff; margin: 0;">Payment Transaction History</h4>
          <?php if ((float)$bill['balance_due'] > 0): ?>
            <a href="record-payment.php?bill_id=<?php echo (int)$bill['id']; ?>" class="btn btn-primary btn-sm no-print">
              + Record Payment
            </a>
          <?php endif; ?>
        </div>

        <?php if (!empty($bill['payments'])): ?>
          <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem; text-align: left;">
            <thead>
              <tr style="color: var(--text-muted); border-bottom: 1px solid var(--border-subtle); font-size: 0.75rem; text-transform: uppercase;">
                <th style="padding: 0.5rem;">Receipt #</th>
                <th style="padding: 0.5rem;">Date</th>
                <th style="padding: 0.5rem;">Method</th>
                <th style="padding: 0.5rem;">Reference</th>
                <th style="padding: 0.5rem; text-align: right;">Amount</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($bill['payments'] as $pay): ?>
                <tr style="border-bottom: 1px solid rgba(255,255,255,0.04);">
                  <td style="padding: 0.6rem 0.5rem; font-family: monospace; color: var(--text-muted);">#PAY-<?php echo (int)$pay['id']; ?></td>
                  <td style="padding: 0.6rem 0.5rem; color: #ffffff;"><?php echo date('d M Y', strtotime($pay['payment_date'])); ?></td>
                  <td style="padding: 0.6rem 0.5rem;">
                    <span class="badge" style="background: rgba(56,189,248,0.1); color: #38bdf8;">
                      <?php echo htmlspecialchars($pay['payment_method']); ?>
                    </span>
                  </td>
                  <td style="padding: 0.6rem 0.5rem; color: var(--text-secondary); font-family: monospace;">
                    <?php echo !empty($pay['transaction_reference']) ? htmlspecialchars($pay['transaction_reference']) : '—'; ?>
                  </td>
                  <td style="padding: 0.6rem 0.5rem; text-align: right; color: var(--color-green); font-weight: 700;">
                    <?php echo formatCurrency($pay['amount']); ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0; font-style: italic;">
            No payment transactions recorded yet. Balance outstanding: <?php echo formatCurrency($bill['balance_due']); ?>.
          </p>
        <?php endif; ?>
      </div>

      <!-- Footer Note & Signatures -->
      <div style="border-top: 1px solid var(--border-subtle); padding-top: 1.5rem; display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 1.5rem; font-size: 0.8rem; color: var(--text-muted);">
        <div>
          <p style="margin: 0 0 0.25rem 0;"><strong>Terms &amp; Warranty Conditions:</strong></p>
          <ul style="margin: 0; padding-left: 1.2rem; line-height: 1.5;">
            <li>Service warranty valid for 30 days or 1,000 km from invoice date.</li>
            <li>Electrical parts and consumables carry manufacturer warranty terms only.</li>
            <li>All disputes subject to Chennai jurisdiction.</li>
          </ul>
        </div>
        <div style="text-align: right; min-width: 200px;">
          <div style="margin-bottom: 2.5rem; color: var(--text-muted);">For MotoCare Workshop</div>
          <div style="border-top: 1px dashed var(--border-subtle); padding-top: 0.35rem; font-weight: 600; color: #ffffff;">
            Authorized Signatory
          </div>
        </div>
      </div>

    </div>
  <?php endif; ?>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
