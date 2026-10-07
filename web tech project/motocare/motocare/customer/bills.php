<?php
/**
 * ============================================================================
 * MotoCare – Two-Wheeler Service & Mechanic Shop Management System
 * File: customer/bills.php
 * Stage 2: Customer Invoices & Billing History (IDOR Protected)
 * ============================================================================
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/billing-helper.php';

requireCustomerLogin();
$customer = getCurrentCustomer();
$customerId = (int)$customer['id'];

$pageTitle = 'My Invoices & Service Bills – MotoCare';
$currentPage = 'bills.php';

$bills = [];
$errorMsg = '';

if ($pdo) {
    try {
        // Query bills strictly bound to current authenticated customer (Prevents IDOR)
        $stmt = $pdo->prepare("
            SELECT 
                b.*,
                bk.booking_code, bk.preferred_date,
                v.brand, v.model, v.registration_number,
                s.service_name
            FROM bills b
            JOIN bookings bk ON b.booking_id = bk.id
            JOIN vehicles v ON (b.vehicle_id = v.id OR bk.vehicle_id = v.id)
            JOIN services s ON bk.service_id = s.id
            WHERE b.customer_id = :cid
            ORDER BY b.id DESC
        ");
        $stmt->execute([':cid' => $customerId]);
        $bills = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $errorMsg = 'Database error: ' . $e->getMessage();
    }
} else {
    // Offline demo fallback
    $bills = [
        [
            'id' => 1,
            'invoice_number' => 'MC-INV-2026-1001',
            'booking_code' => 'MC-2026-1001',
            'service_name' => 'General Service Package',
            'brand' => 'Royal Enfield',
            'model' => 'Classic 350',
            'registration_number' => 'TN-07-AB-1234',
            'bill_date' => date('Y-m-d H:i:s'),
            'total_amount' => 590.00,
            'amount_paid' => 590.00,
            'balance_due' => 0.00,
            'payment_status' => 'Paid'
        ]
    ];
}

include __DIR__ . '/../includes/customer-header.php';
?>

<div class="container" style="max-width: 900px; padding-top: 1rem; padding-bottom: 3rem;">
  
  <div class="section-header" style="text-align: left; margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 1rem;">
    <div>
      <span class="service-tag" style="background: var(--accent-glow-subtle); color: var(--accent-orange); margin-bottom: 0.5rem; display: inline-block;">
        Customer Billing Portal
      </span>
      <h1 class="section-title" style="font-size: 2rem; margin-bottom: 0.25rem;">My Service Invoices</h1>
      <p class="section-description">Itemized invoices, GST receipts, and payment status records for your registered vehicles.</p>
    </div>

    <div>
      <a href="bookings.php" class="btn btn-outline btn-sm">
        ← Back to My Bookings
      </a>
    </div>
  </div>

  <?php if ($errorMsg): ?>
    <div class="form-alert form-alert-error" style="margin-bottom: 1.5rem;">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
      <span><?php echo htmlspecialchars($errorMsg); ?></span>
    </div>
  <?php endif; ?>

  <div class="form-card" style="padding: 0; overflow: hidden;">
    <div style="overflow-x: auto;">
      <table class="table" style="width: 100%; border-collapse: collapse; text-align: left; margin: 0; font-size: 0.88rem;">
        <thead>
          <tr style="border-bottom: 1px solid var(--border-subtle); background: var(--bg-surface); font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em;">
            <th style="padding: 1rem 1.25rem;">Invoice #</th>
            <th style="padding: 1rem 1.25rem;">Vehicle</th>
            <th style="padding: 1rem 1.25rem;">Service Package</th>
            <th style="padding: 1rem 1.25rem;">Date</th>
            <th style="padding: 1rem 1.25rem; text-align: right;">Total Amount</th>
            <th style="padding: 1rem 1.25rem; text-align: center;">Status</th>
            <th style="padding: 1rem 1.25rem; text-align: right;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($bills)): ?>
            <tr>
              <td colspan="7" style="padding: 3rem; text-align: center; color: var(--text-muted);">
                <p style="font-size: 1.1rem; margin-bottom: 0.5rem;">No invoices generated yet.</p>
                <small>Invoices are generated upon completion of workshop service.</small>
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
              <tr style="border-bottom: 1px solid var(--border-subtle);">
                
                <td style="padding: 1rem 1.25rem;">
                  <a href="bill-details.php?id=<?php echo (int)$bill['id']; ?>" style="font-family: monospace; font-weight: 700; color: #ffffff; text-decoration: none;">
                    <?php echo htmlspecialchars($bill['invoice_number']); ?>
                  </a>
                  <div style="font-size: 0.75rem; color: var(--text-muted); font-family: monospace;">
                    #<?php echo htmlspecialchars($bill['booking_code']); ?>
                  </div>
                </td>

                <td style="padding: 1rem 1.25rem;">
                  <div style="color: #ffffff; font-weight: 600;">
                    <?php echo htmlspecialchars($bill['brand'] . ' ' . $bill['model']); ?>
                  </div>
                  <div style="font-size: 0.78rem; color: var(--accent-orange); font-family: monospace;">
                    <?php echo htmlspecialchars($bill['registration_number']); ?>
                  </div>
                </td>

                <td style="padding: 1rem 1.25rem; color: var(--text-secondary);">
                  <?php echo htmlspecialchars($bill['service_name']); ?>
                </td>

                <td style="padding: 1rem 1.25rem; color: var(--text-muted); font-size: 0.85rem;">
                  <?php echo date('d M Y', strtotime($bill['bill_date'])); ?>
                </td>

                <td style="padding: 1rem 1.25rem; text-align: right; font-weight: 700; color: #ffffff;">
                  <?php echo formatCurrency($bill['total_amount']); ?>
                  <?php if ((float)$bill['balance_due'] > 0): ?>
                    <div style="font-size: 0.75rem; color: #f87171; font-weight: normal;">
                      Due: <?php echo formatCurrency($bill['balance_due']); ?>
                    </div>
                  <?php endif; ?>
                </td>

                <td style="padding: 1rem 1.25rem; text-align: center;">
                  <span class="badge" style="<?php echo $statusBadge; ?> font-size: 0.75rem;">
                    <?php echo htmlspecialchars($pStatus); ?>
                  </span>
                </td>

                <td style="padding: 1rem 1.25rem; text-align: right;">
                  <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                    <a href="bill-details.php?id=<?php echo (int)$bill['id']; ?>" class="btn btn-outline btn-sm">
                      View
                    </a>
                    <a href="invoice-pdf.php?id=<?php echo (int)$bill['id']; ?>" class="btn btn-secondary btn-sm" target="_blank">
                      PDF
                    </a>
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
