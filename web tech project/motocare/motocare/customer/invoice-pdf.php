<?php
/**
 * ============================================================================
 * MotoCare – Two-Wheeler Service & Mechanic Shop Management System
 * File: customer/invoice-pdf.php
 * Stage 2: Customer Tax Invoice PDF Download Controller (IDOR Protected)
 * ============================================================================
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/billing-helper.php';
require_once __DIR__ . '/../includes/pdf-helper.php';

requireCustomerLogin();
$customer = getCurrentCustomer();
$customerId = (int)$customer['id'];

$billId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($billId <= 0) {
    die("Error: Invalid invoice ID specified.");
}

$bill = null;
if ($pdo) {
    $bill = fetchFullBillDetails($pdo, $billId);
}

// Strict ownership check (Section 14: Prevent unauthorized PDF access / IDOR)
if (!$bill || (int)$bill['customer_id'] !== $customerId) {
    die("Access Denied: You do not have permission to view or download this invoice.");
}

// Generate PDF using pure-PHP engine
$pdf = new MotoCarePDF("Tax Invoice - " . $bill['invoice_number']);

// 1. Header & Branding
$pdf->setFont('bold', 20);
$pdf->setTextColor(1.0, 0.42, 0.0);
$pdf->text(40, 45, "MOTOCARE");
$pdf->setFont('bold', 12);
$pdf->setTextColor(0.2, 0.2, 0.2);
$pdf->text(155, 45, "TWO-WHEELER WORKSHOP");

$pdf->setFont('regular', 8.5);
$pdf->setTextColor(0.4, 0.4, 0.4);
$pdf->text(40, 58, COMPANY_NAME . " | " . COMPANY_ADDRESS);
$pdf->text(40, 69, "Phone: " . COMPANY_PHONE . " | Email: " . COMPANY_EMAIL . " | GSTIN: " . COMPANY_GSTIN);

// Top right: Invoice details
$pdf->setFont('bold', 14);
$pdf->setTextColor(0.1, 0.1, 0.1);
$pdf->textRight(550, 45, "TAX INVOICE");
$pdf->setFont('bold', 10);
$pdf->setTextColor(0.9, 0.3, 0.0);
$pdf->textRight(550, 58, $bill['invoice_number']);
$pdf->setFont('regular', 8.5);
$pdf->setTextColor(0.3, 0.3, 0.3);
$pdf->textRight(550, 69, "Date: " . date('d M Y, h:i A', strtotime($bill['bill_date'])));

$pdf->setStrokeColor(0.85, 0.85, 0.85);
$pdf->drawLine(40, 78, 555, 1.0);

// 2. Metadata Boxes
$boxY = 88;
$pdf->drawRect(40, $boxY, 245, 65, false, true);
$pdf->drawRect(300, $boxY, 255, 65, false, true);

// Customer Info
$pdf->setFont('bold', 8);
$pdf->setTextColor(0.5, 0.5, 0.5);
$pdf->text(48, $boxY + 12, "BILLED TO (CUSTOMER)");
$pdf->setFont('bold', 10);
$pdf->setTextColor(0.1, 0.1, 0.1);
$pdf->text(48, $boxY + 25, $bill['customer_name']);
$pdf->setFont('regular', 8.5);
$pdf->setTextColor(0.3, 0.3, 0.3);
$pdf->text(48, $boxY + 37, "Phone: " . $bill['customer_phone']);
$pdf->text(48, $boxY + 48, "Email: " . $bill['customer_email']);

// Vehicle & Service Info
$pdf->setFont('bold', 8);
$pdf->setTextColor(0.5, 0.5, 0.5);
$pdf->text(308, $boxY + 12, "VEHICLE & SERVICE APPOINTMENT");
$pdf->setFont('bold', 10);
$pdf->setTextColor(0.1, 0.1, 0.1);
$pdf->text(308, $boxY + 25, $bill['brand'] . " " . $bill['model'] . " (" . $bill['registration_number'] . ")");
$pdf->setFont('regular', 8.5);
$pdf->setTextColor(0.3, 0.3, 0.3);
$pdf->text(308, $boxY + 37, "Booking: " . $bill['booking_code'] . " | Service: " . $bill['service_name']);
$pdf->text(308, $boxY + 48, "Workshop Bay: MotoCare Main Hub");

// 3. Line Items Table Header
$tableY = 168;
$pdf->setFillColor(0.95, 0.95, 0.95);
$pdf->drawRect(40, $tableY, 515, 18, true, false);

$pdf->setFont('bold', 8);
$pdf->setTextColor(0.3, 0.3, 0.3);
$pdf->text(45, $tableY + 12, "#");
$pdf->text(65, $tableY + 12, "ITEM DESCRIPTION");
$pdf->text(285, $tableY + 12, "HSN/SKU");
$pdf->text(370, $tableY + 12, "QTY");
$pdf->text(430, $tableY + 12, "RATE");
$pdf->textRight(550, $tableY + 12, "AMOUNT");

$rowY = $tableY + 20;
$itemNum = 1;

// Labor / Service
$pdf->setFont('bold', 9);
$pdf->setTextColor(0.1, 0.1, 0.1);
$pdf->text(45, $rowY + 10, (string)$itemNum++);
$pdf->text(65, $rowY + 10, $bill['service_name'] . " (Service & Labor)");
$pdf->setFont('regular', 8);
$pdf->setTextColor(0.4, 0.4, 0.4);
$pdf->text(285, $rowY + 10, "998729");
$pdf->setTextColor(0.1, 0.1, 0.1);
$pdf->text(375, $rowY + 10, "1");
$pdf->text(420, $rowY + 10, number_format((float)$bill['service_charge'], 2));
$pdf->setFont('bold', 9);
$pdf->textRight(550, $rowY + 10, number_format((float)$bill['service_charge'], 2));

$rowY += 18;
$pdf->setStrokeColor(0.9, 0.9, 0.9);
$pdf->drawLine(40, $rowY, 555, 0.5);

// Spare Parts (Customer safe: no internal inventory details)
if (!empty($bill['parts'])) {
    foreach ($bill['parts'] as $part) {
        $pTotal = (float)$part['unit_price'] * (int)$part['quantity'];
        $pdf->setFont('regular', 8.5);
        $pdf->setTextColor(0.1, 0.1, 0.1);
        $pdf->text(45, $rowY + 10, (string)$itemNum++);
        $pdf->text(65, $rowY + 10, $part['part_name']);
        $pdf->setTextColor(0.4, 0.4, 0.4);
        $pdf->text(285, $rowY + 10, $part['part_number']);
        $pdf->setTextColor(0.1, 0.1, 0.1);
        $pdf->text(375, $rowY + 10, (string)$part['quantity']);
        $pdf->text(420, $rowY + 10, number_format((float)$part['unit_price'], 2));
        $pdf->setFont('bold', 8.5);
        $pdf->textRight(550, $rowY + 10, number_format($pTotal, 2));

        $rowY += 18;
        $pdf->drawLine(40, $rowY, 555, 0.5);
    }
}

// 4. Totals Breakdown
$totalsY = max($rowY + 15, 340);
$pdf->setStrokeColor(0.8, 0.8, 0.8);
$pdf->drawLine(340, $totalsY, 555, 1.0);

$tY = $totalsY + 14;
$pdf->setFont('regular', 8.5);
$pdf->setTextColor(0.3, 0.3, 0.3);
$pdf->text(350, $tY, "Labor / Service Charge:");
$pdf->textRight(550, $tY, number_format((float)$bill['service_charge'], 2));

$tY += 13;
$pdf->text(350, $tY, "Spare Parts Subtotal:");
$pdf->textRight(550, $tY, number_format((float)$bill['parts_subtotal'], 2));

$tY += 13;
$pdf->setFont('bold', 8.5);
$pdf->text(350, $tY, "Gross Subtotal:");
$pdf->textRight(550, $tY, number_format((float)$bill['subtotal'], 2));

if ((float)$bill['discount'] > 0) {
    $tY += 13;
    $pdf->setFont('regular', 8.5);
    $pdf->setTextColor(0.1, 0.6, 0.2);
    $pdf->text(350, $tY, "Discount Applied:");
    $pdf->textRight(550, $tY, "- " . number_format((float)$bill['discount'], 2));
    $pdf->setTextColor(0.3, 0.3, 0.3);
}

$tY += 13;
$pdf->setFont('regular', 8.5);
$pdf->text(350, $tY, "Taxable Amount:");
$pdf->textRight(550, $tY, number_format((float)$bill['taxable_amount'], 2));

$tY += 13;
$pdf->text(350, $tY, "CGST (9.0%):");
$pdf->textRight(550, $tY, number_format((float)$bill['cgst'], 2));

$tY += 13;
$pdf->text(350, $tY, "SGST (9.0%):");
$pdf->textRight(550, $tY, number_format((float)$bill['sgst'], 2));

$tY += 15;
$pdf->setFillColor(0.96, 0.96, 0.96);
$pdf->drawRect(340, $tY - 11, 215, 20, true, true);
$pdf->setFont('bold', 11);
$pdf->setTextColor(0.9, 0.3, 0.0);
$pdf->text(348, $tY + 3, "GRAND TOTAL:");
$pdf->textRight(548, $tY + 3, "INR " . number_format((float)$bill['total_amount'], 2));

$tY += 22;
$pdf->setFont('bold', 9);
$pdf->setTextColor(0.1, 0.6, 0.2);
$pdf->text(350, $tY, "Amount Paid:");
$pdf->textRight(550, $tY, number_format((float)$bill['amount_paid'], 2));

$tY += 14;
$pdf->setFont('bold', 9.5);
$pdf->setTextColor((float)$bill['balance_due'] > 0 ? 0.8 : 0.4, 0.1, 0.1);
$pdf->text(350, $tY, "Balance Due:");
$pdf->textRight(550, $tY, number_format((float)$bill['balance_due'], 2));

// Left Column: Payment Status Badge
$badgeY = $totalsY + 15;
$pdf->setFont('bold', 8.5);
$pdf->setTextColor(0.4, 0.4, 0.4);
$pdf->text(40, $badgeY, "PAYMENT STATUS");
$pdf->setFont('bold', 12);
$pdf->setTextColor($bill['payment_status'] === 'Paid' ? 0.1 : 0.8, $bill['payment_status'] === 'Paid' ? 0.6 : 0.4, 0.1);
$pdf->text(40, $badgeY + 16, strtoupper($bill['payment_status']));

// Footer
$pdf->drawLine(40, 770, 555, 0.75);
$pdf->setFont('regular', 7.5);
$pdf->setTextColor(0.5, 0.5, 0.5);
$pdf->text(40, 782, "MotoCare Customer Receipt. Registered with GST under Automotive Workshop Services.");
$pdf->textRight(555, 782, "Page 1 of 1");

$pdfOutput = $pdf->render();

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $bill['invoice_number'] . '.pdf"');
header('Content-Length: ' . strlen($pdfOutput));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

echo $pdfOutput;
exit;
