<?php
/**
 * ============================================================================
 * MotoCare – Two-Wheeler Service & Mechanic Shop Management System
 * File: customer/bill.php
 * Stage 2: Legacy Route Alias -> Redirects to customer/bill-details.php
 * ============================================================================
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/auth.php';

requireCustomerLogin();

if (!empty($_GET)) {
    header('Location: bill-details.php?' . http_build_query($_GET));
} else {
    header('Location: bills.php');
}
exit;
