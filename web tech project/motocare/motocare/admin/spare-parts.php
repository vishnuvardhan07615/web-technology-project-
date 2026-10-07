<?php
/**
 * ============================================================================
 * MotoCare – Two-Wheeler Service & Mechanic Shop Management System
 * File: admin/spare-parts.php
 * Stage 2: Spare Parts Inventory Management, Stock Alerts & Adjustments
 * ============================================================================
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

requireAdminLogin();

$pageTitle = 'Inventory & Spare Parts – Admin Control – MotoCare';
$currentPage = 'spare-parts.php';

$successMsg = '';
$errorMsg = '';

// Handle Stock Adjustment Form (Section 16 & 17)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'adjust_stock') {
    $partId = isset($_POST['part_id']) ? (int)$_POST['part_id'] : 0;
    $qtyChange = isset($_POST['qty_change']) ? (int)$_POST['qty_change'] : (isset($_POST['adjustment_quantity']) ? (int)$_POST['adjustment_quantity'] : 0);
    $reason = trim($_POST['reason'] ?? 'Manual Stock Adjustment');

    if ($partId <= 0) {
        $errorMsg = 'Please select a valid spare part.';
    } elseif ($qtyChange === 0) {
        $errorMsg = 'Adjustment quantity must be non-zero (positive to add stock, negative to deduct).';
    } elseif (empty($reason)) {
        $errorMsg = 'Please provide an adjustment reason for audit logging.';
    } else {
        if ($pdo) {
            try {
                $pdo->beginTransaction();

                // Lock row for concurrency safety
                $stmtCheck = $pdo->prepare("SELECT id, part_name, stock_quantity FROM spare_parts WHERE id = :id FOR UPDATE");
                $stmtCheck->execute([':id' => $partId]);
                $part = $stmtCheck->fetch();

                if (!$part) {
                    $pdo->rollBack();
                    $errorMsg = 'Spare part not found in inventory.';
                } else {
                    $currentStock = (int)$part['stock_quantity'];
                    $newStock = $currentStock + $qtyChange;

                    // Section 16 & 21: Stock must NEVER become negative
                    if ($newStock < 0) {
                        $pdo->rollBack();
                        $deduction = abs($qtyChange);
                        $errorMsg = "Adjustment rejected: Final stock cannot be negative. Current stock for '{$part['part_name']}' is {$currentStock}, but attempted deduction was {$deduction}.";
                    } else {
                        // 1. Update spare_parts stock
                        $stmtUpd = $pdo->prepare("UPDATE spare_parts SET stock_quantity = :new_stock WHERE id = :id");
                        $stmtUpd->execute([':new_stock' => $newStock, ':id' => $partId]);

                        // 2. Record inventory transaction audit trail (Section 17)
                        $txType = ($qtyChange > 0) ? 'PURCHASE' : 'ADJUSTMENT';
                        $stmtTx = $pdo->prepare("
                            INSERT INTO inventory_transactions (spare_part_id, transaction_type, quantity, reference_id, reason, created_at)
                            VALUES (:pid, :txtype, :qty, NULL, :reason, NOW())
                        ");
                        $stmtTx->execute([
                            ':pid'    => $partId,
                            ':txtype' => $txType,
                            ':qty'    => $qtyChange,
                            ':reason' => $reason
                        ]);

                        $pdo->commit();
                        $actionWord = ($qtyChange > 0) ? "added to" : "deducted from";
                        $absQty = abs($qtyChange);
                        $successMsg = "Successfully {$actionWord} inventory: {$part['part_name']} updated to {$newStock} units (was {$currentStock}).";
                    }
                }
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $errorMsg = "Database transaction error: " . $e->getMessage();
            }
        } else {
            $successMsg = "Demo: Stock adjusted by {$qtyChange} units (Mock mode).";
        }
    }
}

// Fetch dynamic inventory metrics (Section 5)
$metrics = [
    'total_parts'   => 0,
    'in_stock'      => 0,
    'low_stock'     => 0,
    'out_of_stock'  => 0,
    'total_value'   => 0.00
];

$spareParts = [];
$lowStockAlerts = [];
$outOfStockAlerts = [];
$recentTransactions = [];

if ($pdo) {
    try {
        // Query all parts sorted by stock severity then name
        $stmt = $pdo->query("
            SELECT * FROM spare_parts 
            ORDER BY 
                CASE 
                    WHEN stock_quantity = 0 THEN 1 
                    WHEN stock_quantity <= minimum_stock THEN 2 
                    ELSE 3 
                END, 
                stock_quantity ASC, 
                part_name ASC
        ");
        $spareParts = $stmt->fetchAll();

        // Calculate dynamic metrics & build alert lists
        $metrics['total_parts'] = count($spareParts);
        foreach ($spareParts as $p) {
            $qty = (int)$p['stock_quantity'];
            $min = (int)$p['minimum_stock'];
            $price = (float)$p['price'];
            $metrics['total_value'] += ($qty * $price);

            if ($qty === 0) {
                $metrics['out_of_stock']++;
                $outOfStockAlerts[] = $p;
            } elseif ($qty <= $min) {
                $metrics['low_stock']++;
                $lowStockAlerts[] = $p;
            } else {
                $metrics['in_stock']++;
            }
        }

        // Fetch recent inventory audit log
        $stmtTx = $pdo->query("
            SELECT it.*, sp.part_name, sp.part_number 
            FROM inventory_transactions it
            JOIN spare_parts sp ON it.spare_part_id = sp.id
            ORDER BY it.id DESC
            LIMIT 8
        ");
        $recentTransactions = $stmtTx->fetchAll();

    } catch (PDOException $e) {
        $errorMsg = "Could not load spare parts: " . $e->getMessage();
    }
} else {
    // Offline viva mock data
    $spareParts = [
        ['id' => 1, 'part_name' => 'Fully Synthetic 15W-50 Engine Oil (1L)', 'part_number' => 'OIL-SYN-15W50', 'brand' => 'Motul', 'category' => 'Engine & Lubricants', 'price' => 850.00, 'stock_quantity' => 45, 'minimum_stock' => 10, 'status' => 'Active', 'supplier' => 'Motul Auto Distributors'],
        ['id' => 3, 'part_name' => 'Sintered Front Disc Brake Pad Set', 'part_number' => 'BRK-PAD-DS01', 'brand' => 'Bosch', 'category' => 'Brakes & Braking', 'price' => 380.00, 'stock_quantity' => 2, 'minimum_stock' => 8, 'status' => 'Active', 'supplier' => 'Bosch India Spares'],
        ['id' => 8, 'part_name' => 'High-Flow Engine Air Filter', 'part_number' => 'FLT-AIR-UNIV', 'brand' => 'Purolator', 'category' => 'Filters', 'price' => 220.00, 'stock_quantity' => 0, 'minimum_stock' => 10, 'status' => 'Active', 'supplier' => 'Purolator Filters']
    ];
    $metrics = [
        'total_parts'  => 3,
        'in_stock'     => 1,
        'low_stock'    => 1,
        'out_of_stock' => 1,
        'total_value'  => 39010.00
    ];
    $lowStockAlerts = [$spareParts[1]];
    $outOfStockAlerts = [$spareParts[2]];
}

include __DIR__ . '/../includes/admin-header.php';
?>

<div class="container" style="padding-top: 1rem; padding-bottom: 3rem;">
  
  <!-- Header Title Strip -->
  <div class="section-header" style="text-align: left; margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 1rem;">
    <div>
      <span class="service-tag" style="background: rgba(255,107,0,0.15); color: var(--accent-orange); border-color: rgba(255,107,0,0.3); margin-bottom: 0.5rem; display: inline-block;">
        Workshop Warehouse Control
      </span>
      <h1 class="section-title" style="font-size: 2rem; margin-bottom: 0.25rem;">Spare Parts &amp; Inventory Management</h1>
      <p class="section-description">Real-time stock ledger, unit cost snapshots, low-stock alerts, and restocking adjustments.</p>
    </div>
    
    <div>
      <button type="button" class="btn btn-primary" onclick="document.getElementById('adjustModalContainer').scrollIntoView({behavior: 'smooth'});">
        + Adjust Stock / Restock →
      </button>
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

  <!-- Low-Stock & Out-of-Stock Alert Banners (Section 6) -->
  <?php if (!empty($outOfStockAlerts) || !empty($lowStockAlerts)): ?>
    <div style="display: flex; flex-direction: column; gap: 1rem; margin-bottom: 2rem;">
      
      <!-- Out of Stock Alert Banner -->
      <?php if (!empty($outOfStockAlerts)): ?>
        <div style="background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.4); border-left: 5px solid #ef4444; border-radius: var(--radius-md); padding: 1.25rem 1.5rem; display: flex; align-items: flex-start; gap: 1rem;">
          <div style="font-size: 1.5rem; line-height: 1;">🚫</div>
          <div style="flex: 1;">
            <strong style="color: #f87171; font-size: 1rem; display: block; margin-bottom: 0.35rem;">
              Critical Out of Stock Warning (<?php echo count($outOfStockAlerts); ?> items depleted)
            </strong>
            <div style="color: var(--text-main); font-size: 0.88rem; line-height: 1.5;">
              The following parts have reached 0 inventory and cannot be checked out on job cards:
              <div style="margin-top: 0.4rem; display: flex; flex-wrap: wrap; gap: 0.5rem;">
                <?php foreach ($outOfStockAlerts as $item): ?>
                  <span class="badge" style="background: rgba(239,68,68,0.25); color: #fca5a5; border: 1px solid rgba(239,68,68,0.5);">
                    <?php echo htmlspecialchars($item['part_name']); ?> (<?php echo htmlspecialchars($item['part_number']); ?>) — <strong>0 remaining</strong>
                  </span>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <!-- Low Stock Alert Banner -->
      <?php if (!empty($lowStockAlerts)): ?>
        <div style="background: rgba(245,158,11,0.12); border: 1px solid rgba(245,158,11,0.4); border-left: 5px solid #f59e0b; border-radius: var(--radius-md); padding: 1.25rem 1.5rem; display: flex; align-items: flex-start; gap: 1rem;">
          <div style="font-size: 1.5rem; line-height: 1;">⚠</div>
          <div style="flex: 1;">
            <strong style="color: #fbbf24; font-size: 1rem; display: block; margin-bottom: 0.35rem;">
              Low Stock Reorder Alert (<?php echo count($lowStockAlerts); ?> items near or below reorder threshold)
            </strong>
            <div style="color: var(--text-main); font-size: 0.88rem; line-height: 1.5;">
              Stock is low and requires procurement from distributors:
              <div style="margin-top: 0.4rem; display: flex; flex-wrap: wrap; gap: 0.5rem;">
                <?php foreach ($lowStockAlerts as $item): ?>
                  <span class="badge" style="background: rgba(245,158,11,0.2); color: #fde68a; border: 1px solid rgba(245,158,11,0.4);">
                    <?php echo htmlspecialchars($item['part_name']); ?> — <strong><?php echo (int)$item['stock_quantity']; ?> left</strong> (Reorder level: <?php echo (int)$item['minimum_stock']; ?>)
                  </span>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </div>
      <?php endif; ?>

    </div>
  <?php endif; ?>

  <!-- Dynamic Inventory Metrics Grid (Section 5) -->
  <div class="metrics-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.25rem; margin-bottom: 2.5rem;">
    
    <!-- 1. Total Parts -->
    <div class="dashboard-card" style="background: var(--bg-card); border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 1.4rem;">
      <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.4rem;">Total SKUs</div>
      <div style="font-size: 2.1rem; font-weight: 800; color: #ffffff; font-family: var(--font-heading); line-height: 1;">
        <?php echo $metrics['total_parts']; ?>
      </div>
      <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.4rem;">Registered Catalog Parts</div>
    </div>

    <!-- 2. In Stock -->
    <div class="dashboard-card" style="background: var(--bg-card); border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 1.4rem;">
      <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.4rem;">Parts In Stock</div>
      <div style="font-size: 2.1rem; font-weight: 800; color: #34d399; font-family: var(--font-heading); line-height: 1;">
        <?php echo $metrics['in_stock']; ?>
      </div>
      <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.4rem;">Healthy inventory levels</div>
    </div>

    <!-- 3. Low Stock -->
    <div class="dashboard-card" style="background: var(--bg-card); border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 1.4rem;">
      <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.4rem;">Low Stock Parts</div>
      <div style="font-size: 2.1rem; font-weight: 800; color: #fbbf24; font-family: var(--font-heading); line-height: 1;">
        <?php echo $metrics['low_stock']; ?>
      </div>
      <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.4rem;">At or below minimum threshold</div>
    </div>

    <!-- 4. Out of Stock -->
    <div class="dashboard-card" style="background: var(--bg-card); border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 1.4rem;">
      <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.4rem;">Out of Stock</div>
      <div style="font-size: 2.1rem; font-weight: 800; color: #f87171; font-family: var(--font-heading); line-height: 1;">
        <?php echo $metrics['out_of_stock']; ?>
      </div>
      <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.4rem;">Depleted inventory SKUs</div>
    </div>

    <!-- 5. Total Asset Value -->
    <div class="dashboard-card" style="background: var(--bg-card); border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 1.4rem;">
      <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.4rem;">Total Inventory Value</div>
      <div style="font-size: 1.6rem; font-weight: 800; color: var(--accent-orange); font-family: var(--font-heading); line-height: 1;">
        <?php echo formatCurrency($metrics['total_value']); ?>
      </div>
      <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.4rem;">Current warehouse valuation</div>
    </div>

  </div>

  <!-- Master Inventory Table (Section 4) -->
  <div class="dashboard-card" style="padding: 0; overflow: hidden; background: var(--bg-card); border-radius: var(--radius-lg); border: 1px solid var(--border-subtle); margin-bottom: 2.5rem;">
    <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border-subtle); display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02);">
      <h3 style="font-size: 1.15rem; color: #ffffff; margin: 0;">Workshop Spare Parts Ledger (<?php echo count($spareParts); ?> SKUs)</h3>
      <span class="badge" style="background: rgba(16,185,129,0.1); color: var(--color-green); border: 1px solid rgba(16,185,129,0.3);">
        Automatic Stock Integration
      </span>
    </div>

    <div style="overflow-x: auto;">
      <table class="data-table" style="width: 100%; border-collapse: collapse; text-align: left;">
        <thead>
          <tr style="border-bottom: 1px solid var(--border-subtle); background: var(--bg-surface); font-size: 0.82rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em;">
            <th style="padding: 0.9rem 1.25rem;">Part Name</th>
            <th style="padding: 0.9rem 1.25rem;">Part Number</th>
            <th style="padding: 0.9rem 1.25rem;">Category</th>
            <th style="padding: 0.9rem 1.25rem;">Available Stock</th>
            <th style="padding: 0.9rem 1.25rem;">Unit Price</th>
            <th style="padding: 0.9rem 1.25rem;">Reorder Level</th>
            <th style="padding: 0.9rem 1.25rem;">Status</th>
            <th style="padding: 0.9rem 1.25rem;">Stock Alert</th>
            <th style="padding: 0.9rem 1.25rem; text-align: right;">Action</th>
          </tr>
        </thead>
        <tbody style="font-size: 0.92rem;">
          <?php foreach ($spareParts as $part): 
            $stock = (int)$part['stock_quantity'];
            $reorder = (int)$part['minimum_stock'];

            // Section 4 Rules:
            // If stock_quantity = 0: OUT OF STOCK
            // Else if stock_quantity <= reorder_level: LOW STOCK
            // Else: IN STOCK
            if ($stock === 0) {
                $alertLabel = 'OUT OF STOCK';
                $alertBadge = 'background: rgba(239,68,68,0.2); color: #f87171; border: 1px solid rgba(239,68,68,0.4);';
            } elseif ($stock <= $reorder) {
                $alertLabel = 'LOW STOCK';
                $alertBadge = 'background: rgba(245,158,11,0.2); color: #fbbf24; border: 1px solid rgba(245,158,11,0.4);';
            } else {
                $alertLabel = 'IN STOCK';
                $alertBadge = 'background: rgba(16,185,129,0.15); color: #34d399; border: 1px solid rgba(16,185,129,0.3);';
            }
          ?>
            <tr class="inventory-row" style="border-bottom: 1px solid var(--border-subtle); <?php echo ($stock === 0) ? 'background: rgba(239,68,68,0.03);' : ''; ?>">
              
              <!-- 1. Part Name & Brand -->
              <td style="padding: 1rem 1.25rem;">
                <div style="font-weight: 600; color: #ffffff;"><?php echo htmlspecialchars($part['part_name']); ?></div>
                <div style="font-size: 0.78rem; color: var(--text-muted);">Brand: <?php echo htmlspecialchars($part['brand']); ?></div>
              </td>

              <!-- 2. Part Number -->
              <td style="padding: 1rem 1.25rem; font-family: monospace; font-weight: 700; color: var(--accent-orange);">
                <?php echo htmlspecialchars($part['part_number']); ?>
              </td>

              <!-- 3. Category -->
              <td style="padding: 1rem 1.25rem;">
                <span class="badge" style="background: rgba(255,255,255,0.06); color: var(--text-secondary); font-size: 0.75rem;">
                  <?php echo htmlspecialchars($part['category'] ?? 'General'); ?>
                </span>
              </td>

              <!-- 4. Available Stock -->
              <td style="padding: 1rem 1.25rem;">
                <span style="font-size: 1.15rem; font-weight: 800; font-family: monospace; color: <?php echo ($stock === 0) ? '#f87171' : (($stock <= $reorder) ? '#fbbf24' : '#34d399'); ?>;">
                  <?php echo $stock; ?>
                </span>
                <span style="font-size: 0.75rem; color: var(--text-muted);"> units</span>
              </td>

              <!-- 5. Unit Price -->
              <td style="padding: 1rem 1.25rem; font-weight: 700; color: #ffffff;">
                <?php echo formatCurrency($part['price']); ?>
              </td>

              <!-- 6. Reorder Level -->
              <td style="padding: 1rem 1.25rem; color: var(--text-muted); font-size: 0.85rem;">
                <?php echo $reorder; ?> units
              </td>

              <!-- 7. Status -->
              <td style="padding: 1rem 1.25rem;">
                <span class="badge" style="background: rgba(56,189,248,0.12); color: #38bdf8; border: 1px solid rgba(56,189,248,0.3); font-size: 0.75rem;">
                  <?php echo htmlspecialchars($part['status'] ?? 'Active'); ?>
                </span>
              </td>

              <!-- 8. Stock Alert (Section 4 Rule) -->
              <td style="padding: 1rem 1.25rem;">
                <span class="badge" style="<?php echo $alertBadge; ?> font-size: 0.78rem; font-weight: 700; padding: 0.25rem 0.6rem;">
                  <?php echo $alertLabel; ?>
                </span>
              </td>

              <!-- 9. Action Button -->
              <td style="padding: 1rem 1.25rem; text-align: right;">
                <button type="button" class="btn btn-outline btn-sm" 
                        onclick="prefillAdjustment(<?php echo (int)$part['id']; ?>, '<?php echo htmlspecialchars(addslashes($part['part_name'])); ?>', <?php echo $stock; ?>);"
                        style="padding: 0.35rem 0.75rem; font-size: 0.8rem;">
                  Adjust Stock ↻
                </button>
              </td>

            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Stock Adjustment Card & Recent Audit Log Grid (Section 16 & 17) -->
  <div style="display: grid; grid-template-columns: 1.1fr 0.9fr; gap: 2rem; align-items: start;" id="adjustModalContainer">
    
    <!-- Left Column: Admin Stock Adjustment Form -->
    <div class="form-card" style="padding: 2rem;">
      <div style="border-bottom: 1px solid var(--border-subtle); padding-bottom: 1rem; margin-bottom: 1.5rem;">
        <span class="service-tag" style="background: rgba(16,185,129,0.15); color: var(--color-green); margin-bottom: 0.5rem; display: inline-block;">
          Warehouse Procurement &amp; Inventory Write-Off
        </span>
        <h3 style="font-size: 1.35rem; color: #ffffff; margin: 0 0 0.25rem 0;">
          Adjust Stock Quantity
        </h3>
        <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0;">
          Add newly received shipments (+Qty) or write off damaged / missing inventory (-Qty).
        </p>
      </div>

      <form method="POST" action="spare-parts.php" id="stockAdjustmentForm">
        <input type="hidden" name="action" value="adjust_stock">

        <!-- 1. Select Spare Part -->
        <div class="form-group" style="margin-bottom: 1.25rem;">
          <label for="part_id" class="form-label">Select Spare Part <span class="required-dot">*</span></label>
          <select id="part_id" name="part_id" class="form-select" required onchange="updateSelectedPartHint(this);">
            <option value="" disabled selected>-- Select Part from Warehouse --</option>
            <?php foreach ($spareParts as $p): ?>
              <option value="<?php echo (int)$p['id']; ?>" data-stock="<?php echo (int)$p['stock_quantity']; ?>">
                <?php echo htmlspecialchars($p['part_name']); ?> (<?php echo htmlspecialchars($p['part_number']); ?>) - In Stock: <?php echo (int)$p['stock_quantity']; ?>
              </option>
            <?php endforeach; ?>
          </select>
          <div id="selectedStockHint" style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.35rem;">
            Please select a part to inspect current on-hand units.
          </div>
        </div>

        <!-- 2. Adjustment Quantity -->
        <div class="form-group" style="margin-bottom: 1.25rem;">
          <label for="qty_change" class="form-label">
            Adjustment Quantity <span class="required-dot">*</span>
            <small style="color: var(--text-muted); font-weight: normal;">(Positive for addition, negative for write-off)</small>
          </label>
          <input type="number" id="qty_change" name="qty_change" class="form-input" 
                 placeholder="e.g. 10 to restock or -2 for damaged stock" required>
        </div>

        <!-- 3. Audit Reason -->
        <div class="form-group" style="margin-bottom: 1.5rem;">
          <label for="reason" class="form-label">Audit Reason &amp; Reference <span class="required-dot">*</span></label>
          <input type="text" id="reason" name="reason" class="form-input" 
                 placeholder="e.g. New supplier delivery #INV-4412, Damaged during transit, or Physical count audit" required>
        </div>

        <button type="submit" class="btn btn-primary btn-block" style="padding: 0.9rem;">
          Save Stock Adjustment &amp; Log Audit Trail →
        </button>
      </form>
    </div>

    <!-- Right Column: Recent Inventory Transactions Log (Section 17) -->
    <div class="dashboard-card" style="background: var(--bg-card); border: 1px solid var(--border-subtle); border-radius: var(--radius-lg); padding: 1.75rem;">
      <div style="border-bottom: 1px solid var(--border-subtle); padding-bottom: 0.75rem; margin-bottom: 1.25rem; display: flex; justify-content: space-between; align-items: center;">
        <h3 style="font-size: 1.15rem; color: #ffffff; margin: 0;">Recent Inventory Audit Log</h3>
        <span class="badge" style="background: rgba(255,255,255,0.06); color: var(--text-muted); font-size: 0.75rem;">Audit Trail</span>
      </div>

      <?php if (empty($recentTransactions)): ?>
        <p style="color: var(--text-muted); font-size: 0.85rem; text-align: center; padding: 2rem 0;">
          No recorded inventory transactions yet.
        </p>
      <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
          <?php foreach ($recentTransactions as $tx): 
            $isPositive = ($tx['quantity'] > 0);
          ?>
            <div style="background: var(--bg-surface); padding: 0.85rem 1rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); display: flex; justify-content: space-between; align-items: center;">
              <div>
                <div style="font-size: 0.88rem; font-weight: 600; color: #ffffff;">
                  <?php echo htmlspecialchars($tx['part_name']); ?>
                </div>
                <div style="font-size: 0.75rem; color: var(--text-muted);">
                  Type: <strong style="color: var(--text-secondary);"><?php echo htmlspecialchars($tx['transaction_type']); ?></strong>
                  &bull; <?php echo htmlspecialchars($tx['reason'] ?? 'Standard logging'); ?>
                </div>
              </div>
              <div style="text-align: right;">
                <span style="font-family: monospace; font-weight: 700; font-size: 1rem; color: <?php echo $isPositive ? '#34d399' : '#f87171'; ?>;">
                  <?php echo $isPositive ? '+' . (int)$tx['quantity'] : (int)$tx['quantity']; ?>
                </span>
                <div style="font-size: 0.72rem; color: var(--text-muted);">
                  <?php echo date('d M, H:i', strtotime($tx['created_at'])); ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

  </div>

</div>

<script>
function prefillAdjustment(partId, partName, currentStock) {
  var selectElem = document.getElementById('part_id');
  if (selectElem) {
    selectElem.value = partId;
    updateSelectedPartHint(selectElem);
  }
  var container = document.getElementById('adjustModalContainer');
  if (container) {
    container.scrollIntoView({ behavior: 'smooth' });
  }
}

function updateSelectedPartHint(selectElem) {
  var selected = selectElem.options[selectElem.selectedIndex];
  var hint = document.getElementById('selectedStockHint');
  if (selected && selected.dataset.stock !== undefined) {
    hint.innerHTML = 'Current Available Stock: <strong style="color: var(--accent-orange);">' + selected.dataset.stock + ' units</strong>';
  } else {
    hint.innerHTML = 'Please select a part to inspect current on-hand units.';
  }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
