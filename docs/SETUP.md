# Smart Lost & Found - Setup, Hardening, Mobile & Cloud Deployment Guide

This document provides complete instructions to set up, harden, test, and run the Smart Lost & Found project locally via **XAMPP**, via **Docker Containerization**, over **Mobile Devices (Wi-Fi / Ngrok)**, and on **Cloud Hosting Platforms (Render, Railway, InfinityFree, Heroku)**.

---

## 1. System Requirements & Architecture

- **Web Server**: Apache (via XAMPP, WAMP, Docker, or native Apache2) with `mod_rewrite` and `mod_headers` enabled.
- **PHP**: PHP 8.0+ / 8.2+ with extensions: `mysqli`, `pdo_mysql`, `fileinfo`, `session`, `mbstring`, `gd`.
- **Database**: MySQL 8.0+ / MariaDB 10.4+ or Cloud MySQL (TiDB Serverless, Aiven, Railway MySQL, ClearDB/JawsDB).
- **Mobile & Security Features**:
  - 100% Parameterized Prepared Statements (Zero SQL Injection vulnerabilities).
  - Modern Bcrypt password hashing (`PASSWORD_BCRYPT`) with transparent legacy MD5 migration on login.
  - Contextual XSS sanitization (`cleanHtml()` / `htmlspecialchars()`).
  - Mobile-responsive Bootstrap 5 UI: 44px touch targets, horizontal momentum table scrolling (`-webkit-overflow-scrolling: touch`), and iOS auto-zoom prevention.
  - Interactive live client-side image upload previews with touch modal zoom and remove controls.
  - Apache `.htaccess` hardening: directory indexing disabled (`Options -Indexes`), security headers, sensitive file access blocked (`.env`, `.sql`, `.git`), and script execution denied in `/assets/uploads`.
  - Cloud database URL parser (`JAWSDB_URL`, `DATABASE_URL`, `CLEARDB_DATABASE_URL`, `MYSQL_URL`) with automatic SSL/TLS detection.

---

## 2. Option A: Standard XAMPP Deployment

### Step 1: Place Project in Document Root
Ensure the project is located inside your Apache document root:
```
C:\xampp\htdocs\lost_and_found\
```

### Step 2: Database Setup
1. Launch Apache and MySQL from the XAMPP Control Panel.
2. Import the hardened schema and initial seed data:
   ```bash
   mysql -u root -p < database/smart_lostfound.sql
   ```
   *Or import `database/smart_lostfound.sql` using phpMyAdmin at `http://localhost/phpmyadmin`.*

### Step 3: Run Password Migration (Optional / Recommended)
To upgrade all existing legacy user passwords to Bcrypt:
```bash
C:\xampp\php\php.exe database/migrations/migrate_passwords.php
```

### Step 4: Environment Variables (Optional)
The application works out-of-the-box with local fallbacks. To override configurations:
```bash
copy .env.example .env
```
Edit `.env` to configure `APP_ENV`, `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`.

### Step 5: Access the Web App
Open your browser and navigate to:
```
http://localhost/lost_and_found/
```

---

## 3. Option B: Docker Containerized Deployment

A production-ready Docker containerization stack is pre-configured with PHP 8.2 Apache and MySQL 8.0.

### Step 1: Launch Containers
From the project root directory (`lost_and_found/`), execute:
```bash
docker compose up -d --build
```

### Step 2: Architecture & Persistence
- **Web App Service (`lostfound_web`)**:
  - Image: PHP 8.2 Apache with `mysqli`, `pdo_mysql`, and `gd`.
  - Port: Accessible at `http://localhost:8080`.
  - Dynamic Port: Ready for cloud hosting (`$PORT` binding).
  - Volume: `./assets/uploads` mapped for persistent file uploads.
- **Database Service (`lostfound_db`)**:
  - Image: MySQL 8.0 on port `3307` (avoids conflict with local XAMPP MySQL on 3306).
  - Auto-Init: Automatically imports `database/smart_lostfound.sql` on first launch.
  - Volume: Named volume `db_data` for persistent storage across restarts.

### Step 3: Access Containerized App
Navigate to: `http://localhost:8080`

To stop: `docker compose down`

---

## 4. Option C: Mobile Testing & Tunneling (Wi-Fi LAN / Ngrok)

To test the application immediately on your mobile phone or share a live demo with reviewers:

### Method 1: Local Wi-Fi / LAN Access (Zero Setup)
1. Ensure both your development PC and mobile phone are connected to the same Wi-Fi network.
2. Find your PC's local IP address (run `ipconfig` in terminal, e.g., `10.188.100.12`).
3. On your phone's browser, navigate to:
   ```
   http://<YOUR-LOCAL-IP>/lost_and_found/
   # Example:
   http://10.188.100.12/lost_and_found/
   ```

