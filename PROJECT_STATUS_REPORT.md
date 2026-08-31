# PROJECT STATUS REPORT — Document Tracking System (DTS)
**Repository:** `projects/document-tracker/`  
**Framework:** Laravel 13.x (PHP 8.x)  
**Audit Date:** June 24, 2026  
**Architect:** Lead Full-Stack Software Architect

---

## 1. ARCHITECTURAL & ENVIRONMENT CORES

### 1.1 Framework & Directory Structure

```
document-tracker/src/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AuthController.php          # Login/logout handler
│   │   │   ├── Controller.php               # Base controller
│   │   │   ├── DashboardController.php      # Dashboard KPIs + charts
│   │   │   ├── DocumentController.php        # Core document CRUD + state machine
│   │   │   └── UserController.php            # Admin user CRUD + toggle
│   │   └── Middleware/
│   │       └── EnsureUserIsAdmin.php         # Role-based gate (role_id === 1)
│   ├── Models/
│   │   ├── User.php                          # Authenticatable, BelongsTo Department + Role
│   │   ├── Department.php                    # HasMany Users
│   │   └── Role.php                          # HasMany Users
│   └── Providers/
│       └── AppServiceProvider.php            # Empty boot/register (no custom bindings)
├── config/
│   ├── app.php        # timezone => 'Asia/Manila' (PHT)
│   ├── auth.php       # 'web' guard, 'session' driver, 'users' provider
│   ├── session.php    # 'file' driver default, 120min lifetime, lax same-site
│   └── ... (database, cache, queue, mail, logging, filesystems, services)
├── database/migrations/ (4 migration files, 23 total tables)
├── routes/
│   ├── web.php       # 14 named routes (guest, auth, admin middleware groups)
│   └── console.php   # Default `inspire` command only
├── resources/views/ (11 blade files + 2 partials)
├── public/
│   ├── css/custom.css    # 547-line custom stylesheet
│   ├── js/main.js        # 962-line core JS (sample data, module router)
│   ├── js/core/api.js    # 82-line API client (CSRF-aware fetch wrapper)
│   └── js/modules/       # 7 module files (dashboard, inbox, outbox, scan, upload, users, login)
├── .env                  # APP_ENV=local, DB=mysql://testdb, APP_DEBUG=true
└── composer.json / package.json / vite.config.js
```

### 1.2 Layout Engine

This application does **not** use a Master Blade layout file (no `layouts/app.blade.php`). Each view is a standalone `<html>` document with duplicated sidebar, top-navbar, and asset imports. This is a significant architectural concern — see Section 6.

### 1.3 Timezone & Localization

| Parameter | Value | Source |
|-----------|-------|--------|
| `timezone` | `Asia/Manila` (PHT, UTC+8) | `config/app.php:68` |
| `locale` | `en` | `config/app.php:81` |
| `fallback_locale` | `en` | `config/app.php:83` |
| `faker_locale` | `en_US` | `config/app.php:85` |

**Status:** ✅ Timezone is correctly set to PHT. No i18n/localization present (English only).

---

## 2. DATABASE SCHEMAS & ENTITY RELATIONSHIPS

### 2.1 Entity Relationship Map

```
departments ──┬──< users
              ├──< documents (sender_department_id)
              ├──< documents (current_department_id)
              ├──< document_routes
              ├──< document_events
              ├──< notifications
              ├──< document_views
              └──< document_scans

roles ──< users
permissions ──< role_permissions >── roles (pivot)

users ──< documents (uploaded_by)
      ├──< document_routes (received_by)
      ├──< document_events
      ├──< document_receipts
      ├──< document_scans
      ├──< document_issues
      ├──< document_update_requests
      ├──< notifications
      ├──< document_shares
      ├──< document_views
      └──< export_logs

documents ──< document_files
         ├──< document_routes
         ├──< document_events
         ├──< document_qr_codes
         ├──< document_receipts
         ├──< document_issues
         ├──< document_update_requests
         ├──< notifications
         ├──< document_shares
         └──< document_views

document_types ──< documents
```

### 2.2 Full Schema Inventory (23 Tables)

