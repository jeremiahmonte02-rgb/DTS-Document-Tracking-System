# Document Tracking System (DTS)

A full-stack web application for managing, routing, and tracking physical documents across organizational departments. Built on **Laravel 13** with a **MySQL** backend, DTS replaces paper-based logbooks with a digital workflow that assigns each document a unique tracking number and QR code, enabling real-time status visibility, department-to-department routing, and a complete audit trail.

## Core Features

- **Document Upload & Registration** — Upload files (PDF, DOCX, XLS, JPG, PNG), assign document types, set destination routing via an ordered department pipeline.
- **QR Code Generation** — Each registered document receives a scannable QR code for quick lookup and status updates.
- **QR Scanning / Manual Lookup** — Scan QR codes via device camera using `html5-qrcode` or manually enter a document ID to retrieve full details.
- **Department Routing & Receipt Confirmation** — Multi-step routing: documents move through a sender-defined sequence of departments; each department scans and confirms receipt, advancing the document to the next step.
- **Inbox / Outbox** — Department-filtered views of incoming and outgoing documents with search, type/status/date filters, and export capabilities.
- **Dashboard Analytics** — KPI metric cards (total, pending, in-transit, received today), status distribution doughnut chart (Chart.js), documents-by-department bar chart, and a recent activity feed.
- **User Management (Admin)** — Role-based access control (Administrator, Department User, Auditor) with user CRUD, department assignment, and active/inactive status toggle.
- **Full Audit Trail** — Every event (creation, scan, receipt, rejection, completion) is timestamped and attributed to a user and department, providing an immutable history.
- **Role-Based Authentication** — Secure login with session management, inactive account blocking, and admin-only route protection via policies.

## Tech Stack

