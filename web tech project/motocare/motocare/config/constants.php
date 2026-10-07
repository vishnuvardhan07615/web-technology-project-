<?php
/**
 * ============================================================================
 * MotoCare – Two-Wheeler Service & Mechanic Shop Management System
 * File: config/constants.php
 * Stage 2: Central Application Constants & Configuration
 * ============================================================================
 */

// Application Branding
define('APP_NAME', 'MotoCare');
define('APP_TAGLINE', 'Your Ride. Our Responsibility.');
define('APP_VERSION', '2.0-Stage2');
define('CURRENCY_SYMBOL', '₹');

// Base URL calculation (supports local XAMPP/WAMP/MAMP or root hosting)
if (!defined('BASE_URL')) {
    // Detect protocol
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    
    // Auto-detect project folder if hosted in subfolder (e.g., /motocare)
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $parts = explode('/', trim($scriptDir, '/'));
    $projectFolder = '';
    if (!empty($parts[0]) && in_array(strtolower($parts[0]), ['motocare', 'projects'])) {
        $projectFolder = '/' . $parts[0];
        if (isset($parts[1]) && strtolower($parts[0]) === 'projects') {
            $projectFolder .= '/' . $parts[1];
        }
    }
    
    define('BASE_URL', $protocol . $host . $projectFolder);
}

// User Roles & Session Keys
define('ROLE_ADMIN', 'admin');
define('ROLE_CUSTOMER', 'customer');
define('ROLE_MECHANIC', 'mechanic');

define('SESSION_KEY_ADMIN', 'admin');
define('SESSION_KEY_CUSTOMER', 'customer');
define('SESSION_KEY_MECHANIC', 'mechanic');

// Booking Workflow Statuses
define('BOOKING_PENDING', 'Pending');
define('BOOKING_CONFIRMED', 'Confirmed');
define('BOOKING_VEHICLE_RECEIVED', 'Vehicle Received');
define('BOOKING_INSPECTION', 'Inspection');
define('BOOKING_IN_PROGRESS', 'Service In Progress');
define('BOOKING_QUALITY_CHECK', 'Quality Check');
define('BOOKING_READY_FOR_DELIVERY', 'Ready for Delivery');
define('BOOKING_COMPLETED', 'Completed');
define('BOOKING_CANCELLED', 'Cancelled');

// All allowed booking statuses list
const BOOKING_STATUSES = [
    BOOKING_PENDING,
    BOOKING_CONFIRMED,
    BOOKING_VEHICLE_RECEIVED,
    BOOKING_INSPECTION,
    BOOKING_IN_PROGRESS,
    BOOKING_QUALITY_CHECK,
    BOOKING_READY_FOR_DELIVERY,
    BOOKING_COMPLETED,
    BOOKING_CANCELLED
];

// GST & Tax Configuration (Stage 2 Billing Section 3)
define('GST_RATE', 18.00);  // Total GST percentage
define('CGST_RATE', 9.00);  // Central GST (half of total GST)
define('SGST_RATE', 9.00);  // State GST (half of total GST)

// Workshop Invoicing & Business Metadata
define('COMPANY_NAME', 'MotoCare Workshop Hub');
define('COMPANY_ADDRESS', '#42, Grand Trunk Road, Near Metro Pillar 128, Guindy, Chennai – 600032');
define('COMPANY_PHONE', '+91 98765 43210');
define('COMPANY_EMAIL', 'billing@motocare.com');
define('COMPANY_GSTIN', '33AAAAA0000A1Z5');

// Payment Statuses (Section 8)
define('PAYMENT_PENDING', 'Pending');
define('PAYMENT_PARTIALLY_PAID', 'Partially Paid');
define('PAYMENT_PAID', 'Paid');

const PAYMENT_STATUSES = [
    PAYMENT_PENDING,
    PAYMENT_PARTIALLY_PAID,
    PAYMENT_PAID
];

// Payment Methods Supported
const PAYMENT_METHODS = ['Cash', 'UPI', 'Card', 'Bank Transfer', 'Other'];

// Vehicle Types Supported
const VEHICLE_TYPES = [
    'Motorcycle' => 'Motorcycle (Geared Bike)',
    'Scooter' => 'Scooter (Gearless / Moped)',
    'Electric Two-Wheeler' => 'Electric Two-Wheeler (EV)'
];

// Popular Vehicle Brands
const VEHICLE_BRANDS = [
    'Honda', 'Yamaha', 'TVS', 'Bajaj', 'Hero', 
    'Royal Enfield', 'Suzuki', 'KTM', 'Ola', 'Ather', 'Other'
];

/**
 * Helper: Sanitize general user input string
 *
 * @param mixed $data Raw input
 * @return string Trimmed and stripped input
 */
function sanitizeInput($data) {
    if ($data === null) return '';
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

/**
 * Helper: Format Indian Rupee currency display
 *
 * @param float|int $amount
 * @return string Formatted string e.g. ₹500.00
 */
function formatCurrency($amount) {
    return CURRENCY_SYMBOL . ' ' . number_format((float)$amount, 2);
}

/**
 * Helper: Format readable date
 *
 * @param string|null $dateStr
 * @return string e.g. 10 Oct 2026
 */
function formatDisplayDate($dateStr) {
    if (!$dateStr) return 'N/A';
    $timestamp = strtotime($dateStr);
    return $timestamp ? date('d M Y', $timestamp) : 'N/A';
}
