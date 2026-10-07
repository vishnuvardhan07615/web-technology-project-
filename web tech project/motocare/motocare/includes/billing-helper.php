<?php
/**
 * ============================================================================
 * MotoCare – Two-Wheeler Service & Mechanic Shop Management System
 * File: includes/billing-helper.php
 * Stage 2: Central Billing, GST/Tax Engine, Invoicing & Payment Tracker
 * ============================================================================
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';

/**
 * Perform exact decimal arithmetic calculations for billing totals and taxes.
 * Prevents negative values, floating-point drift, and invalid discounts.
 *
 * @param float|string $serviceCharge Applicable labor/service charge
 * @param float|string $partsSubtotal Sum of (quantity * historical unit_price)
 * @param float|string $discount Promotional discount applied
 * @param float|string $gstRate Total GST percentage (default 18%)
 * @return array Calculated financial metrics
 */
function calculateBillTotals($serviceCharge, $partsSubtotal, $discount = 0.00, $gstRate = GST_RATE) {
    $serviceCharge = max(0.00, round((float)$serviceCharge, 2));
    $partsSubtotal = max(0.00, round((float)$partsSubtotal, 2));
    $gstRate       = max(0.00, round((float)$gstRate, 2));

    // 1. Subtotal = Labor/Service charge + Spare parts subtotal
    $subtotal = round($serviceCharge + $partsSubtotal, 2);

    // 2. Validate discount (cannot be negative, cannot exceed subtotal)
    $discount = round((float)$discount, 2);
    if ($discount < 0.00) {
        $discount = 0.00;
    }
    if ($discount > $subtotal) {
        $discount = $subtotal;
    }

    // 3. Taxable Amount = Subtotal - Discount
    $taxableAmount = round($subtotal - $discount, 2);

    // 4. Indian GST split (CGST 50% + SGST 50%)
    $halfRate = $gstRate / 2.0;
    $cgst = round($taxableAmount * ($halfRate / 100.0), 2);
    $sgst = round($taxableAmount * ($halfRate / 100.0), 2);
    $totalGst = round($cgst + $sgst, 2);

    // 5. Total Amount = Taxable Amount + CGST + SGST
    $totalAmount = round($taxableAmount + $totalGst, 2);

    return [
        'service_charge' => $serviceCharge,
        'parts_subtotal' => $partsSubtotal,
        'subtotal'       => $subtotal,
        'discount'       => $discount,
        'taxable_amount' => $taxableAmount,
        'gst_rate'       => $gstRate,
        'cgst'           => $cgst,
        'sgst'           => $sgst,
        'tax'            => $totalGst,
        'total_amount'   => $totalAmount
    ];
}

/**
 * Generate a collision-safe, sequential invoice number.
 * Format: MC-INV-YYYY-XXXX (e.g., MC-INV-2026-0001)
 *
 * @param PDO $pdo Active database connection
 * @return string Unique invoice code
 */
