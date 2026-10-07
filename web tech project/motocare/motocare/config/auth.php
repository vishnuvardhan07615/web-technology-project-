<?php
/**
 * ============================================================================
 * MotoCare – Two-Wheeler Service & Mechanic Shop Management System
 * File: config/auth.php
 * Stage 2: Unified Authentication, Role Verification & Session Protection
 * ============================================================================
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/constants.php';
}

/**
 * Initialize PHP Session securely
 */
function startAppSession() {
    if (session_status() === PHP_SESSION_NONE) {
        // Enforce cookie security parameters where supported
        $cookieParams = [
            'lifetime' => 0, // Until browser closes
            'path'     => '/',
            'domain'   => '',
            'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => true,
            'samesite' => 'Lax'
        ];
        
        if (PHP_VERSION_ID >= 70300) {
            session_set_cookie_params($cookieParams);
        } else {
            session_set_cookie_params(
                $cookieParams['lifetime'],
                $cookieParams['path'],
                $cookieParams['domain'],
                $cookieParams['secure'],
                $cookieParams['httponly']
            );
        }
        
        session_start();
    }
}

// Ensure session is started on include
startAppSession();

/* ----------------------------------------------------------------------------
 * CUSTOMER AUTHENTICATION HELPERS
 * ---------------------------------------------------------------------------- */

/**
 * Check if a customer is currently authenticated
 *
 * @return bool
 */
function isCustomerLoggedIn() {
    return isset($_SESSION['customer_id']) && !empty($_SESSION['customer_id']);
}

/**
 * Enforce customer authentication; redirects to customer login if not logged in
 */
function requireCustomerLogin() {
    if (!isCustomerLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '';
        header('Location: ' . BASE_URL . '/customer/login.php');
        exit;
    }
}

/**
 * Get active customer details array from session
 *
 * @return array|null
 */
function getCurrentCustomer() {
    if (!isCustomerLoggedIn()) return null;
    return [
        'id'        => $_SESSION['customer_id'],
        'name'      => $_SESSION['customer_name'] ?? 'Customer',
        'email'     => $_SESSION['customer_email'] ?? '',
        'phone'     => $_SESSION['customer_phone'] ?? ''
    ];
}

/**
 * Log in a customer and store session variables
 *
 * @param array $customer Database customer record
 */
function loginCustomer($customer) {
    session_regenerate_id(true); // Prevent session fixation attacks
    $_SESSION['customer_id']    = $customer['id'];
    $_SESSION['customer_name']  = $customer['full_name'];
    $_SESSION['customer_email'] = $customer['email'];
    $_SESSION['customer_phone'] = $customer['phone'];
    $_SESSION['role']           = ROLE_CUSTOMER;
}

/* ----------------------------------------------------------------------------
 * ADMIN AUTHENTICATION HELPERS
 * ---------------------------------------------------------------------------- */

/**
 * Check if workshop administrator is logged in
 *
 * @return bool
 */
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

/**
 * Enforce admin authentication; redirects to admin login if not logged in
 */
function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        header('Location: ' . BASE_URL . '/admin/login.php');
        exit;
    }
}

/**
 * Get active admin user details
 *
 * @return array|null
 */
function getCurrentAdmin() {
    if (!isAdminLoggedIn()) return null;
    return [
        'id'       => $_SESSION['admin_id'],
        'username' => $_SESSION['admin_username'] ?? 'Admin',
        'email'    => $_SESSION['admin_email'] ?? ''
    ];
}

/**
 * Log in an admin and store session variables
 *
 * @param array $admin Database admin record
 */
function loginAdmin($admin) {
    session_regenerate_id(true);
    $_SESSION['admin_id']       = $admin['id'];
    $_SESSION['admin_username'] = $admin['username'];
    $_SESSION['admin_email']    = $admin['email'];
    $_SESSION['role']           = ROLE_ADMIN;
}

/* ----------------------------------------------------------------------------
 * MECHANIC AUTHENTICATION HELPERS
 * ---------------------------------------------------------------------------- */

/**
 * Check if a mechanic technician is logged in
 *
 * @return bool
 */
function isMechanicLoggedIn() {
    return isset($_SESSION['mechanic_id']) && !empty($_SESSION['mechanic_id']);
}

/**
 * Enforce mechanic authentication; redirects to mechanic login if not logged in
 */
function requireMechanicLogin() {
    if (!isMechanicLoggedIn()) {
        header('Location: ' . BASE_URL . '/mechanic/login.php');
        exit;
    }
}

/**
 * Get active mechanic details
 *
 * @return array|null
 */
function getCurrentMechanic() {
    if (!isMechanicLoggedIn()) return null;
    return [
        'id'             => $_SESSION['mechanic_id'],
        'name'           => $_SESSION['mechanic_name'] ?? 'Mechanic',
        'email'          => $_SESSION['mechanic_email'] ?? '',
        'specialization' => $_SESSION['mechanic_specialization'] ?? ''
    ];
}

/**
 * Log in a mechanic and store session variables
 *
 * @param array $mechanic Database mechanic record
 */
function loginMechanic($mechanic) {
    session_regenerate_id(true);
    $_SESSION['mechanic_id']             = $mechanic['id'];
    $_SESSION['mechanic_name']           = $mechanic['full_name'];
    $_SESSION['mechanic_email']          = $mechanic['email'];
    $_SESSION['mechanic_specialization'] = $mechanic['specialization'];
    $_SESSION['role']                    = ROLE_MECHANIC;
}

/* ----------------------------------------------------------------------------
 * GENERAL LOGOUT HELPER
 * ---------------------------------------------------------------------------- */

/**
 * Safely terminate active session and redirect
 *
 * @param string|null $redirectUrl Optional URL to redirect to
 */
function logoutUser($redirectUrl = null) {
    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(), 
            '', 
            time() - 42000,
            $params["path"], 
            $params["domain"],
            $params["secure"], 
            $params["httponly"]
        );
    }

    session_destroy();

    if ($redirectUrl) {
        header("Location: " . $redirectUrl);
        exit;
    }
}

/**
 * Determine dynamic booking route based on role and authentication status
 *
 * Case A (Guest): Redirect to customer login with return redirect
 * Case B (Customer): Direct access to customer/book-service.php
 * Case C (Admin): Route to admin dashboard
 * Case D (Mechanic): Route to mechanic dashboard
 *
 * @param string $rootPath Path prefix (e.g. './' or '../')
 * @return string Target URL
 */
function getBookServiceUrl($rootPath = './', $serviceParam = '') {
    if (isCustomerLoggedIn()) {
        $url = $rootPath . 'customer/book-service.php';
        if (!empty($serviceParam)) {
            $url .= '?service=' . urlencode($serviceParam);
        }
        return $url;
    } elseif (isAdminLoggedIn()) {
        return $rootPath . 'admin/dashboard.php';
    } elseif (isMechanicLoggedIn()) {
        return $rootPath . 'mechanic/dashboard.php';
    } else {
        return $rootPath . 'customer/login.php?redirect=book-service';
    }
}

