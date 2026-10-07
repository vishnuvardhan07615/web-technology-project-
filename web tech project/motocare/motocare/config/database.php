<?php
/**
 * ============================================================================
 * MotoCare – Two-Wheeler Service & Mechanic Shop Management System
 * File: config/database.php
 * Stage 2: Centralized Database Connection (PDO)
 * ============================================================================
 */

// Load constants if not already loaded
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/constants.php';
}

/**
 * ----------------------------------------------------------------------------
 * DATABASE CONFIGURATION CREDENTIALS
 * ----------------------------------------------------------------------------
 * In a standard local development environment (XAMPP / WAMP / MAMP):
 *   - Host:     127.0.0.1 or localhost
 *   - Port:     3306 (or 8889 for MAMP default)
 *   - Database: motocare_db
 *   - Username: root
 *   - Password: "" (blank for XAMPP/WAMP) or "root" (for MAMP)
 *
 * For college viva demonstration:
 * Explain that these settings are centralized here to prevent credential
 * duplication across the codebase and allow easy migration to live servers.
 */
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'motocare_db');
define('DB_USER', 'root');
define('DB_PASSWORD', ''); // Set to 'root' if using MAMP or custom MySQL root password
define('DB_CHARSET', 'utf8mb4');

/**
 * Get the PDO Database Connection instance
 *
 * @param bool $suppressFatal If true, returns null instead of exiting on connection failure
 * @return PDO|null
 */
function getDBConnection($suppressFatal = false) {
    static $pdoInstance = null;

    if ($pdoInstance !== null) {
        return $pdoInstance;
    }

    $port = getenv('DB_PORT') ?: DB_PORT;
    $dsn = "mysql:host=" . DB_HOST . ";port=" . $port . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_TIMEOUT            => 5,
    ];

    try {
        $pdoInstance = new PDO($dsn, DB_USER, DB_PASSWORD, $options);
        return $pdoInstance;
    } catch (PDOException $e) {
        // Fallback for custom local environments (e.g. Homebrew MySQL on port 3307 or socket)
        try {
            $altDsn = "mysql:host=" . DB_HOST . ";port=3307;dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $pdoInstance = new PDO($altDsn, DB_USER, DB_PASSWORD, $options);
            return $pdoInstance;
        } catch (PDOException $e2) {
            try {
                if (file_exists('/tmp/mysql_brew.sock')) {
                    $sockDsn = "mysql:unix_socket=/tmp/mysql_brew.sock;dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
                    $pdoInstance = new PDO($sockDsn, DB_USER, DB_PASSWORD, $options);
                    return $pdoInstance;
                }
            } catch (PDOException $e3) {
                // Keep original error
            }
        }

        // Log detailed technical error internally without leaking database credentials to frontend users
        error_log("MotoCare Database Connection Error: " . $e->getMessage());

        if ($suppressFatal) {
            return null;
        }

        // Friendly error message for college presentation / local setup
        $errorMessage = "Database Connection Unavailable. Please ensure MySQL server is running in XAMPP/MAMP and 'motocare_db' database is imported.";
        
        // If script is an API or included in a page, store the connection error
        global $dbConnectionError;
        $dbConnectionError = $errorMessage;
        
        return null;
    }
}

// Global PDO handle for convenient direct usage
$pdo = getDBConnection(true);
