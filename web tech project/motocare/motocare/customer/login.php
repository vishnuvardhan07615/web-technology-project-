<?php
/**
 * ============================================================================
 * MotoCare – Two-Wheeler Service & Mechanic Shop Management System
 * File: customer/login.php
 * Stage 2: Customer Authentication Page
 * ============================================================================
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

$redirect = trim($_GET['redirect'] ?? $_POST['redirect'] ?? '');
if ($redirect === 'book-service') {
    $_SESSION['redirect_after_login'] = BASE_URL . '/customer/book-service.php';
}

// Redirect if already authenticated
if (isCustomerLoggedIn()) {
    if ($redirect === 'book-service' || !empty($_SESSION['redirect_after_login'])) {
        $target = $_SESSION['redirect_after_login'] ?? (BASE_URL . '/customer/book-service.php');
        unset($_SESSION['redirect_after_login']);
        header('Location: ' . $target);
    } else {
        header('Location: ' . BASE_URL . '/customer/dashboard.php');
    }
    exit;
}

$errorMessage = '';
$emailValue = '';

// Handle Login Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailValue = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($emailValue) || empty($password)) {
        $errorMessage = 'Please enter both email address and password.';
    } else {
        if ($pdo !== null) {
            try {
                $stmt = $pdo->prepare("SELECT * FROM `customers` WHERE `email` = :email LIMIT 1");
                $stmt->execute([':email' => $emailValue]);
                $customer = $stmt->fetch();

                if ($customer && password_verify($password, $customer['password'])) {
                    loginCustomer($customer);
                    $redirectUrl = BASE_URL . '/customer/dashboard.php';
                    if (!empty($_SESSION['redirect_after_login'])) {
                        $redirectUrl = $_SESSION['redirect_after_login'];
                        unset($_SESSION['redirect_after_login']);
                    } elseif ($redirect === 'book-service') {
                        $redirectUrl = BASE_URL . '/customer/book-service.php';
                    }
                    header('Location: ' . $redirectUrl);
                    exit;
                } else {
                    $errorMessage = 'Invalid email address or password. Please try again.';
                }
            } catch (PDOException $e) {
                $errorMessage = 'A database error occurred. Please verify MySQL service is running.';
            }
        } else {
            // Mock authentication for demonstration when database is not connected
            if ($emailValue === 'ramesh@example.com' && $password === 'customer123') {
                loginCustomer([
                    'id' => 1,
                    'full_name' => 'Ramesh Kumar',
                    'email' => 'ramesh@example.com',
                    'phone' => '9876543210'
                ]);
                $redirectUrl = ($redirect === 'book-service') ? (BASE_URL . '/customer/book-service.php') : (BASE_URL . '/customer/dashboard.php');
                header('Location: ' . $redirectUrl);
                exit;
            } else {
                $errorMessage = 'Database offline. Demo login: ramesh@example.com / customer123';
            }
        }
    }
}

$pageTitle = 'Customer Portal Login – MotoCare';
$currentPage = 'login.php';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="section-padding">
  <div class="container" style="max-width: 520px;">
    
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
          Customer Portal
        </span>
        <h1 style="font-size: 2rem; margin-bottom: 0.5rem;">Sign In to MotoCare</h1>
        <p style="font-size: 0.9rem; color: var(--text-muted);">
          Access your two-wheeler service job cards, bills, and appointment history.
        </p>
      </div>

      <?php if ($redirect === 'book-service'): ?>
        <div style="background: rgba(255,94,20,0.12); border: 1px solid var(--accent-orange); color: #ffedd5; padding: 0.85rem 1rem; border-radius: var(--radius-md); font-size: 0.875rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.6rem;">
          <span style="font-size: 1.2rem;">⚡</span>
          <span><strong>Customer login is required to book a service.</strong> Please sign in to schedule your appointment.</span>
        </div>
      <?php endif; ?>

      <?php if (!empty($errorMessage)): ?>
        <div style="background: rgba(239,68,68,0.15); border: 1px solid #ef4444; color: #fca5a5; padding: 0.85rem 1rem; border-radius: var(--radius-md); font-size: 0.875rem; margin-bottom: 1.5rem;">
          <?php echo htmlspecialchars($errorMessage); ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="login.php" novalidate>
        <?php if (!empty($redirect)): ?>
          <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect); ?>">
        <?php endif; ?>

        <div class="form-group" style="margin-bottom: 1.25rem;">
          <label for="email" class="form-label">Email Address</label>
          <input type="email" id="email" name="email" class="form-input" 
                 value="<?php echo htmlspecialchars($emailValue); ?>" 
                 placeholder="ramesh@example.com" required autocomplete="email">
        </div>

        <div class="form-group" style="margin-bottom: 1.5rem;">
          <label for="password" class="form-label">Password</label>
          <input type="password" id="password" name="password" class="form-input" 
                 placeholder="Enter your customer password" required autocomplete="current-password">
        </div>

        <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-bottom: 1.5rem;">
          Sign In
        </button>

        <!-- Verified Demo Account for Viva Box -->
        <div style="background: var(--bg-surface); padding: 1rem; border-radius: var(--radius-md); font-size: 0.825rem; color: var(--text-muted); border: 1px dashed var(--border-subtle); margin-bottom: 1.5rem;">
          <strong style="color: var(--text-primary); display: block; margin-bottom: 0.25rem;">Demo Account for Viva:</strong>
          Email: <code style="color: var(--color-blue); font-weight: 600;">ramesh@example.com</code><br>
          Password: <code style="color: var(--accent-orange); font-weight: 600;">customer123</code>
        </div>

        <div style="text-align: center; font-size: 0.9rem; color: var(--text-muted);">
          Don't have an account yet? 
          <a href="register.php<?php echo (!empty($redirect)) ? '?redirect=' . urlencode($redirect) : ''; ?>" style="color: var(--accent-orange); font-weight: 600;">Create Customer Account</a>
        </div>
      </form>

    </div>
  </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
