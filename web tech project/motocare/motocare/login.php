<?php
/**
 * ============================================================================
 * MotoCare – Two-Wheeler Service & Mechanic Shop Management System
 * File: login.php
 * Stage 2: Portal Gateway Forwarder
 * ============================================================================
 */

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/auth.php';

// Forward seamlessly to the Portal Selection Gateway
header('Location: ' . BASE_URL . '/portal-login.php');
exit;
