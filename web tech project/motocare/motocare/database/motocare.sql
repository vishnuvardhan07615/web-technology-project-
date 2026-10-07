-- ============================================================================
-- MotoCare – Two-Wheeler Service & Mechanic Shop Management System
-- Database Schema: database/motocare.sql
-- Stage 2: Relational Database Design with Foreign Keys & Seed Data
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `motocare_db` 
DEFAULT CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `motocare_db`;

-- ----------------------------------------------------------------------------
-- Disable foreign key checks during schema re-creation
-- ----------------------------------------------------------------------------
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `feedback`;
DROP TABLE IF EXISTS `bills`;
DROP TABLE IF EXISTS `service_records`;
DROP TABLE IF EXISTS `bookings`;
DROP TABLE IF EXISTS `spare_parts`;
DROP TABLE IF EXISTS `mechanics`;
DROP TABLE IF EXISTS `services`;
DROP TABLE IF EXISTS `vehicles`;
DROP TABLE IF EXISTS `customers`;
DROP TABLE IF EXISTS `admin_users`;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- 1. admin_users Table
-- Stores workshop managers & system administrators
-- ============================================================================
CREATE TABLE `admin_users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(80) NOT NULL UNIQUE,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 2. customers Table
-- Stores registered vehicle owners
-- ============================================================================
CREATE TABLE `customers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `phone` VARCHAR(20) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `address` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 3. vehicles Table
-- Stores customer two-wheelers (Motorcycles, Scooters, EVs)
-- ============================================================================
CREATE TABLE `vehicles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `customer_id` INT NOT NULL,
  `vehicle_type` ENUM('Motorcycle', 'Scooter', 'Electric Two-Wheeler') NOT NULL,
  `brand` VARCHAR(80) NOT NULL,
  `model` VARCHAR(100) NOT NULL,
  `registration_number` VARCHAR(30) NOT NULL UNIQUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_vehicles_customer` (`customer_id`),
  CONSTRAINT `fk_vehicles_customer` FOREIGN KEY (`customer_id`) 
    REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 4. services Table
-- Catalog of workshop maintenance packages and repair labor
-- ============================================================================
CREATE TABLE `services` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `service_name` VARCHAR(150) NOT NULL,
  `category` VARCHAR(80) NOT NULL,
  `description` TEXT NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `estimated_duration` VARCHAR(50) NOT NULL,
  `status` ENUM('Active', 'Inactive') DEFAULT 'Active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_services_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 5. mechanics Table
-- Certified technicians, specialization areas, and duty status
-- ============================================================================
CREATE TABLE `mechanics` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `phone` VARCHAR(20) NOT NULL,
  `specialization` VARCHAR(150) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `status` ENUM('Available', 'Busy', 'On Leave') DEFAULT 'Available',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 6. bookings Table
-- Service reservations connecting customer, vehicle, service package & mechanic
-- ============================================================================
CREATE TABLE `bookings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `booking_code` VARCHAR(50) NOT NULL UNIQUE,
  `customer_id` INT NOT NULL,
  `vehicle_id` INT NOT NULL,
  `service_id` INT NOT NULL,
  `mechanic_id` INT NULL,
  `preferred_date` DATE NOT NULL,
  `preferred_time` VARCHAR(50) NOT NULL,
  `problem_description` TEXT NULL,
  `status` ENUM(
    'Pending',
    'Confirmed',
    'Vehicle Received',
    'Inspection',
    'Service In Progress',
    'Quality Check',
    'Ready for Delivery',
    'Completed',
    'Cancelled'
  ) DEFAULT 'Pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_bookings_customer` (`customer_id`),
  INDEX `idx_bookings_vehicle` (`vehicle_id`),
  INDEX `idx_bookings_service` (`service_id`),
  INDEX `idx_bookings_mechanic` (`mechanic_id`),
  INDEX `idx_bookings_status` (`status`),
  CONSTRAINT `fk_bookings_customer` FOREIGN KEY (`customer_id`) 
    REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_bookings_vehicle` FOREIGN KEY (`vehicle_id`) 
    REFERENCES `vehicles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_bookings_service` FOREIGN KEY (`service_id`) 
    REFERENCES `services` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_bookings_mechanic` FOREIGN KEY (`mechanic_id`) 
    REFERENCES `mechanics` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 7. service_records Table