| # | Table | Engine | PK | Foreign Keys | Unique | Indexed |
|---|-------|--------|----|--------------|--------|---------|
| 1 | `cache` | InnoDB | `key` (string) | — | — | `expiration` |
| 2 | `cache_locks` | InnoDB | `key` (string) | — | — | `expiration` |
| 3 | `jobs` | InnoDB | `id` (bigIncrements) | — | — | `queue` |
| 4 | `job_batches` | InnoDB | `id` (string) | — | — | — |
| 5 | `failed_jobs` | InnoDB | `id` (bigIncrements) | — | `uuid` | composite(`connection`,`queue`,`failed_at`) |
| 6 | `departments` | InnoDB | `id` | — | `code` | — |
| 7 | `roles` | InnoDB | `id` | — | `name` | — |
| 8 | `permissions` | InnoDB | `id` | — | `name` | — |
| 9 | `role_permissions` | InnoDB | composite(`role_id`,`permission_id`) | `role_id`->roles(cascade), `permission_id`->permissions(cascade) | composite PK | — |
| 10 | `users` | InnoDB | `id` | `department_id`->departments(set null), `role_id`->roles(set null) | `email` | — |
| 11 | `document_types` | InnoDB | `id` | — | `name` | — |
| 12 | **`documents`** | InnoDB | `id` (string/UUID) | `document_type_id`->document_types(set null), `sender_department_id`->departments(set null), `uploaded_by_user_id`->users(set null), `current_department_id`->departments(set null) | `document_number` | — |
| 13 | `document_files` | InnoDB | `id` | `document_id`->documents(cascade), `uploaded_by_user_id`->users(set null) | — | — |
| 14 | `document_routes` | InnoDB | `id` | `document_id`->documents(cascade), `department_id`->departments(cascade), `received_by_user_id`->users(set null) | composite(`document_id`,`route_order`) | — |
| 15 | `document_events` | InnoDB | `id` | `document_id`->documents(cascade), `user_id`->users(set null), `department_id`->departments(set null) | — | — |
| 16 | `document_qr_codes` | InnoDB | `id` | `document_id`->documents(cascade), `generated_by_user_id`->users(set null) | `qr_token` | — |
| 17 | `document_receipts` | InnoDB | `id` | `document_id`->documents(cascade), `document_route_id`->document_routes(cascade), `department_id`->departments(cascade), `received_by_user_id`->users(cascade) | — | — |
| 18 | `document_scans` | InnoDB | `id` | `scanned_by_user_id`->users(cascade), `department_id`->departments(cascade) | — | — |
| 19 | `document_issues` | InnoDB | `id` | `document_id`->documents(cascade), `reported_by_user_id`->users(cascade), `assigned_department_id`->departments(set null), `resolved_by_user_id`->users(set null) | — | — |
| 20 | `document_update_requests` | InnoDB | `id` | `document_id`->documents(cascade), `requested_by_user_id`->users(cascade), `assigned_to_user_id`->users(set null), `assigned_department_id`->departments(set null) | — | — |
| 21 | `notifications` | InnoDB | `id` | `user_id`->users(cascade), `department_id`->departments(set null) | — | — |
| 22 | `document_shares` | InnoDB | `id` | `document_id`->documents(cascade), `shared_by_user_id`->users(cascade), `shared_with_user_id`->users(set null) | `token` | — |
| 23 | `document_views` | InnoDB | `id` | `document_id`->documents(cascade), `user_id`->users(set null), `department_id`->departments(set null) | — | — |
| 24 | `export_logs` | InnoDB | `id` | `user_id`->users(cascade) | — | — |
| 25 | `sessions` | InnoDB | `id` (string) | — | — | `user_id`, `last_activity` |

### 2.3 Documents Table — Status State Machine

```
ENUM: 'received', 'pending_transfer', 'in_transit', 'rejected', 'cancelled', 'completed'
Default: 'pending_transfer'
```

**Status Transitions (as implemented in controllers):**
1. `pending_transfer` → (creation default)
2. `pending_transfer` → `in_transit` (when route step is received & next step exists)
3. `pending_transfer` → `received` (when route step is received & it was the final step)
4. `in_transit` → `in_transit` (intermediate route receipt)
5. `in_transit` → `received` (final route receipt)
6. `received` → `completed` (only by final department via `completeDocument`)

