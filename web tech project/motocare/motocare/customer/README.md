# Customer Module (Stage 2 Placeholder)

## Overview
The `customer/` directory will house all client-facing dynamic portals and account management scripts for **MotoCare** in Stage 2 (PHP & MySQL).

## Planned Stage 2 Files
- `login.php` - Customer authentication and session initialization.
- `register.php` - New customer sign-up with phone verification and vehicle registration.
- `dashboard.php` - Customer self-service portal showing active service status, pending bills, and vehicle details.
- `bookings.php` - View active bookings, reschedule appointments, or book new service slots.
- `service-history.php` - Complete service logs, past bills, replaced spare parts, and mechanic inspection notes.
- `profile.php` - Manage customer personal details, address, and registered two-wheelers.

## Database Integration
This module will interact with the following MySQL tables:
- `customers`
- `vehicles`
- `bookings`
- `service_records`
- `bills`
- `feedback`

> **Note**: For Stage 1, all booking interactions are simulated on the client side using JavaScript in `booking.html` and `js/script.js`.
