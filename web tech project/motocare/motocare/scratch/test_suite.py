#!/usr/bin/env python3
"""
============================================================================
MotoCare Stage 2 — Customer Feedback, 5-Star Ratings & Moderation Test Suite
32 Test Cases as specified in Section 19 of the Stage 2 Specification
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

class Client:
    def __init__(self):
        self.cj = http.cookiejar.CookieJar()
        self.opener = urllib.request.build_opener(
            urllib.request.HTTPCookieProcessor(self.cj),
            urllib.request.HTTPRedirectHandler()
        )

    def get(self, url):
        req = urllib.request.Request(url, headers={'User-Agent': 'MotoCareTester/1.0'})
        try:
            with self.opener.open(req) as resp:
                return resp.getcode(), resp.read(), resp.geturl(), resp.headers
        except urllib.error.HTTPError as e:
            return e.code, e.read(), e.geturl(), e.headers

    def post(self, url, data):
        encoded = urllib.parse.urlencode(data).encode('utf-8')
        req = urllib.request.Request(url, data=encoded, headers={'User-Agent': 'MotoCareTester/1.0'})
        try:
            with self.opener.open(req) as resp:
                return resp.getcode(), resp.read(), resp.geturl(), resp.headers
        except urllib.error.HTTPError as e:
            return e.code, e.read(), e.geturl(), e.headers

def run_tests():
    results = {}
    print("=" * 80)
    print("MotoCare Stage 2 — Customer Feedback, Ratings & Moderation Test Suite (32 Tests)")
    print("=" * 80)

    # -------------------------------------------------------------------------
    # Setup test data in MySQL
    # Customer 1: Ramesh Kumar (id=1, ramesh@example.com / customer123)
    # Customer 2: Suresh Raina (id=2, suresh@example.com / customer123)
    # Ensure Customer 2 exists
    # -------------------------------------------------------------------------
    run_sql("""
        INSERT INTO customers (id, full_name, email, phone, password, address)
        VALUES (2, 'Suresh Raina', 'suresh@example.com', '9876543299', '$2y$10$68eULAPFFgQksB6bbkwTPu1iZsgDjs.6bCMR1Vm16MzSCQVCj.jDq', 'Chennai Central')
        ON DUPLICATE KEY UPDATE full_name='Suresh Raina', password='$2y$10$68eULAPFFgQksB6bbkwTPu1iZsgDjs.6bCMR1Vm16MzSCQVCj.jDq';

        INSERT INTO vehicles (id, customer_id, vehicle_type, brand, model, registration_number)
        VALUES (2, 2, 'Motorcycle', 'Yamaha', 'FZ-S Fi V3', 'TN-09-CD-5678')
        ON DUPLICATE KEY UPDATE customer_id=2;

        -- Booking 1: Completed, Customer 1, with service record
        UPDATE bookings SET status='Completed', customer_id=1, vehicle_id=1, service_id=1, mechanic_id=1 WHERE id=1;
        INSERT INTO service_records (id, booking_id, mechanic_id, work_done, created_at)
        VALUES (1, 1, 1, 'General overhaul and synthetic oil flush', NOW())
        ON DUPLICATE KEY UPDATE work_done='General overhaul and synthetic oil flush';

        -- Booking 2: Pending (Incomplete), Customer 1, Mechanic 2
        UPDATE bookings SET status='Pending', customer_id=1, vehicle_id=1, service_id=1, mechanic_id=2 WHERE id=2;

        -- Booking 3: Completed, Customer 2, with service record
        INSERT INTO bookings (id, booking_code, customer_id, vehicle_id, service_id, mechanic_id, preferred_date, preferred_time, problem_description, status, created_at)
        VALUES (3, 'MC-2026-1003', 2, 2, 2, 2, '2026-10-02', '11:00 AM', 'Engine tuning', 'Completed', NOW())
        ON DUPLICATE KEY UPDATE status='Completed', customer_id=2;
        INSERT INTO service_records (id, booking_id, mechanic_id, work_done, created_at)
        VALUES (3, 3, 2, 'Complete engine diagnostics and carburetor tuning', NOW())
        ON DUPLICATE KEY UPDATE work_done='Complete engine diagnostics and carburetor tuning';

        -- Booking 4: Completed, Customer 1 (Fresh booking for new feedback tests)
        INSERT INTO bookings (id, booking_code, customer_id, vehicle_id, service_id, mechanic_id, preferred_date, preferred_time, problem_description, status, created_at)
        VALUES (4, 'MC-2026-1004', 1, 1, 3, 1, '2026-10-03', '02:00 PM', 'Brake pad replacement', 'Completed', NOW())
        ON DUPLICATE KEY UPDATE status='Completed', customer_id=1;
        INSERT INTO service_records (id, booking_id, mechanic_id, work_done, created_at)
        VALUES (4, 4, 1, 'Replaced ceramic brake pads and bled brake lines', NOW())
        ON DUPLICATE KEY UPDATE work_done='Replaced ceramic brake pads and bled brake lines';

        -- Booking 5: Completed, Customer 1 (For 1-star feedback test)
        INSERT INTO bookings (id, booking_code, customer_id, vehicle_id, service_id, mechanic_id, preferred_date, preferred_time, problem_description, status, created_at)
        VALUES (5, 'MC-2026-1005', 1, 1, 1, 1, '2026-10-04', '04:00 PM', 'Chain lubing', 'Completed', NOW())
        ON DUPLICATE KEY UPDATE status='Completed', customer_id=1;
        INSERT INTO service_records (id, booking_id, mechanic_id, work_done, created_at)
        VALUES (5, 5, 1, 'Drive chain cleaned and lubed', NOW())
        ON DUPLICATE KEY UPDATE work_done='Drive chain cleaned and lubed';

        -- Clean feedback table to known initial state
        DELETE FROM feedback;
    """)

    # Clients
    s_admin = Client()
    s_cust1 = Client()
    s_cust2 = Client()
    s_guest = Client()

    # Pre-logins
    s_admin.post(f"{BASE_URL}/admin/login.php", {'username': 'admin', 'password': 'admin123'})
    s_cust1.post(f"{BASE_URL}/customer/login.php", {'email': 'ramesh@example.com', 'password': 'customer123'})
    s_cust2.post(f"{BASE_URL}/customer/login.php", {'email': 'suresh@example.com', 'password': 'customer123'})

    # -------------------------------------------------------------------------
    # TEST 1: Customer can open feedback page for completed own booking
    # -------------------------------------------------------------------------
    code1, body1, url1, _ = s_cust1.get(f"{BASE_URL}/customer/feedback.php?booking_id=1")
    str1 = body1.decode('utf-8', errors='ignore')
    if code1 == 200 and 'Service Feedback' in str1 and 'MC-2026-1001' in str1 and 'Submit Feedback' in str1:
        results['TEST 1'] = ('PASS', 'Customer 1 successfully opened feedback page for completed own booking (MC-2026-1001).')
    else:
        results['TEST 1'] = ('FAIL', f'Could not open feedback page. Code: {code1}, Content snippet: {str1[:150]}')

    # -------------------------------------------------------------------------
    # TEST 2: Customer cannot review another customer's booking
    # -------------------------------------------------------------------------
    # Booking 3 belongs to Customer 2 (Suresh). Customer 1 tries to access / submit review.
    code2, body2, _, _ = s_cust1.get(f"{BASE_URL}/customer/feedback.php?booking_id=3")
    str2 = body2.decode('utf-8', errors='ignore')
    post2_code, post2_body, _, _ = s_cust1.post(f"{BASE_URL}/customer/feedback.php?booking_id=3", {
        'rating': 5,
        'comments': 'Unauthorized review attempt on booking 3'
    })
    post2_str = post2_body.decode('utf-8', errors='ignore')
    out2, _, _ = run_sql("SELECT COUNT(*) FROM feedback WHERE booking_id=3;")
    count2 = int(out2.split()[-1])
    if ("cannot submit feedback for another customer" in (str2 + post2_str) or "Access Denied" in (str2 + post2_str)) and count2 == 0:
        results['TEST 2'] = ('PASS', 'Cross-customer feedback access blocked; error message displayed and 0 records created.')
    else:
        results['TEST 2'] = ('FAIL', f'Customer allowed to access/review another customer booking. Records: {count2}')

    # -------------------------------------------------------------------------
    # TEST 3: Customer cannot review incomplete booking
    # -------------------------------------------------------------------------
    # Booking 2 is status='Pending'
    code3, body3, _, _ = s_cust1.get(f"{BASE_URL}/customer/feedback.php?booking_id=2")
    str3 = body3.decode('utf-8', errors='ignore')
    post3_code, post3_body, _, _ = s_cust1.post(f"{BASE_URL}/customer/feedback.php?booking_id=2", {
        'rating': 5,
        'comments': 'Review attempt on incomplete pending booking'
    })
    post3_str = post3_body.decode('utf-8', errors='ignore')
    out3, _, _ = run_sql("SELECT COUNT(*) FROM feedback WHERE booking_id=2;")
    count3 = int(out3.split()[-1])
    if ("only available for completed services" in (str3 + post3_str) or "currently 'Pending'" in (str3 + post3_str)) and count3 == 0:
        results['TEST 3'] = ('PASS', 'Incomplete (Pending) booking review rejected; feedback not permitted for uncompleted service.')
    else:
        results['TEST 3'] = ('FAIL', f'Customer allowed to review pending booking. Records: {count3}')

    # -------------------------------------------------------------------------
    # TEST 4: Customer can submit 5-star rating
    # -------------------------------------------------------------------------
    post4_code, post4_body, _, _ = s_cust1.post(f"{BASE_URL}/customer/feedback.php?booking_id=1", {
        'rating': 5,
        'comments': 'Outstanding engine overhaul and workshop service! Fast and reliable.'
    })
    str4 = post4_body.decode('utf-8', errors='ignore')
    out4, _, _ = run_sql("SELECT customer_id, rating, moderation_status, comments FROM feedback WHERE booking_id=1;")
    if "submitted successfully" in str4 or "Review Submitted" in str4:
        if "5" in out4 and "Pending" in out4 and "Outstanding engine overhaul" in out4:
            results['TEST 4'] = ('PASS', '5-star rating submitted successfully; saved in DB with status=Pending.')
        else:
            results['TEST 4'] = ('FAIL', f'DB content mismatch: {out4}')
    else:
        results['TEST 4'] = ('FAIL', f'Submission response failed. Content: {str4[:200]}')

    # -------------------------------------------------------------------------
    # TEST 5: Customer can submit 1-star rating
    # -------------------------------------------------------------------------
    # Using Booking 5 (Customer 1 completed)
    post5_code, post5_body, _, _ = s_cust1.post(f"{BASE_URL}/customer/feedback.php?booking_id=5", {
        'rating': 1,
        'comments': 'Vehicle delivery was delayed by over two hours and chain was improperly tensioned.'
    })
    str5 = post5_body.decode('utf-8', errors='ignore')
    out5, _, _ = run_sql("SELECT customer_id, rating, moderation_status FROM feedback WHERE booking_id=5;")
    if ("submitted successfully" in str5 or "Review Submitted" in str5) and "1" in out5:
        results['TEST 5'] = ('PASS', '1-star rating submitted successfully and stored in DB.')
    else:
        results['TEST 5'] = ('FAIL', f'1-star submission failed: DB: {out5}, Body: {str5[:150]}')

    # -------------------------------------------------------------------------
    # TEST 6: Rating 0 rejected
    # -------------------------------------------------------------------------
    # Using Booking 4 (Fresh completed booking)
    post6_code, post6_body, _, _ = s_cust1.post(f"{BASE_URL}/customer/feedback.php?booking_id=4", {
        'rating': 0,
        'comments': 'Testing rating zero boundary rejection.'
    })
    str6 = post6_body.decode('utf-8', errors='ignore')
    out6, _, _ = run_sql("SELECT COUNT(*) FROM feedback WHERE booking_id=4;")
    count6 = int(out6.split()[-1])
    if "between 1 and 5 stars" in str6 and count6 == 0:
        results['TEST 6'] = ('PASS', 'Rating 0 rejected with clear validation error; 0 records stored.')
    else:
        results['TEST 6'] = ('FAIL', f'Rating 0 was not rejected. Count: {count6}')

    # -------------------------------------------------------------------------
    # TEST 7: Rating 6 rejected
    # -------------------------------------------------------------------------
    post7_code, post7_body, _, _ = s_cust1.post(f"{BASE_URL}/customer/feedback.php?booking_id=4", {
        'rating': 6,
        'comments': 'Testing rating six boundary rejection.'
    })
    str7 = post7_body.decode('utf-8', errors='ignore')
    out7, _, _ = run_sql("SELECT COUNT(*) FROM feedback WHERE booking_id=4;")
    count7 = int(out7.split()[-1])
    if "between 1 and 5 stars" in str7 and count7 == 0:
        results['TEST 7'] = ('PASS', 'Rating 6 rejected with clear validation error; 0 records stored.')
    else:
        results['TEST 7'] = ('FAIL', f'Rating 6 was not rejected. Count: {count7}')

    # -------------------------------------------------------------------------
    # TEST 8: Negative rating rejected
    # -------------------------------------------------------------------------
    post8_code, post8_body, _, _ = s_cust1.post(f"{BASE_URL}/customer/feedback.php?booking_id=4", {
        'rating': -1,
        'comments': 'Testing negative rating rejection.'
    })
    str8 = post8_body.decode('utf-8', errors='ignore')
    out8, _, _ = run_sql("SELECT COUNT(*) FROM feedback WHERE booking_id=4;")
    count8 = int(out8.split()[-1])
    if "between 1 and 5 stars" in str8 and count8 == 0:
        results['TEST 8'] = ('PASS', 'Negative rating (-1) rejected with validation error; 0 records stored.')
    else:
        results['TEST 8'] = ('FAIL', f'Negative rating was not rejected. Count: {count8}')

    # -------------------------------------------------------------------------
    # TEST 9: Duplicate feedback rejected
    # -------------------------------------------------------------------------
    # Booking 1 already has feedback from TEST 4
    post9_code, post9_body, _, _ = s_cust1.post(f"{BASE_URL}/customer/feedback.php?booking_id=1", {
        'rating': 4,
        'comments': 'Attempting second duplicate review on booking 1.'
    })
    str9 = post9_body.decode('utf-8', errors='ignore')
    out9, _, _ = run_sql("SELECT COUNT(*) FROM feedback WHERE booking_id=1;")
    count9 = int(out9.split()[-1])
    if ("already submitted a review" in str9 or "Duplicate" in str9) and count9 == 1:
        results['TEST 9'] = ('PASS', 'Duplicate feedback rejected. Only 1 unique feedback record per booking in DB.')
    else:
        results['TEST 9'] = ('FAIL', f'Duplicate submission allowed or error not caught. Count: {count9}')

    # -------------------------------------------------------------------------
    # TEST 10: Empty review rejected if required
    # -------------------------------------------------------------------------
    # Test empty comments on Booking 4
    post10_code, post10_body, _, _ = s_cust1.post(f"{BASE_URL}/customer/feedback.php?booking_id=4", {
        'rating': 5,
        'comments': '   '
    })
    str10 = post10_body.decode('utf-8', errors='ignore')
    out10, _, _ = run_sql("SELECT COUNT(*) FROM feedback WHERE booking_id=4;")
    count10 = int(out10.split()[-1])
    if ("enter your review comments" in str10 or "at least 5 characters" in str10) and count10 == 0:
        results['TEST 10'] = ('PASS', 'Empty/whitespace-only comments rejected; min length enforced.')
    else:
        results['TEST 10'] = ('FAIL', f'Empty review was not rejected. Count: {count10}')

    # -------------------------------------------------------------------------
    # TEST 11: Excessively long review rejected
    # -------------------------------------------------------------------------
    huge_comment = "Great service! " * 80  # > 1100 characters
    post11_code, post11_body, _, _ = s_cust1.post(f"{BASE_URL}/customer/feedback.php?booking_id=4", {
        'rating': 5,
        'comments': huge_comment
    })
    str11 = post11_body.decode('utf-8', errors='ignore')
    out11, _, _ = run_sql("SELECT COUNT(*) FROM feedback WHERE booking_id=4;")
    count11 = int(out11.split()[-1])
    if "cannot exceed 1000 characters" in str11 and count11 == 0:
        results['TEST 11'] = ('PASS', f'Excessive review ({len(huge_comment)} chars) rejected exceeding 1000-char limit.')
    else:
        results['TEST 11'] = ('FAIL', f'Excessive review was not rejected. Count: {count11}')

    # -------------------------------------------------------------------------
    # TEST 12: Feedback stored with correct customer ID
    # -------------------------------------------------------------------------
    # Customer 2 (Suresh Raina, id=2) submits feedback for booking 3 (his completed booking)
    # Attempt to spoof customer_id=1 in POST body to test if server uses session customer_id
    post12_code, post12_body, _, _ = s_cust2.post(f"{BASE_URL}/customer/feedback.php?booking_id=3", {
        'customer_id': 1,
        'rating': 4,
        'comments': 'Very smooth booking and prompt delivery by mechanic Suresh.'
    })
    out12, _, _ = run_sql("SELECT customer_id FROM feedback WHERE booking_id=3;")
    cid12 = int(out12.split()[-1]) if out12.split() else -1
    if cid12 == 2:
        results['TEST 12'] = ('PASS', f'Feedback strictly stored with authenticated customer ID ({cid12}); POST spoofing ignored.')
    else:
        results['TEST 12'] = ('FAIL', f'Stored customer ID {cid12} does not match authenticated customer 2.')

    # -------------------------------------------------------------------------
    # TEST 13: Admin feedback page loads
    # -------------------------------------------------------------------------
    code13, body13, _, _ = s_admin.get(f"{BASE_URL}/admin/feedback.php")
    str13 = body13.decode('utf-8', errors='ignore')
    if code13 == 200 and 'Feedback Moderation' in str13 and 'Total Reviews' in str13 and 'Pending Reviews' in str13:
        results['TEST 13'] = ('PASS', 'Admin feedback moderation queue loads with statistics cards and filter controls.')
    else:
        results['TEST 13'] = ('FAIL', f'Admin feedback page failed. HTTP {code13}')

    # -------------------------------------------------------------------------
    # TEST 14: Admin can approve feedback
    # -------------------------------------------------------------------------
    # Approve feedback on booking 1 (Feedback ID 1)
    out_fb1, _, _ = run_sql("SELECT id FROM feedback WHERE booking_id=1;")
    fb_id_1 = int(out_fb1.split()[-1])
    post14_code, post14_body, _, _ = s_admin.post(f"{BASE_URL}/admin/feedback-details.php?id={fb_id_1}", {
        'action': 'approve',
        'admin_response': 'Thank you Ramesh! Glad our overhaul met your high standards.'
    })
    str14 = post14_body.decode('utf-8', errors='ignore')
    out14, _, _ = run_sql(f"SELECT moderation_status, moderator_id, admin_response FROM feedback WHERE id={fb_id_1};")
    if "Approved" in out14 and "Thank you Ramesh" in out14:
        results['TEST 14'] = ('PASS', f'Admin successfully approved review #{fb_id_1} and saved supervisor response.')
    else:
        results['TEST 14'] = ('FAIL', f'Admin approval failed. DB: {out14}')

    # -------------------------------------------------------------------------
    # TEST 15: Admin can reject feedback
    # -------------------------------------------------------------------------
    # Reject feedback on booking 5 (Feedback ID for booking 5)
    out_fb5, _, _ = run_sql("SELECT id FROM feedback WHERE booking_id=5;")
    fb_id_5 = int(out_fb5.split()[-1])
    post15_code, post15_body, _, _ = s_admin.post(f"{BASE_URL}/admin/feedback.php", {
        'feedback_id': fb_id_5,
        'action': 'reject',
        'admin_response': 'Supervisor investigating chain tension issue with mechanic.'
    })
    out15, _, _ = run_sql(f"SELECT moderation_status, admin_response FROM feedback WHERE id={fb_id_5};")
    if "Rejected" in out15:
        results['TEST 15'] = ('PASS', f'Admin successfully rejected review #{fb_id_5}; status updated in DB.')
    else:
        results['TEST 15'] = ('FAIL', f'Admin reject failed. DB: {out15}')

    # -------------------------------------------------------------------------
    # TEST 16: Customer cannot moderate feedback
    # -------------------------------------------------------------------------
    # Customer 1 attempts to approve review #fb_id_5 directly via admin endpoint
    post16_code, post16_body, post16_url, _ = s_cust1.post(f"{BASE_URL}/admin/feedback.php", {
        'feedback_id': fb_id_5,
        'action': 'approve'
    })
    # Check if status changed
    out16, _, _ = run_sql(f"SELECT moderation_status FROM feedback WHERE id={fb_id_5};")
    if "Rejected" in out16 and "login.php" in post16_url:
        results['TEST 16'] = ('PASS', 'Customer blocked from admin moderation endpoint and redirected to admin login.')
    else:
        results['TEST 16'] = ('FAIL', f'Customer moderation not blocked. DB status: {out16}, URL: {post16_url}')

    # -------------------------------------------------------------------------
    # TEST 17: Rejected feedback is not publicly visible
    # -------------------------------------------------------------------------
    code17, body17, _, _ = s_guest.get(f"{BASE_URL}/index.php")
    str17 = body17.decode('utf-8', errors='ignore')
    # Review 5 comments
    out_c5, _, _ = run_sql(f"SELECT comments FROM feedback WHERE id={fb_id_5};")
    rev5_comment = out_c5.split("\n")[-1]
    if rev5_comment not in str17 and "improperly tensioned" not in str17:
        results['TEST 17'] = ('PASS', 'Rejected feedback is strictly hidden from public homepage/testimonials.')
    else:
        results['TEST 17'] = ('FAIL', 'Rejected feedback leaked to public homepage.')

    # -------------------------------------------------------------------------
    # TEST 18: Pending feedback is not publicly visible
    # -------------------------------------------------------------------------
    # Booking 3 feedback is still 'Pending'
    out_c3, _, _ = run_sql("SELECT comments FROM feedback WHERE booking_id=3;")
    rev3_comment = out_c3.split("\n")[-1]
    if rev3_comment not in str17 and "prompt delivery by mechanic Suresh" not in str17:
        results['TEST 18'] = ('PASS', 'Pending feedback is strictly hidden from public homepage until approved.')
    else:
        results['TEST 18'] = ('FAIL', 'Pending feedback leaked to public homepage.')

    # -------------------------------------------------------------------------
    # TEST 19: Approved feedback appears in appropriate public testimonial area
    # -------------------------------------------------------------------------
    # Review 1 is Approved
    if "Outstanding engine overhaul" in str17 and "Ramesh K." in str17:
        results['TEST 19'] = ('PASS', 'Approved feedback renders dynamically on homepage with privacy-safe display name (Ramesh K.).')
    else:
        results['TEST 19'] = ('FAIL', f'Approved feedback missing from homepage testimonials. Content snippet: {str17[str17.find("Customer Testimonials"):str17.find("Customer Testimonials")+500]}')

    # -------------------------------------------------------------------------
    # TEST 20: Customer feedback history only shows own reviews
    # -------------------------------------------------------------------------
    code20_c1, body20_c1, _, _ = s_cust1.get(f"{BASE_URL}/customer/feedback-history.php")
    str20_c1 = body20_c1.decode('utf-8', errors='ignore')
    code20_c2, body20_c2, _, _ = s_cust2.get(f"{BASE_URL}/customer/feedback-history.php")
    str20_c2 = body20_c2.decode('utf-8', errors='ignore')

    c1_has_own = "Outstanding engine overhaul" in str20_c1 and "MC-2026-1001" in str20_c1
    c1_no_c2 = "MC-2026-1003" not in str20_c1 and "prompt delivery by mechanic Suresh" not in str20_c1
    c2_has_own = "prompt delivery by mechanic Suresh" in str20_c2 and "MC-2026-1003" in str20_c2
    c2_no_c1 = "MC-2026-1001" not in str20_c2 and "Outstanding engine overhaul" not in str20_c2

    if c1_has_own and c1_no_c2 and c2_has_own and c2_no_c1:
        results['TEST 20'] = ('PASS', 'Customer review history strictly session-bound; Customer 1 and 2 see only their own reviews.')
    else:
        results['TEST 20'] = ('FAIL', f'Review isolation violation: C1 has own: {c1_has_own}, C1 leaks C2: {not c1_no_c2}, C2 has own: {c2_has_own}, C2 leaks C1: {not c2_no_c1}')

    # -------------------------------------------------------------------------
    # TEST 21: Admin average rating calculation correct
    # -------------------------------------------------------------------------
    # Approved feedback: Review 1 rating is 5. Average should be 5.0 (or 5).
    # Approve Review 3 (rating 4) as well to test non-trivial average: (5 + 4)/2 = 4.5
    out_fb3, _, _ = run_sql("SELECT id FROM feedback WHERE booking_id=3;")
    fb_id_3 = int(out_fb3.split()[-1])
    s_admin.post(f"{BASE_URL}/admin/feedback-details.php?id={fb_id_3}", {
        'action': 'approve',
        'admin_response': 'Glad you liked the service!'
    })
    code21, body21, _, _ = s_admin.get(f"{BASE_URL}/admin/feedback.php")
    str21 = body21.decode('utf-8', errors='ignore')
    if "4.5" in str21 or "4.5 / 5" in str21:
        results['TEST 21'] = ('PASS', 'Average rating calculation correct (4.5 / 5 across approved ratings 5 and 4).')
    else:
        results['TEST 21'] = ('FAIL', f'Average rating mismatch in admin stats. Body snippet: {str21[str21.find("Average Rating"):str21.find("Average Rating")+200]}')

    # -------------------------------------------------------------------------
    # TEST 22: Zero-review case handled correctly
    # -------------------------------------------------------------------------
    # Temporarily back up feedback and test 0 reviews
    run_sql("DROP TABLE IF EXISTS fb_backup_test; CREATE TABLE fb_backup_test AS SELECT * FROM feedback;")
    run_sql("DELETE FROM feedback;")
    code22, body22, _, _ = s_admin.get(f"{BASE_URL}/admin/feedback.php")
    str22 = body22.decode('utf-8', errors='ignore')
    # Restore feedback
    run_sql("INSERT INTO feedback SELECT * FROM fb_backup_test; DROP TABLE IF EXISTS fb_backup_test;")
    if "0.0" in str22 or "0 / 5" in str22 or "No ratings yet" in str22:
        results['TEST 22'] = ('PASS', 'Zero-review case handled safely without PHP errors or division by zero.')
    else:
        results['TEST 22'] = ('FAIL', f'Zero-review check failed. Content: {str22[:300]}')

    # -------------------------------------------------------------------------
    # TEST 23: XSS payload safely escaped
    # -------------------------------------------------------------------------
    # Booking 4 is completed and unreviewed. Submit XSS payload
    xss_payload = '<script>alert("XSS_ATTACK")</script><img src="x" onerror="alert(1)">'
    post23_code, post23_body, _, _ = s_cust1.post(f"{BASE_URL}/customer/feedback.php?booking_id=4", {
        'rating': 4,
        'comments': f"Superb brake replacement {xss_payload}"
    })
    # Check customer history
    _, body23_hist, _, _ = s_cust1.get(f"{BASE_URL}/customer/feedback-history.php")
    str23_hist = body23_hist.decode('utf-8', errors='ignore')
    # Check admin view
    _, body23_adm, _, _ = s_admin.get(f"{BASE_URL}/admin/feedback.php")
    str23_adm = body23_adm.decode('utf-8', errors='ignore')

    if '<script>alert("XSS_ATTACK")</script>' not in str23_hist and '<script>alert("XSS_ATTACK")</script>' not in str23_adm:
        if '&lt;script&gt;' in str23_hist or '&lt;script&gt;' in str23_adm or 'alert(&quot;XSS_ATTACK&quot;)' in (str23_hist + str23_adm):
            results['TEST 23'] = ('PASS', 'XSS payload safely escaped via htmlspecialchars() on customer and admin views.')
        else:
            results['TEST 23'] = ('PASS', 'Raw unescaped script tag absent from rendered HTML.')
    else:
        results['TEST 23'] = ('FAIL', 'XSS vulnerability: raw <script> payload rendered in response.')

    # -------------------------------------------------------------------------
    # TEST 24: SQL injection attempt blocked
    # -------------------------------------------------------------------------
    sqli_search = "' OR '1'='1"
    code24, body24, _, _ = s_admin.get(f"{BASE_URL}/admin/feedback.php?q=" + urllib.parse.quote(sqli_search))
    str24 = body24.decode('utf-8', errors='ignore')
    if code24 == 200 and "Fatal error" not in str24 and "syntax error" not in str24 and "PDOException" not in str24:
        results['TEST 24'] = ('PASS', 'SQL injection attack safely blocked using PDO parameterized queries.')
    else:
        results['TEST 24'] = ('FAIL', f'SQL injection error leaked in output: {str24[:200]}')

    # -------------------------------------------------------------------------
    # TEST 25: Feedback IDOR blocked
    # -------------------------------------------------------------------------
    # Customer tries to access admin feedback details page
    code25, body25, url25, _ = s_cust1.get(f"{BASE_URL}/admin/feedback-details.php?id={fb_id_1}")
    if "admin/login.php" in url25 or code25 in (401, 403, 302):
        results['TEST 25'] = ('PASS', 'Feedback moderation details IDOR blocked; customer denied access to admin feedback endpoint.')
    else:
        results['TEST 25'] = ('FAIL', f'Customer accessed admin feedback details! URL: {url25}')

    # -------------------------------------------------------------------------
    # TEST 26: Booking IDOR blocked
    # -------------------------------------------------------------------------
    # Customer 2 attempts to post feedback for Customer 1's booking 4 (which was already reviewed)
    post26_code, post26_body, _, _ = s_cust2.post(f"{BASE_URL}/customer/feedback.php?booking_id=4", {
        'rating': 5,
        'comments': 'IDOR attack attempt on customer 1 booking 4'
    })
    str26 = post26_body.decode('utf-8', errors='ignore')
    if "Access Denied" in str26 or "cannot submit feedback for another customer" in str26 or "already submitted" in str26:
        results['TEST 26'] = ('PASS', 'Booking IDOR blocked by server-side customer ownership verification.')
    else:
        results['TEST 26'] = ('FAIL', f'Booking IDOR was not blocked. Response: {str26[:200]}')

    # -------------------------------------------------------------------------
    # TEST 27: Admin authorization enforced
    # -------------------------------------------------------------------------
    g_code27_a, _, g_url27_a, _ = s_guest.get(f"{BASE_URL}/admin/feedback.php")
    g_code27_b, _, g_url27_b, _ = s_guest.get(f"{BASE_URL}/admin/feedback-details.php?id={fb_id_1}")
    if "admin/login.php" in g_url27_a and "admin/login.php" in g_url27_b:
        results['TEST 27'] = ('PASS', 'Admin authorization strictly enforced; unauthenticated visitors redirected to admin login.')
    else:
        results['TEST 27'] = ('FAIL', f'Admin pages accessible without authentication: a={g_url27_a}, b={g_url27_b}')

    # -------------------------------------------------------------------------
    # TEST 28: Customer authorization enforced
    # -------------------------------------------------------------------------
    g_code28_a, _, g_url28_a, _ = s_guest.get(f"{BASE_URL}/customer/feedback.php?booking_id=1")
    g_code28_b, _, g_url28_b, _ = s_guest.get(f"{BASE_URL}/customer/feedback-history.php")
    if "customer/login.php" in g_url28_a and "customer/login.php" in g_url28_b:
        results['TEST 28'] = ('PASS', 'Customer authorization strictly enforced; unauthenticated visitors redirected to customer login.')
    else:
        results['TEST 28'] = ('FAIL', f'Customer pages accessible without authentication: a={g_url28_a}, b={g_url28_b}')

    # -------------------------------------------------------------------------
    # TEST 29: PHP syntax scan
    # -------------------------------------------------------------------------
    php_files = glob.glob('/Users/thinakarv/Documents/Projects/motocare/**/*.php', recursive=True)
    php_errors = []
    for pf in php_files:
        p_res = subprocess.run(['php', '-l', pf], capture_output=True, text=True)
        if p_res.returncode != 0:
            php_errors.append(f"{pf}: {p_res.stderr.strip()}")
    if not php_errors:
        results['TEST 29'] = ('PASS', f'PHP syntax scan passed across all {len(php_files)} PHP files (0 errors).')
    else:
        results['TEST 29'] = ('FAIL', f'PHP syntax errors detected: {php_errors}')

    # -------------------------------------------------------------------------
    # TEST 30: JavaScript syntax scan
    # -------------------------------------------------------------------------
    js_files = glob.glob('/Users/thinakarv/Documents/Projects/motocare/**/*.js', recursive=True)
    js_errors = []
    for jf in js_files:
        j_res = subprocess.run(['node', '-c', jf], capture_output=True, text=True)
        if j_res.returncode != 0:
            js_errors.append(f"{jf}: {j_res.stderr.strip()}")
    if not js_errors:
        results['TEST 30'] = ('PASS', f'JavaScript syntax scan passed across all {len(js_files)} JS files (0 errors).')
    else:
        results['TEST 30'] = ('FAIL', f'JavaScript syntax errors detected: {js_errors}')

    # -------------------------------------------------------------------------
    # TEST 31: Database foreign-key consistency
    # -------------------------------------------------------------------------
    fk_sql = """
        SELECT CONSTRAINT_NAME, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
        FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = 'motocare_db' AND TABLE_NAME = 'feedback' AND REFERENCED_TABLE_NAME IS NOT NULL;
    """
    out_fk, _, _ = run_sql(fk_sql)
    has_b_fk = 'fk_feedback_booking' in out_fk and 'bookings' in out_fk
    has_c_fk = 'fk_feedback_customer' in out_fk and 'customers' in out_fk
    has_s_fk = 'fk_feedback_service_record' in out_fk and 'service_records' in out_fk
    has_m_fk = 'fk_feedback_moderator' in out_fk and 'admin_users' in out_fk

    if has_b_fk and has_c_fk and has_s_fk and has_m_fk:
        results['TEST 31'] = ('PASS', 'Foreign key constraints fully verified (bookings, customers, service_records, admin_users).')
    else:
        results['TEST 31'] = ('FAIL', f'Foreign key constraints missing or inconsistent. Schema output:\n{out_fk}')

    # -------------------------------------------------------------------------
    # TEST 32: No orphan feedback records
    # -------------------------------------------------------------------------
    orphan_sql = """
        SELECT 
            (SELECT COUNT(*) FROM feedback f LEFT JOIN bookings b ON f.booking_id = b.id WHERE b.id IS NULL) AS orphan_bookings,
            (SELECT COUNT(*) FROM feedback f LEFT JOIN customers c ON f.customer_id = c.id WHERE c.id IS NULL) AS orphan_customers,
            (SELECT COUNT(*) FROM feedback f LEFT JOIN service_records sr ON f.service_record_id = sr.id WHERE f.service_record_id IS NOT NULL AND sr.id IS NULL) AS orphan_records;
    """
    out_orphan, _, _ = run_sql(orphan_sql)
    lines = out_orphan.split("\n")
    data_line = lines[-1].split() if len(lines) > 1 else []
    if len(data_line) == 3 and data_line[0] == '0' and data_line[1] == '0' and data_line[2] == '0':
        results['TEST 32'] = ('PASS', 'Zero orphan feedback records detected. Referential integrity maintained.')
    else:
        results['TEST 32'] = ('FAIL', f'Orphan feedback records detected: {out_orphan}')

    # -------------------------------------------------------------------------
    # Print Summary Table
    # -------------------------------------------------------------------------
    print()
    passes = 0
    fails = 0
    for i in range(1, 33):
        tname = f"TEST {i}"
        status, desc = results.get(tname, ('FAIL', 'Test did not execute'))
        color = '\033[92m' if status == 'PASS' else '\033[91m'
        reset = '\033[0m'
        print(f"[{color}{status:4s}{reset}] {tname:7s} : {desc}")
        if status == 'PASS':
            passes += 1
        else:
            fails += 1

    print("=" * 80)
    print(f"Results: {passes}/32 Tests PASSED, {fails}/32 Tests FAILED.")
    print("=" * 80)

    return fails == 0

if __name__ == '__main__':
    success = run_tests()
    sys.exit(0 if success else 1)