### 2.4 Document Routes Table — Step Status State Machine

```
ENUM: 'pending', 'current', 'received', 'skipped', 'rejected'
Default: 'pending'
```

**Transition Logic:**
- First route step is set to `current` on document creation
- All subsequent steps are `pending`
- On receipt: `current` → `received`; next step (`route_order + 1`) → `current`
- If no next step exists: document status → `received`

---

## 3. AUTHENTICATION & ACCESS CONTROL SEGMENT

### 3.1 Authentication Architecture

| Component | Implementation | File |
|-----------|---------------|------|
| Guard | `session` driver (web) | `config/auth.php:42` |
| User Provider | Eloquent (`App\Models\User`) | `config/auth.php:66` |
| Login | `Auth::attempt()` with `rememberMe` | `AuthController.php:34` |
| Logout | `Auth::logout()` + session invalidation + token regenerate | `AuthController.php:64-68` |
| Session Driver | `file` (configurable via .env) | `config/session.php:21` |
| Session Lifetime | 120 minutes | `config/session.php:35` |
| CSRF | `@csrf` on all forms; `X-CSRF-TOKEN` header on AJAX | All blade forms + `api.js` |

### 3.2 Security Guardrails

| Security Control | Implementation | Location |
|-----------------|----------------|----------|
| Inactive user block | `Auth::logout()` if `$user->status !== 'active'` | `AuthController.php:38-43` |
| Session regeneration | `$request->session()->regenerate()` on login | `AuthController.php:46` |
| Session invalidation | `invalidate()` + `regenerateToken()` on logout | `AuthController.php:67-68` |
| Admin middleware | `EnsureUserIsAdmin` checks `role_id === 1` | `EnsureUserIsAdmin.php:13` |
| Password hashing | `Hash::make()` — Bcrypt (12 rounds per .env) | `UserController.php:93` |
| `autocomplete="one-time-code"` | Applied to email + password inputs | `login.blade.php:73,83` |
| `$errors->any()` validation | Error list display for login | `login.blade.php:61-69` |
| `old('email')` input retention | Retains email on validation failure | `login.blade.php:73` |
| `@csrf` on all forms | Every POST form includes CSRF token | All blade views |
| `meta[name="csrf-token"]` | Exposed for AJAX requests | `scan.blade.php:7`, `users.blade.php:6` |
| `X-Requested-With: XMLHttpRequest` | All AJAX requests include this header | `api.js:26` |
| `http_only: true` session cookie | JavaScript cannot access session cookie | `config/session.php:185` |
| `same_site: lax` | CSRF mitigation for same-site | `config/session.php:202` |
| Auth context exposed to JS | `window.DTS_AUTH_CONTEXT` | `partials/auth-context.blade.php` |

### 3.3 Route Protection Matrix

```
PUBLIC (guest middleware):
  GET  /            → showLogin   (name: login)
  POST /login       → login       (name: login.submit)

AUTHENTICATED (auth middleware):
  GET  /dashboard   → index       (name: dashboard)
  GET  /upload      → create      (name: documents.create)
  POST /upload      → store       (name: documents.store)
  GET  /scan        → showScanPage (name: scan)
  GET  /scan/lookup → lookupDocument (name: scan.lookup)
  POST /scan/receive → receiveDocument (name: scan.receive)
  GET  /inbox       → inbox       (name: inbox)
  GET  /api/inbox/data → getInboxData (name: api.inbox.data)
  GET  /outbox      → outbox      (name: outbox)
  GET  /api/outbox/data → getOutboxData (name: api.outbox.data)
  GET  /document-details/{doc} → showDocumentDetails (name: document-details.show)
  POST /documents/confirm-receipt → confirmReceipt (name: documents.confirm-receipt)
  POST /documents/{doc}/receive → receiveDocument (name: documents.receive)
  POST /documents/{doc}/complete → completeDocument (name: documents.complete)
  POST /logout      → logout      (name: logout)

ADMIN (auth + admin middleware, role_id === 1):
  GET  /manage-users         → index          (name: users)
  GET  /api/users/data       → getUsersData   (name: api.users.data)
  GET  /api/users/stats      → stats          (name: api.users.stats)
  POST /api/users            → store          (name: api.users.store)
  PUT  /api/users/{id}       → update         (name: api.users.update)
  PATCH /api/users/{id}/toggle-status → toggleStatus (name: api.users.toggle-status)
```

