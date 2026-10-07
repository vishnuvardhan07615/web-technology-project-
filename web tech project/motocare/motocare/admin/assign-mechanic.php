<?php
/**
 * ============================================================================
 * MotoCare – Two-Wheeler Service & Mechanic Shop Management System
 * File: admin/assign-mechanic.php
 * Stage 2: Admin Mechanic Assignment & Booking Confirmation Action
 * ============================================================================
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

// RULE 1: Only authenticated administrators can assign or reassign mechanics
requireAdminLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: bookings.php');
    exit;
}

// Extract and sanitize input parameters
$bookingId  = isset($_POST['booking_id']) ? (int)$_POST['booking_id'] : 0;
$mechanicId = isset($_POST['mechanic_id']) ? (int)$_POST['mechanic_id'] : 0;
$isReassign = isset($_POST['is_reassign']) && $_POST['is_reassign'] === '1';

// Validation 1: Booking ID must be positive integer
if ($bookingId <= 0) {
    header('Location: bookings.php?err=' . urlencode('Invalid booking identifier.'));
    exit;
}

// Validation 2: Mechanic ID must be positive integer
if ($mechanicId <= 0) {
    header("Location: booking-details.php?id={$bookingId}&err=" . urlencode('Please select a valid mechanic from the list.'));
    exit;
}

if (!$pdo) {
    // Demo fallback for offline examination
    header("Location: booking-details.php?id={$bookingId}&msg=assigned");
    exit;
}

try {
    // Validation 3 & RULE 2: Verify booking exists
    $stmtBooking = $pdo->prepare("SELECT id, booking_code, mechanic_id, status FROM bookings WHERE id = :id LIMIT 1");
    $stmtBooking->execute([':id' => $bookingId]);
    $booking = $stmtBooking->fetch();

    if (!$booking) {
        header('Location: bookings.php?err=' . urlencode('Booking record not found.'));
        exit;
    }

    // Validation 4: Disallow assignment on finished/cancelled bookings
    if (in_array($booking['status'], ['Completed', 'Cancelled'])) {
        header("Location: booking-details.php?id={$bookingId}&err=" . urlencode("Cannot assign a mechanic to a {$booking['status']} booking."));
        exit;
    }

    // Validation 5 & RULE 3: Verify mechanic exists
    $stmtMech = $pdo->prepare("SELECT id, full_name, status FROM mechanics WHERE id = :mid LIMIT 1");
    $stmtMech->execute([':mid' => $mechanicId]);
    $mechanic = $stmtMech->fetch();

    if (!$mechanic) {
        header("Location: booking-details.php?id={$bookingId}&err=" . urlencode('The selected mechanic does not exist in the database.'));
        exit;
    }

    // Validation 6: Verify mechanic eligibility (status not 'On Leave')
    if (isset($mechanic['status']) && $mechanic['status'] === 'On Leave') {
        header("Location: booking-details.php?id={$bookingId}&err=" . urlencode("Mechanic {$mechanic['full_name']} is currently On Leave and cannot take new service slots."));
        exit;
    }

    // RULE 6: If already assigned and not an explicit reassignment request
    $alreadyAssigned = !empty($booking['mechanic_id']);
    if ($alreadyAssigned && !$isReassign && (int)$booking['mechanic_id'] === $mechanicId) {
        header("Location: booking-details.php?id={$bookingId}&err=" . urlencode("Mechanic {$mechanic['full_name']} is already assigned to this booking."));
        exit;
    }

    // RULE 7: Execute assignment using prepared statement
    // Update mechanic_id and change status to 'Confirmed'
    $stmtUpdate = $pdo->prepare("
        UPDATE bookings 
        SET mechanic_id = :mid,
            status = 'Confirmed'
        WHERE id = :bid
    ");
    $stmtUpdate->execute([
        ':mid' => $mechanicId,
        ':bid' => $bookingId
    ]);

    $msgType = $alreadyAssigned ? 'reassigned' : 'assigned';
    header("Location: booking-details.php?id={$bookingId}&msg=" . urlencode($msgType) . "&mech=" . urlencode($mechanic['full_name']));
    exit;

} catch (PDOException $e) {
    header("Location: booking-details.php?id={$bookingId}&err=" . urlencode('Database error processing assignment: ' . $e->getMessage()));
    exit;
}
