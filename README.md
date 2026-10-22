# Smart Lost & Found

A modular PHP & MySQL web application for managing, searching, and automatically matching lost and found items in a college campus environment (Kongu Engineering College).

---

## 🌟 Key Features
- **Multi-Role Authentication**: Dedicated portals for Students, Staff, and Administrators.
- **Lost & Found Reporting**: Upload items with descriptions, location stamps, categories, and photographs.
- **Intelligent Auto-Matching**: Automated text and location similarity scoring engine that pairs lost and found reports and notifies both parties.
- **Interactive Dashboards**: Real-time status indicators (Pending, Matched, Returned), live unread notification counter, and personal submission tracking.
- **Comprehensive Admin Panel**: Module-based administration for managing items, user accounts, algorithmic matches, system audit logs, and printable analytical reports.
- **Automated Test Suite**: CLI test runner verifying database connection, schema, password security, and matching algorithms.

---

## 🏗️ Architecture & Project Layout

```plaintext
lost_and_found/
├── admin/               # Administration controllers and views
├── auth/                # Login, registration, and logout controllers
├── student/             # Student dashboard
├── staff/               # Staff dashboard
├── items/               # Item reporting, search, details, matches, and feedback
├── assets/              # CSS, JavaScript, and static images
│   ├── css/             # Scoped stylesheets (style, auth, forms, staff, student)
│   ├── js/              # Client-side scripts (app.js, index-effects.js)
│   ├── images/          # Static media (itpark.jpg)
│   └── uploads/         # Destination for item photo uploads
├── components/          # Reusable view partials (head, navbar, admin_navbar, footer)
├── config/              # Central configuration, database connection, .env reader
├── database/            # Consolidated database schema & migrations
├── docs/                # Architectural & setup guides (SETUP.md, ARCHITECTURE.md)
├── src/utils/           # Core helpers (auth, matching, logger, sanitize, uploader)
├── tests/               # Automated unit & integration CLI tests
└── index.php            # Primary web entry point
```

---

## 🚀 Quick Start Guide

### 1. Database Setup
Ensure MySQL is running (e.g. in XAMPP) and import the consolidated database schema:
```bash
mysql -u root -p < database/smart_lostfound.sql
```

### 2. Configure Environment
Copy `.env.example` to `.env` and set your database credentials:
```ini
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=smart_lostfound
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 3. Run Automated Tests
```powershell
C:\xampp\php\php.exe tests/run_tests.php
```

### 4. Run the Project
- **Via XAMPP Apache**: Open `http://localhost/lost_and_found/` in your browser.
- **Via PHP Built-in Server**:
  ```powershell
  C:\xampp\php\php.exe -S localhost:8000
  ```
  Then open `http://localhost:8000`.

---

## 👥 Default Demo Credentials

| Role | Email | Password | Access Portal |
| :--- | :--- | :--- | :--- |
| **Student** | `alice@student.kec.edu` | `alice123` | `auth/login_student.php` |
| **Staff** | `bob@kec.edu` | `bob123` | `auth/login_staff.php` |
| **Admin** | `admin@kec.edu` | `admin123` | `auth/login_admin.php` |

---

## 📖 Further Documentation
- [Architecture Guide](docs/ARCHITECTURE.md): Detailed architectural design, matching algorithm formula, and component overview.
- [Setup & Operation Guide](docs/SETUP.md): Detailed local setup, configuration options, and troubleshooting.
