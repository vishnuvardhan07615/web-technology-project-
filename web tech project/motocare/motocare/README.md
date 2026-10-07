# MotoCare – Two-Wheeler Service & Mechanic Shop Management System

> **"Your Ride. Our Responsibility."**

MotoCare is a modern web application designed for two-wheeler service centers, motorcycle workshops, and electric bike repair hubs. It bridges vehicle owners and workshop personnel to streamline appointment scheduling, service tracking, transparent estimating, and garage operations.

---

## 📌 Project Overview
- **Project Name:** MotoCare – Two-Wheeler Service & Mechanic Shop Management System
- **Project Type:** Individual College Industrial-Use-Case Project
- **Target Vehicles:** Motorcycles, Scooters, and Electric Two-Wheelers (EVs)
- **Current Milestone:** Stage 1 (Static Web Application & Front-End UI Architecture)
- **Future Milestone:** Stage 2 (Dynamic System using PHP & MySQL)

---

## 🎯 Problem Statement & Industrial Need
Independent two-wheeler garages and multi-brand service centers typically face several challenges:
1. **Unorganized Scheduling:** Customers experience long waiting times and uncertainty over slot availability.
2. **Opaque Pricing & Trust Deficit:** Lack of transparent rate charts for routine maintenance, oil changes, and spares.
3. **EV Readiness Gap:** Rapid adoption of electric scooters (Ather, Ola, TVS iQube) requires specialized diagnostic checkups and battery monitoring.
4. **Manual Record Keeping:** Inefficient paper job cards lead to lost customer service history and improper parts inventory tracking.

**MotoCare** solves these problems by providing an intuitive, customer-facing portal and a structured administrative framework for mechanics and shop managers.

---

## 🏗️ Two-Stage Architectural Roadmap

### Stage 1: Front-End & Responsive Web Interface (Current)
- Built completely with **HTML5**, **CSS3 (Custom Properties & Flex/Grid)**, and **Vanilla JavaScript**.
- Zero external libraries or heavy frameworks (no React, Angular, Vue, or Node.js) ensuring lightning-fast load times and 100% offline capability.
- Directly runnable by opening `index.html` in any modern web browser.
- Interactive service booking with instant client-side validation, service ID generation (e.g., `MC-2026-1045`), and local booking confirmation.
- Filterable and categorical service catalog covering General, Mechanical, Electrical, Tyre/Wheel, and EV services.

### Stage 2: Back-End, Database & Multi-User Portals (Upcoming)
- **Technology Stack:** PHP 8+ and MySQL / MariaDB.
- **Portals:**
  - **Customer Portal:** Authentication, booking history, live service tracker, digital invoice download.
  - **Mechanic Portal:** Digital job cards, inspection reports, parts requisition, service stage updates.
  - **Admin / Workshop Manager Portal:** Scheduling calendar, mechanic workload allocation, spare parts inventory control, billing, and revenue analytics.

---

## 📁 Project Directory Structure

```text
motocare/
│
├── index.html              # Homepage (Hero, Stats, Popular Services, Features, Workflow, Reviews)
├── about.html              # About Us (Story, Mission, Facilities, Mechanic Staff & Team)
├── services.html           # Full Service Catalog (Categorized with pricing & instant booking links)
├── booking.html            # Online Service Appointment Form with JS validation & Service ID generator
├── contact.html            # Contact info, opening hours, emergency assistance, and inquiry form
│
├── css/
│   └── style.css           # Complete responsive stylesheet (CSS variables, dark-automotive theme)
│
├── js/
│   └── script.js           # Client-side validation, mobile nav, ID generation, scroll animations
│
├── images/                 # Image assets and iconography
│   ├── logo/               # MotoCare branding and vector logos
│   ├── bikes/              # Vehicle and workshop media
│   ├── services/           # Service category illustrations
│   └── mechanics/          # Technician profile images
│
├── customer/               # Stage 2 Customer Module (README specification)
│   └── README.md
│
├── admin/                  # Stage 2 Admin Module (README specification)
│   └── README.md
│
├── mechanic/               # Stage 2 Mechanic Module (README specification)
│   └── README.md
│
├── config/                 # Stage 2 Database & App Configuration (README specification)
│   └── README.md
│
├── database/               # Stage 2 SQL Schema & Seeds (README specification)
│   └── README.md
│
└── README.md               # Project Master Documentation
```

