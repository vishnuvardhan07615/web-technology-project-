#!/usr/bin/env python3
"""
============================================================================
MotoCare Stage 2 — Master QA & Integration Audit Suite
Comprehensive Verification for LWP Review 02
============================================================================
"""

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
    if res.returncode != 0:
        print(f"[SQL ERROR]: {res.stderr.strip()}", file=sys.stderr)
    return res.stdout.strip(), res.stderr.strip(), res.returncode

def query_val(sql):
    res = subprocess.run(MYSQL_CMD + ['-NB', '-e', sql], capture_output=True, text=True)
    if res.returncode != 0:
        print(f"[SQL ERROR in query_val]: {res.stderr.strip()}", file=sys.stderr)
    return res.stdout.strip()

def query_row(sql):
    res = subprocess.run(MYSQL_CMD + ['-NB', '-e', sql], capture_output=True, text=True)
    if res.returncode != 0:
        print(f"[SQL ERROR in query_row]: {res.stderr.strip()}", file=sys.stderr)
    line = res.stdout.strip().split('\n')[-1] if res.stdout.strip() else ""
    return line.split('\t') if line else []

class Client:
    def __init__(self):
        self.cj = http.cookiejar.CookieJar()
        self.opener = urllib.request.build_opener(
            urllib.request.HTTPCookieProcessor(self.cj),
            urllib.request.HTTPRedirectHandler()
        )

    def get(self, url):
        req = urllib.request.Request(url, headers={'User-Agent': 'MotoCareMasterQA/1.0'})
        try:
            with self.opener.open(req) as resp:
                return resp.getcode(), resp.read(), resp.geturl(), resp.headers
        except urllib.error.HTTPError as e:
            return e.code, e.read(), e.geturl(), e.headers

    def post(self, url, data):
        encoded = urllib.parse.urlencode(data).encode('utf-8')
        req = urllib.request.Request(url, data=encoded, headers={'User-Agent': 'MotoCareMasterQA/1.0'})
        try:
            with self.opener.open(req) as resp:
                return resp.getcode(), resp.read(), resp.geturl(), resp.headers
        except urllib.error.HTTPError as e:
            return e.code, e.read(), e.geturl(), e.headers

