<?php
/**
 * ============================================================================
 * MotoCare – Two-Wheeler Service & Mechanic Shop Management System
 * File: customer/register.php
 * Stage 2: Customer Registration Foundation
 * ============================================================================
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

$redirect = trim($_GET['redirect'] ?? $_POST['redirect'] ?? '');
if ($redirect === 'book-service') {
    $_SESSION['redirect_after_login'] = BASE_URL . '/customer/book-service.php';
}

if (isCustomerLoggedIn()) {
    $target = (!empty($_SESSION['redirect_after_login'])) ? $_SESSION['redirect_after_login'] : (BASE_URL . '/customer/dashboard.php');
    unset($_SESSION['redirect_after_login']);
    header('Location: ' . $target);
    exit;
}

$errorMessage = '';
$successMessage = '';
$fullName = '';
$email = '';
$phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $address = trim($_POST['address'] ?? '');

    if (empty($fullName) || empty($email) || empty($phone) || empty($password)) {
        $errorMessage = 'Please complete all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = 'Please enter a valid email address.';
    } elseif (!preg_match('/^[6-9]\d{9}$/', $phone)) {
        $errorMessage = 'Please enter a valid 10-digit mobile number.';
    } elseif (strlen($password) < 6) {
        $errorMessage = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirmPassword) {
        $errorMessage = 'Passwords do not match.';
    } else {
        if ($pdo !== null) {
            try {
                // Check if email already registered
                $checkStmt = $pdo->prepare("SELECT `id` FROM `customers` WHERE `email` = :email LIMIT 1");
                $checkStmt->execute([':email' => $email]);
                if ($checkStmt->fetch()) {
                    $errorMessage = 'An account with this email address is already registered.';
                } else {
                    $passwordHash = password_hash($password, PASSWORD_BCRYPT);
                    $insertStmt = $pdo->prepare(
                        "INSERT INTO `customers` (`full_name`, `email`, `phone`, `password`, `address`) 
                         VALUES (:full_name, :email, :phone, :password, :address)"
                    );
                    $insertStmt->execute([
                        ':full_name' => $fullName,
                        ':email'     => $email,
                        ':phone'     => $phone,
                        ':password'  => $passwordHash,
                        ':address'   => $address
                    ]);

                    $newCustomerId = $pdo->lastInsertId();
                    loginCustomer([
                        'id'        => $newCustomerId,
                        'full_name' => $fullName,
                        'email'     => $email,
                        'phone'     => $phone
                    ]);
                    $redirectUrl = BASE_URL . '/customer/dashboard.php';
                    if (!empty($_SESSION['redirect_after_login'])) {
                        $redirectUrl = $_SESSION['redirect_after_login'];
                        unset($_SESSION['redirect_after_login']);
                    } elseif ($redirect === 'book-service') {
                        $redirectUrl = BASE_URL . '/customer/book-service.php';
                    }
                    header('Location: ' . $redirectUrl);
                    exit;
                }
            } catch (PDOException $e) {
                $errorMessage = 'Database error: Could not complete registration. Check MySQL connection.';
            }
        } else {
            $successMessage = 'Registration foundation active. Connect database to store live records.';
        }
    }
}

$pageTitle = 'Create Customer Account – MotoCare';
$currentPage = 'register.php';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="section-padding">
  <div class="container" style="max-width: 580px;">

    <div style="margin-bottom: 1.25rem;">
      <a href="<?php echo BASE_URL; ?>/portal-login.php" style="display: inline-flex; align-items: center; gap: 0.4rem; color: var(--text-muted); font-size: 0.875rem; text-decoration: none;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
        <span>Back to Portal Login</span>
      </a>
    </div>

    <div class="form-card" style="padding: 2.5rem 2rem;">
      
      <div style="text-align: center; margin-bottom: 2rem;">
        <div style="margin-bottom: 1rem;">
          <img src="<?php echo BASE_URL; ?>/images/logo/logo.svg" alt="MotoCare" width="170" height="40" style="margin: 0 auto;">
        </div>
        <span class="service-tag" style="background: var(--accent-glow-subtle); color: var(--accent-orange); margin-bottom: 0.75rem; display: inline-block;">
          Customer Registration
        </span>
        <h1 style="font-size: 2rem; margin-bottom: 0.5rem;">Join MotoCare</h1>
        <p style="font-size: 0.9rem; color: var(--text-muted);">
          Create an account to book two-wheeler service slots and track repair status.
        </p>
      </div>

      <?php if (!empty($errorMessage)): ?>
        <div style="background: rgba(239,68,68,0.15); border: 1px solid #ef4444; color: #fca5a5; padding: 0.85rem 1rem; border-radius: var(--radius-md); font-size: 0.875rem; margin-bottom: 1.5rem;">
          <?php echo htmlspecialchars($errorMessage); ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($successMessage)): ?>
        <div style="background: rgba(16,185,129,0.15); border: 1px solid #10b981; color: #6ee7b7; padding: 0.85rem 1rem; border-radius: var(--radius-md); font-size: 0.875rem; margin-bottom: 1.5rem;">
          <?php echo htmlspecialchars($successMessage); ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="register.php" novalidate>
        <?php if (!empty($redirect)): ?>
          <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect); ?>">
        <?php endif; ?>
        <div class="form-group" style="margin-bottom: 1.25rem;">
          <label for="full_name" class="form-label">Full Name <span class="required-dot">*</span></label>
          <input type="text" id="full_name" name="full_name" class="form-input" 
                 value="<?php echo htmlspecialchars($fullName); ?>" 
                 placeholder="e.g. Ramesh Kumar" required autocomplete="name">
        </div>

        <div class="form-group" style="margin-bottom: 1.25rem;">
          <label for="email" class="form-label">Email Address <span class="required-dot">*</span></label>
          <input type="email" id="email" name="email" class="form-input" 
                 value="<?php echo htmlspecialchars($email); ?>" 
                 placeholder="name@example.com" required autocomplete="email">
        </div>

        <div class="form-group" style="margin-bottom: 1.25rem;">
          <label for="phone" class="form-label">Mobile Number <span class="required-dot">*</span></label>
          <input type="tel" id="phone" name="phone" class="form-input" 
                 value="<?php echo htmlspecialchars($phone); ?>" 
                 placeholder="10-digit mobile number" maxlength="10" required autocomplete="tel">
        </div>

        <div class="form-grid" style="margin-bottom: 1.25rem;">
          <div class="form-group">
            <label for="password" class="form-label">Password <span class="required-dot">*</span></label>
            <input type="password" id="password" name="password" class="form-input" 
                   placeholder="Min 6 characters" required autocomplete="new-password">
          </div>
          <div class="form-group">
            <label for="confirm_password" class="form-label">Confirm Password <span class="required-dot">*</span></label>
            <input type="password" id="confirm_password" name="confirm_password" class="form-input" 
                   placeholder="Re-type password" required autocomplete="new-password">
          </div>
        </div>

        <div class="form-group" style="margin-bottom: 1.5rem;">
          <label for="address" class="form-label">Residential Address (Optional)</label>
          <textarea id="address" name="address" class="form-textarea" style="min-height: 80px;" placeholder="Door no, Street name, City, Pincode..."></textarea>
        </div>

        <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-bottom: 1.5rem;">
          Create Customer Account
        </button>

        <div style="text-align: center; font-size: 0.9rem; color: var(--text-muted);">
          Already have an account? 
          <a href="login.php" style="color: var(--accent-orange); font-weight: 600;">Sign In Here</a>
        </div>
      </form>

    </div>
  </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
