# Phase 4: Simulation Smell Remediation — Client-Side to DB-Native Migration

## Scope
Convert all hardcoded mock data arrays, client-side state mutations, and simulated API flows in `main.js` and Blade views to server-authoritative queries using the Phase 3 Eloquent models.

---

## S1 — Remove `sampleDocuments[]` and `sampleDocumentsWithRoutes[]`

**File:** `public/js/main.js`  
**Lines:** 4–300 (276 lines of mock data)  
**Risk:** High — entire inbox/outbox/document-detail rendering depends on these arrays.

### Dependencies to clear first:
| Consumer | Location (main.js) | Action |
|---|---|---|
| `loadInbox()` | line 422 | Rewrite to fetch from `api.inbox.data` |
| `loadOutbox()` | line 445 | Rewrite to fetch from `api.outbox.data` |
| `renderDocumentTable()` | line 452 | Delete (replaced by inbox.js/outbox.js server-side rendering) |
| `advanceRoute()` | line 469 | Delete entire function |
| `loadDocumentDetails()` | line 540 | Rewrite — this is a client-side JS function that should be deleted; details are server-rendered in `document-details.blade.php` |
| `simulateScan()` | line 653 | Delete entire function |
| `handleManualScan()` | line 668 | Delete entire function |
| `displayScanResult()` | line 692 | Delete entire function |
| `confirmReceipt()` | line 746 | Delete entire function |
| `showRoutedDocumentError()` | line 818 | Keep (modal utility used by scan.js) |
| `viewDocument()` | line 533 | Delete (navigation done server-side via inbox.js/scan.js) |

### Execution order:
1. Delete `sampleDocuments[]` and `sampleDocumentsWithRoutes[]` arrays.
2. Rewrite `loadInbox()` to route to `inbox.blade.php` (server-rendered, already exists at `/inbox`).
3. Rewrite `loadOutbox()` to route to `outbox.blade.php` (server-rendered, already exists at `/outbox`).
4. Delete `renderDocumentTable()`, `advanceRoute()`, `loadDocumentDetails()`, `simulateScan()`, `handleManualScan()`, `displayScanResult()`, `confirmReceipt()`, `viewDocument()`.
5. Update `initializePage()` (line 338) — remove `/inbox`, `/outbox`, `/document-details`, `/scan` cases; `/scan` is handled by `modules/scan.js`, inbox/outbox by their respective modules.
6. Remove `window.confirmReceipt`, `window.viewDocument`, `window.advanceRoute` exports.

---

## S2 — Remove `departments[]` and `documentTypes[]` arrays

**File:** `public/js/main.js`  
**Lines:** 302–328  
**Status:** Dead code — never referenced anywhere in the file.  
**Action:** Delete both arrays.

---

## S3 — Remove Hardcoded `'Executive Office'` Department Strings

**File:** `public/js/main.js`  
**Lines:** 419, 443, 474, 754  

Each instance hardcodes `'Executive Office'` as the current user's department. After mock data removal and server-side rendering, these become unreachable code and will be deleted alongside S1.

---

## S4 — Remove Simulated Scan (`simulateScan`, `handleManualScan`, `displayScanResult`)

**File:** `public/js/main.js`  
**Lines:** 653–743  

The real scan workflow lives in `modules/scan.js` which talks to the backend via `scan.lookup` and `scan.receive` routes. These client-side mock functions must be deleted.

---

## S5 — Remove Client-Side `confirmReceipt` (main.js)

**File:** `public/js/main.js`  
**Lines:** 746–815  

The real receipt confirmation flows through:
- `modules/scan.js` → `POST /scan/receive` → `DocumentController@receiveDocument`
- `document-details.blade.php` inline script → `POST /documents/{number}/receive`

The client-side mock must be deleted.

---

## S6 — Remove Client-Side `markAsReceived()` in document-details.blade.php

**File:** `resources/views/document-details.blade.php`  
**Lines:** 552–575  

This function calls `window.advanceRoute()` (client-side mutation). The correct implementation is the `markAsReceivedBtn` click handler at lines 698–772 which calls the backend properly. Keep the new handler, delete the old function.

### Also delete:
- `requestUpdate()` (line 577–579) — simulated, no backend
- `reportIssue()` (line 581–594) — opens modal, keep the modal but make it submit to a real endpoint
- `handleReportIssueSubmit()` (line 596–638) — simulated `setTimeout` API call, replace with real fetch

---

## S7 — Remove Quick-Test Buttons Placeholder IDs

**File:** `resources/views/scan.blade.php`  
**Lines:** 192–197  