def run_audit():
    results = {}
    print("=" * 80)
    print("MOTOCARE STAGE 2 — MASTER QA & INTEGRATION AUDIT")
    print("=" * 80)

    s_admin = Client()
    s_mech1 = Client() # Arun Kumar (id=1)
    s_mech2 = Client() # Karthik Raja (id=2)
    s_cust_a = Client() # Existing Ramesh (id=1)
    s_cust_new = Client() # E2E New Customer
    s_guest = Client()

    # Logins
    s_admin.post(f"{BASE_URL}/admin/login.php", {'username': 'admin', 'password': 'admin123'})
    s_mech1.post(f"{BASE_URL}/mechanic/login.php", {'email': 'arun@motocare.com', 'password': 'mechanic123'})
    s_mech2.post(f"{BASE_URL}/mechanic/login.php", {'email': 'karthik@motocare.com', 'password': 'mechanic123'})
    s_cust_a.post(f"{BASE_URL}/customer/login.php", {'email': 'ramesh@example.com', 'password': 'customer123'})

    # =========================================================================
    # SECTION 1: COMPLETE END-TO-END WORKFLOW TEST (Steps 1–59)
    # =========================================================================
    print("\n--- Running Section 1: End-to-End Workflow (Steps 1 to 59) ---")

    test_email = "e2e_customer@motocare.test"
    test_phone = "9845123456"
    test_pass  = "TestPass123"

    # Clean prior test user if exists
    run_sql(f"""
        DELETE FROM feedback WHERE customer_id IN (SELECT id FROM (SELECT id FROM customers WHERE email='{test_email}') AS c);
        DELETE FROM payments WHERE bill_id IN (SELECT id FROM (SELECT id FROM bills WHERE customer_id IN (SELECT id FROM customers WHERE email='{test_email}')) AS b);
        DELETE FROM bills WHERE customer_id IN (SELECT id FROM (SELECT id FROM customers WHERE email='{test_email}') AS c);
        DELETE FROM service_record_parts WHERE service_record_id IN (SELECT id FROM (SELECT id FROM service_records WHERE booking_id IN (SELECT id FROM bookings WHERE customer_id IN (SELECT id FROM customers WHERE email='{test_email}'))) AS sr);
        DELETE FROM service_records WHERE booking_id IN (SELECT id FROM (SELECT id FROM bookings WHERE customer_id IN (SELECT id FROM customers WHERE email='{test_email}')) AS bk);
        DELETE FROM bookings WHERE customer_id IN (SELECT id FROM (SELECT id FROM customers WHERE email='{test_email}') AS c);
        DELETE FROM vehicles WHERE customer_id IN (SELECT id FROM (SELECT id FROM customers WHERE email='{test_email}') AS c);
        DELETE FROM customers WHERE email='{test_email}';
    """)

    # Step 1: Register a new customer account
    reg_code, reg_body, reg_url, _ = s_cust_new.post(f"{BASE_URL}/customer/register.php", {
        'full_name': 'Vikramaditya Sharma',
        'email': test_email,
        'phone': test_phone,
        'password': test_pass,
        'confirm_password': test_pass,
        'address': 'No. 42 Tech Park Road, Bangalore'
    })
    reg_str = reg_body.decode('utf-8', errors='ignore')
    new_cid = int(query_val(f"SELECT id FROM customers WHERE email='{test_email}';") or 0)
    step1_pass = new_cid > 0 and ('dashboard.php' in reg_url or 'Dashboard' in reg_str)
    results['Step 1-2: Customer Registration & Auto-Login'] = ('PASS' if step1_pass else 'FAIL', f'Customer ID: {new_cid}')

    # Step 3: Add/register a two-wheeler
    veh_reg = "KA-01-EQ-7788"
    add_v_code, add_v_body, add_v_url, _ = s_cust_new.post(f"{BASE_URL}/customer/add-vehicle.php", {
        'vehicle_type': 'Motorcycle',
        'brand': 'KTM',
        'model': 'Duke 390',
        'registration_number': veh_reg
    })
    new_vid = int(query_val(f"SELECT id FROM vehicles WHERE registration_number='{veh_reg}' AND customer_id={new_cid};") or 0)
    step3_pass = new_vid > 0
    results['Step 3: Register Two-Wheeler'] = ('PASS' if step3_pass else 'FAIL', f'Vehicle ID: {new_vid}, Reg: {veh_reg}')

    # Step 4: Verify vehicle in customer's vehicle list
    vlist_code, vlist_body, _, _ = s_cust_new.get(f"{BASE_URL}/customer/vehicles.php")
    vlist_str = vlist_body.decode('utf-8', errors='ignore')
    step4_pass = veh_reg in vlist_str and 'Duke 390' in vlist_str
    results['Step 4: Verify Vehicle in Fleet List'] = ('PASS' if step4_pass else 'FAIL', 'Vehicle rendered in customer garage')

    # Step 5: Create a service booking
    tomorrow = "2026-10-15"
    book_code, book_body, book_url, _ = s_cust_new.post(f"{BASE_URL}/customer/book-service.php", {
        'vehicle_id': new_vid,
        'service_id': 1, # General Service
        'preferred_date': tomorrow,
        'preferred_time': '10:00 AM - 01:00 PM',
        'problem_description': 'Front brake lever soft and periodic maintenance.'
    })
    b_row = query_row(f"SELECT id, booking_code, status FROM bookings WHERE customer_id={new_cid} ORDER BY id DESC LIMIT 1;")
    new_bid = int(b_row[0]) if b_row else 0
    new_bcode = b_row[1] if len(b_row) > 1 else ""
    b_status = b_row[2] if len(b_row) > 2 else ""
    step5_pass = new_bid > 0 and new_bcode.startswith('MC-') and b_status == 'Pending'
    results['Step 5-6: Create Service Booking & Generate Code'] = ('PASS' if step5_pass else 'FAIL', f'Booking #{new_bid}: {new_bcode} (Status: {b_status})')

    # Step 7: Verify booking in customer bookings list
    cb_code, cb_body, _, _ = s_cust_new.get(f"{BASE_URL}/customer/bookings.php")
    cb_str = cb_body.decode('utf-8', errors='ignore')
    step7_pass = new_bcode in cb_str
    results['Step 7: Verify Booking in Customer Bookings'] = ('PASS' if step7_pass else 'FAIL', f'Booking code {new_bcode} visible in customer history')

    # Step 8-9: Admin login & verify booking in admin booking management
    ab_code, ab_body, _, _ = s_admin.get(f"{BASE_URL}/admin/bookings.php")
    ab_str = ab_body.decode('utf-8', errors='ignore')
    step8_pass = new_bcode in ab_str and 'Vikramaditya' in ab_str
    results['Step 8-9: Admin Sees New Booking in Queue'] = ('PASS' if step8_pass else 'FAIL', f'Booking {new_bcode} found in admin list')

    # Step 10-12: Open booking details and assign mechanic (Arun Kumar, id=1)
    asgn_code, asgn_body, asgn_url, _ = s_admin.post(f"{BASE_URL}/admin/assign-mechanic.php", {
        'booking_id': new_bid,
        'mechanic_id': 1
    })
    asgn_row = query_row(f"SELECT status, mechanic_id FROM bookings WHERE id={new_bid};")
    stat_asgn = asgn_row[0] if asgn_row else ""
    mid_asgn = int(asgn_row[1]) if len(asgn_row) > 1 else 0
    step11_pass = stat_asgn == 'Confirmed' and mid_asgn == 1
    results['Step 10-12: Admin Assigns Mechanic & Changes Status to Confirmed'] = ('PASS' if step11_pass else 'FAIL', f'Status: {stat_asgn}, Mechanic ID: {mid_asgn}')

    # Step 13: Assigned mechanic visible to admin and customer
    cdet_code, cdet_body, _, _ = s_cust_new.get(f"{BASE_URL}/customer/booking-details.php?id={new_bid}")
    cdet_str = cdet_body.decode('utf-8', errors='ignore')
    step13_pass = 'Arun Kumar' in cdet_str and 'Confirmed' in cdet_str
    results['Step 13: Assigned Mechanic Visible to Customer & Admin'] = ('PASS' if step13_pass else 'FAIL', 'Arun Kumar displayed in booking details')

    # Step 14-16: Mechanic login and verify assigned booking appears
    m_code, m_body, _, _ = s_mech1.get(f"{BASE_URL}/mechanic/dashboard.php")
    m_str = m_body.decode('utf-8', errors='ignore')
    step14_pass = new_bcode in m_str
    results['Step 14-16: Assigned Booking Appears on Mechanic Dashboard'] = ('PASS' if step14_pass else 'FAIL', f'Booking {new_bcode} in Arun workshop queue')

    # Step 17-27: Status Progression, Notes, Spare Parts & Job Card Lifecycle
    # 1. Confirmed -> Vehicle Received
    s_mech1.post(f"{BASE_URL}/mechanic/update-status.php", {
        'booking_id': new_bid,
        'action': 'advance_status',
        'next_status': 'Vehicle Received'
    })

    # Test invalid skip: Try to skip from Vehicle Received directly to Completed (Must be rejected)
    skip_code, skip_body, skip_url, _ = s_mech1.post(f"{BASE_URL}/mechanic/update-status.php", {
        'booking_id': new_bid,
        'action': 'advance_status',
        'next_status': 'Completed'
    })
    out_skip = query_val(f"SELECT status FROM bookings WHERE id={new_bid};")
    invalid_skip_blocked = (out_skip == 'Vehicle Received')
    results['Step 18: Invalid/Skipped Status Transition Blocked'] = ('PASS' if invalid_skip_blocked else 'FAIL', 'Direct skip to Completed rejected')

    # 2. Vehicle Received -> Inspection
    s_mech1.post(f"{BASE_URL}/mechanic/update-status.php", {
        'booking_id': new_bid,
        'action': 'advance_status',
        'next_status': 'Inspection'
    })

    # 3. In Inspection: Record Inspection Notes, Technician Notes, Labor Hours, and add Spare Parts
    stock_p1_initial = int(query_val("SELECT stock_quantity FROM spare_parts WHERE id=1;") or 0)
    jc_code, jc_body, _, _ = s_mech1.post(f"{BASE_URL}/mechanic/update-status.php", {
        'booking_id': new_bid,
        'action': 'save_job_card',
        'inspection_notes': 'Brake pads worn, chain slack 35mm, oil level low.',
        'work_done': 'Performed synthetic oil change, bled brake lines, tensioned chain.',
        'labor_hours': 2.5,
        'technician_notes': 'Vehicle test-ridden for 3 km; smooth acceleration.',
        'has_parts_form': 1,
        'spare_parts[0][part_id]': 1,
        'spare_parts[0][quantity]': 2
    })
    stock_p1_after = int(query_val("SELECT stock_quantity FROM spare_parts WHERE id=1;") or 0)
    step24_pass = (stock_p1_initial - stock_p1_after) == 2
    results['Step 19-24: Add Notes, Labor Hours & Deduct Spare Parts'] = ('PASS' if step24_pass else 'FAIL', f'Stock deducted: {stock_p1_initial} -> {stock_p1_after} (used 2)')

    # Step 26: Save/update again - verify saving again does NOT double-deduct stock
    s_mech1.post(f"{BASE_URL}/mechanic/update-status.php", {
        'booking_id': new_bid,
        'action': 'save_job_card',
        'inspection_notes': 'Brake pads worn, chain slack 35mm, oil level low.',
        'work_done': 'Performed synthetic oil change, bled brake lines, tensioned chain.',
        'labor_hours': 2.5,
        'technician_notes': 'Vehicle test-ridden for 3 km; smooth acceleration.',
        'has_parts_form': 1,
        'spare_parts[0][part_id]': 1,
        'spare_parts[0][quantity]': 2
    })
    stock_p1_resave = int(query_val("SELECT stock_quantity FROM spare_parts WHERE id=1;") or 0)
    step26_pass = stock_p1_resave == stock_p1_after
    results['Step 26: Re-saving Job Card Prevents Double-Deduction'] = ('PASS' if step26_pass else 'FAIL', f'Stock remained {stock_p1_resave}')

    # Step 27: Increase part quantity 2 -> 3 (should deduct 1 additional unit)
    s_mech1.post(f"{BASE_URL}/mechanic/update-status.php", {
        'booking_id': new_bid,
        'action': 'save_job_card',
        'inspection_notes': 'Brake pads worn, chain slack 35mm, oil level low.',
        'work_done': 'Performed synthetic oil change, bled brake lines, tensioned chain.',
        'labor_hours': 2.5,
        'technician_notes': 'Vehicle test-ridden for 3 km; smooth acceleration.',
        'has_parts_form': 1,
        'spare_parts[0][part_id]': 1,
        'spare_parts[0][quantity]': 3
    })
    stock_p1_inc = int(query_val("SELECT stock_quantity FROM spare_parts WHERE id=1;") or 0)
    step27_pass = (stock_p1_after - stock_p1_inc) == 1
    results['Step 27: Net-Delta Quantity Adjustment (Increase/Decrease)'] = ('PASS' if step27_pass else 'FAIL', f'Stock adjusted 1 unit to {stock_p1_inc}')

    # 4. Progress remainder of workflow with notes preserved:
    # Inspection -> Service In Progress -> Quality Check -> Ready for Delivery -> Completed
    transitions_remaining = ['Service In Progress', 'Quality Check', 'Ready for Delivery', 'Completed']
    progression_ok = True
    for next_st in transitions_remaining:
        s_mech1.post(f"{BASE_URL}/mechanic/update-status.php", {
            'booking_id': new_bid,
            'action': 'advance_status',
            'next_status': next_st,
            'inspection_notes': 'Brake pads worn, chain slack 35mm, oil level low.',
            'work_done': 'Performed synthetic oil change, bled brake lines, tensioned chain.',
            'labor_hours': 2.5,
            'technician_notes': 'Vehicle test-ridden for 3 km; smooth acceleration.'
        })
        out_curr = query_val(f"SELECT status FROM bookings WHERE id={new_bid};")
        if next_st not in out_curr:
            progression_ok = False
            break

    results['Step 17: Service Workflow Status Progression'] = ('PASS' if progression_ok else 'FAIL', 'Sequential progression to Completed succeeded')

    # Steps 28-40: Admin Billing & Invoice Generation
    gen_code, gen_body, gen_url, _ = s_admin.post(f"{BASE_URL}/admin/generate-bill.php", {
        'booking_id': new_bid,
        'discount': 50.00
    })
    b_row = query_row(f"SELECT id, invoice_number, service_charge, parts_subtotal, subtotal, discount, taxable_amount, gst_rate, cgst, sgst, total_amount, payment_status, balance_due FROM bills WHERE booking_id={new_bid};")
    bill_id = int(b_row[0]) if len(b_row) > 1 else 0
    inv_num = b_row[1] if len(b_row) > 1 else ""
    svc_charge = float(b_row[2])
    parts_subtot = float(b_row[3])
    subtot = float(b_row[4])
    disc = float(b_row[5])
    taxable = float(b_row[6])
    gst_rate = float(b_row[7])
    cgst = float(b_row[8])
    sgst = float(b_row[9])
    tot_amt = float(b_row[10])
    p_stat = b_row[11]
    bal_due = float(b_row[12])

    math_ok = (
        round(subtot, 2) == round(svc_charge + parts_subtot, 2) and
        round(taxable, 2) == round(subtot - disc, 2) and
        round(cgst, 2) == round(taxable * 0.09, 2) and
        round(sgst, 2) == round(taxable * 0.09, 2) and
        round(tot_amt, 2) == round(taxable + cgst + sgst, 2) and
        round(bal_due, 2) == round(tot_amt, 2)
    )
    results['Step 28-39: Bill Generation, Pricing & 18% GST (9% CGST + 9% SGST)'] = ('PASS' if math_ok and bill_id > 0 else 'FAIL', f'Invoice: {inv_num}, Total: {tot_amt}, Math Verified: {math_ok}')

    # Step 40: Duplicate bill generation idempotency
    s_admin.post(f"{BASE_URL}/admin/generate-bill.php", {'booking_id': new_bid})
    dup_bills_count = int(query_val(f"SELECT COUNT(*) FROM bills WHERE booking_id={new_bid};") or 0)
    results['Step 40: Duplicate Bill Generation Idempotency'] = ('PASS' if dup_bills_count == 1 else 'FAIL', f'Single invoice {inv_num} maintained')

    # Step 41-44: Record payments, multiple installments & overpayment rejection
    # Attempt overpayment: tot_amt + 500 should be rejected
    pay_bad_code, pay_bad_body, _, _ = s_admin.post(f"{BASE_URL}/admin/record-payment.php?bill_id={bill_id}", {
        'amount': tot_amt + 500,
        'payment_method': 'Cash'
    })
    pay_bad_str = pay_bad_body.decode('utf-8', errors='ignore')
    overpay_blocked = "exceeds" in pay_bad_str or "Overpayment" in pay_bad_str or "greater than" in pay_bad_str

    # Record partial payment: 50%
    half_pay = round(tot_amt / 2, 2)
    s_admin.post(f"{BASE_URL}/admin/record-payment.php?bill_id={bill_id}", {
        'amount': half_pay,
        'payment_method': 'UPI',
        'transaction_reference': 'UPI-REF-001'
    })
    stat_half = query_val(f"SELECT payment_status FROM bills WHERE id={bill_id};")

    # Record remaining balance
    rem_bal = float(query_val(f"SELECT balance_due FROM bills WHERE id={bill_id};") or 0)
    s_admin.post(f"{BASE_URL}/admin/record-payment.php?bill_id={bill_id}", {
        'amount': rem_bal,
        'payment_method': 'Cash',
        'transaction_reference': 'CASH-REC-002'
    })
    fin_row = query_row(f"SELECT payment_status, balance_due, amount_paid FROM bills WHERE id={bill_id};")
    stat_final = fin_row[0]
    bal_final = float(fin_row[1])
    results['Step 41-44: Payment Tracking, Multi-Installments & Overpayment Rejection'] = (
        'PASS' if (overpay_blocked and stat_half == 'Partially Paid' and stat_final == 'Paid' and bal_final == 0.0) else 'FAIL',
        f'Overpay blocked: {overpay_blocked}, Partial: {stat_half}, Final: {stat_final}, Balance: {bal_final}'
    )

    # Step 45-49: Customer Billing & PDF Invoice
    c_inv_code, c_inv_body, _, _ = s_cust_new.get(f"{BASE_URL}/customer/bill-details.php?id={bill_id}")
    c_inv_str = c_inv_body.decode('utf-8', errors='ignore')
    c_inv_visible = inv_num in c_inv_str and ('Paid' in c_inv_str or 'Payment' in c_inv_str)

    pdf_code, pdf_body, _, pdf_headers = s_cust_new.get(f"{BASE_URL}/customer/invoice-pdf.php?id={bill_id}")
    pdf_valid = pdf_code == 200 and pdf_body.startswith(b'%PDF-1.4')

    # IDOR check: Customer A (Ramesh) attempts to open Vikramaditya's invoice
    idor_inv_code, idor_inv_body, idor_inv_url, _ = s_cust_a.get(f"{BASE_URL}/customer/bill-details.php?id={bill_id}")
    idor_inv_str = idor_inv_body.decode('utf-8', errors='ignore')
    idor_inv_blocked = "Access Denied" in idor_inv_str or "bills.php" in idor_inv_url or inv_num not in idor_inv_str

    results['Step 45-49: Customer Invoice View, PDF Generation & IDOR Protection'] = (
        'PASS' if (c_inv_visible and pdf_valid and idor_inv_blocked) else 'FAIL',
        f'Invoice visible: {c_inv_visible}, PDF binary valid: {pdf_valid}, IDOR blocked: {idor_inv_blocked}'
    )

    # Step 50-54: Customer Feedback Submission
    fb_sub_code, fb_sub_body, _, _ = s_cust_new.post(f"{BASE_URL}/customer/feedback.php?booking_id={new_bid}", {
        'rating': 5,
        'comments': 'Superb KTM Duke service! Arun tuned the brakes and engine to perfection.'
    })
    fb_row = query_row(f"SELECT id, rating, moderation_status FROM feedback WHERE booking_id={new_bid};")
    fb_id = int(fb_row[0]) if fb_row else 0
    fb_mstat = fb_row[2] if len(fb_row) > 2 else ""
    step50_pass = fb_id > 0 and fb_mstat == 'Pending'

    # Duplicate rejection check
    dup_fb_code, dup_fb_body, _, _ = s_cust_new.post(f"{BASE_URL}/customer/feedback.php?booking_id={new_bid}", {
        'rating': 4,
        'comments': 'Duplicate review attempt'
    })
    dup_fb_str = dup_fb_body.decode('utf-8', errors='ignore')
    out_fb_cnt = int(query_val(f"SELECT COUNT(*) FROM feedback WHERE booking_id={new_bid};") or 0)
    step53_pass = out_fb_cnt == 1 and ("already submitted" in dup_fb_str or "Duplicate" in dup_fb_str)

    results['Step 50-54: Customer Feedback Submission & Duplicate Rejection'] = (
        'PASS' if (step50_pass and step53_pass) else 'FAIL',
        f'Feedback ID: {fb_id}, Status: {fb_mstat}, Duplicate prevented: {step53_pass}'
    )

    # Step 55-59: Admin Moderation & Public Visibility
    # Verify feedback appears in admin moderation queue
    afb_code, afb_body, _, _ = s_admin.get(f"{BASE_URL}/admin/feedback.php?status=Pending")
    afb_str = afb_body.decode('utf-8', errors='ignore')
    step56_pass = 'Superb KTM Duke service' in afb_str

    # Pending feedback must NOT be on public homepage
    home_code1, home_body1, _, _ = s_guest.get(f"{BASE_URL}/index.php")
    home_str1 = home_body1.decode('utf-8', errors='ignore')
    pending_hidden = 'Superb KTM Duke service' not in home_str1

    # Admin approves feedback
    s_admin.post(f"{BASE_URL}/admin/feedback-details.php?id={fb_id}", {
        'action': 'approve',
        'admin_response': 'Thank you Vikramaditya! Always a pleasure tuning KTM Dukes.'
    })
    out_fb_appr = query_val(f"SELECT moderation_status FROM feedback WHERE id={fb_id};")
    fb_approved = 'Approved' in out_fb_appr

    # Approved feedback appears on public homepage
    home_code2, home_body2, _, _ = s_guest.get(f"{BASE_URL}/index.php")
    home_str2 = home_body2.decode('utf-8', errors='ignore')
    approved_visible = 'Superb KTM Duke service' in home_str2 and 'Vikramaditya S.' in home_str2

    results['Step 55-59: Admin Moderation & Privacy-Safe Homepage Testimonials'] = (
        'PASS' if (step56_pass and pending_hidden and fb_approved and approved_visible) else 'FAIL',
        f'Moderation queue: {step56_pass}, Pending hidden: {pending_hidden}, Approved: {fb_approved}, Public visible: {approved_visible}'
    )

    # =========================================================================
    # SECTION 2: SECURITY & AUTHORIZATION TESTS
    # =========================================================================
    print("\n--- Running Section 2: Security & Authorization ---")

    # 1. Customer A cannot view Customer B's vehicle
    c_veh_code, c_veh_body, c_veh_url, _ = s_cust_a.get(f"{BASE_URL}/customer/book-service.php?vehicle_id={new_vid}")
    c_veh_str = c_veh_body.decode('utf-8', errors='ignore')
    veh_idor_blocked = veh_reg not in c_veh_str or "Access Denied" in c_veh_str
    results['Security: Cross-Customer Vehicle IDOR Blocked'] = ('PASS' if veh_idor_blocked else 'FAIL', 'Vehicle not accessible to other customer')

    # 2. Customer A cannot view Customer B's booking
    c_bk_code, c_bk_body, c_bk_url, _ = s_cust_a.get(f"{BASE_URL}/customer/booking-details.php?id={new_bid}")
    c_bk_str = c_bk_body.decode('utf-8', errors='ignore')
    bk_idor_blocked = new_bcode not in c_bk_str or "Access Denied" in c_bk_str or "bookings.php" in c_bk_url
    results['Security: Cross-Customer Booking IDOR Blocked'] = ('PASS' if bk_idor_blocked else 'FAIL', 'Booking not accessible to other customer')

    # 3. Mechanic A cannot open Mechanic B's assigned job card
    m_idor_code, m_idor_body, m_idor_url, _ = s_mech2.get(f"{BASE_URL}/mechanic/job-card.php?id={new_bid}")
    m_idor_str = m_idor_body.decode('utf-8', errors='ignore')
    mech_idor_blocked = "Access Denied" in m_idor_str or "dashboard.php" in m_idor_url or new_bcode not in m_idor_str
    results['Security: Cross-Mechanic Job Card IDOR Blocked'] = ('PASS' if mech_idor_blocked else 'FAIL', 'Job card protected from unauthorized mechanic')

    # 4. Customer cannot access admin pages
    c_adm_code, _, c_adm_url, _ = s_cust_a.get(f"{BASE_URL}/admin/dashboard.php")
    c_adm_blocked = "admin/login.php" in c_adm_url
    results['Security: Customer Access to Admin Portal Blocked'] = ('PASS' if c_adm_blocked else 'FAIL', 'Redirected to admin login')

    # 5. Customer cannot access mechanic pages
    c_mech_code, _, c_mech_url, _ = s_cust_a.get(f"{BASE_URL}/mechanic/dashboard.php")
    c_mech_blocked = "mechanic/login.php" in c_mech_url
    results['Security: Customer Access to Mechanic Portal Blocked'] = ('PASS' if c_mech_blocked else 'FAIL', 'Redirected to mechanic login')

    # 6. Mechanic cannot access admin pages
    m_adm_code, _, m_adm_url, _ = s_mech1.get(f"{BASE_URL}/admin/bills.php")
    m_adm_blocked = "admin/login.php" in m_adm_url
    results['Security: Mechanic Access to Admin Portal Blocked'] = ('PASS' if m_adm_blocked else 'FAIL', 'Redirected to admin login')

    # 7. Unauthenticated guests redirected on protected routes
    g_c_code, _, g_c_url, _ = s_guest.get(f"{BASE_URL}/customer/dashboard.php")
    g_a_code, _, g_a_url, _ = s_guest.get(f"{BASE_URL}/admin/dashboard.php")
    g_m_code, _, g_m_url, _ = s_guest.get(f"{BASE_URL}/mechanic/dashboard.php")
    guest_blocked = ("customer/login.php" in g_c_url) and ("admin/login.php" in g_a_url) and ("mechanic/login.php" in g_m_url)
    results['Security: Unauthenticated Guests Redirected to Respective Logins'] = ('PASS' if guest_blocked else 'FAIL', 'Strict authentication enforcement')

    # =========================================================================
    # SECTION 3: DATABASE INTEGRITY & ORPHAN CHECKS
    # =========================================================================
    print("\n--- Running Section 3: Database Integrity & Orphan Checks ---")

    orphan_check_sql = """
        SELECT 
            (SELECT COUNT(*) FROM service_records sr LEFT JOIN bookings b ON sr.booking_id = b.id WHERE b.id IS NULL) AS orphan_records,
            (SELECT COUNT(*) FROM service_record_parts srp LEFT JOIN service_records sr ON srp.service_record_id = sr.id WHERE sr.id IS NULL) AS orphan_parts,
            (SELECT COUNT(*) FROM bills bi LEFT JOIN bookings b ON bi.booking_id = b.id WHERE b.id IS NULL) AS orphan_bills,
            (SELECT COUNT(*) FROM payments p LEFT JOIN bills bi ON p.bill_id = bi.id WHERE bi.id IS NULL) AS orphan_payments,
            (SELECT COUNT(*) FROM feedback f LEFT JOIN bookings b ON f.booking_id = b.id WHERE b.id IS NULL) AS orphan_feedback;
    """
    o_row = query_row(orphan_check_sql)
    o_vals = [int(x) for x in o_row] if o_row else [1, 1, 1, 1, 1]
    zero_orphans = all(v == 0 for v in o_vals)
    results['Database Integrity: Zero Orphan Records'] = (
        'PASS' if zero_orphans else 'FAIL',
        f'Orphans: Records={o_vals[0]}, Parts={o_vals[1]}, Bills={o_vals[2]}, Payments={o_vals[3]}, Feedback={o_vals[4]}'
    )

    # Verify Foreign Keys on all Stage 2 tables
    fk_check_sql = """
        SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS 
        WHERE TABLE_SCHEMA='motocare_db' AND CONSTRAINT_TYPE='FOREIGN KEY';
    """
    fk_count = int(query_val(fk_check_sql) or 0)
    results['Database Integrity: Foreign Key Constraints Active'] = ('PASS' if fk_count >= 10 else 'FAIL', f'Total Active FK Constraints: {fk_count}')

    # =========================================================================
    # SECTION 4: NAVIGATION & ROUTE ACCESSIBILITY
    # =========================================================================
    print("\n--- Running Section 4: Navigation & Route Accessibility ---")

    routes_to_test = [
        # Customer
        ('Customer Dashboard', s_cust_new, f"{BASE_URL}/customer/dashboard.php", [200]),
        ('Customer Vehicles', s_cust_new, f"{BASE_URL}/customer/vehicles.php", [200]),
        ('Customer Add Vehicle', s_cust_new, f"{BASE_URL}/customer/add-vehicle.php", [200]),
        ('Customer Book Service', s_cust_new, f"{BASE_URL}/customer/book-service.php", [200]),
        ('Customer Bookings', s_cust_new, f"{BASE_URL}/customer/bookings.php", [200]),
        ('Customer Bills', s_cust_new, f"{BASE_URL}/customer/bills.php", [200]),
        ('Customer Feedback History', s_cust_new, f"{BASE_URL}/customer/feedback-history.php", [200]),

        # Admin
        ('Admin Dashboard', s_admin, f"{BASE_URL}/admin/dashboard.php", [200]),
        ('Admin Bookings', s_admin, f"{BASE_URL}/admin/bookings.php", [200]),
        ('Admin Mechanics', s_admin, f"{BASE_URL}/admin/mechanics.php", [200]),
        ('Admin Spare Parts', s_admin, f"{BASE_URL}/admin/spare-parts.php", [200]),
        ('Admin Bills', s_admin, f"{BASE_URL}/admin/bills.php", [200]),
        ('Admin Payments', s_admin, f"{BASE_URL}/admin/payments.php", [200]),
        ('Admin Feedback Moderation', s_admin, f"{BASE_URL}/admin/feedback.php", [200]),

        # Mechanic
        ('Mechanic Dashboard', s_mech1, f"{BASE_URL}/mechanic/dashboard.php", [200]),
        ('Mechanic Assigned Services', s_mech1, f"{BASE_URL}/mechanic/assigned-services.php", [200]),
        ('Mechanic Job Card', s_mech1, f"{BASE_URL}/mechanic/job-card.php?id={new_bid}", [200]),

        # Public
        ('Public Home', s_guest, f"{BASE_URL}/index.php", [200]),
        ('Public About', s_guest, f"{BASE_URL}/about.php", [200]),
        ('Public Services', s_guest, f"{BASE_URL}/services.php", [200]),
        ('Public Booking', s_guest, f"{BASE_URL}/booking.php", [200]),
        ('Public Contact', s_guest, f"{BASE_URL}/contact.php", [200]),
        ('Portal Login Gateway', s_guest, f"{BASE_URL}/portal-login.php", [200])
    ]

    all_routes_ok = True
    failed_routes = []
    for r_label, r_client, r_url, exp_codes in routes_to_test:
        c, _, _, _ = r_client.get(r_url)
        if c not in exp_codes:
            all_routes_ok = False
            failed_routes.append(f"{r_label} (HTTP {c})")

    results['Navigation & Routes: All Major Endpoints Accessible'] = (
        'PASS' if all_routes_ok else 'FAIL',
        f'Verified {len(routes_to_test)} routes with zero 404/500 errors' if all_routes_ok else f'Failed: {failed_routes}'
    )

    # =========================================================================
    # SUMMARY
    # =========================================================================
    print("\n" + "=" * 80)
    print("MASTER QA AUDIT RESULTS BREAKDOWN")
    print("=" * 80)
    total_passes = 0
    total_fails = 0
    for test_key, (status, detail) in results.items():
        color = '\033[92m' if status == 'PASS' else '\033[91m'
        reset = '\033[0m'
        print(f"[{color}{status:4s}{reset}] {test_key:55s} : {detail}")
        if status == 'PASS':
            total_passes += 1
        else:
            total_fails += 1

    print("=" * 80)
    print(f"Total: {total_passes} PASSED, {total_fails} FAILED out of {len(results)} Master Audit Tests.")
    print("=" * 80)

    return total_fails == 0

if __name__ == '__main__':
    ok = run_audit()
    sys.exit(0 if ok else 1)
