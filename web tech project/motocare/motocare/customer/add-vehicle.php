<?php
/**
 * ============================================================================
 * MotoCare – Two-Wheeler Service & Mechanic Shop Management System
 * File: customer/add-vehicle.php
 * Stage 2: Register New Two-Wheeler (Dynamic Customer Flow)
 * ============================================================================
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

requireCustomerLogin();
$customer = getCurrentCustomer();
$customerId = (int)$customer['id'];

$pageTitle = 'Register New Two-Wheeler – MotoCare';
$currentPage = 'add-vehicle.php';

$errorMsg = '';
$successMsg = '';

// Pre-fill form variables
$vehicleTypeVal = '';
$brandVal = '';
$modelVal = '';
$regNumberVal = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehicleTypeVal = trim($_POST['vehicle_type'] ?? '');
    $brandVal = trim($_POST['brand'] ?? '');
    $modelVal = trim($_POST['model'] ?? '');
    $regNumberVal = strtoupper(trim($_POST['registration_number'] ?? ''));

    // 1. Server-side validation
    if (empty($vehicleTypeVal) || !array_key_exists($vehicleTypeVal, VEHICLE_TYPES)) {
        $errorMsg = 'Please select a valid two-wheeler type (Motorcycle, Scooter, or Electric Two-Wheeler).';
    } elseif (empty($brandVal) || strlen($brandVal) < 2) {
        $errorMsg = 'Please enter or select a valid manufacturer / brand.';
    } elseif (empty($modelVal) || strlen($modelVal) < 2) {
        $errorMsg = 'Please enter the specific model name (e.g. Classic 350, Activa 6G, 450X).';
    } elseif (empty($regNumberVal) || strlen($regNumberVal) < 4 || strlen($regNumberVal) > 20) {
        $errorMsg = 'Please enter a valid registration number (e.g. TN-07-AB-1234).';
    } else {
        if ($pdo) {
            try {
                // Check if registration number already exists
                $stmtCheck = $pdo->prepare("SELECT id FROM vehicles WHERE registration_number = :reg LIMIT 1");
                $stmtCheck->execute([':reg' => $regNumberVal]);
                if ($stmtCheck->fetch()) {
                    $errorMsg = "A vehicle with registration number '{$regNumberVal}' is already registered in MotoCare.";
                } else {
                    // Insert vehicle bound to authenticated customer ID from session
                    $stmtInsert = $pdo->prepare("
                        INSERT INTO vehicles (customer_id, vehicle_type, brand, model, registration_number, created_at)
                        VALUES (:cid, :vtype, :brand, :model, :reg, NOW())
                    ");
                    $stmtInsert->execute([
                        ':cid'   => $customerId,
                        ':vtype' => $vehicleTypeVal,
                        ':brand' => $brandVal,
                        ':model' => $modelVal,
                        ':reg'   => $regNumberVal
                    ]);

                    $newVehicleId = $pdo->lastInsertId();

                    // If redirected with request to book immediately
                    if (isset($_GET['redirect']) && $_GET['redirect'] === 'book') {
                        header("Location: book-service.php?vehicle_id=" . $newVehicleId . "&msg=vehicle_added");
                        exit;
                    }

                    header("Location: vehicles.php?msg=added");
                    exit;
                }
            } catch (PDOException $e) {
                $errorMsg = 'Database error: ' . $e->getMessage();
            }
        } else {
            // Offline demo fallback
            $successMsg = "Demo: Vehicle {$brandVal} {$modelVal} ({$regNumberVal}) registered to your garage.";
        }
    }
}

require_once __DIR__ . '/../includes/customer-header.php';
?>

<div class="container" style="max-width: 640px; padding-top: 1rem; padding-bottom: 3rem;">
  <div class="form-card" style="padding: 2.5rem 2rem;">
    <div style="margin-bottom: 2rem;">
      <span class="service-tag" style="background: var(--accent-glow-subtle); color: var(--accent-orange); margin-bottom: 0.5rem; display: inline-block;">
        Vehicle Onboarding
      </span>
      <h1 style="font-size: 1.85rem; margin-bottom: 0.25rem;">Add New Two-Wheeler</h1>
      <p style="color: var(--text-muted); font-size: 0.9rem;">
        Add your motorcycle, scooter, or electric vehicle to your digital garage for fast appointment scheduling.
      </p>
    </div>

    <?php if ($errorMsg): ?>
      <div class="form-alert form-alert-error" style="margin-bottom: 1.5rem;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
        <span><?php echo htmlspecialchars($errorMsg); ?></span>
      </div>
    <?php endif; ?>

    <?php if ($successMsg): ?>
      <div class="form-alert form-alert-success" style="margin-bottom: 1.5rem;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
        <span><?php echo htmlspecialchars($successMsg); ?></span>
      </div>
    <?php endif; ?>

    <form method="POST" action="add-vehicle.php<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''; ?>">
      <div class="form-grid">
        <div class="form-group full-width">
          <label for="vehicle_type" class="form-label">Vehicle Type <span class="required-dot">*</span></label>
          <select id="vehicle_type" name="vehicle_type" class="form-select" required>
            <option value="" disabled <?php echo empty($vehicleTypeVal) ? 'selected' : ''; ?>>-- Select Two-Wheeler Type --</option>
            <?php foreach (VEHICLE_TYPES as $typeKey => $typeLabel): ?>
              <option value="<?php echo htmlspecialchars($typeKey); ?>" <?php echo ($vehicleTypeVal === $typeKey) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($typeLabel); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label for="brand" class="form-label">Brand / Manufacturer <span class="required-dot">*</span></label>
          <select id="brand" name="brand" class="form-select" required>
            <option value="" disabled <?php echo empty($brandVal) ? 'selected' : ''; ?>>-- Select Brand --</option>
            <?php foreach (VEHICLE_BRANDS as $brand): ?>
              <option value="<?php echo htmlspecialchars($brand); ?>" <?php echo ($brandVal === $brand) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($brand); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label for="model" class="form-label">Model Name <span class="required-dot">*</span></label>
          <input type="text" id="model" name="model" class="form-input" 
                 placeholder="e.g. Classic 350 / Activa 6G / 450X" 
                 value="<?php echo htmlspecialchars($modelVal); ?>" required>
        </div>

        <div class="form-group full-width">
          <label for="registration_number" class="form-label">Registration Number <span class="required-dot">*</span></label>
          <input type="text" id="registration_number" name="registration_number" class="form-input" 
                 placeholder="e.g. TN-07-AB-1234" 
                 style="text-transform: uppercase;" 
                 value="<?php echo htmlspecialchars($regNumberVal); ?>" required>
          <small style="color: var(--text-muted); font-size: 0.75rem; margin-top: 0.35rem; display: block;">
            Government license plate number format (e.g. State Code + District + Series + Number).
          </small>
        </div>

        <div class="form-group full-width" style="margin-top: 1rem; display: flex; gap: 1rem; flex-wrap: wrap;">
          <button type="submit" class="btn btn-primary btn-block btn-lg" style="flex: 1;">
            Save Vehicle to My Garage →
          </button>
          <a href="vehicles.php" class="btn btn-secondary btn-lg">
            Cancel
          </a>
        </div>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
