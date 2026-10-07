# Mechanic Module (Stage 2 Placeholder)

## Overview
The `mechanic/` directory will provide an interface for shop technicians and mechanics to update job cards, report vehicle diagnostics, and log part replacements in real time.

## Planned Stage 2 Files
- `login.php` - Technician login with staff credentials.
- `dashboard.php` - Overview of assigned bikes in bay, priority tickets, and daily task checklist.
- `assigned-services.php` - Detailed job card inspection:
  - Update vehicle repair status (Inspection -> Parts Required -> In Progress -> Quality Check -> Ready for Delivery).
  - Add mechanic notes, oil grade used, battery health readings, and spare parts replaced.
  - Mark services as completed for supervisor sign-off.

## Database Integration
This module writes and updates:
- `mechanics`
- `bookings`
- `service_records`
- `spare_parts` (inventory deduction)