---

## 🚀 How to Run Stage 1
Stage 1 requires **no installation, build steps, or local server runtime**.

### Method 1: Direct File Opening
1. Navigate to the `motocare/` project folder on your computer.
2. Double-click `index.html` (or right-click -> *Open With* -> *Google Chrome* / *Brave* / *Safari* / *Firefox* / *Edge*).

### Method 2: Local HTTP Server (Optional)
If you prefer running via a local server (e.g. for VS Code Live Server or Python):
```bash
# Python 3 built-in server
python3 -m http.server 8000
# Then visit: http://localhost:8000 in your browser
```

---

## 🚀 STAGE 2 – DYNAMIC WEBSITE

### 🛠️ Technology Stack
- **Server-Side Runtime:** PHP 8+ (Plain, Vanilla PHP with PDO)
- **Database Engine:** MySQL 8.0+ / MariaDB (InnoDB, `utf8mb4_unicode_ci`)
- **Frontend Presentation:** HTML5, CSS3 (Automotive Dark Theme), Vanilla JavaScript
- **Security & Cryptography:** Bcrypt hashing via `password_hash()` and `password_verify()`, PDO Prepared Statements with parameterized queries, PHP Session authentication, input sanitization via `htmlspecialchars()`.

### 🏛️ System Architecture
```text
┌─────────────────────────────────────────────────────────────┐
│                       Client Browser                        │
│          HTML5 / CSS3 / Vanilla JS Interactive UI           │
└──────────────┬───────────────────────────────▲──────────────┘
               │ HTTP Requests                 │ HTTP Responses
               ▼                               │
┌─────────────────────────────────────────────────────────────┐
│                 PHP Server-Side Application                 │
│                                                             │
│  ├── Configuration:   config/database.php, auth.php         │
│  ├── Access Portals:  customer/ | admin/ | mechanic/        │
│  ├── Templating:      includes/header, footer, portal-nav   │
│  └── Logic Layer:     Auth checks, Form sanitization, PDO   │
└──────────────┬───────────────────────────────▲──────────────┘
               │ PDO Prepared Statements       │ Associative Arrays
               ▼                               │
┌─────────────────────────────────────────────────────────────┐
│                     MySQL Database                          │
│                      (motocare_db)                          │
│                                                             │
│  10 Relational Tables:                                      │
│  admin_users, customers, vehicles, services, mechanics,     │
│  bookings, service_records, spare_parts, bills, feedback    │
└─────────────────────────────────────────────────────────────┘
```

### 📁 Purpose of Major Stage 2 Directories

| Directory | Purpose | Key Files |
| :--- | :--- | :--- |
| **`root/`** | Public customer-facing pages | `index.php`, `about.php`, `services.php`, `booking.php`, `contact.php` |
| **`customer/`** | Customer self-service portal | `login.php`, `register.php`, `dashboard.php`, `vehicles.php`, `add-vehicle.php`, `bookings.php`, `book-service.php`, `service-history.php`, `bill.php` |
| **`admin/`** | Workshop manager control center | `login.php`, `dashboard.php`, `bookings.php`, `booking-details.php`, `customers.php`, `vehicles.php`, `mechanics.php`, `services.php`, `spare-parts.php`, `service-records.php`, `bills.php`, `feedback.php` |
| **`mechanic/`** | Workshop technician workstation | `login.php`, `dashboard.php`, `assigned-services.php`, `service-details.php` |
| **`config/`** | Central configuration & security | `database.php` (PDO connection), `auth.php` (session protection & role checkers), `constants.php` (global constants & utilities) |
| **`includes/`** | Reusable layout and header templates | `header.php`, `footer.php`, `customer-header.php`, `admin-header.php`, `mechanic-header.php` |
| **`database/`** | Complete MySQL relational schema | `motocare.sql` (10 tables, foreign key constraints, indexes, 19 services, sample inventory & accounts) |
| **`css/`** | Shared visual design & theme | `style.css` (custom properties, responsive layouts, alert states) |
| **`js/`** | Shared client-side behavior | `script.js` (mobile drawer, dynamic calculations, booking validation) |