---

## 4. BUSINESS LOGIC & DATA PIPELINES

### 4.1 Document Lifecycle Pipeline

```
┌──────────────┐    ┌──────────────┐    ┌──────────────┐    ┌──────────────┐
│   CREATION   │ →  │ PENDING      │ →  │   IN         │ →  │   RECEIVED   │
│ (Upload Form)│    │ TRANSFER     │    │   TRANSIT    │    │              │
└──────────────┘    └──────────────┘    └──────────────┘    └──────────────┘
                                                                    ↓
                    ┌──────────────┐    ┌──────────────┐    ┌──────────────┐
                    │   REJECTED   │    │   COMPLETED  │ ←  │  FINAL STEP  │
                    │              │    │  (by final   │    │   RECEIVED   │
                    └──────────────┘    │  department) │    └──────────────┘
                                        └──────────────┘
```

**Step-by-Step Flow:**

1. **Upload (`DocumentController::store`)** — Validates input (title, type, dept, file, routes JSON); generates UUID `id`; generates tracking number `DTS-{year}-{random4}`; stores file in `private/documents`; creates `documents`, `document_files`, `document_events` records; creates `document_routes` with first step as `current`, rest as `pending`.

2. **Scan/Lookup (`DocumentController::lookupDocument`)** — Queries by `document_number`; returns document + routes (ordered) + events (ascending); used by scan page and mobile QR reader.

3. **Receive (`DocumentController::receiveDocument`)** — Validates that user's department matches the `current` route step; marks step `received`; marks next step `current`; if no next step: document status → `received` + sets `completed_at`.

4. **Complete (`DocumentController::completeDocument`)** — Only the final department (last route step) can mark it `completed`; sets `completed_at` timestamp.

### 4.2 Drag-and-Drop Route Builder

The **Upload page** features a custom route-builder workspace (no external drag-and-drop library):

- **Department pool** (`#visual-dept-pool`): Scrollable list of all departments. Clicking toggles selection (highlighted green).
- **ADD SELECTED button** (`#addToRouteBtn`): Appends selected departments to the route chain with up/down/remove controls.
- **CLEAR button** (`#clearRouteBtn`): Wipes the entire route chain.
- **Hidden input** (`#routesInput`): Stores serialized JSON `[{department_id, route_order}, ...]` for form submission.
- **Load Existing Document dropdown**: Pre-fills form from existing document data including route chain.

**Known Bug (see Section 6):** The `#addToRouteBtn` and `#clearRouteBtn` had inline CSS bugs where high-specificity rules broke hover transitions. The current code shows them resolved with `!important` overrides (see `upload.blade.php:295-319`).

### 4.3 Inbox/Outbox Data Pipelines

| Feature | Endpoint | Query Logic |
|---------|----------|-------------|
| **Inbox** | `GET /api/inbox/data` | Documents where user's department has a `document_routes` entry with `received_at` NOT NULL AND no later step has been received |
| **Outbox** | `GET /api/outbox/data` | Documents where `sender_department_id` = user's department, filtered by statuses |
| **Users** | `GET /api/users/data` | Paginated Eloquent query with search/filter by name, email, role, department, status |

Both inbox/outbox support filters: `search` (title/doc number), `type` (document_type_id), `status`, `date`. All paginated at 10 results per page.

### 4.4 Dashboard Data Aggregation

The `DashboardController::index()` gathers:
- Metric cards: total count, pending, in-transit, received today
- Status distribution: grouped by `documents.status` (for doughnut chart)
- Department distribution: grouped by `current_department_id` (for bar chart)
- Activity feed: last 10 `document_events` with user/document info

---

## 5. FRONTEND DESIGN SYSTEMS & COLOR TOKEN CORES

### 5.1 Global Asset Architecture

