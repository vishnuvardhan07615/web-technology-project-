<?php
/**
 * ============================================================================
 * MotoCare – Two-Wheeler Service & Mechanic Shop Management System
 * File: customer/book-service.php
 * Stage 2: Dynamic Customer Service Booking Workflow
 * ============================================================================
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

// Administrative staff and workshop mechanics should not operate as customer booking agents
if (isAdminLoggedIn()) {
    header('Location: ' . BASE_URL . '/admin/dashboard.php');
    exit;
}
if (isMechanicLoggedIn()) {
    header('Location: ' . BASE_URL . '/mechanic/dashboard.php');
    exit;
}

requireCustomerLogin();
$customer = getCurrentCustomer();
$customerId = (int)$customer['id'];

$pageTitle = 'Schedule Service – MotoCare Customer Portal';
$currentPage = 'book-service.php';

$errorMsg = '';
$bookingSuccess = false;
$confirmedBooking = null;

// Pre-fill / default selections from GET query parameters
$selectedVehicleId = isset($_GET['vehicle_id']) ? (int)$_GET['vehicle_id'] : 0;
$selectedServiceId = isset($_GET['service_id']) ? (int)$_GET['service_id'] : 0;
$preferredDateVal = date('Y-m-d'); // Default to today
$preferredTimeVal = '';
$problemDescVal = '';

// Load customer vehicles from MySQL (filtered by customer_id)
$customerVehicles = [];
if ($pdo) {
    try {
        $stmtVeh = $pdo->prepare("
            SELECT id, vehicle_type, brand, model, registration_number 
            FROM vehicles 
            WHERE customer_id = :cid 
            ORDER BY id DESC
        ");
        $stmtVeh->execute([':cid' => $customerId]);
        $customerVehicles = $stmtVeh->fetchAll();
    } catch (PDOException $e) {
        $errorMsg = 'Error loading vehicles: ' . $e->getMessage();
    }
} else {
    // Offline demo vehicles
    $customerVehicles = [
        ['id' => 1, 'vehicle_type' => 'Motorcycle', 'brand' => 'Royal Enfield', 'model' => 'Classic 350', 'registration_number' => 'TN-07-AB-1234'],
        ['id' => 2, 'vehicle_type' => 'Scooter', 'brand' => 'Honda', 'model' => 'Activa 6G', 'registration_number' => 'TN-09-CD-5678']
    ];
}

// Load active services from MySQL (status = 'Active')
$activeServices = [];
if ($pdo) {
    try {
        $stmtSvc = $pdo->query("
            SELECT id, service_name, category, description, price, estimated_duration 
            FROM services 
            WHERE status = 'Active' 
            ORDER BY category ASC, price ASC
        ");
        $activeServices = $stmtSvc->fetchAll();
    } catch (PDOException $e) {
        $errorMsg = 'Error loading services: ' . $e->getMessage();
    }
} else {
    // Offline demo services
    $activeServices = [
        ['id' => 1, 'service_name' => 'General Service', 'category' => 'General Service', 'price' => 500.00, 'estimated_duration' => '2 - 3 Hours'],
        ['id' => 2, 'service_name' => 'Oil Change', 'category' => 'General Service', 'price' => 450.00, 'estimated_duration' => '30 - 45 Mins'],
        ['id' => 5, 'service_name' => 'Engine Service & Overhaul', 'category' => 'Mechanical', 'price' => 1500.00, 'estimated_duration' => '1 - 2 Days'],
        ['id' => 6, 'service_name' => 'Brake Service', 'category' => 'Mechanical', 'price' => 300.00, 'estimated_duration' => '1 Hour'],
        ['id' => 9, 'service_name' => 'Battery Service', 'category' => 'Electrical', 'price' => 200.00, 'estimated_duration' => '30 Mins'],
        ['id' => 16, 'service_name' => 'EV General Checkup', 'category' => 'Electric Vehicles', 'price' => 600.00, 'estimated_duration' => '1.5 Hours']
    ];
}

// Group services by category for clean optgroups
$servicesByCategory = [];
foreach ($activeServices as $svc) {
    $servicesByCategory[$svc['category']][] = $svc;
}

// Available Workshop Time Slots
$timeSlots = [
    '08:00 AM - 10:00 AM' => 'Early Bird Slot: 08:00 AM - 10:00 AM',
    '10:00 AM - 01:00 PM' => 'Morning Peak Slot: 10:00 AM - 01:00 PM',
    '02:00 PM - 04:00 PM' => 'Afternoon Slot: 02:00 PM - 04:00 PM',
    '04:00 PM - 07:00 PM' => 'Evening Slot: 04:00 PM - 07:00 PM'
];

// -----------------------------------------------------------------------------
// POST Handler: Dynamic Booking Submission
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedVehicleId = (int)($_POST['vehicle_id'] ?? 0);
    $selectedServiceId = (int)($_POST['service_id'] ?? 0);
    $preferredDateVal  = trim($_POST['preferred_date'] ?? '');
    $preferredTimeVal  = trim($_POST['preferred_time'] ?? '');
    $problemDescVal    = trim($_POST['problem_description'] ?? '');

    $todayDate = date('Y-m-d');

    // 1. Server-side validation: Vehicle
    if ($selectedVehicleId <= 0) {
        $errorMsg = 'Please select a vehicle from your registered garage.';
    } elseif ($selectedServiceId <= 0) {
        $errorMsg = 'Please select a service package.';
    } elseif (empty($preferredDateVal)) {
        $errorMsg = 'Please select a preferred appointment date.';
    } elseif ($preferredDateVal < $todayDate) {
        $errorMsg = 'Please select today or a future date for your service appointment.';
    } elseif (empty($preferredTimeVal) || !array_key_exists($preferredTimeVal, $timeSlots)) {
        $errorMsg = 'Please select a valid workshop time slot.';
    } else {
        // Verify database existence and ownership
        if ($pdo) {
            try {
                // Rule 1: Vehicle MUST belong to the authenticated customer
                $stmtCheckVeh = $pdo->prepare("
                    SELECT id, vehicle_type, brand, model, registration_number 
                    FROM vehicles 
                    WHERE id = :vid AND customer_id = :cid 
                    LIMIT 1
                ");
                $stmtCheckVeh->execute([
                    ':vid' => $selectedVehicleId,
                    ':cid' => $customerId
                ]);
                $vehicleData = $stmtCheckVeh->fetch();

                if (!$vehicleData) {
                    $errorMsg = 'Selected vehicle does not belong to your account or does not exist.';
                } else {
                    // Rule 2: Service MUST exist and be Active
                    $stmtCheckSvc = $pdo->prepare("
                        SELECT id, service_name, category, price, estimated_duration 
                        FROM services 
                        WHERE id = :sid AND status = 'Active' 
                        LIMIT 1
                    ");
                    $stmtCheckSvc->execute([':sid' => $selectedServiceId]);
                    $serviceData = $stmtCheckSvc->fetch();

                    if (!$serviceData) {
                        $errorMsg = 'Selected service package is invalid or currently unavailable.';
                    } else {
                        // Generate Unique Server-Side Booking Code: MC-YYYY-XXXX
                        $bookingYear = date('Y');
                        $bookingCode = '';
                        $isUnique = false;
                        $attempts = 0;

                        while (!$isUnique && $attempts < 10) {
                            $attempts++;
                            $randCode = mt_rand(1000, 9999);
                            $candidateCode = "MC-{$bookingYear}-{$randCode}";

                            $stmtCheckCode = $pdo->prepare("SELECT id FROM bookings WHERE booking_code = :code LIMIT 1");
                            $stmtCheckCode->execute([':code' => $candidateCode]);
                            if (!$stmtCheckCode->fetch()) {
                                $bookingCode = $candidateCode;
                                $isUnique = true;
                            }
                        }

                        if (!$bookingCode) {
                            $bookingCode = "MC-{$bookingYear}-" . time();
                        }

                        // Insert booking into MySQL: mechanic_id = NULL, status = 'Pending'
                        $stmtInsert = $pdo->prepare("
                            INSERT INTO bookings (
                                booking_code,
                                customer_id,
                                vehicle_id,
                                service_id,
                                mechanic_id,
                                preferred_date,
                                preferred_time,
                                problem_description,
                                status,
                                created_at
                            ) VALUES (
                                :bcode,
                                :cid,
                                :vid,
                                :sid,
                                NULL,
                                :pdate,
                                :ptime,
                                :pdesc,
                                'Pending',
                                NOW()
                            )
                        ");

                        $stmtInsert->execute([
                            ':bcode' => $bookingCode,
                            ':cid'   => $customerId,
                            ':vid'   => $selectedVehicleId,
                            ':sid'   => $selectedServiceId,
                            ':pdate' => $preferredDateVal,
                            ':ptime' => $preferredTimeVal,
                            ':pdesc' => $problemDescVal
                        ]);

                        $bookingInsertId = $pdo->lastInsertId();

                        // Populate confirmation payload
                        $bookingSuccess = true;
                        $confirmedBooking = [
                            'id'           => $bookingInsertId,
                            'booking_code' => $bookingCode,
                            'vehicle_name' => $vehicleData['brand'] . ' ' . $vehicleData['model'],
                            'registration' => $vehicleData['registration_number'],
                            'service_name' => $serviceData['service_name'],
                            'service_price'=> $serviceData['price'],
                            'preferred_date'=> $preferredDateVal,
                            'preferred_time'=> $preferredTimeVal,
                            'problem_notes'=> $problemDescVal,
                            'status'       => 'Pending'
                        ];
                    }
                }
            } catch (PDOException $e) {
                $errorMsg = 'Database error while saving booking: ' . $e->getMessage();
            }
        } else {
            // Offline demo mode confirmation
            $bookingSuccess = true;
            $confirmedBooking = [
                'id'           => 99,
                'booking_code' => 'MC-' . date('Y') . '-' . mt_rand(1000, 9999),
                'vehicle_name' => 'Demo Two-Wheeler',
                'registration' => 'TN-07-DEMO-99',
                'service_name' => 'General Service',
                'service_price'=> 500.00,
                'preferred_date'=> $preferredDateVal,
                'preferred_time'=> $preferredTimeVal,
                'problem_notes'=> $problemDescVal,
                'status'       => 'Pending'
            ];
        }
    }
}

require_once __DIR__ . '/../includes/customer-header.php';
?>

<div class="container" style="max-width: 820px; padding-top: 1rem; padding-bottom: 3rem;">

  <?php if ($bookingSuccess && $confirmedBooking): ?>
    <!-- ======================================================================
         SUCCESS CONFIRMATION VIEW (Section 7)
         ====================================================================== -->
    <div class="form-card" style="padding: 3rem 2.5rem; text-align: center; border: 1px solid rgba(16,185,129,0.4); box-shadow: 0 10px 30px rgba(16,185,129,0.1);">
      <div style="width: 72px; height: 72px; margin: 0 auto 1.5rem; border-radius: 50%; background: rgba(16,185,129,0.15); display: flex; align-items: center; justify-content: center; border: 1px solid rgba(16,185,129,0.4);">
        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="var(--color-green)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
      </div>

      <span class="service-tag" style="background: rgba(16,185,129,0.15); color: var(--color-green); border-color: rgba(16,185,129,0.3); margin-bottom: 0.75rem; font-size: 0.8rem;">
        Reservation Received
      </span>

      <h1 style="font-size: 2.2rem; color: #ffffff; margin-bottom: 0.5rem; font-family: var(--font-heading);">
        Service Booking Confirmed!
      </h1>
      <p style="color: var(--text-muted); font-size: 0.95rem; max-width: 520px; margin: 0 auto 2rem;">
        Your appointment has been registered in the MotoCare workshop system. Our team is preparing your bay allocation.
      </p>

      <!-- Confirmation Summary Card -->
      <div style="background: var(--bg-surface); border: 1px solid var(--border-subtle); border-radius: var(--radius-lg); padding: 1.75rem; text-align: left; max-width: 560px; margin: 0 auto 2.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-subtle); padding-bottom: 1rem; margin-bottom: 1.25rem;">
          <div>
            <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em;">Booking Code</div>
            <div style="font-family: var(--font-heading); font-size: 1.4rem; font-weight: 800; color: var(--accent-orange);">
              <?php echo htmlspecialchars($confirmedBooking['booking_code']); ?>
            </div>
          </div>
          <span class="badge" style="background: rgba(245,158,11,0.15); color: #fbbf24; border: 1px solid rgba(245,158,11,0.3); padding: 0.4rem 0.85rem; font-size: 0.85rem;">
            ● Status: <?php echo htmlspecialchars($confirmedBooking['status']); ?>
          </span>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; font-size: 0.9rem;">
          <div>
            <span style="color: var(--text-muted); display: block; font-size: 0.8rem;">Vehicle</span>
            <strong style="color: #ffffff;"><?php echo htmlspecialchars($confirmedBooking['vehicle_name']); ?></strong>
            <span style="font-family: monospace; font-size: 0.8rem; color: var(--accent-orange); display: block;">
              <?php echo htmlspecialchars($confirmedBooking['registration']); ?>
            </span>
          </div>

          <div>
            <span style="color: var(--text-muted); display: block; font-size: 0.8rem;">Selected Service</span>
            <strong style="color: #ffffff;"><?php echo htmlspecialchars($confirmedBooking['service_name']); ?></strong>
            <span style="font-size: 0.85rem; color: var(--text-secondary); display: block;">
              Estimated: <?php echo formatCurrency($confirmedBooking['service_price']); ?>
            </span>
          </div>

          <div>
            <span style="color: var(--text-muted); display: block; font-size: 0.8rem;">Appointment Date</span>
            <strong style="color: #ffffff;"><?php echo formatDisplayDate($confirmedBooking['preferred_date']); ?></strong>
          </div>

          <div>
            <span style="color: var(--text-muted); display: block; font-size: 0.8rem;">Time Slot</span>
            <strong style="color: #ffffff;"><?php echo htmlspecialchars($confirmedBooking['preferred_time']); ?></strong>
          </div>
        </div>

        <?php if (!empty($confirmedBooking['problem_notes'])): ?>
          <div style="margin-top: 1.25rem; padding-top: 1rem; border-top: 1px dashed var(--border-subtle); font-size: 0.85rem;">
            <span style="color: var(--text-muted);">Customer Problem Notes:</span>
            <p style="margin: 0.25rem 0 0; color: var(--text-secondary); font-style: italic;">
              "<?php echo htmlspecialchars($confirmedBooking['problem_notes']); ?>"
            </p>
          </div>
        <?php endif; ?>
      </div>

      <!-- Action Buttons (Section 7 Requirements) -->
      <div style="display: flex; justify-content: center; gap: 1rem; flex-wrap: wrap;">
        <a href="bookings.php" class="btn btn-primary btn-lg">
          View My Bookings →
        </a>
        <a href="booking-details.php?code=<?php echo urlencode($confirmedBooking['booking_code']); ?>" class="btn btn-outline btn-lg">
          Booking Details
        </a>
        <a href="book-service.php" class="btn btn-secondary btn-lg">
          Book Another Service
        </a>
        <a href="dashboard.php" class="btn btn-secondary btn-lg">
          Customer Dashboard
        </a>
      </div>
    </div>

  <?php else: ?>
    <!-- ======================================================================
         BOOKING FORM VIEW (Section 3)
         ====================================================================== -->
    <div class="form-card" style="padding: 2.5rem 2rem;">
      <div style="margin-bottom: 2rem;">
        <span class="service-tag" style="background: var(--accent-glow-subtle); color: var(--accent-orange); margin-bottom: 0.5rem; display: inline-block;">
          Direct Scheduling
        </span>
        <h1 style="font-size: 1.85rem; margin-bottom: 0.25rem;">Book a Service Slot</h1>
        <p style="color: var(--text-muted); font-size: 0.9rem;">
          Schedule routine maintenance, diagnostics, or repairs for any two-wheeler in your garage.
        </p>
      </div>

      <?php if (isset($_GET['msg']) && $_GET['msg'] === 'vehicle_added'): ?>
        <div class="form-alert form-alert-success" style="margin-bottom: 1.5rem;">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
          <span>Two-wheeler added successfully! Select it below to proceed with your booking.</span>
        </div>
      <?php endif; ?>

      <?php if ($errorMsg): ?>
        <div class="form-alert form-alert-error" style="margin-bottom: 1.5rem;">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
          <span><?php echo htmlspecialchars($errorMsg); ?></span>
        </div>
      <?php endif; ?>

      <?php if (empty($customerVehicles)): ?>
        <!-- No vehicles warning block -->
        <div style="background: rgba(245,158,11,0.1); border: 1px solid rgba(245,158,11,0.3); border-radius: var(--radius-md); padding: 1.5rem; text-align: center; margin-bottom: 2rem;">
          <h3 style="color: #fbbf24; font-size: 1.2rem; margin-bottom: 0.5rem;">No Two-Wheelers in Your Garage</h3>
          <p style="color: var(--text-secondary); font-size: 0.9rem; max-width: 500px; margin: 0 auto 1.25rem;">
            Before booking a service slot, please register your bike, scooter, or EV so our mechanics know the exact make, model, and engine specifications.
          </p>
          <a href="add-vehicle.php?redirect=book" class="btn btn-primary">
            + Register Your Two-Wheeler Now
          </a>
        </div>
      <?php endif; ?>

      <form method="POST" action="book-service.php" id="customerBookingForm">
        <div class="form-grid">
          
          <!-- 1. Vehicle Dropdown (Populated strictly from logged-in customer's vehicles) -->
          <div class="form-group full-width">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
              <label for="vehicle_id" class="form-label" style="margin-bottom: 0;">
                Select Registered Vehicle <span class="required-dot">*</span>
              </label>
              <a href="add-vehicle.php?redirect=book" style="font-size: 0.8rem; color: var(--accent-orange); font-weight: 600;">
                + Add Another Vehicle
              </a>
            </div>
            <select id="vehicle_id" name="vehicle_id" class="form-select" required <?php echo empty($customerVehicles) ? 'disabled' : ''; ?>>
              <option value="" disabled <?php echo empty($selectedVehicleId) ? 'selected' : ''; ?>>-- Select Vehicle from Garage --</option>
              <?php foreach ($customerVehicles as $veh): ?>
                <option value="<?php echo htmlspecialchars($veh['id']); ?>" <?php echo ($selectedVehicleId === (int)$veh['id']) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($veh['brand'] . ' ' . $veh['model'] . ' (' . $veh['registration_number'] . ') [' . $veh['vehicle_type'] . ']'); ?>
                </option>
              <?php endforeach; ?>
            </select>
            <small style="color: var(--text-muted); font-size: 0.75rem; margin-top: 0.35rem; display: block;">
              Only vehicles linked to your authenticated account are available.
            </small>
          </div>

          <!-- 2. Service Package Dropdown (Active services from services table) -->
          <div class="form-group full-width">
            <label for="service_id" class="form-label">Service Package Required <span class="required-dot">*</span></label>
            <select id="service_id" name="service_id" class="form-select" required>
              <option value="" disabled <?php echo empty($selectedServiceId) ? 'selected' : ''; ?>>-- Select Service Package --</option>
              <?php foreach ($servicesByCategory as $catName => $servicesList): ?>
                <optgroup label="<?php echo htmlspecialchars($catName); ?>">
                  <?php foreach ($servicesList as $svc): ?>
                    <option value="<?php echo htmlspecialchars($svc['id']); ?>" <?php echo ($selectedServiceId === (int)$svc['id']) ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($svc['service_name'] . ' (' . formatCurrency($svc['price']) . ' - ' . $svc['estimated_duration'] . ')'); ?>
                    </option>
                  <?php endforeach; ?>
                </optgroup>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- 3. Preferred Date -->
          <div class="form-group">
            <label for="preferred_date" class="form-label">Preferred Date <span class="required-dot">*</span></label>
            <input type="date" id="preferred_date" name="preferred_date" class="form-input" 
                   min="<?php echo date('Y-m-d'); ?>" 
                   value="<?php echo htmlspecialchars($preferredDateVal); ?>" required>
            <small style="color: var(--text-muted); font-size: 0.75rem; margin-top: 0.35rem; display: block;">
              Select today or any upcoming date.
            </small>
          </div>

          <!-- 4. Preferred Time Slot -->
          <div class="form-group">
            <label for="preferred_time" class="form-label">Preferred Time Slot <span class="required-dot">*</span></label>
            <select id="preferred_time" name="preferred_time" class="form-select" required>
              <option value="" disabled <?php echo empty($preferredTimeVal) ? 'selected' : ''; ?>>-- Choose Workshop Slot --</option>
              <?php foreach ($timeSlots as $slotKey => $slotLabel): ?>
                <option value="<?php echo htmlspecialchars($slotKey); ?>" <?php echo ($preferredTimeVal === $slotKey) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($slotLabel); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- 5. Problem Description -->
          <div class="form-group full-width">
            <label for="problem_description" class="form-label">Specific Complaints / Noise / Symptoms (Optional)</label>
            <textarea id="problem_description" name="problem_description" class="form-textarea" rows="4" 
                      placeholder="Detail any engine noises, braking vibrations, electrical cutoffs, or specific requests for the technician..."><?php echo htmlspecialchars($problemDescVal); ?></textarea>
          </div>

          <!-- Submit Button -->
          <div class="form-group full-width" style="margin-top: 1rem;">
            <button type="submit" class="btn btn-primary btn-block btn-lg" <?php echo empty($customerVehicles) ? 'disabled' : ''; ?>>
              Confirm Workshop Booking →
            </button>
          </div>

        </div>
      </form>
    </div>
  <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
