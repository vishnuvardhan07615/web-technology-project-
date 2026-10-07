<?php
/**
 * ============================================================================
 * MotoCare – Two-Wheeler Service & Mechanic Shop Management System
 * File: mechanic/update-status.php
 * Stage 2: Mechanic Status Progression, Job Card & Atomic Stock Deduction
 * ============================================================================
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

// 1. Verify mechanic authentication
requireMechanicLogin();
$mechanic = getCurrentMechanic();
$mechanicId = (int)$mechanic['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

// 2. Validate booking ID
$bookingId = isset($_POST['booking_id']) ? (int)$_POST['booking_id'] : 0;
$action    = trim($_POST['action'] ?? 'advance_status'); // 'advance_status' or 'save_notes_only'
$requestedStatus = trim($_POST['next_status'] ?? '');

// Inputs for job card notes
$inspectionNotes = trim($_POST['inspection_notes'] ?? '');
$workDone        = trim($_POST['work_done'] ?? '');
$laborHours      = isset($_POST['labor_hours']) && is_numeric($_POST['labor_hours']) ? (float)$_POST['labor_hours'] : 0.00;
$technicianNotes = trim($_POST['technician_notes'] ?? '');
$customPartsUsedText = trim($_POST['parts_used'] ?? '');

// Parts form payload
$hasPartsForm = isset($_POST['has_parts_form']) || isset($_POST['parts']) || isset($_POST['part_id']) || isset($_POST['spare_parts']);
$submittedPartsRaw = [];

if (isset($_POST['parts']) && is_array($_POST['parts'])) {
    foreach ($_POST['parts'] as $p) {
        if (is_array($p) && isset($p['part_id'])) {
            $submittedPartsRaw[] = [
                'part_id'  => $p['part_id'],
                'quantity' => $p['quantity'] ?? ''
            ];
        }
    }
} elseif (isset($_POST['spare_parts']) && is_array($_POST['spare_parts'])) {
    foreach ($_POST['spare_parts'] as $p) {
        if (is_array($p) && isset($p['part_id'])) {
            $submittedPartsRaw[] = [
                'part_id'  => $p['part_id'],
                'quantity' => $p['quantity'] ?? ''
            ];
        }
    }
} elseif (isset($_POST['part_id']) && is_array($_POST['part_id'])) {
    $qtys = $_POST['quantity'] ?? [];
    foreach ($_POST['part_id'] as $idx => $pid) {
        $submittedPartsRaw[] = [
            'part_id'  => $pid,
            'quantity' => $qtys[$idx] ?? ''
        ];
    }
}

if ($bookingId <= 0) {
    header('Location: dashboard.php?err=' . urlencode('Invalid service booking ID.'));
    exit;
}

if (!$pdo) {
    // Offline demo fallback
    header("Location: job-card.php?id={$bookingId}&msg=" . urlencode('Status updated successfully (Demo Mode).'));
    exit;
}

// Allowed Strict Sequential Transitions (Section 5 Requirement)
$allowedTransitions = [
    'Confirmed'           => 'Vehicle Received',
    'Vehicle Received'    => 'Inspection',
    'Inspection'          => 'Service In Progress',
    'Service In Progress' => 'Quality Check',
    'Quality Check'       => 'Ready for Delivery',
    'Ready for Delivery'  => 'Completed'
];

try {
    // BEGIN ATOMIC TRANSACTION (Sections 11, 12, 18, 23)
    $pdo->beginTransaction();

    // 3 & 4. Verify booking exists and belongs STRICTLY to this mechanic (Row Lock)
    $stmtB = $pdo->prepare("SELECT id, booking_code, mechanic_id, status FROM bookings WHERE id = :id FOR UPDATE");
    $stmtB->execute([':id' => $bookingId]);
    $booking = $stmtB->fetch();

    if (!$booking) {
        $pdo->rollBack();
        header('Location: dashboard.php?err=' . urlencode('Booking record not found.'));
        exit;
    }

    // SECTION 3, 19, 21, 22: Strict Anti-IDOR Authorization Check
    if ((int)$booking['mechanic_id'] !== $mechanicId) {
        $pdo->rollBack();
        header('Location: dashboard.php?err=' . urlencode('Access Denied: You are not authorized to update this service ticket.'));
        exit;
    }

    $currentStatus = $booking['status'];

    // 5. Check or create existing service record (Row Lock)
    $stmtRec = $pdo->prepare("SELECT * FROM service_records WHERE booking_id = :bid FOR UPDATE");
    $stmtRec->execute([':bid' => $bookingId]);
    $existingRecord = $stmtRec->fetch();

    // Prepare merged fields
    $updInspectionNotes = ($inspectionNotes !== '') ? $inspectionNotes : ($existingRecord['inspection_notes'] ?? '');
    $updWorkDone        = ($workDone !== '') ? $workDone : ($existingRecord['work_done'] ?? '');
    $updLaborHours      = ($laborHours > 0) ? $laborHours : (float)($existingRecord['labor_hours'] ?? 0.00);
    $updTechnicianNotes = ($technicianNotes !== '') ? $technicianNotes : ($existingRecord['technician_notes'] ?? '');
    $updPartsUsed       = ($customPartsUsedText !== '') ? $customPartsUsedText : ($existingRecord['parts_used'] ?? '');

    $serviceRecordId = 0;
    if ($existingRecord) {
        $serviceRecordId = (int)$existingRecord['id'];
    } else {
        // Create initial service record row so foreign keys for service_record_parts exist
        $stmtInsRec = $pdo->prepare("
            INSERT INTO service_records (
                booking_id, mechanic_id, inspection_notes, work_done,
                labor_hours, technician_notes, parts_used, service_start,
                service_status, created_at
            ) VALUES (
                :bid, :mid, :inotes, :work,
                :hours, :notes, '', NOW(),
                :status, NOW()
            )
        ");
        $stmtInsRec->execute([
            ':bid'    => $bookingId,
            ':mid'    => $mechanicId,
            ':inotes' => $updInspectionNotes,
            ':work'   => $updWorkDone,
            ':hours'  => $updLaborHours,
            ':notes'  => $updTechnicianNotes,
            ':status' => $currentStatus
        ]);
        $serviceRecordId = (int)$pdo->lastInsertId();
    }

    // 6. PROCESS SPARE PARTS SELECTION & DEDUCTION (Sections 7, 8, 9, 10, 11, 12, 13, 14, 15)
    if ($hasPartsForm) {
        // Validate submitted parts rows
        $submittedPartsMap = [];
        foreach ($submittedPartsRaw as $item) {
            $pidRaw = $item['part_id'] ?? '';
            $qtyRaw = $item['quantity'] ?? '';

            // Skip entirely empty row if mechanic left empty
            if (empty(trim((string)$pidRaw)) && empty(trim((string)$qtyRaw))) {
                continue;
            }

            // Section 22: Validate spare_part_id
            if (!is_numeric($pidRaw) || (int)$pidRaw <= 0) {
                $pdo->rollBack();
                header("Location: job-card.php?id={$bookingId}&err=" . urlencode('Invalid spare_part_id: Please select a valid catalog part.'));
                exit;
            }
            $pid = (int)$pidRaw;

            // Section 9: Validate Quantity (integer, greater than 0, reject 0, negative, decimals)
            $qtyStr = trim((string)$qtyRaw);
            if (!is_numeric($qtyStr) || (string)(int)$qtyStr !== $qtyStr || (int)$qtyStr <= 0) {
                $pdo->rollBack();
                header("Location: job-card.php?id={$bookingId}&err=" . urlencode('Invalid quantity: Spare part quantities must be whole positive integers greater than zero.'));
                exit;
            }
            $qty = (int)$qtyStr;

            // Consolidate duplicates
            $submittedPartsMap[$pid] = ($submittedPartsMap[$pid] ?? 0) + $qty;
        }

        // Fetch currently saved parts for this job card
        $stmtCurParts = $pdo->prepare("
            SELECT spare_part_id, quantity, unit_price 
            FROM service_record_parts 
            WHERE service_record_id = :srid 
            FOR UPDATE
        ");
        $stmtCurParts->execute([':srid' => $serviceRecordId]);
        $curPartsRows = $stmtCurParts->fetchAll();

        $existingPartsMap = [];
        foreach ($curPartsRows as $cp) {
            $existingPartsMap[(int)$cp['spare_part_id']] = (int)$cp['quantity'];
        }

        // Distinct list of all involved part IDs
        $allPartIds = array_unique(array_merge(array_keys($submittedPartsMap), array_keys($existingPartsMap)));

        // Lock & verify all involved spare parts in inventory
        $partDbRows = [];
        foreach ($allPartIds as $pid) {
            $stmtPart = $pdo->prepare("
                SELECT id, part_name, part_number, price, stock_quantity, status 
                FROM spare_parts 
                WHERE id = :id 
                FOR UPDATE
            ");
            $stmtPart->execute([':id' => $pid]);
            $pRow = $stmtPart->fetch();

            if (!$pRow) {
                $pdo->rollBack();
                header("Location: job-card.php?id={$bookingId}&err=" . urlencode("Invalid spare_part_id: Part #{$pid} not found in inventory."));
                exit;
            }
            $partDbRows[$pid] = $pRow;
        }

        // Section 10 & 12: Verify available stock against NET DELTA
        foreach ($allPartIds as $pid) {
            $prevQty = $existingPartsMap[$pid] ?? 0;
            $newQty  = $submittedPartsMap[$pid] ?? 0;
            $netDelta = $newQty - $prevQty;

            // If net increase in parts usage, verify stock availability
            if ($netDelta > 0) {
                $availableStock = (int)$partDbRows[$pid]['stock_quantity'];
                if ($availableStock < $netDelta) {
                    $pdo->rollBack();
                    header("Location: job-card.php?id={$bookingId}&err=" . urlencode("Insufficient stock for '{$partDbRows[$pid]['part_name']}'. Available: {$availableStock}, requested additional: {$netDelta}."));
                    exit;
                }
            }
        }

        // Section 11, 12, 15: Apply stock adjustments & log inventory transactions
        foreach ($allPartIds as $pid) {
            $prevQty = $existingPartsMap[$pid] ?? 0;
            $newQty  = $submittedPartsMap[$pid] ?? 0;
            $netDelta = $newQty - $prevQty;

            if ($netDelta !== 0) {
                // 1. Deduct or return inventory
                $stmtStockUpd = $pdo->prepare("UPDATE spare_parts SET stock_quantity = stock_quantity - :delta WHERE id = :id");
                $stmtStockUpd->execute([':delta' => $netDelta, ':id' => $pid]);

                // 2. Audit Trail in inventory_transactions (Section 17)
                $txType = ($netDelta > 0) ? 'SERVICE_USAGE' : 'RETURN';
                $txQty = ($netDelta > 0) ? -$netDelta : abs($netDelta);
                $txReason = ($netDelta > 0) 
                    ? "Job Card {$booking['booking_code']} parts usage ({$netDelta} pcs)" 
                    : "Job Card {$booking['booking_code']} parts returned/adjusted (" . abs($netDelta) . " pcs)";

                $stmtTx = $pdo->prepare("
                    INSERT INTO inventory_transactions (spare_part_id, transaction_type, quantity, reference_id, reason, created_at)
                    VALUES (:pid, :txtype, :qty, :refid, :reason, NOW())
                ");
                $stmtTx->execute([
                    ':pid'    => $pid,
                    ':txtype' => $txType,
                    ':qty'    => $txQty,
                    ':refid'  => $serviceRecordId,
                    ':reason' => $txReason
                ]);
            }

            // 3. Sync service_record_parts table (Section 13 & 14)
            if ($newQty === 0) {
                // Part was removed completely from job card
                $stmtDel = $pdo->prepare("DELETE FROM service_record_parts WHERE service_record_id = :srid AND spare_part_id = :pid");
                $stmtDel->execute([':srid' => $serviceRecordId, ':pid' => $pid]);
            } elseif (isset($existingPartsMap[$pid])) {
                // Part quantity updated
                $stmtUpdSRP = $pdo->prepare("UPDATE service_record_parts SET quantity = :qty, updated_at = NOW() WHERE service_record_id = :srid AND spare_part_id = :pid");
                $stmtUpdSRP->execute([':qty' => $newQty, ':srid' => $serviceRecordId, ':pid' => $pid]);
            } else {
                // New part added: snapshot current unit price (Section 14)
                $priceSnapshot = (float)$partDbRows[$pid]['price'];
                $stmtInsSRP = $pdo->prepare("
                    INSERT INTO service_record_parts (service_record_id, spare_part_id, quantity, unit_price, created_at)
                    VALUES (:srid, :pid, :qty, :price, NOW())
                ");
                $stmtInsSRP->execute([
                    ':srid'  => $serviceRecordId,
                    ':pid'   => $pid,
                    ':qty'   => $newQty,
                    ':price' => $priceSnapshot
                ]);
            }
        }

        // Build textual summary for legacy compatibility
        $partsSummaryItems = [];
        foreach ($submittedPartsMap as $pid => $qty) {
            $partsSummaryItems[] = "{$partDbRows[$pid]['part_name']} ({$qty})";
        }
        $updPartsUsed = implode(', ', $partsSummaryItems);
    }

    // 7. HANDLE ACTION: SAVE NOTES ONLY OR SAVE JOB CARD
    if ($action === 'save_notes_only' || $action === 'save_job_card') {
        $stmtUpdRec = $pdo->prepare("
            UPDATE service_records 
            SET inspection_notes = :inotes,
                work_done        = :work,
                labor_hours      = :hours,
                technician_notes = :notes,
                parts_used       = :parts
            WHERE id = :srid
        ");
        $stmtUpdRec->execute([
            ':inotes' => $updInspectionNotes,
            ':work'   => $updWorkDone,
            ':hours'  => $updLaborHours,
            ':notes'  => $updTechnicianNotes,
            ':parts'  => $updPartsUsed,
            ':srid'   => $serviceRecordId
        ]);

        $pdo->commit();
        header("Location: job-card.php?id={$bookingId}&msg=" . urlencode('Job card and spare parts usage saved successfully.'));
        exit;
    }

    // 8. HANDLE ACTION: ADVANCE STATUS
    if (empty($requestedStatus)) {
        $pdo->rollBack();
        header("Location: job-card.php?id={$bookingId}&err=" . urlencode('No target status specified.'));
        exit;
    }

    // Enforce valid transition along state machine
    if (!isset($allowedTransitions[$currentStatus]) || $allowedTransitions[$currentStatus] !== $requestedStatus) {
        $allowedNext = $allowedTransitions[$currentStatus] ?? 'None';
        $pdo->rollBack();
        header("Location: job-card.php?id={$bookingId}&err=" . urlencode("Invalid status progression from '{$currentStatus}' to '{$requestedStatus}'. Expected '{$allowedNext}'."));
        exit;
    }

    // BUSINESS PREREQUISITE 1: Transition from Inspection -> Service In Progress
    if ($currentStatus === 'Inspection' && $requestedStatus === 'Service In Progress') {
        if (empty(trim($updInspectionNotes))) {
            $pdo->rollBack();
            header("Location: job-card.php?id={$bookingId}&err=" . urlencode('Please record diagnostic inspection findings before starting workshop service.'));
            exit;
        }
    }

    // BUSINESS PREREQUISITE 2: Transition from Ready for Delivery -> Completed
    if ($currentStatus === 'Ready for Delivery' && $requestedStatus === 'Completed') {
        if (empty(trim($updWorkDone))) {
            $pdo->rollBack();
            header("Location: job-card.php?id={$bookingId}&err=" . urlencode('Cannot mark Completed: Please log the work performed and labor details on the job card first.'));
            exit;
        }
    }

    $isStarting = in_array($requestedStatus, ['Vehicle Received', 'Inspection', 'Service In Progress']) ? 1 : 0;
    $isEnding   = ($requestedStatus === 'Completed') ? 1 : 0;

    // Update service record status and timestamps
    $stmtRecUpd = $pdo->prepare("
        UPDATE service_records 
        SET inspection_notes = :inotes,
            work_done        = :work,
            labor_hours      = :hours,
            technician_notes = :notes,
            parts_used       = :parts,
            service_status   = :status,
            service_start    = CASE WHEN service_start IS NULL AND :is_starting = 1 THEN NOW() ELSE service_start END,
            service_end      = CASE WHEN :is_ending = 1 THEN NOW() ELSE service_end END
        WHERE id = :srid
    ");
    $stmtRecUpd->execute([
        ':inotes'      => $updInspectionNotes,
        ':work'        => $updWorkDone,
        ':hours'       => $updLaborHours,
        ':notes'       => $updTechnicianNotes,
        ':parts'       => $updPartsUsed,
        ':status'      => $requestedStatus,
        ':is_starting' => $isStarting,
        ':is_ending'   => $isEnding,
        ':srid'        => $serviceRecordId
    ]);

    // Update booking status
    $stmtBkUpd = $pdo->prepare("UPDATE bookings SET status = :status WHERE id = :bid");
    $stmtBkUpd->execute([
        ':status' => $requestedStatus,
        ':bid'    => $bookingId
    ]);

    // Commit Transaction
    $pdo->commit();

    header("Location: job-card.php?id={$bookingId}&msg=" . urlencode("Status progressed to '{$requestedStatus}' successfully."));
    exit;

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    header("Location: job-card.php?id={$bookingId}&err=" . urlencode('Database transaction error: ' . $e->getMessage()));
    exit;
}
