# Smart Lost & Found - Architecture Documentation

## Overview
Smart Lost & Found is a web-based campus application built using PHP, MySQL, Bootstrap 5, and vanilla JavaScript. It automates reporting, tracking, intelligent matching, and recovery of lost items for students, staff, and campus administration.

---

## Directory Structure

```plaintext
lost_and_found/
├── .env.example             # Template for local environment variables
├── .gitignore               # Git rules ignoring local logs & runtime uploads
├── README.md                # Project landing documentation
├── config.php               # Root backward-compatibility forwarder
├── index.html               # Web root landing redirect
├── index.php                # Main entry point with role-based routing
│
├── admin/                   # Administration Portal
│   ├── index.php            # Admin forwarder to dashboard.php
│   ├── dashboard.php        # Admin overview, statistics, and quick actions
│   ├── items.php            # Item management (filtering, status updates, deletion)
│   ├── users.php            # User account management and role modification
│   ├── matches.php          # Algorithmic match review and status management
│   ├── activity.php         # Audit log viewer for all system actions
│   └── reports.php          # Analytics reports with printable formatting
│
├── components/              # Reusable UI Layout Components
│   ├── head.php             # Unified HTML <head>, responsive meta, CSS links
│   ├── navbar.php           # Role-aware navigation bar with live unread badge
│   ├── admin_navbar.php     # Administration panel top navigation
│   └── footer.php           # Unified scripts and closing HTML tags
│
├── config/                  # Configuration & Environment
│   ├── config.php           # Central application bootstrap and autoloader
│   ├── database.php         # MySQLi database connection handler
│   └── env.php              # Lightweight .env environment variable parser
│
├── src/utils/               # Modular Business Logic & Helpers
│   ├── auth.php             # Authentication, session check, role authorization
│   ├── helpers.php          # URL generation, asset resolution, redirection, flash msgs
│   ├── logger.php           # System activity logging into activity_log table
│   ├── matching.php         # Intelligent auto-matching engine & similarity scoring
│   ├── sanitize.php         # SQL escaping and HTML XSS sanitization
│   └── uploader.php         # Safe image validation and file uploading
│
├── assets/                  # Frontend Static Assets
│   ├── css/                 # Stylesheets (style.css, auth.css, forms.css, student.css, staff.css)
│   ├── js/                  # Scripts (app.js, index-effects.js)
│   ├── images/              # Static media (itpark.jpg, campus branding)
│   └── uploads/             # Destination for user-uploaded item pictures
│
├── database/                # Database Assets & Migrations
│   ├── smart_lostfound.sql  # Consolidated full schema, indexes, and seed accounts
│   └── migrations/
│       └── update_admin.sql # Historical migration record
│
├── docs/                    # Architectural & Setup Documentation
│   ├── ARCHITECTURE.md      # Architecture overview (this file)
│   └── SETUP.md             # Installation and operational guide
│
├── tests/                   # Automated CLI Test Suite
│   ├── run_tests.php        # Master CLI test runner
│   ├── test_db.php          # Database connectivity and schema validator
│   ├── test_matching.php    # Unit tests for matching engine scoring
│   └── test_auth.php        # Security, hashing, and sanitization tests
│
├── auth/                    # Login, registration, and logout controllers
├── student/                 # Student dashboard
├── staff/                   # Staff dashboard
└── items/                   # Reporting, search, details, matches, and feedback
```

---

## Core Architecture Components

### 1. Intelligent Matching Engine (`src/utils/matching.php`)
When a user reports a lost or found item, `autoMatchItem($conn, $itemId, $type)` evaluates existing opposite records (`lost` vs `found`) using a weighted scoring formula:
- **Item Name Similarity (`similar_text`) & Substrings**:
  - Exact match: 100 points
  - Similarity $\ge 70\%$: proportional points ($70 - 100$)
  - Substring inclusion: 80 points
- **Category Match**:
  - Exact match: 30 points
  - Similarity $\ge 50\%$: 15 points
- **Location Similarity**:
  - Exact location: 20 points
  - Similarity $\ge 40\%$: proportional points
- **Keyword Boost**: +15 points for partial substring hits across place and name
- **Threshold**: Any pair scoring $\ge 70$ points with name similarity is automatically recorded in the `matches` table, triggers a notification to both users, and updates item statuses from `Pending` to `Matched`.

### 2. Multi-Role Authentication & Access Control (`src/utils/auth.php`)
- Roles supported: `student`, `staff`, `admin`.
- Role verification using `requireRole($roles)` ensures unauthorized access is immediately redirected to the appropriate login portal.
- Supports both modern password hashing (`password_hash` / `password_verify` with BCRYPT) and backward-compatible MD5 verification.

### 3. Dynamic Base Path & Asset Resolution (`src/utils/helpers.php`)
- `BASE_URL` is automatically detected from the active request environment, allowing the project to run cleanly whether hosted at `http://localhost/lost_and_found/`, at the web server root `http://localhost:8000/`, or behind a virtual host.
- Helper functions `asset($path)` and `url($path)` ensure all `<link>`, `<img>`, and `<a>` tags resolve to the proper path without hardcoded directory names.

### 4. Activity Audit Logging (`src/utils/logger.php`)
Every key action (user registration, logins, lost/found submissions, status changes, match generations, deletions) is captured in `activity_log` with the actor's user ID, role, action, target item ID, details, IP address, and timestamp.
