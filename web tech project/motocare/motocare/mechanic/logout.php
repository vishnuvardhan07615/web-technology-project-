<?php
/**
 * ============================================================================
 * MotoCare – Two-Wheeler Service & Mechanic Shop Management System
 * File: mechanic/logout.php
 * Stage 2: Technician Session Logout Handler
 * ============================================================================
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/auth.php';

logoutUser(SESSION_KEY_MECHANIC);
header('Location: login.php');
exit;
