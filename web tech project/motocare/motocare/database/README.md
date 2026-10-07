# Database Schema & Migrations (Stage 2 Placeholder)

## Overview
The `database/` directory will store database migration scripts, table schemas, sample seed data, and SQL dumps for the **MotoCare** management platform.

## Planned Stage 2 Files
- `motocare.sql` - Complete relational schema with table structures, foreign key constraints, indexes, and initial test seed data.

## Proposed Relational Database Tables

1. **`admin_users`**
   - `id`, `username`, `email`, `password_hash`, `role`, `created_at`
2. **`customers`**
   - `id`, `full_name`, `email`, `phone`, `password_hash`, `address`, `created_at`
3. **`vehicles`**
   - `id`, `customer_id`, `vehicle_type` (Motorcycle / Scooter / EV), `brand`, `model`, `reg_number`, `year`, `odometer_reading`
4. **`services`**
   - `id`, `category` (General, Mechanical, Electrical, Tyre, EV), `name`, `description`, `base_price`, `estimated_time_mins`
5. **`mechanics`**
   - `id`, `full_name`, `specialization`, `phone`, `status` (Available, Busy, Off-duty), `rating`
6. **`bookings`**
   - `id`, `service_code` (e.g., MC-2026-1045), `customer_id`, `vehicle_id`, `service_id`, `preferred_date`, `preferred_time`, `problem_description`, `status` (Pending, Confirmed, In Progress, Completed, Cancelled), `created_at`
7. **`service_records`**
   - `id`, `booking_id`, `mechanic_id`, `inspection_notes`, `work_done`, `oil_grade`, `battery_voltage`, `completion_date`
8. **`spare_parts`**
   - `id`, `part_number`, `part_name`, `compatible_models`, `unit_price`, `quantity_in_stock`, `reorder_level`
9. **`bills`**
   - `id`, `booking_id`, `labor_amount`, `parts_amount`, `tax_gst`, `discount`, `total_amount`, `payment_status` (Pending, Paid), `payment_method`
10. **`feedback`**
    - `id`, `booking_id`, `customer_id`, `rating` (1-5), `review_text`, `created_at`

> **Note**: In Stage 1, these models inform the structure of client-side forms and simulated service tokens.
