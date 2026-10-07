<?php
/**
 * ============================================================================
 * MotoCare – Two-Wheeler Service & Mechanic Shop Management System
 * File: mechanic/login.php
 * Stage 2: Technician Login Authentication
 * ============================================================================
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/constants.php';

// Redirect if already logged in
if (isMechanicLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$pageTitle = 'Technician Login – MotoCare Workshop';
$error = '';
$emailVal = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailVal = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($emailVal) || empty($password)) {
        $error = 'Please enter both your work email and password.';
    } else {
        if ($pdo) {
            try {
                $stmt = $pdo->prepare("SELECT * FROM mechanics WHERE email = :email LIMIT 1");
                $stmt->execute([':email' => $emailVal]);
                $mechanic = $stmt->fetch();

                if ($mechanic && password_verify($password, $mechanic['password'])) {
                    // Set session using unified loginMechanic helper
                    loginMechanic($mechanic);
                    $_SESSION[SESSION_KEY_MECHANIC] = [
                        'id' => $mechanic['id'],
                        'name' => $mechanic['full_name'],
                        'email' => $mechanic['email'],
                        'specialization' => $mechanic['specialization'],
                        'phone' => $mechanic['phone'],
                        'status' => $mechanic['status'],
                        'logged_in_at' => time()
                    ];
                    header('Location: dashboard.php');
                    exit;
                } else {
                    $error = 'Invalid technician email or password.';
                }
            } catch (PDOException $e) {
                $error = 'Database error during authentication: ' . $e->getMessage();
            }
        } else {
            // Offline demo fallback for college viva
            if (($emailVal === 'arun@motocare.com' || $emailVal === 'karthik@motocare.com') && $password === 'mechanic123') {
                $demoMech = [
                    'id' => ($emailVal === 'arun@motocare.com') ? 1 : 2,
                    'full_name' => ($emailVal === 'arun@motocare.com') ? 'Arun Kumar' : 'Karthik Raja',
                    'email' => $emailVal,
                    'specialization' => ($emailVal === 'arun@motocare.com') ? 'Royal Enfield & Cruiser Specialist' : 'EV & Electrical Diagnostics',
                    'phone' => '9876500001',
                    'status' => 'Available'
                ];
                loginMechanic($demoMech);
                $_SESSION[SESSION_KEY_MECHANIC] = array_merge($demoMech, [
                    'name' => $demoMech['full_name'],
                    'logged_in_at' => time()
                ]);
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Demo Mode: Use arun@motocare.com and password mechanic123';
            }
        }
    }
}

$rootPath = '../';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo sanitizeInput($pageTitle); ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="icon" type="image/svg+xml" href="<?php echo $rootPath; ?>images/logo/favicon.svg">
  <link rel="stylesheet" href="<?php echo $rootPath; ?>css/style.css">
</head>
<body>

  <header class="site-header" id="siteHeader">
    <div class="container">
      <nav class="navbar" aria-label="Main Navigation">
        <a href="<?php echo $rootPath; ?>index.php" class="brand-logo" title="MotoCare Home">
          <img src="<?php echo $rootPath; ?>images/logo/logo.svg" alt="MotoCare Logo" width="160" height="38">
        </a>
        <div class="nav-actions">
          <a href="<?php echo $rootPath; ?>portal-login.php" class="btn btn-outline btn-sm">← Back to Portal Login</a>
        </div>
      </nav>
    </div>
  </header>

  <main style="padding: 3rem 0 4rem;">
    <div class="container" style="max-width: 480px;">
      
      <div style="margin-bottom: 1.25rem;">
        <a href="<?php echo $rootPath; ?>portal-login.php" style="display: inline-flex; align-items: center; gap: 0.4rem; color: var(--text-muted); font-size: 0.875rem; text-decoration: none;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
          <span>Back to Portal Login</span>
        </a>
      </div>

      <div class="auth-card" style="background: var(--bg-card); border: 1px solid rgba(16,185,129,0.3); border-radius: var(--radius-lg); padding: 2.5rem; box-shadow: var(--shadow-lg);">
        
        <div style="text-align: center; margin-bottom: 2rem;">
          <div style="margin-bottom: 1rem;">
            <img src="<?php echo $rootPath; ?>images/logo/logo.svg" alt="MotoCare Logo" width="170" height="40" style="margin: 0 auto;">
          </div>
          <span class="service-tag" style="background: rgba(16,185,129,0.15); color: var(--color-green); border-color: rgba(16,185,129,0.3); margin-bottom: 0.75rem; display: inline-block;">
            Mechanic Portal
          </span>
          <h1 style="font-size: 1.8rem; color: #ffffff; margin-bottom: 0.5rem; font-family: var(--font-heading);">Technician Workshop Bay</h1>
          <p style="color: var(--text-muted); font-size: 0.9rem; margin: 0;">Sign in to access assigned bikes, digital job cards &amp; service logs.</p>
        </div>

        <?php if ($error): ?>
          <div class="form-alert form-alert-error" style="margin-bottom: 1.5rem;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            <span><?php echo htmlspecialchars($error); ?></span>
          </div>
        <?php endif; ?>

        <form method="POST" action="login.php" class="booking-form" style="display: flex; flex-direction: column; gap: 1.25rem;">
          
          <div class="form-group">
            <label for="email" class="form-label">Work Email <span class="required">*</span></label>
            <input type="email" id="email" name="email" class="form-input" required 
                   placeholder="arun@motocare.com" value="<?php echo htmlspecialchars($emailVal); ?>">
          </div>

          <div class="form-group">
            <label for="password" class="form-label">Password <span class="required">*</span></label>
            <input type="password" id="password" name="password" class="form-input" required 
                   placeholder="Enter your technician password">
          </div>

          <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top: 0.5rem; background: linear-gradient(135deg, #10b981, #059669); border-color: #10b981;">
            Sign In as Mechanic
          </button>
        </form>

        <!-- Verified Demo Account for Viva Box -->
        <div style="margin-top: 1.5rem; padding: 1rem; background: var(--bg-surface); border-radius: var(--radius-md); border: 1px dashed var(--border-subtle); font-size: 0.825rem; color: var(--text-muted);">
          <strong style="color: var(--text-primary); display: block; margin-bottom: 0.25rem;">Demo Account for Viva:</strong>
          Email: <code style="color: var(--color-green); font-weight: 600;">arun@motocare.com</code><br>
          Password: <code style="color: var(--accent-orange); font-weight: 600;">mechanic123</code>
        </div>
      </div>

    </div>
  </main>

  <footer class="site-footer" style="padding: 2rem 0; border-top: 1px solid var(--border-subtle); text-align: center; color: var(--text-muted); font-size: 0.85rem;">
    <div class="container">
      &copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. Technician Workshop System.
    </div>
  </footer>

</body>
</html>