The three quick-test buttons reference `DOC-2026-0001`, `DOC-2026-0002`, `DOC-2026-0003` — old random-4 format IDs that don't match `DTS-YYYY-NNNN`. Options:
- **Option A (Recommended):** Remove the quick-test section entirely in production.
- **Option B:** If kept for dev, update to valid `DTS-2026-0001` format after seeding test data.

---

## S8 — Remove `formatDateTime` and `formatTimeAgo` Utility Functions

**File:** `public/js/main.js`  
**Lines:** 933–956  

These are only used by the now-deleted mock-data rendering functions. When S1 is executed, these become dead code. Delete them.

---

## S9 — Remove `setTimeout` Simulated Delays

**Locations:**
- `public/js/main.js` line 657 (`simulateScan` 2s delay) — delete with S4
- `public/js/main.js` line 766 (`confirmReceipt` 800ms delay) — delete with S5
- `public/js/main.js` line 787 (`confirmReceipt` 1s delay) — delete with S5
- `resources/views/document-details.blade.php` line 615 (`handleReportIssueSubmit` 1200ms delay) — replace with real fetch (S6)
- `resources/views/document-details.blade.php` line 530 (`downloadDocument` 1500ms delay) — implement real download

---

## S10 — Timezone-Neutral Date Formatting

**File:** `public/js/main.js`  
**Lines:** 933–943  

`formatDateTime` uses `toLocaleString('en-US')` which is client-local-timezone. After deletion (S8), all date formatting will be done server-side via `Carbon::parse(...)->format(...)` which respects `Asia/Manila`. **No further action needed.**

---

## S11 — Replace `showNotifications()` Stub

**File:** `resources/views/dashboard.blade.php`  
**Line:** 80  

`showNotifications()` is called on bell icon click but not defined anywhere. Either:
- Create a notification dropdown module, or
- Remove the `onclick` attribute if notifications aren't implemented.

---

## S12 — Hardcoded Sidebar Badge Values

**Files:** All Blade views  
**Locations:**
- `dashboard.blade.php` line 48 — `$unreadNotificationsCount` (correctly uses server variable)
- `inbox.blade.php` line 47, `outbox.blade.php` line 47, `document-details.blade.php` line 47, `scan.blade.php` line 74, `upload.blade.php` line 47 — hardcoded `<span class="badge bg-danger ms-auto">3</span>`

**Action:** Replace all hardcoded `3` with `{{ $unreadNotificationsCount ?? 0 }}` passed from each controller's view data.

---

## S13 — Hardcoded Security Info in document-details.blade.php

**File:** `resources/views/document-details.blade.php`  
**Lines:** 382–396  

`<strong>Views:</strong> 12 times` is hardcoded. Either:
- Remove the views line entirely, or
- Add a `document_views` table and count, or
- Replace with `<strong>Views:</strong> N/A` to avoid lying to users.

---

## S14 — Dead Scan Quick-Test Buttons Route Assignment

**File:** `public/js/modules/scan.js`, `public/js/main.js`

The `handleManualScan()` in main.js (line 668) searches `sampleDocuments` and `sampleDocumentsWithRoutes` for manual entry. After S1 deletion of those arrays, manual entry no longer works. However, `modules/scan.js` already has the correct implementation using `executeDocumentLookupQuery()` fetching from the server. **No fix needed** — after S1, the main.js manual scan code is deleted.

---

## S15 — Remove `showRoutesModal()` Reference

**File:** `resources/views/upload.blade.php`  
**Line:** 351  

`onclick="showRoutesModal()"` references a function that doesn't exist in any JS file. Either implement it or remove the `onclick`.

---

## Cleanup Summary

| Priority | ID | File | Action |
|---|---|---|---|
| P0 | S1 | main.js:4-300 | Delete 276 lines of mock data & dependent functions |
| P0 | S5 | main.js:746-815 | Delete client-side confirmReceipt |
| P0 | S6 | document-details.blade.php:552-575 | Delete old markAsReceived |
| P0 | S6 | document-details.blade.php:596-638 | Rewrite reportIssue with real fetch |
| P1 | S2 | main.js:302-328 | Delete dead arrays |
| P1 | S7 | scan.blade.php:192-197 | Remove quick-test buttons |
| P1 | S8 | main.js:933-956 | Delete formatDateTime/formatTimeAgo |
| P1 | S12 | all Blade views | Fix hardcoded badge 3 |
| P1 | S13 | document-details.blade.php:382-396 | Fix hardcoded Views count |
| P2 | S9 | multiple files | Remove setTimeout simulations |
| P2 | S11 | dashboard.blade.php:80 | Fix showNotifications |
| P2 | S15 | upload.blade.php:351 | Fix showRoutesModal |
