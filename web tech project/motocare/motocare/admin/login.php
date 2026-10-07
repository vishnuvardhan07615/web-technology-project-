<?php
/**
 * ============================================================================
 * MotoCare – Two-Wheeler Service & Mechanic Shop Management System
 * File: admin/login.php
 * Stage 2: Admin Control Center Authentication
 * ============================================================================
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

if (isAdminLoggedIn()) {
    header('Location: ' . BASE_URL . '/admin/dashboard.php');
    exit;
}

$errorMessage = '';
$usernameVal = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usernameVal = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($usernameVal) || empty($password)) {
        $errorMessage = 'Please provide both username/email and password.';
    } else {
        if ($pdo !== null) {
            try {
                $stmt = $pdo->prepare("SELECT * FROM `admin_users` WHERE `username` = :user OR `email` = :email LIMIT 1");
                $stmt->execute([':user' => $usernameVal, ':email' => $usernameVal]);
                $admin = $stmt->fetch();

                if ($admin && password_verify($password, $admin['password'])) {
                    loginAdmin($admin);
                    header('Location: ' . BASE_URL . '/admin/dashboard.php');
                    exit;
                } else {
                    $errorMessage = 'Invalid administrative credentials.';
                }
            } catch (PDOException $e) {
                $errorMessage = 'Database offline. Please start MySQL.';
            }
        } else {
            // Mock authentication for presentation when DB is offline
            if ($usernameVal === 'admin' && $password === 'admin123') {
                loginAdmin([
                    'id'       => 1,
                    'username' => 'admin',
                    'email'    => 'admin@motocare.com'
                ]);
                header('Location: ' . BASE_URL . '/admin/dashboard.php');
                exit;
            } else {
                $errorMessage = 'Database offline. Demo login: admin / admin123';
            }
        }
    }
}

$pageTitle = 'Admin Portal Login – MotoCare';
$currentPage = 'login.php';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="section-padding">
  <div class="container" style="max-width: 500px;">
    
    <div style="margin-bottom: 1.25rem;">
      <a href="<?php echo BASE_URL; ?>/portal-login.php" style="display: inline-flex; align-items: center; gap: 0.4rem; color: var(--text-muted); font-size: 0.875rem; text-decoration: none;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
        <span>Back to Portal Login</span>
      </a>
    </div>

    <div class="form-card" style="padding: 2.5rem 2rem; border-color: rgba(239,68,68,0.3);">
      
      <div style="text-align: center; margin-bottom: 2rem;">
        <div style="margin-bottom: 1rem;">
          <img src="<?php echo BASE_URL; ?>/images/logo/logo.svg" alt="MotoCare" width="170" height="40" style="margin: 0 auto;">
        </div>
        <span class="service-tag" style="background: rgba(239,68,68,0.15); color: #f87171; border-color: rgba(239,68,68,0.3); margin-bottom: 0.75rem; display: inline-block;">
          Admin Portal
        </span>
        <h1 style="font-size: 2rem; margin-bottom: 0.5rem;">Admin Control Login</h1>
        <p style="font-size: 0.9rem; color: var(--text-muted);">
          Restricted access for workshop supervisors and garage managers.
        </p>
      </div>

      <?php if (!empty($errorMessage)): ?>
        <div style="background: rgba(239,68,68,0.15); border: 1px solid #ef4444; color: #fca5a5; padding: 0.85rem 1rem; border-radius: var(--radius-md); font-size: 0.875rem; margin-bottom: 1.5rem;">
          <?php echo htmlspecialchars($errorMessage); ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="login.php" novalidate>
        <div class="form-group" style="margin-bottom: 1.25rem;">
          <label for="username" class="form-label">Username or Email</label>
          <input type="text" id="username" name="username" class="form-input" 
                 value="<?php echo htmlspecialchars($usernameVal); ?>" 
                 placeholder="admin" required autocomplete="username">
        </div>

        <div class="form-group" style="margin-bottom: 1.5rem;">
          <label for="password" class="form-label">Password</label>
          <input type="password" id="password" name="password" class="form-input" 
                 placeholder="Enter admin password" required autocomplete="current-password">
        </div>

        <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-bottom: 1.5rem; background: linear-gradient(135deg, #ef4444, #b91c1c);">
          Sign In as Admin
        </button>

        <!-- Verified Demo Account for Viva Box -->
        <div style="background: var(--bg-surface); padding: 1rem; border-radius: var(--radius-md); font-size: 0.825rem; color: var(--text-muted); border: 1px dashed var(--border-subtle);">
          <strong style="color: var(--text-primary); display: block; margin-bottom: 0.25rem;">Demo Account for Viva:</strong>
          Username/Email: <code style="color: var(--color-blue); font-weight: 600;">admin</code><br>
          Password: <code style="color: var(--accent-orange); font-weight: 600;">admin123</code>
        </div>
      </form>

    </div>
  </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
