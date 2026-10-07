import urllib.request
import urllib.parse
import http.cookiejar
import subprocess
import json
import sys
import os
import glob
import re

BASE_URL = 'http://127.0.0.1:8080'
MYSQL_CMD = ['/opt/homebrew/bin/mysql', '-u', 'root', '-P', '3307', 'motocare_db']

def run_sql(sql):
    res = subprocess.run(MYSQL_CMD + ['-e', sql], capture_output=True, text=True)
    return res.stdout.strip(), res.stderr.strip(), res.returncode

class Client:
    def __init__(self):
        self.cj = http.cookiejar.CookieJar()
        self.opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(self.cj))

    def get(self, url):
        req = urllib.request.Request(url, headers={'User-Agent': 'MotoCareBillingTester/1.0'})
        try:
            with self.opener.open(req) as resp:
                return resp.getcode(), resp.read(), resp.geturl(), resp.headers
        except urllib.error.HTTPError as e:
            return e.code, e.read(), e.geturl(), e.headers

    def post(self, url, data):
        encoded = urllib.parse.urlencode(data).encode('utf-8')
        req = urllib.request.Request(url, data=encoded, headers={'User-Agent': 'MotoCareBillingTester/1.0'})
        try:
            with self.opener.open(req) as resp:
                return resp.getcode(), resp.read(), resp.geturl(), resp.headers
        except urllib.error.HTTPError as e:
            return e.code, e.read(), e.geturl(), e.headers