| Technology | Version | Usage |
|------------|---------|-------|
| Bootstrap 5 | 5.3.2 | Grid, components, utilities, modal, dropdown, table, badge |
| Bootstrap Icons | 1.11.3 | All icons across the UI |
| Tailwind CSS | CDN (v3+) | Login page layout, utility classes via CDN script |
| Inter Font | Google Fonts | Primary sans-serif (login, route-builder) |
| Manrope Font | Google Fonts | Display/heading face (login title, route-builder) |
| Material Symbols | Google Fonts | Login page icons (`shield`, `login`, `visibility`, `verified_user`) |
| Chart.js | 4.4.1 | Dashboard doughnut + bar charts |
| QRCode.js | 1.0.0 | QR generation in upload modal + scan page |
| html5-qrcode | 2.3.8 | Camera QR scanner on scan page |
| Custom CSS | `public/css/custom.css` (547 lines) | Main stylesheet |

### 5.2 Complete Color Token Map

#### Primary Brand Tokens

| Token | Hex | Usage | Source |
|-------|-----|-------|--------|
| `--primary-color` | `#0d6efd` | CSS variable primary | `custom.css:4` |
| `--secondary-color` | `#6c757d` | CSS variable secondary | `custom.css:5` |
| `--success-color` | `#198754` | CSS variable success | `custom.css:6` |
| `--danger-color` | `#dc3545` | CSS variable danger | `custom.css:7` |
| `--warning-color` | `#ffc107` | CSS variable warning | `custom.css:8` |
| `--info-color` | `#0dcaf0` | CSS variable info | `custom.css:9` |

#### Sidebar / Navigation

| Element | Hex | Context |
|---------|-----|---------|
| Sidebar background | `#1e3a8a` → `#1e40af` | Gradient `linear-gradient(180deg, ...)` (custom.css:28) |
| Sidebar text (inactive) | `rgba(255,255,255,0.8)` | `custom.css:52` |
| Sidebar text (active/hover) | `#ffffff` | `custom.css:61,67` |
| Sidebar hover bg | `rgba(255,255,255,0.1)` | `custom.css:62` |
| Sidebar active bg | `rgba(255,255,255,0.15)` | `custom.css:68` |
| Sidebar border | `rgba(255,255,255,0.1)` | Bottom header border (custom.css:38) |
| Top navbar bg | `#ffffff` | `custom.css:89` |
| Top navbar shadow | `rgba(0,0,0,0.1)` | `custom.css:90` |

#### Dashboard Stat Cards

| Card | Icon Gradient | Icon Color | Source |
|------|--------------|------------|--------|
| Primary (total) | `#667eea` → `#764ba2` | `#ffffff` | `custom.css:124-127` |
| Success (received) | `#84fab0` → `#8fd3f4` | `#1e7e34` | `custom.css:129-132` |
| Warning (pending) | `#fad0c4` → `#ffd1ff` | `#856404` | `custom.css:134-137` |
| Info (transit) | `#a1c4fd` → `#c2e9fb` | `#004085` | `custom.css:139-142` |

#### Status Badges

| Status | Background | Text | Source |
|--------|-----------|------|--------|
| Pending Transfer | `#ffc107` | `#000` | `custom.css:146-148` |
| In Transit | `#0dcaf0` | `#000` | `custom.css:150-153` |
| Received | `#198754` | `#fff` | `custom.css:155-158` |
| Rejected | `#dc3545` | `#fff` | `custom.css:160-163` |

#### Login Page

| Element | Hex | Source |
|---------|-----|--------|
| Background | `#1e3a8a` → `#1e40af` | Inline gradient (login.blade.php:40) |
| Card bg | `#ffffff` | `login.blade.php:45` |
| Input border | `#d1d5db` | `login.blade.php:74` |
| Input focus border | `#0d6efd` | `login.blade.php:16` |
| Input focus shadow | `rgba(13,110,253,0.2)` | `login.blade.php:17` |
| Primary button bg | `#0d6efd` | `login.blade.php:21` |
| Primary button hover | `#0b5ed7` | `login.blade.php:25` |
| Forgot link | `#0d6efd` | `login.blade.php:28` |
| Forgot link hover | `#0b5ed7` | `login.blade.php:32` |
| Footer text | `rgba(255,255,255,0.5)` | `login.blade.php:120` |
| Footer links | `rgba(255,255,255,0.7)` | `login.blade.php:111-114` |
| Shield icon bg | `rgba(13,110,253,0.08)` | `login.blade.php:49` |

