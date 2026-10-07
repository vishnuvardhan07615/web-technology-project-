# Configuration Module (Stage 2 Placeholder)

## Overview
The `config/` directory is reserved for application configuration files, database connections, environment constants, and system helper functions in Stage 2.

## Planned Stage 2 Files
- `database.php` - MySQL database connection instance using PDO or `mysqli` with robust error handling and UTF-8 charset.
- `constants.php` - Global constants such as site URL, currency symbol (₹), workshop details, SMS gateway keys, and upload limits.
- `session.php` - Secure session management, CSRF token validation, and role-based access control (RBAC) helpers.

## Security Best Practices for Stage 2
- Store sensitive database credentials outside the web root or via `.env` files.
- Enable prepared statements for all dynamic SQL queries to eliminate SQL injection vulnerabilities.
- Configure secure cookies (`HttpOnly`, `SameSite=Strict`, `Secure`).