function generateUniqueInvoiceNumber(PDO $pdo) {
    $year = date('Y');
    $prefix = "MC-INV-{$year}-";

    // Find highest current sequence number for this year
    $stmt = $pdo->prepare("
        SELECT invoice_number 
        FROM bills 
        WHERE invoice_number LIKE :prefix 
        ORDER BY id DESC 
        LIMIT 1
    ");
    $stmt->execute([':prefix' => "{$prefix}%"]);
    $lastInvoice = $stmt->fetchColumn();

    $nextSeq = 1;
    if ($lastInvoice) {
        $parts = explode('-', $lastInvoice);
        $lastNum = (int)end($parts);
        $nextSeq = $lastNum + 1;
    }

    // Ensure collision safety
    do {
        $invoiceNum = $prefix . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM bills WHERE invoice_number = :inv");
        $stmtCheck->execute([':inv' => $invoiceNum]);
        $exists = (int)$stmtCheck->fetchColumn() > 0;
        if ($exists) {
            $nextSeq++;
        }
    } while ($exists);

    return $invoiceNum;
}

/**
 * Atomically create or retrieve a bill for a completed booking.
 * Idempotent: If a bill already exists, opens the existing bill rather than duplicating.
 *
 * @param PDO $pdo Active database connection
 * @param int $bookingId ID of the service booking
 * @param float $discount Optional initial discount
 * @return array Result array with status, bill, or error message
 */
function getOrCreateBillForBooking(PDO $pdo, int $bookingId, float $discount = 0.00) {
    if ($bookingId <= 0) {
        return ['success' => false, 'error' => 'Invalid booking ID specified.'];
    }

    try {
        $pdo->beginTransaction();

        // 1. Lock booking record
        $stmtB = $pdo->prepare("
            SELECT b.*, s.price AS catalog_service_price, s.service_name
            FROM bookings b
            JOIN services s ON b.service_id = s.id
            WHERE b.id = :id
            FOR UPDATE
        ");
        $stmtB->execute([':id' => $bookingId]);
        $booking = $stmtB->fetch(PDO::FETCH_ASSOC);

        if (!$booking) {
            $pdo->rollBack();
            return ['success' => false, 'error' => 'Service booking not found in database.'];
        }

        // Section 5 Requirement: Only Completed service bookings can generate a bill
        if ($booking['status'] !== 'Completed') {
            $pdo->rollBack();
            return [
                'success' => false, 
                'error'   => "Incomplete booking cannot generate bill. Current status is '{$booking['status']}'. Workshop service must be marked Completed first."
            ];
        }

        // Section 5 Verification: Customer exists
        $stmtC = $pdo->prepare("SELECT id FROM customers WHERE id = :cid");
        $stmtC->execute([':cid' => (int)$booking['customer_id']]);
        if (!$stmtC->fetchColumn()) {
            $pdo->rollBack();
            return ['success' => false, 'error' => 'Customer associated with this booking does not exist.'];
        }

        // Section 5 Verification: Vehicle exists
        $stmtV = $pdo->prepare("SELECT id FROM vehicles WHERE id = :vid");
        $stmtV->execute([':vid' => (int)$booking['vehicle_id']]);
        if (!$stmtV->fetchColumn()) {
            $pdo->rollBack();
            return ['success' => false, 'error' => 'Vehicle associated with this booking does not exist.'];
        }

        // Section 5 Verification: Service exists
        $stmtS = $pdo->prepare("SELECT id FROM services WHERE id = :sid");
        $stmtS->execute([':sid' => (int)$booking['service_id']]);
        if (!$stmtS->fetchColumn()) {
            $pdo->rollBack();
            return ['success' => false, 'error' => 'Service package associated with this booking does not exist.'];
        }

        // 2. Check for existing bill (Idempotency check - Section 5 & 13)
        $stmtExist = $pdo->prepare("SELECT * FROM bills WHERE booking_id = :bid FOR UPDATE");
        $stmtExist->execute([':bid' => $bookingId]);
        $existingBill = $stmtExist->fetch(PDO::FETCH_ASSOC);

        if ($existingBill) {
            // Bill already exists! Return without creating duplicate
            $pdo->commit();
            return [
                'success'    => true, 
                'is_existing' => true, 
                'bill'       => $existingBill,
                'bill_id'    => (int)$existingBill['id'],
                'invoice'    => $existingBill['invoice_number']
            ];
        }

        // 3. Fetch Service Record (Section 5: verify service record exists)
        $stmtRec = $pdo->prepare("SELECT id, mechanic_id FROM service_records WHERE booking_id = :bid FOR UPDATE");
        $stmtRec->execute([':bid' => $bookingId]);
        $record = $stmtRec->fetch(PDO::FETCH_ASSOC);
        if (!$record || empty($record['id'])) {
            $pdo->rollBack();
            return ['success' => false, 'error' => 'Service record / job card does not exist for this completed booking.'];
        }
        $recordId = (int)$record['id'];

        // Section 5 Verification: Assigned mechanic exists
        $mechId = !empty($booking['mechanic_id']) ? (int)$booking['mechanic_id'] : (!empty($record['mechanic_id']) ? (int)$record['mechanic_id'] : 0);
        if ($mechId <= 0) {
            $pdo->rollBack();
            return ['success' => false, 'error' => 'Assigned mechanic does not exist for this completed booking.'];
        }
        $stmtM = $pdo->prepare("SELECT id FROM mechanics WHERE id = :mid");
        $stmtM->execute([':mid' => $mechId]);
        if (!$stmtM->fetchColumn()) {
            $pdo->rollBack();
            return ['success' => false, 'error' => 'Assigned mechanic record not found in database.'];
        }

        // Section 5 Verification: No invalid/orphan parts records exist
        $stmtOrphan = $pdo->prepare("
            SELECT COUNT(*) FROM service_record_parts srp
            LEFT JOIN spare_parts sp ON srp.spare_part_id = sp.id
            WHERE srp.service_record_id = :srid AND sp.id IS NULL
        ");
        $stmtOrphan->execute([':srid' => $recordId]);
        if ((int)$stmtOrphan->fetchColumn() > 0) {
            $pdo->rollBack();
            return ['success' => false, 'error' => 'Invalid or orphan spare parts records found on this job card.'];
        }

        // 4. Calculate Spare Parts Subtotal using HISTORICAL unit_price snapshot (Section 2 & 18)
        $partsSubtotal = 0.00;
        $stmtParts = $pdo->prepare("
            SELECT quantity, unit_price 
            FROM service_record_parts 
            WHERE service_record_id = :srid
        ");
        $stmtParts->execute([':srid' => $recordId]);
        $usedParts = $stmtParts->fetchAll(PDO::FETCH_ASSOC);
        foreach ($usedParts as $up) {
            $lineTotal = round((float)$up['unit_price'] * (int)$up['quantity'], 2);
            $partsSubtotal = round($partsSubtotal + $lineTotal, 2);
        }

        // 5. Service / Labor Charge
        $serviceCharge = (float)($booking['service_price'] ?? $booking['catalog_service_price'] ?? 0.00);

        // 6. Calculate all taxes and grand totals
        $calc = calculateBillTotals($serviceCharge, $partsSubtotal, $discount, GST_RATE);

        // 7. Generate collision-safe unique invoice number
        $invoiceNumber = generateUniqueInvoiceNumber($pdo);

        // 8. Insert new Bill record
        $stmtInsert = $pdo->prepare("
            INSERT INTO bills (
                invoice_number, booking_id, customer_id, vehicle_id,
                service_record_id, service_charge, parts_subtotal, subtotal,
                discount, taxable_amount, gst_rate, cgst, sgst, tax,
                total_amount, payment_status, payment_method, amount_paid,
                balance_due, invoice_date, bill_date, created_at, updated_at
            ) VALUES (
                :inv, :bid, :cid, :vid,
                :srid, :scharge, :psub, :sub,
                :disc, :taxable, :gstrate, :cgst, :sgst, :tax,
                :total, 'Pending', NULL, 0.00,
                :bal, CURDATE(), NOW(), NOW(), NOW()
            )
        ");
        $stmtInsert->execute([
            ':inv'     => $invoiceNumber,
            ':bid'     => $bookingId,
            ':cid'     => (int)$booking['customer_id'],
            ':vid'     => (int)$booking['vehicle_id'],
            ':srid'    => $recordId,
            ':scharge' => $calc['service_charge'],
            ':psub'    => $calc['parts_subtotal'],
            ':sub'     => $calc['subtotal'],
            ':disc'    => $calc['discount'],
            ':taxable' => $calc['taxable_amount'],
            ':gstrate' => $calc['gst_rate'],
            ':cgst'    => $calc['cgst'],
            ':sgst'    => $calc['sgst'],
            ':tax'     => $calc['tax'],
            ':total'   => $calc['total_amount'],
            ':bal'     => $calc['total_amount']
        ]);

        $newBillId = (int)$pdo->lastInsertId();

        // 9. Fetch newly created bill record
        $stmtNew = $pdo->prepare("SELECT * FROM bills WHERE id = :id");
        $stmtNew->execute([':id' => $newBillId]);
        $newBill = $stmtNew->fetch(PDO::FETCH_ASSOC);

        $pdo->commit();

        return [
            'success'     => true,
            'is_existing' => false,
            'bill'        => $newBill,
            'bill_id'     => $newBillId,
            'invoice'     => $invoiceNumber
        ];

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['success' => false, 'error' => 'Database error generating invoice: ' . $e->getMessage()];
    }
}

/**
 * Record a payment against an outstanding bill with immutable ledger entries.
 * Enforces transaction protection, non-zero positive amounts, and balance validation.
 *
 * @param PDO $pdo Active database connection
 * @param int $billId ID of the bill
 * @param float $amount Amount paid in INR
 * @param string $paymentMethod Method: Cash, UPI, Card, Bank Transfer, Other
 * @param string $ref Transaction or reference number
 * @param string $notes Optional payment notes
 * @param string $date Payment date (YYYY-MM-DD), defaults to today
 * @return array Result array with status and updated totals
 */
function recordBillPayment(PDO $pdo, int $billId, float $amount, string $paymentMethod, string $ref = '', string $notes = '', string $date = '') {
    if ($billId <= 0) {
        return ['success' => false, 'error' => 'Invalid invoice ID specified.'];
    }

    $amount = round((float)$amount, 2);
    if ($amount <= 0.00) {
        return ['success' => false, 'error' => 'Payment amount must be greater than zero.'];
    }

    if (!in_array($paymentMethod, PAYMENT_METHODS)) {
        return ['success' => false, 'error' => 'Invalid payment method selected.'];
    }

    $paymentDate = !empty($date) ? $date : date('Y-m-d');

    try {
        $pdo->beginTransaction();

        // 1. Lock bill row
        $stmtBill = $pdo->prepare("SELECT * FROM bills WHERE id = :id FOR UPDATE");
        $stmtBill->execute([':id' => $billId]);
        $bill = $stmtBill->fetch(PDO::FETCH_ASSOC);

        if (!$bill) {
            $pdo->rollBack();
            return ['success' => false, 'error' => 'Invoice record not found.'];
        }

        // 2. Check if already fully settled
        if ($bill['payment_status'] === 'Paid' || (float)$bill['balance_due'] <= 0.00) {
            $pdo->rollBack();
            return ['success' => false, 'error' => 'This invoice is already fully settled. No balance remaining.'];
        }

        // 3. Prevent overpayment (amount cannot exceed balance due)
        $currentBalance = round((float)$bill['balance_due'], 2);
        if ($amount > $currentBalance) {
            $pdo->rollBack();
            return [
                'success' => false, 
                'error'   => "Payment amount (₹" . number_format($amount, 2) . ") exceeds remaining balance (₹" . number_format($currentBalance, 2) . ")."
            ];
        }

        // 4. Insert into payments table
        $stmtPay = $pdo->prepare("
            INSERT INTO payments (
                bill_id, amount, payment_method, transaction_reference,
                payment_date, notes, created_at
            ) VALUES (
                :bid, :amt, :method, :ref,
                :pdate, :notes, NOW()
            )
        ");
        $stmtPay->execute([
            ':bid'    => $billId,
            ':amt'    => $amount,
            ':method' => $paymentMethod,
            ':ref'    => !empty($ref) ? trim($ref) : null,
            ':pdate'  => $paymentDate,
            ':notes'  => !empty($notes) ? trim($notes) : null
        ]);

        // 5. Recalculate total paid and balance due strictly from ledger
        $stmtSum = $pdo->prepare("SELECT COALESCE(SUM(amount), 0.00) FROM payments WHERE bill_id = :bid");
        $stmtSum->execute([':bid' => $billId]);
        $totalPaid = round((float)$stmtSum->fetchColumn(), 2);

        $totalAmount = (float)$bill['total_amount'];
        $newBalance = max(0.00, round($totalAmount - $totalPaid, 2));

        // 6. Determine new payment status (Section 8)
        if ($newBalance <= 0.00) {
            $newStatus = 'Paid';
        } elseif ($totalPaid > 0.00) {
            $newStatus = 'Partially Paid';
        } else {
            $newStatus = 'Pending';
        }

        // 7. Update bill row
        $stmtUpd = $pdo->prepare("
            UPDATE bills 
            SET amount_paid    = :paid,
                balance_due    = :bal,
                payment_status = :status,
                payment_method = :method,
                updated_at     = NOW()
            WHERE id = :id
        ");
        $stmtUpd->execute([
            ':paid'   => $totalPaid,
            ':bal'    => $newBalance,
            ':status' => $newStatus,
            ':method' => $paymentMethod,
            ':id'     => $billId
        ]);

        $pdo->commit();

        return [
            'success'        => true,
            'amount_paid'    => $totalPaid,
            'balance_due'    => $newBalance,
            'payment_status' => $newStatus,
            'message'        => "Payment of ₹" . number_format($amount, 2) . " recorded successfully. Status: {$newStatus}."
        ];

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['success' => false, 'error' => 'Database error recording payment: ' . $e->getMessage()];
    }
}

/**
 * Fetch complete billing record with customer, vehicle, mechanic, service record,
 * spare parts line items, and transaction payment history.
 *
 * @param PDO $pdo Active database connection
 * @param int $billId ID of the bill
 * @return array|null Complete billing data structure
 */
function fetchFullBillDetails(PDO $pdo, int $billId) {
    if ($billId <= 0) return null;

    try {
        $stmt = $pdo->prepare("
            SELECT 
                b.*,
                bk.booking_code, bk.preferred_date, bk.preferred_time, bk.problem_description,
                c.id AS customer_id, c.full_name AS customer_name, c.phone AS customer_phone, 
                c.email AS customer_email, c.address AS customer_address,
                v.id AS vehicle_id, v.brand, v.model, v.registration_number, v.vehicle_type,
                s.id AS service_id, s.service_name, s.category AS service_category, s.description AS service_description,
                m.id AS mechanic_id, m.full_name AS mechanic_name, m.phone AS mechanic_phone, m.specialization AS mechanic_specialization,
                sr.id AS record_id, sr.inspection_notes, sr.work_done, sr.labor_hours, sr.technician_notes,
                sr.service_start, sr.service_end
            FROM bills b
            JOIN bookings bk ON b.booking_id = bk.id
            JOIN customers c ON b.customer_id = c.id
            JOIN vehicles v ON (b.vehicle_id = v.id OR bk.vehicle_id = v.id)
            JOIN services s ON bk.service_id = s.id
            LEFT JOIN mechanics m ON bk.mechanic_id = m.id
            LEFT JOIN service_records sr ON b.booking_id = sr.booking_id
            WHERE b.id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $billId]);
        $bill = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$bill) return null;

        // Fetch used parts line items
        $parts = [];
        if (!empty($bill['service_record_id']) || !empty($bill['record_id'])) {
            $srid = !empty($bill['service_record_id']) ? (int)$bill['service_record_id'] : (int)$bill['record_id'];
            $stmtParts = $pdo->prepare("
                SELECT srp.*, sp.part_name, sp.part_number, sp.category, sp.brand AS part_brand
                FROM service_record_parts srp
                JOIN spare_parts sp ON srp.spare_part_id = sp.id
                WHERE srp.service_record_id = :srid
                ORDER BY srp.id ASC
            ");
            $stmtParts->execute([':srid' => $srid]);
            $parts = $stmtParts->fetchAll(PDO::FETCH_ASSOC);
        }
        $bill['parts'] = $parts;

        // Fetch payment transactions
        $stmtPay = $pdo->prepare("
            SELECT * 
            FROM payments 
            WHERE bill_id = :bid 
            ORDER BY payment_date ASC, id ASC
        ");
        $stmtPay->execute([':bid' => $billId]);
        $bill['payments'] = $stmtPay->fetchAll(PDO::FETCH_ASSOC);

        return $bill;

    } catch (PDOException $e) {
        return null;
    }
}