#### Route Builder (Upload Page)

| Element | Hex | Source |
|---------|-----|--------|
| ADD SELECTED btn text/border | `#1b7344` | `upload.blade.php:300-301` |
| ADD SELECTED btn hover bg | `#1b7344` | `upload.blade.php:306-307` |
| CLEAR btn text/border | `#6c757d` / `#ced4da` | `upload.blade.php:310-311` |
| CLEAR btn hover bg | `#6c757d` | `upload.blade.php:316-317` |
| Department hover bg | `rgba(27,115,68,0.04)` | `upload.blade.php:297` |
| Department selected bg | `rgba(27,115,68,0.08)` | Inline style (upload.blade.php:252) |

#### Chart Colors (Dashboard)

| Chart Element | Hex | Source |
|-------------|-----|--------|
| Received (doughnut) | `#198754` (green) | `dashboard.js:31` |
| In Transit (doughnut) | `#0dcaf0` (teal) | `dashboard.js:32` |
| Pending (doughnut) | `#ffc107` (yellow) | `dashboard.js:33` |
| Rejected (doughnut) | `#dc3545` (red) | `dashboard.js:34` |
| Bar chart fill | `#0d6efd` (blue) | `dashboard.js:80` |

#### Modal Headers

| Modal | Background | Text | Source |
|-------|-----------|------|--------|
| Upload Form | `bg-primary` (`#0d6efd`) | `#fff` | `upload.blade.php:119` |
| Routes Modal | `bg-success` (`#198754`) | `#fff` | `upload.blade.php:391` |
| QR Code Modal | `bg-success` | `#fff` | `upload.blade.php:418` |
| Add User Modal | `bg-primary` | `#fff` | `users.blade.php:294` |
| Edit User Modal | `bg-warning` (`#ffc107`) | `text-dark` | `users.blade.php:351` |
| Access Denied Modal | `bg-danger` (`#dc3545`) | `#fff` | `access-denied-modal.blade.php:5` |
| Report Issue Modal | `bg-warning` | `text-dark` | `document-details.blade.php:408` |

---

## 6. SYSTEM STABILITY & BUG AUDITS

### 6.1 Known Defect Resolution Log

| Bug ID | Description | Status | Resolution |
|--------|-------------|--------|------------|
| **BUG-001** | `#addToRouteBtn` / `#clearRouteBtn` hover transitions broken by high-specificity inline CSS | ✅ **RESOLVED** | Inline `!important` overrides added (`upload.blade.php:304-319`); `btn-custom-academic` and `btn-custom-clear` classes defined with explicit `!important` on hover colors. |
| **BUG-002** | Print QR functionality targeting wrong container | ✅ **RESOLVED** | Dual handlers: one in `upload.js:359-394` for the upload modal (targets `.modal-body .text-center`), another in `document-details.blade.php:643-694` for details page (targets `#qrCodePrintContainer`). |
| **BUG-003** | View Details button in upload modal not navigating | ✅ **RESOLVED** | Event delegation in `upload.js:340-356` reads `#generatedDocId` text and constructs `/document-details/{docNumber}` URL. |
| **BUG-004** | Status filter not triggering re-fetch (inbox/outbox) | ✅ **RESOLVED** | Multiple selector fallbacks added (`inbox.js:48-58`, `outbox.js:55-65`) using `[data-filter="status"]`, `#status-filter`, `select[name="status"]`. |
| **BUG-005** | Component text contrast in modals | ✅ **RESOLVED** | Text-white applied on all colored modal headers via Tailwind/Bootstrap classes (`text-white`, `text-dark`). |
| **BUG-006** | Responsive form padding constraints | ✅ **RESOLVED** | `.stat-card` responsive margin and `.main-content` mobile sidebar toggle in `custom.css:397-413`. |