| Layer            | Technology                                                              |
|------------------|-------------------------------------------------------------------------|
| **Framework**    | [Laravel 13](https://laravel.com/) (PHP 8.3)                            |
| **Database**     | MySQL 8.0 (via Docker)                                                  |
| **Frontend**     | Bootstrap 5.3, Bootstrap Icons, Chart.js 4.4, QRCode.js, html5-qrcode  |
| **Assets**       | Vite 8 + Laravel Vite Plugin + TailwindCSS 4 (for `welcome.blade.php`) |
| **Auth**         | Custom `AuthController` with Laravel's `Auth` facade + bcrypt passwords |
| **Authorization**| Laravel Policies (`DocumentPolicy`, `UserPolicy`) with route-level `can()` gates |
| **Infrastructure** | Docker Compose (PHP 8.3-Apache, MySQL 8.0, phpMyAdmin)               |

## Project Structure

```
document-tracker/
├── docker-compose.yml          # PHP, MySQL, phpMyAdmin services
├── Dockerfile                  # Multi-stage: node:20-alpine (assets) → php:8.3-apache
├── .dockerignore
└── src/                        # Laravel application root
    ├── .env.example
    ├── artisan
    ├── composer.json
    ├── package.json
    ├── vite.config.js
    ├── README.md               # ← this file
    │
    ├── app/
    │   ├── Http/
    │   │   └── Controllers/
    │   │       ├── AuthController.php              # Login/logout
    │   │       ├── DashboardController.php         # Dashboard analytics
    │   │       ├── DocumentController.php          # Upload, scan, lookup, receive, complete, reject, cancel
    │   │       ├── UserController.php              # Admin user CRUD + toggle-status
    │   │       └── Controller.php
    │   ├── Models/
    │   │   ├── User.php
    │   │   ├── Role.php
    │   │   ├── Permission.php
    │   │   ├── Department.php
    │   │   ├── Document.php
    │   │   ├── DocumentType.php
    │   │   ├── DocumentRoute.php
    │   │   ├── DocumentEvent.php
    │   │   ├── DocumentFile.php
    │   │   └── DocumentNumberSequence.php
    │   ├── Policies/
    │   │   ├── DocumentPolicy.php
    │   │   └── UserPolicy.php
    │   └── Providers/
    │       └── AppServiceProvider.php
    │
    ├── bootstrap/
    │   ├── app.php
    │   └── providers.php
    │
    ├── config/
    │   ├── app.php
    │   ├── auth.php
    │   ├── database.php
    │   ├── filesystems.php
    │   ├── session.php
    │   └── ...
    │
    ├── database/
    │   ├── migrations/
    │   │   ├── 0001_01_01_000001_create_cache_table.php
    │   │   ├── 0001_01_01_000002_create_jobs_table.php
    │   │   ├── 2026_05_23_151257_create_dts_core_tables.php
    │   │   ├── 2026_06_22_125738_add_completed_status_to_documents_table.php
    │   │   ├── 2026_06_25_000001_create_document_number_sequences_table.php
    │   │   ├── 2026_06_25_000003_add_dts_performance_indexes.php
    │   │   └── 2026_06_25_000004_add_slug_to_permissions_table.php
    │   └── seeders/
    │       ├── DatabaseSeeder.php
    │       ├── DepartmentAndUserSeeder.php       # 12 depts, 10 users
    │       ├── RolePermissionSeeder.php           # 3 roles, 9 permissions
    │       └── DocumentTransactionSeeder.php      # 4 doc types, 20 sample documents
    │
    ├── public/
    │   ├── index.php
    │   ├── css/custom.css
    │   ├── js/
    │   │   ├── main.js
    │   │   ├── core/api.js
    │   │   └── modules/
    │   │       ├── dashboard.js
    │   │       ├── upload.js
    │   │       ├── scan.js
    │   │       └── login.js
    │   └── ...
    │
    ├── resources/
    │   ├── views/
    │   │   ├── login.blade.php
    │   │   ├── dashboard.blade.php
    │   │   ├── upload.blade.php
    │   │   ├── scan.blade.php
    │   │   ├── inbox.blade.php
    │   │   ├── outbox.blade.php
    │   │   ├── document-details.blade.php
    │   │   ├── users.blade.php
    │   │   ├── welcome.blade.php
    │   │   └── partials/
    │   │       ├── sidebar-nav.blade.php
    │   │       ├── auth-context.blade.php
    │   │       └── access-denied-modal.blade.php
    │   ├── css/app.css
    │   └── js/app.js
    │
    ├── routes/
    │   ├── web.php             # All application routes
    │   └── console.php
    │
    ├── storage/
    ├── tests/
    └── vendor/
```

## Database Schema (20 Custom Tables)

The core migration (`2026_05_23_151257_create_dts_core_tables.php`) creates:

`departments`, `roles`, `permissions`, `role_permissions`, `users`, `document_types`, `documents`, `document_files`, `document_routes`, `document_events`, `document_qr_codes`, `document_receipts`, `document_scans`, `document_issues`, `document_update_requests`, `notifications`, `document_shares`, `document_views`, `export_logs`, `sessions`

### Document Status Lifecycle

```
pending_transfer → in_transit → received → completed
                                         → rejected
                                         → cancelled
```

## Installation & Getting Started

### Prerequisites

- PHP 8.3+
- Composer
- Node.js 20+ & npm
- MySQL 8.0
- Docker & Docker Compose (optional, for containerized setup)

### Option A — Local Development

```bash
# 1. Clone the repository
git clone <repo-url> document-tracker
cd document-tracker/src

# 2. Install PHP dependencies
composer install

# 3. Configure environment
cp .env.example .env
php artisan key:generate

# 4. Edit .env for MySQL
#   DB_CONNECTION=mysql
#   DB_HOST=127.0.0.1
#   DB_PORT=3306
#   DB_DATABASE=dts
#   DB_USERNAME=root
#   DB_PASSWORD=secret

# 5. Create the database
mysql -u root -p -e "CREATE DATABASE dts CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 6. Run migrations and seeders
php artisan migrate
php artisan db:seed

# 7. Install front-end dependencies & build
npm install
npm run build

# 8. Start the dev server
php artisan serve
```

### Option B — Docker

```bash
# 1. Clone and enter the project
git clone <repo-url> document-tracker
cd document-tracker

# 2. Start all services (app, mysql, phpmyadmin)
docker compose up -d

# 3. Enter the app container
docker compose exec app bash

# 4. Inside the container, run:
cd src
composer install
cp .env.example .env
php artisan key:generate

# 5. Configure .env for Docker MySQL:
#   DB_CONNECTION=mysql
#   DB_HOST=db
#   DB_PORT=3306
#   DB_DATABASE=testdb
#   DB_USERNAME=user
#   DB_PASSWORD=pass

# 6. Run migrations and seeders
php artisan migrate
php artisan db:seed

# 7. Build front-end assets
npm install
npm run build
```

The app is now available at **http://localhost:8082** and phpMyAdmin at **http://localhost:8081**.

### Seed Users

Run `php artisan db:seed` to create 12 departments, 3 roles, and 10 users:

| Name            | Email                     | Password      | Department        | Role         |
|-----------------|---------------------------|---------------|-------------------|--------------|
| Sarah Johnson   | sarah.johnson@uc.edu.ph   | P@ssword2026  | Executive Office  | Administrator |
| John Smith      | john.smith@uc.edu.ph      | P@ssword2026  | Finance           | Dept. User   |
| Emily Davis     | emily.davis@uc.edu.ph     | P@ssword2026  | HR                | Dept. User   |
| Robert Wilson   | robert.wilson@uc.edu.ph   | P@ssword2026  | IT                | Dept. User   |
| David Martinez  | david.martinez@uc.edu.ph  | P@ssword2026  | Legal             | Auditor      |
| Jennifer Lee    | jennifer.lee@uc.edu.ph    | P@ssword2026  | Marketing         | Dept. User   |
| Lisa Anderson   | lisa.anderson@uc.edu.ph   | P@ssword2026  | Operations        | Dept. User   |
| Amanda White    | amanda.white@uc.edu.ph    | P@ssword2026  | Customer Service  | Dept. User   |
| Michael Brown   | michael.brown@uc.edu.ph   | P@ssword2026  | Finance           | Inactive     |
| Jessica Taylor  | jessica.taylor@uc.edu.ph  | P@ssword2026  | HR                | Auditor      |

## Route / API Reference

All routes are defined in `routes/web.php`.

### Public (Guest)

| Method | URI          | Name             | Controller Action          |
|--------|--------------|------------------|----------------------------|
| GET    | `/`          | `login`          | `AuthController@showLogin` |
| POST   | `/login`     | `login.submit`   | `AuthController@login`     |

### Authenticated

| Method | URI                            | Name                    | Controller Action                     |
|--------|--------------------------------|-------------------------|---------------------------------------|
| POST   | `/logout`                      | `logout`                | `AuthController@logout`               |
| GET    | `/dashboard`                   | `dashboard`             | `DashboardController@index`           |
| GET    | `/upload`                      | `documents.create`      | `DocumentController@create`           |
| POST   | `/upload`                      | `documents.store`       | `DocumentController@store`            |
| GET    | `/scan`                        | `scan`                  | `DocumentController@showScanPage`     |
| GET    | `/scan/lookup`                 | `scan.lookup`           | `DocumentController@lookupDocument`   |
| POST   | `/scan/receive`                | `scan.receive`          | `DocumentController@receiveDocument`  |
| GET    | `/inbox`                       | `inbox`                 | `DocumentController@inbox`            |
| GET    | `/api/inbox/data`              | `api.inbox.data`        | `DocumentController@getInboxData`     |
| GET    | `/outbox`                      | `outbox`                | `DocumentController@outbox`           |
| GET    | `/api/outbox/data`             | `api.outbox.data`       | `DocumentController@getOutboxData`    |
| GET    | `/document-details/{number}`   | `document-details.show` | `DocumentController@showDocumentDetails` |
| POST   | `/documents/{number}/receive`  | `documents.receive`     | `DocumentController@receiveDocument`  |
| POST   | `/documents/{number}/complete` | `documents.complete`    | `DocumentController@completeDocument` |
| POST   | `/documents/{number}/reject`   | `documents.reject`      | `DocumentController@rejectDocument`   |
| POST   | `/documents/{number}/cancel`   | `documents.cancel`      | `DocumentController@cancelDocument`   |
| POST   | `/api/issues`                  | `api.issues.report`     | `DocumentController@reportIssue`      |

### Admin Only (`can:viewAny User` gate)

| Method | URI                            | Name                    | Controller Action                     |
|--------|--------------------------------|-------------------------|---------------------------------------|
| GET    | `/manage-users`                | `users`                 | `UserController@index`                |
| GET    | `/api/users/data`              | `api.users.data`        | `UserController@getUsersData`         |
| GET    | `/api/users/stats`             | `api.users.stats`       | `UserController@stats`                |
| POST   | `/api/users`                   | `api.users.store`       | `UserController@store`                |
| PUT    | `/api/users/{user}`            | `api.users.update`      | `UserController@update`               |
| PATCH  | `/api/users/{user}/toggle-status` | `api.users.toggle-status` | `UserController@toggleStatus`      |

### Key AJAX Endpoints (used by front-end JS)

| Endpoint              | Payload                                                         | Response                     |
|-----------------------|-----------------------------------------------------------------|------------------------------|
| `POST /upload`        | `title, documentType, description, fileUpload, routes (JSON string)` | `{ success, message, document_number }` |
| `GET /scan/lookup`    | `document_number`                                               | `{ success, document, routes, events }` |
| `POST /scan/receive`  | `document_number, note`                                         | `{ success, message }`      |
| `POST /documents/{number}/receive` | `note`                                                  | `{ success, message }`      |
| `POST /documents/{number}/complete` | —                                                      | `{ success, message }`      |
| `POST /documents/{number}/reject` | `reason`                                                   | `{ success, message }`      |
| `POST /documents/{number}/cancel` | —                                                       | `{ success, message }`      |

## Front-End Assets

| Asset                             | Purpose                          |
|-----------------------------------|----------------------------------|
| `public/css/custom.css`           | Application-wide custom styles   |
| `public/js/main.js`               | Shared utilities, sidebar toggle, toast/spinner helpers |
| `public/js/core/api.js`           | API client (lookup, receive, confirm) |
| `public/js/modules/dashboard.js`  | Charts (Chart.js) and activity feed rendering |
| `public/js/modules/upload.js`     | Upload form handling, route builder, QR modal |
| `public/js/modules/scan.js`       | QR scanner (html5-qrcode), manual lookup, receipt flow |
| `public/js/modules/login.js`      | Login form password toggle       |

## Known Issues

- Route builder does not allow adding the same department multiple times in the routing sequence.
- Login email field does not retain value after a failed login attempt.
- Print QR code button in successful upload modal does not work.
- View document details button in successful upload modal does not work.
- Print QR code button in document details screen prints the entire page instead of only the QR code.
- Current department field in document-details screen does not reflect the document's actual location during transit.
- After a document reaches `received` status (route complete), viewing full details does not show the completion date.

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