-- Mechanic workshop job cards, diagnostics, parts replaced, and time tracking
-- ============================================================================
CREATE TABLE `service_records` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `booking_id` INT NOT NULL UNIQUE,
  `mechanic_id` INT NOT NULL,
  `inspection_notes` TEXT NULL,
  `work_done` TEXT NULL,
  `labor_hours` DECIMAL(4,2) DEFAULT 0.00,
  `technician_notes` TEXT NULL,
  `parts_used` TEXT NULL,
  `service_start` DATETIME NULL,
  `service_end` DATETIME NULL,
  `service_status` VARCHAR(50) DEFAULT 'Inspection',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_records_booking` (`booking_id`),
  INDEX `idx_records_mechanic` (`mechanic_id`),
  CONSTRAINT `fk_records_booking` FOREIGN KEY (`booking_id`) 
    REFERENCES `bookings` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_records_mechanic` FOREIGN KEY (`mechanic_id`) 
    REFERENCES `mechanics` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 8. spare_parts Table
-- Parts inventory control, minimum reorder thresholds, and pricing
-- ============================================================================
CREATE TABLE `spare_parts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `part_name` VARCHAR(150) NOT NULL,
  `part_number` VARCHAR(100) NOT NULL UNIQUE,
  `brand` VARCHAR(80) NOT NULL,
  `category` VARCHAR(80) NOT NULL DEFAULT 'General',
  `price` DECIMAL(10,2) NOT NULL,
  `stock_quantity` INT NOT NULL DEFAULT 0,
  `minimum_stock` INT NOT NULL DEFAULT 5,
  `status` ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
  `supplier` VARCHAR(150) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 8b. service_record_parts Table
-- Relational spare parts usage logging on workshop digital job cards
-- ============================================================================
CREATE TABLE IF NOT EXISTS `service_record_parts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `service_record_id` INT NOT NULL,
  `spare_part_id` INT NOT NULL,
  `quantity` INT NOT NULL,
  `unit_price` DECIMAL(10,2) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_record_parts_record` (`service_record_id`),
  INDEX `idx_record_parts_part` (`spare_part_id`),
  CONSTRAINT `fk_srp_record` FOREIGN KEY (`service_record_id`) 
    REFERENCES `service_records` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_srp_part` FOREIGN KEY (`spare_part_id`) 
    REFERENCES `spare_parts` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 8c. inventory_transactions Table