### 6.2 Security Logging & Verification

| Audit component | Implementation | Location |
|----------------|---------------|----------|
| Document event logging | Every status change writes to `document_events` table with `user_id`, `department_id`, `event_type`, `event_label`, `old_status`, `new_status`, `note`, `metadata` (JSON with IP + user agent) | `DocumentController.php:92-103, 350-359, 426-435` |
| Document view tracking | `document_views` table logs `user_id`, `department_id`, `ip_address`, `user_agent`, `viewed_at` | Schema in migration |
| Export audit | `export_logs` table tracks who exported what/when | Schema in migration |
| Scan logging | `document_scans` records each QR scan attempt with result enum and user/department context | Schema in migration |
| "All access is logged" label | Displayed on login page footer | `login.blade.php:106` |
| CSRF token meta | `<meta name="csrf-token">` on pages with AJAX | `scan.blade.php:7`, `users.blade.php:6` |
| Auth context exposed | `window.DTS_AUTH_CONTEXT` with userId, departmentId, role, etc. | `partials/auth-context.blade.php` |

### 6.3 Open Issues & Architectural Concerns

| Severity | Issue | Details |
|----------|-------|---------|
| **Critical** | No Master Blade layout | Every view duplicates `<head>`, sidebar, top-navbar, scripts. 1,400+ lines of redundant HTML. Refactoring to `layouts/app.blade.php` with `@yield('content')` or `@section('content')` would eliminate ~60% of template code. |
| **High** | No API route file (`routes/api.php`) | All JSON endpoints are in `web.php` (session-based). No token-based API for mobile or third-party integration. |
| **High** | No Tests | `tests/` directory exists but no test files were found. No PHPUnit feature/unit tests for the 5 controllers or document lifecycle. |
| **Medium** | Hardcoded role checks | Admin is checked via `role_id === 1` in middleware and views. No RBAC service or policy class. `EnsureUserIsAdmin.php` uses a simple hard equality check. |
| **Medium** | `DB::raw('LOWER(status)')` in `confirmReceipt` | `document-routes.status` is an ENUM, but the query in `confirmReceipt` uses `where(DB::raw('LOWER(status)'), 'pending')`. ENUMs are already stored in the exact case used at insert. |
| **Medium** | Dashboard Activity Feed shows `3` as hardcoded notification badge | `DashboardController.php:61` sets `$unreadNotificationsCount = 3` — no real query. |
| **Medium** | `$userDeptId ?? 2` fallback | Inbox/Outbox controllers fallback to department ID 2 if user has no department_id (`?? 2`). Should return 401 or throw. |
| **Low** | Stale sample data in `main.js` | ~280 lines of hardcoded `sampleDocuments` and `sampleDocumentsWithRoutes` arrays — used only in deprecated `loadInbox()`/`loadOutbox()` methods. Real modules (`inbox.js`, `outbox.js`) use AJAX. |
| **Low** | No `completed` status in outbox status filter | Outbox filter lists only 'received', 'pending_transfer', 'in_transit', 'rejected' — missing 'completed' and 'cancelled'. |
| **Low** | `config/app.php` timezone is PHT; `.env` does not override | Must ensure DB timezone (MySQL `default-time-zone`) matches `Asia/Manila`. |

---

## SUMMARY METRICS

| Dimension | Count |
|-----------|-------|
| PHP Controllers | 5 |
| Models (Eloquent) | 3 |
| Custom Middleware | 1 |
| Service Providers | 1 |
| Migration Files | 4 |
| Database Tables | 25 |
| Named Routes | 14 |
| Blade Templates | 11 (+2 partials) |
| Blade Partials | 2 |
| JS Module Files | 7 |
| CSS Files | 1 (547 lines) |
| Config Files | 10 |
| Frontend Libraries | 8 (Bootstrap 5, Icons, Tailwind, Chart.js, QRCode.js, html5-qrcode, 2 fonts) |
| Resolved Bugs | 6 |
| Open Technical Debt Items | 10 |

---

*Report generated from exhaustive source audit of 52 application files across the full stack (PHP, Blade, JS, CSS, SQL migrations, and configuration).*