---

### ⚙️ Local Setup Instructions (XAMPP / WAMP / MAMP)

Follow these steps to deploy and run MotoCare locally:

#### 1. Install & Launch Local Server
- Download and install **XAMPP** (or WAMP / MAMP).
- Open the XAMPP Control Panel.
- Start **Apache** and **MySQL** services.

#### 2. Import the Database
- Open your browser and navigate to phpMyAdmin: `http://localhost/phpmyadmin/`
- Click **Import** in the top navigation bar.
- Choose file: Select `motocare/database/motocare.sql`.
- Click **Import** (or **Go** at the bottom).
- The `motocare_db` database and all 10 tables with seed records will be created automatically.

#### 3. Place the Project in the Web Root
- Copy the entire `motocare` project folder into your web server's document root:
  - **XAMPP (Windows):** `C:\xampp\htdocs\motocare`
  - **XAMPP (macOS):** `/Applications/XAMPP/xamppfiles/htdocs/motocare`
  - **WAMP (Windows):** `C:\wamp64\www\motocare`
  - **MAMP (macOS):** `/Applications/MAMP/htdocs/motocare`

#### 4. Configure Database Connection
- Open `config/database.php` in any text editor.
- Verify your local database credentials:
  ```php
  define('DB_HOST', 'localhost');
  define('DB_NAME', 'motocare_db');
  define('DB_USER', 'root');       // Default for XAMPP / WAMP
  define('DB_PASSWORD', '');       // Default is blank on XAMPP (root on MAMP)
  ```

#### 5. Open and Test in Your Browser
- Visit the public homepage: `http://localhost/motocare/index.php`
- Access customer portal: `http://localhost/motocare/customer/login.php`
- Access technician bay: `http://localhost/motocare/mechanic/login.php`
- Access admin panel: `http://localhost/motocare/admin/login.php`

---

### 🔑 Stage 2 Default Sample Login Credentials

All sample accounts in `database/motocare.sql` are pre-configured with secure bcrypt password hashes. For college viva testing, you can use:

| Role | Username / Email | Password | Portal URL |
| :--- | :--- | :--- | :--- |
| **Workshop Admin** | `admin` or `admin@motocare.com` | `admin123` | `admin/login.php` |
| **Senior Mechanic** | `arun@motocare.com` | `mechanic123` | `mechanic/login.php` |
| **EV Technician** | `sanjay@motocare.com` | `mechanic123` | `mechanic/login.php` |
| **Customer** | `ramesh@example.com` | `customer123` | `customer/login.php` |

> **Offline Viva Resilience Note:** If MySQL is temporarily offline during an oral examination or demonstration, all login portals (`customer/login.php`, `admin/login.php`, `mechanic/login.php`) detect the connection state and allow seamless authentication into the demo interfaces using the above credentials without throwing fatal server errors.

---

## 💡 Key Highlights for Viva / College Demonstration
1. **Separation of Concerns:** Database credentials reside exclusively in `config/database.php`. Session logic is isolated in `config/auth.php`. Reusable views are centralized in `includes/`.
2. **Relational Integrity:** Fully normalized 10-table schema with foreign key constraints (`ON DELETE CASCADE`, `ON UPDATE CASCADE`) and targeted indexes on search columns (`customer_id`, `mechanic_id`, `status`).
3. **Multi-Role User Experience:** Tailored workflows for 3 distinct user types:
   - **Customer:** Add two-wheelers, book services, track live repair stages, download invoice slips.
   - **Mechanic:** View assigned lift bays, log diagnostic inspection notes, record spare parts consumed, mark jobs ready for quality check.
   - **Admin:** Master oversight of appointments, mechanic roster allocation, parts inventory thresholds, pricing, and invoice reconciliation.
4. **Security by Design:** Parameterized PDO queries eliminate SQL injection vectors. Bcrypt password hashing ensures credentials are never stored in plaintext. XSS protection is enforced with context-aware `htmlspecialchars()`.

---

## 👨‍💻 Author & Academic Attribution
- **Developer:** Student Individual Project
- **Review:** Stage 2 Dynamic Web Application Presentation
- **Organization:** MotoCare Service Systems