### Method 2: One-Click Tunneling Assistant (`scripts/tunnel.bat`)
A helper script is provided to expose your local instance to the public internet via HTTPS:
1. Double-click `scripts/tunnel.bat` (or run `powershell -File scripts/tunnel.ps1`).
2. Select **Option 1 (Ngrok)** or **Option 2 (Localtunnel)**.
3. Open the generated HTTPS forwarding URL (e.g., `https://xxxx.ngrok-free.app/lost_and_found/`) on your mobile device over 4G/5G.

---

## 5. Option D: Cloud Deployment & Database Hosting Setup

The project includes `composer.json`, `Procfile`, and a dynamic-port `Dockerfile` for zero-friction cloud deployment.

### A. Deploying to Render.com (Recommended Free/Low-Cost)
1. **Database Setup**:
   - Create a free MySQL database on [TiDB Cloud Serverless](https://tidbcloud.com) or [Aiven.io](https://aiven.io/mysql).
   - Import `database/smart_lostfound.sql` into your cloud database via its web console or MySQL CLI.
   - Copy the connection string (e.g., `mysql://user:pass@host:4000/smart_lostfound?ssl-mode=REQUIRED`).
2. **Web Service Setup**:
   - Push this repository to GitHub.
   - On Render, click **New > Web Service** and connect your repository.
   - **Environment**: Select `Docker` or `PHP`.
   - **Environment Variables**:
     - `DATABASE_URL`: Your cloud MySQL connection string.
     - `DB_SSL`: `true` (if using TiDB or Aiven).
     - `APP_ENV`: `production`
     - `APP_DEBUG`: `false`
   - Render automatically provisions the application and binds to `$PORT`.

### B. Deploying to Railway.app
1. Push project to GitHub and open [Railway.app](https://railway.app).
2. Click **New Project > Provision MySQL**.
3. Right-click the MySQL database, click **Connect**, and import `database/smart_lostfound.sql`.
4. Add a new service from your GitHub repo. Railway automatically detects `composer.json` and connects the internal `MYSQL_URL` / `DATABASE_URL` variable.

### C. Deploying to Traditional Web Hosts (InfinityFree, cPanel)
1. Upload all files into `public_html/`.
2. Create a MySQL database and user in cPanel.
3. Import `database/smart_lostfound.sql` via phpMyAdmin.
4. Update `config/database.php` or `.env` with your assigned database name, username, and password.

---

## 6. Pre-Configured Test & Demo Accounts

All seed accounts are configured with Bcrypt hashes:

| Role | Email | Password | Dashboard URL | Primary Features |
| :--- | :--- | :--- | :--- | :--- |
| **Student** | `alice@student.kec.edu` | `alice123` | `student/dashboard.php` | Report lost items, view notifications, mark returned |
| **Staff** | `bob@kec.edu` | `bob123` | `staff/dashboard.php` | Report found items, view matches, manage inventory |
| **Admin** | `admin@kec.edu` | `admin123` | `admin/dashboard.php` | System metrics, user roles, match approvals, reports |

---

## 7. Verification & Pre-Demo Check

Follow these steps right before presenting the project:

### 1. Run the Automated Test Suite
```powershell
C:\xampp\php\php.exe tests/run_tests.php
```
**Expected Result**:
```plaintext
========================================================
  Smart Lost & Found - Automated Test Suite Runner
========================================================

Running: Database & Schema Test... 
   [PASS] Database connection verified, 6 tables present, default accounts confirmed.

Running: Intelligent Matching Engine Test... 
   [PASS] Matching engine scoring verified across exact, substring, fuzzy, and negative test cases.

Running: Authentication & Security Test... 
   [PASS] Sanitization, Bcrypt auth/rehash, and prepared statement engine verified.

========================================================
  Test Summary: 3 Passed, 0 Failed in 0.49s
========================================================
```

### 2. Verify PHP Syntax Integrity
```powershell
powershell -Command "& { $files = Get-ChildItem -Recurse -Filter *.php; foreach ($f in $files) { $res = & C:\xampp\php\php.exe -l $f.FullName 2>&1; if ($LASTEXITCODE -ne 0) { Write-Host 'FAIL: ' $f.FullName } }; Write-Host 'All files clean! Count: ' $files.Count }"
```
**Expected Result**: `All files clean! Count: 40`

### 3. Pre-Demo Walkthrough Flow
1. **Public Search**: Go to `http://localhost/lost_and_found/items/search_items.php` and search items by keyword without logging in.
2. **Student Flow**: Log in as `alice@student.kec.edu` (`alice123`). Report a new lost item (e.g., "HP Laptop Charger" in "Library") and test the live image preview.
3. **Staff Flow**: Open private window, log in as `bob@kec.edu` (`bob123`). Report a found item (e.g., "Laptop Charger" in "Library").
4. **Auto-Match Demonstration**: Observe how the Intelligent Matching Engine calculates similarity and triggers match notifications instantly.
5. **Admin Flow**: Log in as `admin@kec.edu` (`admin123`). Showcase the Admin Dashboard, User Management, Activity Logs, and printable Reports & Analytics.