-- Industrial audit trail for spare part purchases, service usage & adjustments
-- ============================================================================
CREATE TABLE IF NOT EXISTS `inventory_transactions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `spare_part_id` INT NOT NULL,
  `transaction_type` ENUM('PURCHASE', 'SERVICE_USAGE', 'ADJUSTMENT', 'RETURN') NOT NULL,
  `quantity` INT NOT NULL,
  `reference_id` INT NULL,
  `reason` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_inv_part` (`spare_part_id`),
  INDEX `idx_inv_type` (`transaction_type`),
  CONSTRAINT `fk_inv_part` FOREIGN KEY (`spare_part_id`) 
    REFERENCES `spare_parts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 9. bills Table
-- Comprehensive billing, GST/Tax, line items, and payment tracking
-- ============================================================================
CREATE TABLE `bills` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `invoice_number` VARCHAR(100) NOT NULL UNIQUE,
  `booking_id` INT NOT NULL,
  `customer_id` INT NOT NULL,
  `vehicle_id` INT NULL,
  `service_record_id` INT NULL,
  `service_charge` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `parts_subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `discount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `taxable_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `gst_rate` DECIMAL(5,2) NOT NULL DEFAULT 18.00,
  `cgst` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `sgst` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `tax` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `payment_status` ENUM('Pending', 'Partially Paid', 'Paid') NOT NULL DEFAULT 'Pending',
  `payment_method` ENUM('Cash', 'UPI', 'Card', 'Bank Transfer', 'Other') NULL,
  `amount_paid` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `balance_due` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `invoice_date` DATE NULL,
  `bill_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_bills_booking` (`booking_id`),
  INDEX `idx_bills_customer` (`customer_id`),
  INDEX `idx_bills_vehicle` (`vehicle_id`),
  INDEX `idx_bills_invoice` (`invoice_number`),
  CONSTRAINT `fk_bills_booking` FOREIGN KEY (`booking_id`) 
    REFERENCES `bookings` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_bills_customer` FOREIGN KEY (`customer_id`) 
    REFERENCES `customers` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_bills_service_record` FOREIGN KEY (`service_record_id`) 
    REFERENCES `service_records` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_bills_vehicle` FOREIGN KEY (`vehicle_id`) 
    REFERENCES `vehicles` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 9b. payments Table
-- Multi-transaction payment ledger supporting partial and full settlements
-- ============================================================================
CREATE TABLE IF NOT EXISTS `payments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `bill_id` INT NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `payment_method` ENUM('Cash', 'UPI', 'Card', 'Bank Transfer', 'Other') NOT NULL DEFAULT 'Cash',
  `transaction_reference` VARCHAR(150) NULL,
  `payment_date` DATE NOT NULL,
  `notes` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_payments_bill` (`bill_id`),
  CONSTRAINT `fk_payments_bill` FOREIGN KEY (`bill_id`) 
    REFERENCES `bills` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 10. feedback Table
-- Customer reviews and star ratings (1 to 5)
-- ============================================================================
CREATE TABLE `feedback` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `customer_id` INT NOT NULL,
  `booking_id` INT NOT NULL,
  `service_record_id` INT NULL,
  `rating` TINYINT NOT NULL,
  `comments` TEXT NULL,
  `admin_response` TEXT NULL,
  `moderation_status` ENUM('Pending', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending',
  `moderator_id` INT NULL,
  `moderated_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_feedback_customer` (`customer_id`),
  INDEX `idx_feedback_booking` (`booking_id`),
  INDEX `idx_feedback_service_record` (`service_record_id`),
  INDEX `idx_feedback_moderation` (`moderation_status`),
  UNIQUE KEY `uq_feedback_booking` (`booking_id`),
  CONSTRAINT `fk_feedback_customer` FOREIGN KEY (`customer_id`) 
    REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_feedback_booking` FOREIGN KEY (`booking_id`) 
    REFERENCES `bookings` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_feedback_service_record` FOREIGN KEY (`service_record_id`) 
    REFERENCES `service_records` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_feedback_moderator` FOREIGN KEY (`moderator_id`) 
    REFERENCES `admin_users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- SEED DATA
-- Default sample accounts (All passwords hashed using bcrypt password_hash)
-- Passwords:
--   Admin:    admin123
--   Mechanic: mechanic123
--   Customer: customer123
-- ============================================================================

-- 1. Admin Account (admin / admin123)
INSERT INTO `admin_users` (`id`, `username`, `email`, `password`) VALUES
(1, 'admin', 'admin@motocare.com', '$2y$10$QKIadv6NhMVMLJippG7ADufQj33kPoj07k.JfCvFqZTT7YtpHUclu');

-- 2. Certified Mechanics (Password: mechanic123)
INSERT INTO `mechanics` (`id`, `full_name`, `email`, `phone`, `specialization`, `password`, `status`) VALUES
(1, 'Arun Kumar', 'arun@motocare.com', '9876543201', 'Senior Mechanic & Royal Enfield / Cruiser Specialist', '$2y$10$rs4EYrgsqS4F/HIdnIhi7.vPPK2ozGL4bwRQafQqjv70Vj92Sm9dq', 'Available'),
(2, 'Karthik', 'karthik@motocare.com', '9876543202', 'Engine Overhaul & Transmission Specialist', '$2y$10$rs4EYrgsqS4F/HIdnIhi7.vPPK2ozGL4bwRQafQqjv70Vj92Sm9dq', 'Available'),
(3, 'Sanjay', 'sanjay@motocare.com', '9876543203', 'Electrical Diagnostics & EV Powertrains', '$2y$10$rs4EYrgsqS4F/HIdnIhi7.vPPK2ozGL4bwRQafQqjv70Vj92Sm9dq', 'Available'),
(4, 'Praveen', 'praveen@motocare.com', '9876543204', 'Suspension Tuning & Brake Systems', '$2y$10$rs4EYrgsqS4F/HIdnIhi7.vPPK2ozGL4bwRQafQqjv70Vj92Sm9dq', 'Available');

-- 3. Sample Customer (Password: customer123)
INSERT INTO `customers` (`id`, `full_name`, `email`, `phone`, `password`, `address`) VALUES
(1, 'Ramesh Kumar', 'ramesh@example.com', '9876543210', '$2y$10$68eULAPFFgQksB6bbkwTPu1iZsgDjs.6bCMR1Vm16MzSCQVCj.jDq', '#14, Anna Nagar 2nd Street, Chennai - 600040');

-- 4. Sample Vehicles for Ramesh Kumar
INSERT INTO `vehicles` (`id`, `customer_id`, `vehicle_type`, `brand`, `model`, `registration_number`) VALUES
(1, 1, 'Motorcycle', 'Royal Enfield', 'Classic 350', 'TN-07-AB-1234'),
(2, 1, 'Scooter', 'Honda', 'Activa 6G', 'TN-09-CD-5678');

-- 5. Comprehensive Services Catalog (19 Packages across 5 Disciplines)
INSERT INTO `services` (`id`, `service_name`, `category`, `description`, `price`, `estimated_duration`, `status`) VALUES
-- GENERAL SERVICE
(1, 'General Service', 'General Service', '36-point diagnostic inspection, oil top-up, spark plug cleaning, throttle tuning, and foam wash.', 500.00, '2 - 3 Hours', 'Active'),
(2, 'Oil Change', 'General Service', 'High-grade synthetic/mineral oil draining and refill, magnetic drain plug inspection, and filter change.', 450.00, '30 - 45 Mins', 'Active'),
(3, 'Chain Cleaning & Lube', 'General Service', 'Degreasing of chain link rollers, O-ring check, slack tension adjustment to spec, and synthetic lubrication.', 200.00, '30 Mins', 'Active'),
(4, 'Bike Washing & Polish', 'General Service', 'High-pressure snow foam wash, degreasing of rims and swingarm, air drying, and silicone body polish.', 150.00, '45 Mins', 'Active'),

-- MECHANICAL
(5, 'Engine Service & Overhaul', 'Mechanical', 'Disassembly inspection, decarbonization, cylinder kit check, valve lapping, gasket renewal, and timing setup.', 1500.00, '1 - 2 Days', 'Active'),
(6, 'Brake Service', 'Mechanical', 'Disc pad/drum shoe replacement, caliper pin greasing, disc rotor runout inspection, and hydraulic fluid bleed.', 300.00, '1 Hour', 'Active'),
(7, 'Clutch Service', 'Mechanical', 'Friction plate and steel plate renewal, clutch cable lubrication, hub inspection, and bite point adjustment.', 500.00, '1.5 Hours', 'Active'),
(8, 'Suspension Service', 'Mechanical', 'Front telescopic fork oil replacement, oil seal renewal, rear shock preload calibration and bush greasing.', 600.00, '2 Hours', 'Active'),

-- ELECTRICAL
(9, 'Battery Service', 'Electrical', 'Digital load test, terminal cleaning, specific gravity test, electrolyte top-up, and charging relay check.', 200.00, '30 Mins', 'Active'),
(10, 'Electrical Diagnosis', 'Electrical', 'Multimeter analysis of stator coil, RR unit (rectifier regulator), ignition coil, and spark plug resistance.', 300.00, '1 Hour', 'Active'),
(11, 'Headlight & Indicator Repair', 'Electrical', 'High/low beam switch repair, LED conversion bulb fitment, flasher relay replacement, and beam angle leveling.', 200.00, '45 Mins', 'Active'),
(12, 'Horn & Wiring Repair', 'Electrical', 'Dual trumpet horn fitment, relay installation, wiring short-circuit tracing, and insulated sleeve replacement.', 250.00, '45 Mins', 'Active'),

-- TYRE & WHEEL
(13, 'Tyre Replacement', 'Tyre & Wheel', 'Pneumatic machine mounting for tubeless/tube tyres with new rubber valve stem and bead seal.', 200.00, '45 Mins', 'Active'),
(14, 'Puncture Repair', 'Tyre & Wheel', 'Mushroom plug / strip repair for tubeless radial tyres and cold vulcanizing patch repair for tubes.', 100.00, '20 Mins', 'Active'),
(15, 'Wheel Truing & Alignment', 'Tyre & Wheel', 'Spoke wheel tightening and truing on dial gauges, alloy wheel rim bend rectification, and dynamic balance test.', 300.00, '1 Hour', 'Active'),

-- ELECTRIC VEHICLES (EV)
(16, 'EV General Checkup', 'Electric Vehicles', '42-point EV safety scan, firmware update check, throttle potentiometer test, and regenerative brake calibration.', 600.00, '1.5 Hours', 'Active'),
(17, 'Battery Pack Inspection', 'Electric Vehicles', 'Cell voltage balance check, internal resistance test, BMS communication scan, and IP67 seal integrity inspection.', 450.00, '1 Hour', 'Active'),
(18, 'Motor & Controller Diagnosis', 'Electric Vehicles', 'BLDC motor winding resistance check, Hall sensor testing, MCU controller MOSFET test, and belt tensioning.', 700.00, '2 Hours', 'Active'),
(19, 'Charging System Check', 'Electric Vehicles', 'Onboard charger port inspection, pin continuity testing, earthing verification, and fast-charge handshake test.', 350.00, '45 Mins', 'Active');

-- 6. Sample Spare Parts Inventory
INSERT INTO `spare_parts` (`id`, `part_name`, `part_number`, `brand`, `category`, `price`, `stock_quantity`, `minimum_stock`, `status`, `supplier`) VALUES
(1, 'Fully Synthetic 15W-50 Engine Oil (1L)', 'OIL-SYN-15W50', 'Motul', 'Engine & Lubricants', 850.00, 45, 10, 'Active', 'Motul Auto Distributors'),
(2, 'Semi-Synthetic 10W-30 4T Oil (1L)', 'OIL-SEM-10W30', 'Castrol', 'Engine & Lubricants', 420.00, 60, 15, 'Active', 'Castrol South Agencies'),
(3, 'Sintered Front Disc Brake Pad Set', 'BRK-PAD-DS01', 'Bosch', 'Brakes & Braking', 380.00, 30, 8, 'Active', 'Bosch India Spares'),
(4, 'Rear Drum Brake Shoe Kit', 'BRK-SHO-RM02', 'TVS Girling', 'Brakes & Braking', 290.00, 25, 6, 'Active', 'TVS Components'),
(5, 'Iridium Spark Plug (CR8EIX)', 'PLG-NGK-CR8E', 'NGK', 'Ignition & Electrical', 450.00, 40, 10, 'Active', 'NGK Spark Plugs'),
(6, 'Heavy-Duty Brass Roller Drive Chain (120L)', 'CHN-ROL-120L', 'Rolon', 'Drive & Transmission', 1150.00, 18, 5, 'Active', 'Rolon Drive Systems'),
(7, 'Maintenance-Free 12V 5Ah VRLA Battery', 'BAT-12V-5AH', 'Exide', 'Electrical & Battery', 1350.00, 20, 4, 'Active', 'Exide Industries Ltd'),
(8, 'High-Flow Engine Air Filter', 'FLT-AIR-UNIV', 'Purolator', 'Filters', 220.00, 35, 10, 'Active', 'Purolator Filters'),
(9, 'DOT-4 Synthetic Hydraulic Brake Fluid (250ml)', 'FLD-DOT4-250', 'Brembo', 'Fluids & Chemicals', 190.00, 25, 5, 'Active', 'Brembo Performance Parts'),
(10, 'Telescopic Fork Damper Oil (500ml)', 'SUS-OIL-FORK', 'Yamalube', 'Suspension & Forks', 280.00, 30, 8, 'Active', 'Yamaha Genuine Parts');

-- 7. Sample Initial Booking
INSERT INTO `bookings` (`id`, `booking_code`, `customer_id`, `vehicle_id`, `service_id`, `mechanic_id`, `preferred_date`, `preferred_time`, `problem_description`, `status`) VALUES
(1, 'MC-2026-1001', 1, 1, 1, 1, '2026-10-10', '10:00 AM - 01:00 PM', 'Periodic 5000 km general service and slight front disc squeak.', 'Confirmed'),
(2, 'MC-2026-1002', 1, 2, 2, 2, '2026-10-12', '08:00 AM - 10:00 AM', 'Engine oil replacement and throttle cable check.', 'Pending');

-- 8. Sample Service Record for Booking #1
INSERT INTO `service_records` (`id`, `booking_id`, `mechanic_id`, `inspection_notes`, `work_done`, `parts_used`, `service_status`) VALUES
(1, 1, 1, 'Inspected spark plug gap and chain slack. Front disc caliper needs cleaning.', 'General inspection completed. Cleaned air filter.', 'Motul 15W-50 (1L), Air Filter', 'In Progress');

-- 9. Sample Bill for Booking #1
INSERT INTO `bills` (`id`, `invoice_number`, `booking_id`, `customer_id`, `vehicle_id`, `service_record_id`, `service_charge`, `parts_subtotal`, `subtotal`, `discount`, `taxable_amount`, `gst_rate`, `cgst`, `sgst`, `tax`, `total_amount`, `payment_status`, `payment_method`, `amount_paid`, `balance_due`, `invoice_date`) VALUES
(1, 'MC-INV-2026-0001', 1, 1, 1, 1, 500.00, 0.00, 500.00, 0.00, 500.00, 18.00, 45.00, 45.00, 90.00, 590.00, 'Pending', NULL, 0.00, 590.00, '2026-10-10');

-- 10. Sample Feedback
INSERT INTO `feedback` (`id`, `customer_id`, `booking_id`, `service_record_id`, `rating`, `comments`, `moderation_status`, `created_at`) VALUES
(1, 1, 1, 1, 5, 'Excellent preliminary inspection by Arun Kumar. Clean workshop and clear communication.', 'Approved', NOW());
