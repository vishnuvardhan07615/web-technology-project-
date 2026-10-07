<?php
/**
 * ============================================================================
 * MotoCare – Two-Wheeler Service & Mechanic Shop Management System
 * File: includes/feedback-helper.php
 * Stage 2: Customer Feedback, 5-Star Ratings & Moderation Engine
 * ============================================================================
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

/**
 * Verify whether a customer is eligible to submit feedback for a booking.
 * 
 * Rules:
 * - Customer must be authenticated
 * - Booking must belong to the logged-in customer
 * - Booking status must be 'Completed'
 * - Service record must exist
 * - Booking must not be cancelled
 * - Feedback must not already exist
 *
 * @param PDO $pdo Active database connection
 * @param int $customerId Authenticated customer ID
 * @param int $bookingId Booking ID to review
 * @return array ['eligible' => bool, 'booking' => array|null, 'error' => string|null, 'feedback' => array|null]
 */
function canCustomerSubmitFeedback(PDO $pdo, int $customerId, int $bookingId): array {
    if ($customerId <= 0 || $bookingId <= 0) {
        return ['eligible' => false, 'booking' => null, 'error' => 'Invalid customer or booking reference.', 'feedback' => null];
    }

    try {
        // Query booking details with customer, vehicle, service, mechanic, and invoice
        $stmt = $pdo->prepare("
            SELECT 
                b.id AS booking_id,
                b.booking_code,
                b.customer_id,
                b.vehicle_id,
                b.service_id,
                b.mechanic_id,
                b.status,
                b.preferred_date,
                b.preferred_time,
                b.created_at,
                c.full_name AS customer_name,
                c.phone AS customer_phone,
                c.email AS customer_email,
                v.brand, v.model, v.registration_number,
                s.service_name, s.price AS service_price,
                m.full_name AS mechanic_name,
                sr.id AS service_record_id,
                sr.work_done,
                bi.id AS bill_id,
                bi.invoice_number
            FROM bookings b
            JOIN customers c ON b.customer_id = c.id
            JOIN vehicles v ON b.vehicle_id = v.id
            JOIN services s ON b.service_id = s.id
            LEFT JOIN mechanics m ON b.mechanic_id = m.id
            LEFT JOIN service_records sr ON b.id = sr.booking_id
            LEFT JOIN bills bi ON b.id = bi.booking_id
            WHERE b.id = :bid
            LIMIT 1
        ");
        $stmt->execute([':bid' => $bookingId]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$booking) {
            return ['eligible' => false, 'booking' => null, 'error' => 'Booking record not found in system.', 'feedback' => null];
        }

        // 1. Verify customer ownership
        if ((int)$booking['customer_id'] !== $customerId) {
            return ['eligible' => false, 'booking' => null, 'error' => 'Access Denied: You cannot submit feedback for another customer\'s booking.', 'feedback' => null];
        }

        // 2. Verify booking is Completed
        if ($booking['status'] !== 'Completed') {
            return [
                'eligible' => false, 
                'booking'  => $booking, 
                'error'    => "Feedback is only available for completed services. This service is currently '{$booking['status']}'.",
                'feedback' => null
            ];
        }

        // 3. Verify service record exists
        if (empty($booking['service_record_id'])) {
            return [
                'eligible' => false,
                'booking'  => $booking,
                'error'    => 'Service record not found for this completed booking.',
                'feedback' => null
            ];
        }

        // 4. Check if feedback already exists for this booking
        $stmtFb = $pdo->prepare("SELECT * FROM feedback WHERE booking_id = :bid LIMIT 1");
        $stmtFb->execute([':bid' => $bookingId]);
        $existingFeedback = $stmtFb->fetch(PDO::FETCH_ASSOC);

        if ($existingFeedback) {
            return [
                'eligible' => false,
                'booking'  => $booking,
                'error'    => 'You have already submitted a review for this completed service.',
                'feedback' => $existingFeedback
            ];
        }

        return ['eligible' => true, 'booking' => $booking, 'error' => null, 'feedback' => null];

    } catch (PDOException $e) {
        return ['eligible' => false, 'booking' => null, 'error' => 'Database error: ' . $e->getMessage(), 'feedback' => null];
    }
}

/**
 * Submit verified feedback and 5-star rating for a completed booking.
 *
 * @param PDO $pdo Active database connection
 * @param int $customerId Authenticated customer ID
 * @param int $bookingId Completed booking ID
 * @param int $rating Star rating (1 to 5)
 * @param string $comments Customer review comment
 * @return array ['success' => bool, 'feedback_id' => int|null, 'error' => string|null]
 */
function submitFeedback(PDO $pdo, int $customerId, int $bookingId, int $rating, string $comments): array {
    // 1. Validate rating bounds
    if ($rating < 1 || $rating > 5) {
        return ['success' => false, 'feedback_id' => null, 'error' => 'Rating must be an integer between 1 and 5 stars.'];
    }

    // 2. Validate review comments
    $trimmedComments = trim($comments);
    if (empty($trimmedComments)) {
        return ['success' => false, 'feedback_id' => null, 'error' => 'Please enter your review comments.'];
    }
    if (mb_strlen($trimmedComments) < 5) {
        return ['success' => false, 'feedback_id' => null, 'error' => 'Review comment must be at least 5 characters long.'];
    }
    if (mb_strlen($trimmedComments) > 1000) {
        return ['success' => false, 'feedback_id' => null, 'error' => 'Review comment cannot exceed 1000 characters.'];
    }

    // 3. Check customer eligibility
    $check = canCustomerSubmitFeedback($pdo, $customerId, $bookingId);
    if (!$check['eligible']) {
        return ['success' => false, 'feedback_id' => null, 'error' => $check['error']];
    }

    $booking = $check['booking'];
    $serviceRecordId = !empty($booking['service_record_id']) ? (int)$booking['service_record_id'] : null;

    try {
        $pdo->beginTransaction();

        // Secondary lock check for race conditions
        $stmtLock = $pdo->prepare("SELECT id FROM feedback WHERE booking_id = :bid FOR UPDATE");
        $stmtLock->execute([':bid' => $bookingId]);
        if ($stmtLock->fetchColumn()) {
            $pdo->rollBack();
            return ['success' => false, 'feedback_id' => null, 'error' => 'Duplicate review prevented. Feedback already exists for this booking.'];
        }

        // Insert new feedback record with moderation_status = 'Pending'
        $stmtInsert = $pdo->prepare("
            INSERT INTO feedback (
                customer_id, booking_id, service_record_id, rating,
                comments, admin_response, moderation_status, created_at, updated_at
            ) VALUES (
                :cid, :bid, :srid, :rating,
                :comments, NULL, 'Pending', NOW(), NOW()
            )
        ");
        $stmtInsert->execute([
            ':cid'      => $customerId,
            ':bid'      => $bookingId,
            ':srid'     => $serviceRecordId,
            ':rating'   => $rating,
            ':comments' => $trimmedComments
        ]);

        $feedbackId = (int)$pdo->lastInsertId();
        $pdo->commit();

        return ['success' => true, 'feedback_id' => $feedbackId, 'error' => null];

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if ($e->getCode() == 23000) {
            return ['success' => false, 'feedback_id' => null, 'error' => 'Duplicate feedback: You have already reviewed this booking.'];
        }
        return ['success' => false, 'feedback_id' => null, 'error' => 'Database error while saving feedback: ' . $e->getMessage()];
    }
}

/**
 * Moderate a feedback record (Approve / Reject) with optional admin response.
 *
 * @param PDO $pdo Active database connection
 * @param int $feedbackId Feedback ID
 * @param int $adminId Authenticated admin ID
 * @param string $status 'Approved' or 'Rejected'
 * @param string|null $adminResponse Optional public reply from workshop supervisor
 * @return array ['success' => bool, 'error' => string|null]
 */
function moderateFeedback(PDO $pdo, int $feedbackId, int $adminId, string $status, ?string $adminResponse = null): array {
    if ($feedbackId <= 0 || $adminId <= 0) {
        return ['success' => false, 'error' => 'Invalid feedback or administrator ID.'];
    }

    if (!in_array($status, ['Approved', 'Rejected', 'Pending'])) {
        return ['success' => false, 'error' => 'Invalid moderation status. Must be Approved, Rejected, or Pending.'];
    }

    $trimmedResponse = $adminResponse !== null ? trim($adminResponse) : null;
    if ($trimmedResponse === '') {
        $trimmedResponse = null;
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT id FROM feedback WHERE id = :id FOR UPDATE");
        $stmt->execute([':id' => $feedbackId]);
        if (!$stmt->fetchColumn()) {
            $pdo->rollBack();
            return ['success' => false, 'error' => 'Feedback record not found.'];
        }

        $stmtUpd = $pdo->prepare("
            UPDATE feedback
            SET moderation_status = :status,
                moderator_id      = :mid,
                admin_response    = :response,
                moderated_at      = NOW(),
                updated_at        = NOW()
            WHERE id = :id
        ");
        $stmtUpd->execute([
            ':status'   => $status,
            ':mid'      => $adminId,
            ':response' => $trimmedResponse,
            ':id'       => $feedbackId
        ]);

        $pdo->commit();
        return ['success' => true, 'error' => null];

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['success' => false, 'error' => 'Database error during moderation: ' . $e->getMessage()];
    }
}

/**
 * Fetch feedback metrics and statistics.
 *
 * @param PDO $pdo Active database connection
 * @return array
 */
function getFeedbackStats(PDO $pdo): array {
    $stats = [
        'total_reviews'    => 0,
        'pending_reviews'  => 0,
        'approved_reviews' => 0,
        'rejected_reviews' => 0,
        'average_rating'   => 0.0,
        'rating_breakdown' => [
            5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0
        ]
    ];

    try {
        $stmt = $pdo->query("
            SELECT 
                COUNT(*) AS total_reviews,
                COUNT(CASE WHEN moderation_status = 'Pending' THEN 1 END) AS pending_reviews,
                COUNT(CASE WHEN moderation_status = 'Approved' THEN 1 END) AS approved_reviews,
                COUNT(CASE WHEN moderation_status = 'Rejected' THEN 1 END) AS rejected_reviews,
                COALESCE(AVG(CASE WHEN moderation_status = 'Approved' THEN rating END), 0.0) AS avg_rating,
                COALESCE(AVG(rating), 0.0) AS all_avg_rating
            FROM feedback
        ");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $stats['total_reviews']    = (int)$row['total_reviews'];
            $stats['pending_reviews']  = (int)$row['pending_reviews'];
            $stats['approved_reviews'] = (int)$row['approved_reviews'];
            $stats['rejected_reviews'] = (int)$row['rejected_reviews'];
            // If approved reviews exist, use approved avg; otherwise use overall avg or 0.0
            $stats['average_rating']   = $stats['approved_reviews'] > 0 
                ? round((float)$row['avg_rating'], 1) 
                : round((float)$row['all_avg_rating'], 1);
        }

        // Breakdown per star
        $stmtStars = $pdo->query("
            SELECT rating, COUNT(*) AS cnt 
            FROM feedback 
            GROUP BY rating
        ");
        while ($sRow = $stmtStars->fetch(PDO::FETCH_ASSOC)) {
            $r = (int)$sRow['rating'];
            if (isset($stats['rating_breakdown'][$r])) {
                $stats['rating_breakdown'][$r] = (int)$sRow['cnt'];
            }
        }

    } catch (PDOException $e) {
        // Return defaults
    }

    return $stats;
}

/**
 * Get public testimonials (Approved reviews only).
 * Privacy-safe: Never exposes customer email, phone, full registration, or private admin audit data.
 *
 * @param PDO $pdo Active database connection
 * @param int $limit Maximum testimonials to fetch
 * @return array
 */
function getPublicTestimonials(PDO $pdo, int $limit = 6): array {
    $testimonials = [];
    try {
        $stmt = $pdo->prepare("
            SELECT 
                f.id,
                f.rating,
                f.comments,
                f.admin_response,
                f.created_at,
                c.full_name AS customer_name,
                v.brand, v.model,
                s.service_name
            FROM feedback f
            JOIN customers c ON f.customer_id = c.id
            JOIN bookings b ON f.booking_id = b.id
            JOIN vehicles v ON b.vehicle_id = v.id
            JOIN services s ON b.service_id = s.id
            WHERE f.moderation_status = 'Approved'
            ORDER BY f.created_at DESC
            LIMIT :lim
        ");
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($raw as $r) {
            // Privacy safe customer name (e.g. Ramesh V. or Ramesh Kumar)
            $nameParts = explode(' ', trim($r['customer_name']));
            if (count($nameParts) > 1) {
                $safeName = $nameParts[0] . ' ' . strtoupper(substr($nameParts[count($nameParts) - 1], 0, 1)) . '.';
            } else {
                $safeName = $nameParts[0];
            }

            // Initials avatar
            $initials = '';
            foreach ($nameParts as $np) {
                if (!empty($np)) {
                    $initials .= strtoupper($np[0]);
                }
                if (strlen($initials) >= 2) break;
            }
            if (empty($initials)) $initials = 'MC';

            $testimonials[] = [
                'id'            => (int)$r['id'],
                'rating'        => (int)$r['rating'],
                'comments'      => $r['comments'],
                'admin_response'=> $r['admin_response'],
                'date'          => $r['created_at'],
                'display_name'  => $safeName,
                'initials'      => $initials,
                'vehicle_desc'  => $r['brand'] . ' ' . $r['model'] . ' Owner',
                'service_name'  => $r['service_name']
            ];
        }

    } catch (PDOException $e) {
        // Return empty array
    }

    return $testimonials;
}
