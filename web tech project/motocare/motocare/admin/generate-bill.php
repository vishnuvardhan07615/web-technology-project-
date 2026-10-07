<?php
/**
 * ============================================================================
 * MotoCare – Two-Wheeler Service & Mechanic Shop Management System
 * File: admin/generate-bill.php
 * Stage 2: Invoice Generation Controller (Atomic Transaction & Validation)
 * ============================================================================
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/billing-helper.php';

requireAdminLogin();

$bookingId = isset($_REQUEST['booking_id']) ? (int)$_REQUEST['booking_id'] : 0;
$discount  = isset($_REQUEST['discount']) ? (float)$_REQUEST['discount'] : 0.00;

if ($bookingId <= 0) {
    header('Location: bills.php?err=' . urlencode('Invalid service booking ID specified.'));
    exit;
}

if (!$pdo) {
    header('Location: bills.php?err=' . urlencode('Database offline. Could not generate bill.'));
    exit;
}

$res = getOrCreateBillForBooking($pdo, $bookingId, $discount);

if ($res['success']) {
    $billId = (int)$res['bill_id'];
    $msg = $res['is_existing'] 
        ? "Existing invoice loaded for this booking." 
        : "Invoice #{$res['invoice']} generated successfully!";
    header("Location: bill-details.php?id={$billId}&msg=" . urlencode($msg));
    exit;
} else {
    // Redirect back to booking details with error explanation
    header("Location: booking-details.php?id={$bookingId}&err=" . urlencode($res['error']));
    exit;
}