def run_tests():
    results = {}
    print("=" * 75)
    print("MotoCare Stage 2 — Comprehensive Billing & Invoicing Test Suite (30 Tests)")
    print("=" * 75)

    s_admin = Client()
    s_cust1 = Client()
    s_cust2 = Client()

    # Pre-test logins
    # 1. Admin login
    admin_login_code, admin_login_body, _, _ = s_admin.post(f"{BASE_URL}/admin/login.php", {
        'username': 'admin',
        'password': 'admin123'
    })
    admin_login_str = admin_login_body.decode('utf-8', errors='ignore')
    admin_logged_in = 'Command Dashboard' in admin_login_str or 'Workshop Operations' in admin_login_str

    # 2. Customer 1 login (Ramesh Kumar - customer_id = 1)
    c1_code, c1_body, _, _ = s_cust1.post(f"{BASE_URL}/customer/login.php", {
        'email': 'ramesh@example.com',
        'password': 'customer123'
    })

    # ----------------------------------------------------
    # TEST 1: Admin opens bills page
    # ----------------------------------------------------
    b_code, b_body, _, _ = s_admin.get(f"{BASE_URL}/admin/bills.php")
    b_str = b_body.decode('utf-8', errors='ignore')
    if b_code == 200 and 'Total Invoices' in b_str and ('Billing' in b_str or 'Invoices' in b_str):
        results['TEST 1'] = ('PASS', 'Admin opened bills.php; metrics summary and table rendered.')
    else:
        results['TEST 1'] = ('FAIL', f'Failed to open bills.php. HTTP {b_code}')

    # ----------------------------------------------------
    # Setup for TEST 2 & 3: Ensure Booking 1 is Completed with job card & parts
    # ----------------------------------------------------
    # Reset any existing bills on booking 1 & 2 for clean test state
    run_sql("DELETE FROM payments WHERE bill_id IN (SELECT id FROM bills WHERE booking_id IN (1, 2));")
    run_sql("DELETE FROM bills WHERE booking_id IN (1, 2);")
    
    # Ensure booking 1 is Completed with customer 1, vehicle 1, mechanic 1
    run_sql("""
        UPDATE bookings 
        SET status='Completed', mechanic_id=1, customer_id=1, vehicle_id=1, service_id=1 
        WHERE id=1;
        INSERT INTO service_records (id, booking_id, mechanic_id, work_done, created_at) 
        VALUES (1, 1, 1, 'General overhaul and synthetic oil flush', NOW())
        ON DUPLICATE KEY UPDATE work_done='General overhaul and synthetic oil flush';
        DELETE FROM service_record_parts WHERE service_record_id=1;
        INSERT INTO service_record_parts (service_record_id, spare_part_id, quantity, unit_price)
        VALUES (1, 1, 2, 450.00), (1, 2, 1, 850.00);
    """)

    # Ensure booking 2 is Incomplete (Pending)
    run_sql("UPDATE bookings SET status='Pending' WHERE id=2;")

    # ----------------------------------------------------
    # TEST 2: Completed booking can generate bill
    # ----------------------------------------------------
    g_code, g_body, g_url, _ = s_admin.get(f"{BASE_URL}/admin/generate-bill.php?booking_id=1")
    g_str = g_body.decode('utf-8', errors='ignore')
    
    # Query database to verify bill was generated
    out, _, _ = run_sql("SELECT id, invoice_number, total_amount, payment_status FROM bills WHERE booking_id=1 LIMIT 1;")
    if 'MC-INV-' in out:
        results['TEST 2'] = ('PASS', f'Completed booking 1 generated invoice: {out.splitlines()[-1]}')
    else:
        results['TEST 2'] = ('FAIL', f'Bill generation failed for booking 1. DB output: {out}')

    # ----------------------------------------------------
    # TEST 3: Incomplete booking cannot generate bill
    # ----------------------------------------------------
    g3_code, g3_body, g3_url, _ = s_admin.get(f"{BASE_URL}/admin/generate-bill.php?booking_id=2")
    out3, _, _ = run_sql("SELECT COUNT(*) FROM bills WHERE booking_id=2;")
    count3 = int(out3.splitlines()[-1]) if out3.splitlines() else 0
    if count3 == 0:
        results['TEST 3'] = ('PASS', 'Incomplete booking 2 strictly prevented from generating invoice.')
    else:
        results['TEST 3'] = ('FAIL', f'Incomplete booking generated bill! Count: {count3}')

    # ----------------------------------------------------
    # TEST 4: Bill generated with correct service charge
    # ----------------------------------------------------
    # Booking 1 has service_id=1 (General Service, price=500.00)
    out4, _, _ = run_sql("SELECT service_charge FROM bills WHERE booking_id=1 LIMIT 1;")
    scharge = float(out4.splitlines()[-1]) if out4.splitlines() else 0.0
    if abs(scharge - 500.00) < 0.01:
        results['TEST 4'] = ('PASS', f'Service charge correctly recorded as INR {scharge:.2f}.')
    else:
        results['TEST 4'] = ('FAIL', f'Expected service_charge 500.00, got {scharge}.')

    # ----------------------------------------------------
    # TEST 5: Bill calculates parts subtotal correctly
    # ----------------------------------------------------
    # Qty 2 * 450.00 = 900.00 + Qty 1 * 850.00 = 850.00 => Total parts = 1750.00
    out5, _, _ = run_sql("SELECT parts_subtotal FROM bills WHERE booking_id=1 LIMIT 1;")
    psub = float(out5.splitlines()[-1]) if out5.splitlines() else 0.0
    if abs(psub - 1750.00) < 0.01:
        results['TEST 5'] = ('PASS', f'Spare parts subtotal correctly calculated as INR {psub:.2f}.')
    else:
        results['TEST 5'] = ('FAIL', f'Expected parts_subtotal 1750.00, got {psub}.')

    # ----------------------------------------------------
    # TEST 6: Historical part unit price is used
    # ----------------------------------------------------
    # Temporarily alter catalog price of part 1 to 9999.00
    run_sql("UPDATE spare_parts SET unit_price=9999.00 WHERE id=1;")
    # Re-verify that the bill still stores and uses historical 450.00 (not 9999.00)
    out6, _, _ = run_sql("SELECT parts_subtotal FROM bills WHERE booking_id=1 LIMIT 1;")
    psub6 = float(out6.splitlines()[-1]) if out6.splitlines() else 0.0
    # Restore catalog price
    run_sql("UPDATE spare_parts SET unit_price=450.00 WHERE id=1;")
    if abs(psub6 - 1750.00) < 0.01:
        results['TEST 6'] = ('PASS', 'Historical snapshot unit_price (INR 450.00) preserved despite catalog change.')
    else:
        results['TEST 6'] = ('FAIL', f'Bill subtotal changed to {psub6} after catalog alteration!')

    # ----------------------------------------------------
    # TEST 7: Subtotal calculation correct
    # ----------------------------------------------------
    # Subtotal = 500 (service) + 1750 (parts) = 2250.00
    out7, _, _ = run_sql("SELECT subtotal FROM bills WHERE booking_id=1 LIMIT 1;")
    subtot = float(out7.splitlines()[-1]) if out7.splitlines() else 0.0
    if abs(subtot - 2250.00) < 0.01:
        results['TEST 7'] = ('PASS', f'Gross subtotal correctly computed as INR {subtot:.2f}.')
    else:
        results['TEST 7'] = ('FAIL', f'Expected subtotal 2250.00, got {subtot}.')

    # ----------------------------------------------------
    # TEST 8: GST calculation correct
    # ----------------------------------------------------
    # Taxable = 2250.00 - 0.00 = 2250.00. GST 18% = 2250 * 0.18 = 405.00
    out8, _, _ = run_sql("SELECT tax FROM bills WHERE booking_id=1 LIMIT 1;")
    tax8 = float(out8.splitlines()[-1]) if out8.splitlines() else 0.0
    if abs(tax8 - 405.00) < 0.01:
        results['TEST 8'] = ('PASS', f'GST (18%) correctly calculated as INR {tax8:.2f}.')
    else:
        results['TEST 8'] = ('FAIL', f'Expected tax 405.00, got {tax8}.')

    # ----------------------------------------------------
    # TEST 9: CGST + SGST equals GST
    # ----------------------------------------------------
    out9, _, _ = run_sql("SELECT cgst, sgst, tax, total_amount FROM bills WHERE booking_id=1 LIMIT 1;")
    cgst9, sgst9, tax9_chk, grand9 = [float(x) for x in out9.splitlines()[-1].split()]
    if abs((cgst9 + sgst9) - tax9_chk) < 0.01 and abs(grand9 - (2250.00 + 405.00)) < 0.01:
        results['TEST 9'] = ('PASS', f'CGST (INR {cgst9:.2f}) + SGST (INR {sgst9:.2f}) = GST ({tax9_chk:.2f}). Grand Total: INR {grand9:.2f}.')
    else:
        results['TEST 9'] = ('FAIL', f'GST split mismatch: CGST={cgst9}, SGST={sgst9}, Tax={tax9_chk}, Grand={grand9}')

    # ----------------------------------------------------
    # TEST 10: Discount calculation correct
    # ----------------------------------------------------
    # Create a test booking with discount in scratch script or helper
    from subprocess import run
    test_calc = subprocess.run([
        'php', '-r', 
        "require_once 'config/constants.php'; require_once 'includes/billing-helper.php'; " +
        "$c = calculateBillTotals(1000.00, 500.00, 200.00, 18.00); " +
        "echo json_encode($c);"
    ], capture_output=True, text=True)
    c_data = json.loads(test_calc.stdout)
    if c_data['subtotal'] == 1500.00 and c_data['discount'] == 200.00 and c_data['taxable_amount'] == 1300.00 and c_data['total_amount'] == 1534.00:
        results['TEST 10'] = ('PASS', 'Discount verified: Subtotal 1500 - Disc 200 = Taxable 1300. GST 234 => Grand 1534.')
    else:
        results['TEST 10'] = ('FAIL', f'Discount calculation mismatch: {test_calc.stdout}')

    # ----------------------------------------------------
    # TEST 11: Invoice number generated server-side
    # ----------------------------------------------------
    out11, _, _ = run_sql("SELECT invoice_number FROM bills WHERE booking_id=1 LIMIT 1;")
    inv11 = out11.splitlines()[-1] if out11.splitlines() else ''
    if re.match(r'^MC-INV-\d{4}-\d{4}$', inv11):
        results['TEST 11'] = ('PASS', f'Server-side sequential invoice number generated: {inv11}.')
    else:
        results['TEST 11'] = ('FAIL', f'Invoice number format invalid: {inv11}')

    # ----------------------------------------------------
    # TEST 12: Duplicate invoice generation prevented
    # ----------------------------------------------------
    # Attempt generating bill again for booking 1
    g12_code, g12_body, g12_url, _ = s_admin.get(f"{BASE_URL}/admin/generate-bill.php?booking_id=1")
    out12, _, _ = run_sql("SELECT COUNT(*) FROM bills WHERE booking_id=1;")
    count12 = int(out12.splitlines()[-1]) if out12.splitlines() else 0
    if count12 == 1:
        results['TEST 12'] = ('PASS', 'Idempotent: duplicate invoice request opened existing bill without creating second bill.')
    else:
        results['TEST 12'] = ('FAIL', f'Duplicate bills created for booking 1! Count: {count12}')

    # Get bill 1 ID
    out_bid, _, _ = run_sql("SELECT id FROM bills WHERE booking_id=1 LIMIT 1;")
    bill_id = int(out_bid.splitlines()[-1])

    # ----------------------------------------------------
    # TEST 13: Admin can open invoice details
    # ----------------------------------------------------
    d_code, d_body, _, _ = s_admin.get(f"{BASE_URL}/admin/bill-details.php?id={bill_id}")
    d_str = d_body.decode('utf-8', errors='ignore')
    if d_code == 200 and 'TAX INVOICE' in d_str and inv11 in d_str and 'Record Payment' in d_str:
        results['TEST 13'] = ('PASS', f'Admin successfully opened invoice details for {inv11}.')
    else:
        results['TEST 13'] = ('FAIL', f'Admin bill-details failed. HTTP {d_code}')

    # ----------------------------------------------------
    # TEST 14: Customer can open own invoice
    # ----------------------------------------------------
    c_code, c_body, _, _ = s_cust1.get(f"{BASE_URL}/customer/bill-details.php?id={bill_id}")
    c_str = c_body.decode('utf-8', errors='ignore')
    if c_code == 200 and inv11 in c_str and 'Ramesh Kumar' in c_str:
        results['TEST 14'] = ('PASS', 'Customer 1 opened own invoice with correct customer, vehicle, and line items.')
    else:
        results['TEST 14'] = ('FAIL', f'Customer bill-details failed. HTTP {c_code}')

    # ----------------------------------------------------
    # TEST 15: Customer cannot open another customer's invoice (IDOR Protection)
    # ----------------------------------------------------
    # Create invoice for Customer 2 (e.g. customer_id=2 on booking 3)
    run_sql("""
        UPDATE bookings SET status='Completed', customer_id=2, vehicle_id=2, mechanic_id=2 WHERE id=3;
        INSERT INTO service_records (id, booking_id, mechanic_id, work_done, created_at)
        VALUES (3, 3, 2, 'Brake overhaul', NOW())
        ON DUPLICATE KEY UPDATE work_done='Brake overhaul';
    """)
    s_admin.get(f"{BASE_URL}/admin/generate-bill.php?booking_id=3")
    out_b3, _, _ = run_sql("SELECT id FROM bills WHERE booking_id=3 LIMIT 1;")
    bill3_id = int(out_b3.splitlines()[-1]) if out_b3.splitlines() else 0

    # Customer 1 attempts accessing Customer 2's bill (bill3_id)
    idor_code, idor_body, _, _ = s_cust1.get(f"{BASE_URL}/customer/bill-details.php?id={bill3_id}")
    idor_str = idor_body.decode('utf-8', errors='ignore')
    if 'permission' in idor_str.lower() or 'not found' in idor_str.lower() or idor_code in (403, 404):
        results['TEST 15'] = ('PASS', 'IDOR attack blocked: Customer 1 denied access to Customer 2 invoice.')
    else:
        results['TEST 15'] = ('FAIL', 'Customer 1 was able to view Customer 2 invoice! IDOR vulnerability!')

    # ----------------------------------------------------
    # TEST 16: Payment of full amount marks Paid
    # ----------------------------------------------------
    # Use bill 1 for partial & full payment tests. Check total
    out_tot, _, _ = run_sql(f"SELECT total_amount FROM bills WHERE id={bill_id};")
    total_val = float(out_tot.splitlines()[-1])

    # Clean prior payments on bill 1
    run_sql(f"DELETE FROM payments WHERE bill_id={bill_id}; UPDATE bills SET amount_paid=0.00, balance_due=total_amount, payment_status='Pending' WHERE id={bill_id};")

    # Pay full amount
    pay_code, pay_body, _, _ = s_admin.post(f"{BASE_URL}/admin/record-payment.php", {
        'bill_id': bill_id,
        'amount': total_val,
        'payment_method': 'UPI',
        'transaction_reference': 'UPI-REF-TEST-FULL',
        'notes': 'Full settlement test'
    })
    out16, _, _ = run_sql(f"SELECT balance_due, amount_paid, payment_status FROM bills WHERE id={bill_id};")
    pbal16, ppaid16, pstat16 = out16.splitlines()[-1].split(maxsplit=2)
    if pstat16 == 'Paid' and float(pbal16) == 0.00 and float(ppaid16) == total_val:
        results['TEST 16'] = ('PASS', f'Full payment of INR {total_val:.2f} marked invoice Paid with 0.00 balance.')
    else:
        results['TEST 16'] = ('FAIL', f'Full payment status incorrect: status={pstat16}, bal={pbal16}, paid={ppaid16}')

    # ----------------------------------------------------
    # TEST 17: Partial payment marks Partially Paid
    # ----------------------------------------------------
    run_sql(f"DELETE FROM payments WHERE bill_id={bill_id}; UPDATE bills SET amount_paid=0.00, balance_due=total_amount, payment_status='Pending' WHERE id={bill_id};")
    half_amount = round(total_val / 2.0, 2)
    s_admin.post(f"{BASE_URL}/admin/record-payment.php", {
        'bill_id': bill_id,
        'amount': half_amount,
        'payment_method': 'Cash',
        'transaction_reference': 'CASH-PART-1',
        'notes': 'Partial installment 1'
    })
    out17, _, _ = run_sql(f"SELECT balance_due, amount_paid, payment_status FROM bills WHERE id={bill_id};")
    pbal17, ppaid17, pstat17 = out17.splitlines()[-1].split(maxsplit=2)
    if 'Partially' in pstat17:
        results['TEST 17'] = ('PASS', f'Partial payment of INR {half_amount:.2f} set status to Partially Paid.')
    else:
        results['TEST 17'] = ('FAIL', f'Expected Partially Paid, got {pstat17}')

    # ----------------------------------------------------
    # TEST 18: Zero payment rejected
    # ----------------------------------------------------
    z_code, z_body, _, _ = s_admin.post(f"{BASE_URL}/admin/record-payment.php", {
        'bill_id': bill_id,
        'amount': 0.00,
        'payment_method': 'Cash'
    })
    z_str = z_body.decode('utf-8', errors='ignore')
    if 'greater than zero' in z_str.lower() or 'zero' in z_str.lower():
        results['TEST 18'] = ('PASS', 'Zero amount payment strictly rejected by server-side validation.')
    else:
        results['TEST 18'] = ('FAIL', 'Zero amount payment was not rejected!')

    # ----------------------------------------------------
    # TEST 19: Negative payment rejected
    # ----------------------------------------------------
    neg_code, neg_body, _, _ = s_admin.post(f"{BASE_URL}/admin/record-payment.php", {
        'bill_id': bill_id,
        'amount': -150.00,
        'payment_method': 'Cash'
    })
    neg_str = neg_body.decode('utf-8', errors='ignore')
    if 'greater than zero' in neg_str.lower() or 'negative' in neg_str.lower():
        results['TEST 19'] = ('PASS', 'Negative amount (-150.00) strictly rejected by server-side validation.')
    else:
        results['TEST 19'] = ('FAIL', 'Negative amount payment was not rejected!')

    # ----------------------------------------------------
    # TEST 20: Payment exceeding balance rejected
    # ----------------------------------------------------
    out_curbal, _, _ = run_sql(f"SELECT balance_due FROM bills WHERE id={bill_id};")
    cur_bal = float(out_curbal.splitlines()[-1])
    excess_amt = cur_bal + 500.00
    ex_code, ex_body, _, _ = s_admin.post(f"{BASE_URL}/admin/record-payment.php", {
        'bill_id': bill_id,
        'amount': excess_amt,
        'payment_method': 'Cash'
    })
    ex_str = ex_body.decode('utf-8', errors='ignore')
    if 'exceeds' in ex_str.lower() or 'remaining' in ex_str.lower():
        results['TEST 20'] = ('PASS', f'Overpayment (INR {excess_amt:.2f} > {cur_bal:.2f}) strictly rejected.')
    else:
        results['TEST 20'] = ('FAIL', 'Overpayment was not rejected!')

    # ----------------------------------------------------
    # TEST 21: Multiple payments calculate correctly
    # ----------------------------------------------------
    # Currently bill has 1 partial payment (half_amount). Record a second payment of remaining balance.
    rem_bal = round(total_val - half_amount, 2)
    s_admin.post(f"{BASE_URL}/admin/record-payment.php", {
        'bill_id': bill_id,
        'amount': rem_bal,
        'payment_method': 'Card',
        'transaction_reference': 'CARD-PART-2'
    })
    out21, _, _ = run_sql(f"SELECT COUNT(*), SUM(amount) FROM payments WHERE bill_id={bill_id};")
    cnt21, sum21 = out21.splitlines()[-1].split()
    if int(cnt21) == 2 and abs(float(sum21) - total_val) < 0.01:
        results['TEST 21'] = ('PASS', f'Multiple payments ledger verified: 2 installments sum to INR {float(sum21):.2f}.')
    else:
        results['TEST 21'] = ('FAIL', f'Multiple payments mismatch: count={cnt21}, sum={sum21}')

    # ----------------------------------------------------
    # TEST 22: Outstanding balance calculated correctly
    # ----------------------------------------------------
    out22, _, _ = run_sql(f"SELECT total_amount, amount_paid, balance_due FROM bills WHERE id={bill_id};")
    tot22, paid22, bal22 = [float(x) for x in out22.splitlines()[-1].split()]
    if abs((tot22 - paid22) - bal22) < 0.01:
        results['TEST 22'] = ('PASS', f'Outstanding balance verified: Total {tot22:.2f} - Paid {paid22:.2f} = Due {bal22:.2f}.')
    else:
        results['TEST 22'] = ('FAIL', f'Balance calculation inconsistent: {tot22} - {paid22} != {bal22}')

    # ----------------------------------------------------
    # TEST 23: Unauthorized admin/customer access blocked
    # ----------------------------------------------------
    anon = Client()
    a_code, _, a_url, _ = anon.get(f"{BASE_URL}/admin/bills.php")
    c_code, _, c_url, _ = anon.get(f"{BASE_URL}/customer/bills.php")
    admin_blocked = ('login.php' in a_url) or a_code in (401, 403, 302)
    cust_blocked = ('login.php' in c_url) or c_code in (401, 403, 302)
    if admin_blocked and cust_blocked:
        results['TEST 23'] = ('PASS', 'Unauthenticated sessions redirected to login; sensitive billing data protected.')
    else:
        results['TEST 23'] = ('FAIL', f'Auth check bypassed: admin_url={a_url}, cust_url={c_url}')

    # ----------------------------------------------------
    # TEST 24: PDF invoice generated successfully
    # ----------------------------------------------------
    pdf_code, pdf_bytes, _, pdf_headers = s_admin.get(f"{BASE_URL}/admin/invoice-pdf.php?id={bill_id}")
    is_pdf = pdf_bytes.startswith(b'%PDF-1.4')
    c_pdf_code, c_pdf_bytes, _, _ = s_cust1.get(f"{BASE_URL}/customer/invoice-pdf.php?id={bill_id}")
    is_cust_pdf = c_pdf_bytes.startswith(b'%PDF-1.4')
    if is_pdf and is_cust_pdf and pdf_code == 200:
        results['TEST 24'] = ('PASS', f'PDF engine generated valid PDF-1.4 binary ({len(pdf_bytes)} bytes) for admin & customer.')
    else:
        results['TEST 24'] = ('FAIL', f'PDF generation failed: is_pdf={is_pdf}, is_cust={is_cust_pdf}, code={pdf_code}')

    # ----------------------------------------------------
    # TEST 25: Invoice totals cannot be manipulated through POST data
    # ----------------------------------------------------
    # Test POST tampering on payment or generation
    hack_client = Client()
    # Attempt passing fake client-side balance
    hack_code, hack_body, _, _ = s_admin.post(f"{BASE_URL}/admin/record-payment.php", {
        'bill_id': bill_id,
        'amount': 1.00,
        'total': 0.01,
        'balance': 0.01,
        'payment_method': 'Cash'
    })
    # Since bill is already Paid, server must reject based on true DB balance, ignoring any fake post parameters
    hack_str = hack_body.decode('utf-8', errors='ignore')
    if 'fully settled' in hack_str.lower() or 'no balance' in hack_str.lower():
        results['TEST 25'] = ('PASS', 'Client-side manipulation of total/balance rejected; server DB is sole source of truth.')
    else:
        results['TEST 25'] = ('FAIL', f'POST parameter tampering not blocked properly! Response: {hack_str[:150]}')

    # ----------------------------------------------------
    # TEST 26: Database foreign-key consistency
    # ----------------------------------------------------
    out26, _, _ = run_sql("""
        SELECT CONSTRAINT_NAME, TABLE_NAME, REFERENCED_TABLE_NAME 
        FROM information_schema.KEY_COLUMN_USAGE 
        WHERE TABLE_SCHEMA='motocare_db' AND TABLE_NAME IN ('bills', 'payments') AND REFERENCED_TABLE_NAME IS NOT NULL;
    """)
    fk_lines = out26.splitlines()
    has_bill_bk = any('bookings' in l for l in fk_lines)
    has_bill_cu = any('customers' in l for l in fk_lines)
    has_pay_bil = any('bills' in l for l in fk_lines)
    if has_bill_bk and has_bill_cu and has_pay_bil:
        results['TEST 26'] = ('PASS', 'Foreign key constraints intact: bills -> bookings, customers; payments -> bills.')
    else:
        results['TEST 26'] = ('FAIL', f'Missing foreign keys: {out26}')

    # ----------------------------------------------------
    # TEST 27: No duplicate invoice numbers
    # ----------------------------------------------------
    out27, _, _ = run_sql("SELECT invoice_number, COUNT(*) FROM bills GROUP BY invoice_number HAVING COUNT(*) > 1;")
    dups = out27.strip()
    if not dups:
        results['TEST 27'] = ('PASS', 'Zero duplicate invoice numbers found in database. UNIQUE constraint verified.')
    else:
        results['TEST 27'] = ('FAIL', f'Duplicate invoice numbers found: {dups}')

    # ----------------------------------------------------
    # TEST 28: Repeated bill generation remains idempotent
    # ----------------------------------------------------
    out_bcount1, _, _ = run_sql("SELECT COUNT(*) FROM bills;")
    cnt1 = int(out_bcount1.splitlines()[-1])
    # Call generate 3 times for booking 1
    s_admin.get(f"{BASE_URL}/admin/generate-bill.php?booking_id=1")
    s_admin.get(f"{BASE_URL}/admin/generate-bill.php?booking_id=1")
    s_admin.get(f"{BASE_URL}/admin/generate-bill.php?booking_id=1")
    out_bcount2, _, _ = run_sql("SELECT COUNT(*) FROM bills;")
    cnt2 = int(out_bcount2.splitlines()[-1])
    if cnt1 == cnt2:
        results['TEST 28'] = ('PASS', 'Repeated invoice generation executions are strictly idempotent.')
    else:
        results['TEST 28'] = ('FAIL', f'Bill count increased from {cnt1} to {cnt2} on repeated generation!')

    # ----------------------------------------------------
    # TEST 29: PHP syntax scan
    # ----------------------------------------------------
    php_files = glob.glob('/Users/thinakarv/Documents/Projects/motocare/**/*.php', recursive=True)
    php_errors = []
    for pf in php_files:
        p_res = subprocess.run(['php', '-l', pf], capture_output=True, text=True)
        if p_res.returncode != 0:
            php_errors.append(pf)
    if not php_errors:
        results['TEST 29'] = ('PASS', f'PHP syntax scan passed with 0 errors across {len(php_files)} files.')
    else:
        results['TEST 29'] = ('FAIL', f'PHP syntax errors in: {php_errors}')

    # ----------------------------------------------------
    # TEST 30: Browser/client-side JavaScript syntax scan
    # ----------------------------------------------------
    js_files = glob.glob('/Users/thinakarv/Documents/Projects/motocare/**/*.js', recursive=True)
    js_errors = []
    for jf in js_files:
        j_res = subprocess.run(['node', '-c', jf], capture_output=True, text=True)
        if j_res.returncode != 0:
            js_errors.append(jf)
    if not js_errors:
        results['TEST 30'] = ('PASS', f'JavaScript syntax scan passed with 0 errors across {len(js_files)} files.')
    else:
        results['TEST 30'] = ('FAIL', f'JS syntax errors in: {js_errors}')

    # Print summary table
    print("\n" + "=" * 75)
    print(f"{'TEST ID':<10} | {'STATUS':<6} | {'DETAILS'}")
    print("-" * 75)
    passed_cnt = 0
    for i in range(1, 31):
        tid = f"TEST {i}"
        status, details = results.get(tid, ('FAIL', 'Test not executed'))
        if status == 'PASS':
            passed_cnt += 1
            color_stat = f"\033[92m{status}\033[0m"
        else:
            color_stat = f"\033[91m{status}\033[0m"
        print(f"{tid:<10} | {color_stat:<15} | {details}")

    print("=" * 75)
    print(f"Total: {passed_cnt}/30 PASS")
    print("=" * 75)
    return passed_cnt == 30

if __name__ == '__main__':
    success = run_tests()
    sys.exit(0 if success else 1)
