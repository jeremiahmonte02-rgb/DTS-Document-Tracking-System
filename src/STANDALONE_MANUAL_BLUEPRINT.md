# Frontend Architectural Blueprint: Standalone Interactive DTS User Manual

**Role:** Frontend Architect  
**Project:** Document Tracking System (DTS) — Standalone Interactive User Manual  
**Scope:** Read-Only Architectural Analysis, Asset Mapping, View Flattening & Tour Guide Engine Design  

---

## Executive Architectural Summary

The objective is to produce a **100% client-side, zero-backend, pixel-perfect reproduction** of the Document Tracking System to serve as an interactive, guided onboarding and training manual. 

### Recommended Architecture: Single-Page Multi-View Application (SPA Shell)
Rather than splitting views across separate `.html` files that cause screen flashes and lose tour state on page reloads, the manual is designed as a **Single-Page Application Shell (`index.html`)** containing three conditionally visible view containers:
1. `<section id="view-dashboard" class="dts-view active">`
2. `<section id="view-upload" class="dts-view d-none">`
3. `<section id="view-details" class="dts-view d-none">`

#### Architectural Advantages:
- **Zero Page-Reload Latency:** View transitions during the tour are instantaneous ($0\text{ ms}$).
- **Unbroken State Machine:** JavaScript execution context is preserved from Step 1 to the end without serialization complexity in `localStorage` or URL query parsing.
- **Unified Spotlight & Overlay:** The tutorial backdrop overlay and tooltips smoothly animate across transitions without DOM tearing.

*(Note: Independent static pages `dashboard.html`, `upload.html`, and `document-details.html` can also be generated as non-interactive static references if needed, sharing the same asset bundle.)*

---

## 1. Asset & Layout Extraction Mapping

### 1.1 Core HTML Shell & DOM Hierarchy
Derived from `resources/views/layouts/app.blade.php` and `resources/views/partials/sidebar-nav.blade.php`:

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DTS User Manual & Interactive Guide</title>
    <!-- Stylesheets -->
</head>
<body>
    <!-- 1. Collapsible Sidebar Navigation Shell -->
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="d-flex align-items-center justify-content-between">
                <div class="sidebar-brand">
                    <i class="bi bi-file-earmark-text"></i>
                    <span class="sidebar-brand-text">DTS</span>
                </div>
                <button class="btn btn-link sidebar-collapse-btn" id="sidebarCollapseBtn" title="Toggle sidebar">
                    <i class="bi bi-chevron-left"></i>
                </button>
            </div>
            <small class="text-white-50 sidebar-subtitle">Document Tracking</small>
        </div>
        <ul class="sidebar-nav nav flex-column">
            <li class="nav-item"><a class="nav-link active" data-view-target="dashboard" href="#dashboard"><i class="bi bi-speedometer2"></i><span>Dashboard</span></a></li>
            <li class="nav-item"><a class="nav-link" data-view-target="upload" href="#upload"><i class="bi bi-cloud-upload"></i><span>Upload Document</span></a></li>
            <li class="nav-item"><a class="nav-link" href="#scan"><i class="bi bi-qr-code-scan"></i><span>Scan QR Code</span></a></li>
            <li class="nav-item"><a class="nav-link" href="#inbox"><i class="bi bi-inbox"></i><span>Inbox</span></a></li>
            <li class="nav-item"><a class="nav-link" href="#outbox"><i class="bi bi-send"></i><span>Outbox</span></a></li>
        </ul>
    </nav>

    <!-- 2. Main Content Canvas -->
    <div class="main-content">
        <!-- Top Navigation Bar -->
        <nav class="top-navbar d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <button class="btn btn-link d-md-none me-2" id="mobileMenuToggle">
                    <i class="bi bi-list fs-4"></i>
                </button>
                <h5 class="mb-0" id="manualPageTitle">Dashboard</h5>
            </div>
            <div class="d-flex align-items-center gap-3">
                <!-- Notifications Dropdown (Static Mock) -->
                <div class="dropdown">
                    <button class="btn btn-link position-relative dropdown-toggle" type="button" id="notificationBell" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-bell fs-5"></i>
                        <span id="notificationBadge" class="notification-badge">2</span>
                    </button>
                    <!-- Pre-filled Dropdown Menu -->
                </div>
                <!-- User Profile Dropdown (Static Mock) -->
                <div class="dropdown">
                    <button class="btn btn-link dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle fs-5"></i>
                        <span class="d-none d-md-inline">Juan Dela Cruz</span>
                    </button>
                    <!-- Pre-filled Profile Menu -->
                </div>
            </div>
        </nav>

        <!-- Viewport Container for Injected Views -->
        <div class="container-fluid p-4" id="viewContainer">
            <!-- Flattened Views Swap In Here -->
        </div>
    </div>

    <!-- 3. Global Modals (Partials) -->
    <!-- #globalConfirmModal (from confirm-modal.blade.php) -->
    <!-- #qrCodeModal (from upload.blade.php) -->
    <!-- #reportIssueModal (from document-details.blade.php) -->
    <!-- #editRoutingModal (from document-details.blade.php) -->

    <!-- 4. Interactive Tour Guide Engine Layer -->
    <div id="tourBackdrop" class="tour-backdrop d-none"></div>
    <div id="tourTooltipCard" class="tour-tooltip-card shadow-lg d-none"></div>
</body>
</html>
```

---

### 1.2 Asset Manifest: Keep, Extract, and Prune

#### A. External CDNs (Preserve in `<head>` & Scripts)
| Library | Source / CDN URL | Purpose |
| :--- | :--- | :--- |
| **Bootstrap 5.3.2 CSS** | `https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css` | Foundation grid, components, utility classes |
| **Bootstrap Icons 1.11.3** | `https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css` | System iconography (`bi-*`) |
| **Satoshi Font** | `https://api.fontshare.com/css?f[]=satoshi@400,500,600,700,900&display=swap` | System typography token imported in `custom.css` |
| **Bootstrap 5.3.2 JS Bundle** | `https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js` | Modals, dropdowns, collapse components |
| **Chart.js 4.4.1 (UMD)** | `https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js` | Renders Dashboard Status and Department charts |
| **QRCode.js 1.0.0** | `https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js` | Generates dynamic client-side QR codes in modals |

#### B. Internal Assets to Copy/Extract to Isolated Folder
| Source File | Destination in Isolated Manual | Adaptations Needed |
| :--- | :--- | :--- |
| `public/css/custom.css` | `assets/css/custom.css` | None. Kept 100% identical (all design tokens & component classes are here). |
| `public/js/core/format.js` | `assets/js/core/format.js` | None. Formats tabular dwell & completion hours. |
| `public/js/modules/timeline-renderer.js` | `assets/js/modules/timeline-renderer.js` | None. Reusable engine for the document audit trail. |
| `public/js/modules/confirm-modal.js` | `assets/js/modules/confirm-modal.js` | None. Powers `window.showConfirmModal()`. |
| `public/js/main.js` | `assets/js/main-static.js` | Strip out network fetch handlers (`/api/notifications/read`, Laravel CSRF, `showSpinner` redirect loops). Preserve sidebar collapse, mobile toggling, and dropdown empty-states. |

#### C. Backend & Real-time Assets to Prune (Do NOT Copy)
- Laravel Echo (`laravel-echo@1.19.0`) & Pusher JS (`pusher-js@8.4.0`)
- `public/js/modules/realtime-announcements.js` & `realtime-near-overdue.js`
- `public/js/modules/realtime-document.js` & `realtime-inbox.js`
- `public/js/core/api.js` (CSRF token extraction and HTTP requests)
- Laravel Blade CSRF tags (`<meta name="csrf-token">`, `@csrf`)

---

## 2. Core View Flattening: Dynamic Directives to Static Scenarios

To make the user manual realistic, all views tell a coherent story around a dummy document:
- **Document Reference:** `PO-2026-10-0042`
- **Title:** `Server Hardware & Network Switch Procurement`
- **Document Type:** `Purchase Order (PO)`
- **Origin Department:** `IT Department`
- **Current Custody:** `Finance Department` (Reviewing budget clearance)
- **Next Destination:** `Executive Office` (Final approval)

---

### 2.1 View 1: `dashboard.blade.php` (Tracking & Overview)

| Dynamic Blade Directive | Source Expression | Static Replacement in Manual |
| :--- | :--- | :--- |
| **Month Label** | `{{ $startOfMonth->format('F Y') }}` | `<strong>October 2026</strong>` |
| **Month Picker Input** | `value="{{ $startOfMonth->format('Y-m') }}"` | `value="2026-10" max="2026-10"` |
| **Metric: Total Documents** | `{{ $totalDocuments }}` | `<h2 class="mb-0 tabular-nums">24</h2>` |
| **Metric: Pending Transfer** | `{{ $pendingDocuments }}` | `<h2 class="mb-0 tabular-nums">3</h2>` |
| **Metric: Received in Month** | `{{ $receivedInMonth }}` | `<h2 class="mb-0 tabular-nums">18</h2>` |
| **Metric: In Transit** | `{{ $inTransitDocuments }}` | `<h2 class="mb-0 tabular-nums">5</h2>` |
| **Metric: Avg Dwell Time** | `{{ $avgDwellHours }}` | `<h2 class="mb-0 tabular-nums js-format-time" data-hours="4.2">4.2 <small class="fs-6 text-muted">hrs</small></h2>` |
| **Metric: Overdue Count** | `{{ $overdueCount }}` | `<h2 class="mb-0 tabular-nums">1</h2>` |
| **Metric: Avg Completion** | `{{ $avgCompletionHours }}` | `<h2 class="mb-0 tabular-nums js-format-time" data-hours="16.5">16.5 <small class="fs-6 text-muted">hrs</small></h2>` |
| **Announcement Banner** | `@include('partials.announcement-banner')` | Hardcoded alert: Near-Overdue warning for `PO-2026-10-0038` and System Scheduled Maintenance notice. |
| **Chart: Status Distribution** | `data-metrics='@json($statusMetrics ?? [])'` | `data-metrics='{"pending_transfer":3,"in_transit":5,"received":18,"completed":12,"rejected":1}'` |
| **Chart: Departments** | `data-metrics='@json($departmentDistribution ?? [])'` | `data-metrics='{"Finance Department":8,"Accounting":6,"IT Department":4,"Executive Office":3}'` |
| **Activity Feed** | `@forelse ($activityFeed as $event)` | 3 hardcoded feed items with realistic timestamps (`5 mins ago`, `42 mins ago`, `2 hours ago`) highlighting `PO-2026-10-0042`. |

---

### 2.2 View 2: `upload.blade.php` (Document Creation & Route Builder)

| Dynamic Blade Directive | Source Expression | Static Replacement in Manual |
| :--- | :--- | :--- |
| **Form Attributes** | `data-store-url="{{ route('documents.store') }}"` | `id="uploadForm" data-manual-demo="true"` (intercepted by tour engine) |
| **Document Types** | `@foreach($documentTypes as $type)` | Static options: `Purchase Order (PO)`, `Disbursement Voucher (DV)`, `Liquidation Report (LR)`, `Travel Authority (TA)`, `Office Order (OO)`. |
| **Department Pool** | `@foreach($departments as $dept)` | 8 static departments: `Central Services`, `CCIS`, `Customer Service`, `Executive Office`, `Facilities`, `Finance Department`, `HR Department`, `IT Department`. |
| **Route Sequence List** | `<ul id="routeList">` | Pre-built sequence (or dynamically populated when the user clicks during the guided step): `1. Finance Department`, `2. Executive Office`. |
| **Pre-fill Title/Description** | Inputs empty by default | Interactive simulated auto-fill: `PO-2026-10-0042: Server Hardware & Network Switch Procurement`. |
| **QR Code Modal** | Dynamic generation on POST | Modal `#qrCodeModal` wired directly to client-side button click with QR text `PO-2026-10-0042`. |

---

### 2.3 View 3: `document-details.blade.php` (Receiving & Audit Trail)

| Dynamic Blade Directive | Source Expression | Static Replacement in Manual |
| :--- | :--- | :--- |
| **Document Number** | `{{ $document->document_number }}` | `<span id="docId" class="font-mono text-primary fw-bold">PO-2026-10-0042</span>` |
| **Document Title** | `{{ $document->title }}` | `Server Hardware & Network Switch Procurement` |
| **Document Type** | `{{ $document->document_type_name }}` | `Purchase Order (PO)` |
| **Sender Department** | `{{ $document->origin_department }}` | `IT Department` |
| **Current Location** | `{{ $document->current_department }}` | `Finance Department` |
| **Document Status Badge** | Dynamic `@if($document->status...)` | `<span class="badge bg-warning text-dark px-3 py-1">IN TRANSIT</span>` |
| **Upload Date / By** | `{{ $document->upload_date }}` | `Oct 06, 2026 09:30 AM` by `Juan Dela Cruz` |
| **Scheduled Route Steps** | `@foreach($routes as $route)` | 3 structured step cards: <br>1. **IT Department** (`COMPLETED`, green badge)<br>2. **Finance Department** (`CURRENT`, yellow pulsing border)<br>3. **Executive Office** (`NEXT`, blue badge) |
| **Audit Trail Timeline** | `fetch('/scan/lookup')` | Static payload passed to `window.renderTimeline('auditTrail', mockEvents)` displaying the complete history (Created $\to$ QR Printed $\to$ Dispatched $\to$ Arrived). |
| **Receive / Action Buttons** | Dynamic permissions `@if($userIsNext)` | Static active button: `<button id="markAsReceivedBtn" class="btn btn-primary"><i class="bi bi-check-circle"></i> Mark as Received</button>`. |

---

## 3. Tour Guide Engine Strategy & Evaluation

### 3.1 Head-to-Head Comparison

```
+--------------------------------------------------------------------------------------------------+
| Criteria                | Intro.js             | Shepherd.js         | Custom Vanilla Engine     |
+--------------------------------------------------------------------------------------------------+
| Licensing               | Commercial (Paid) or | MIT (Free)          | 100% Owned / Unrestricted |
|                         | AGPLv3 (Restricted)  |                     |                           |
| Dependencies            | None                 | Floating UI / Popper| ZERO (Native DOM)         |
| Bundle Size             | ~15 KB               | ~40 KB              | ~3.5 KB                   |
| DOM View Swapping       | Poor (Requires hacky | Moderate            | Native first-class        |
|                         | lifecycle hooks)     |                     | citizen                   |
| Styling Integration     | Clashes with BS5     | Requires heavy CSS  | Pixel-perfect Bootstrap 5 |
|                         | z-indexes            | override            | & Satoshi design tokens   |
| Interactive Click Gates | Limited to 'next'    | Can attach handlers | Native event interception |
|                         | buttons              |                     | & action triggers         |
+--------------------------------------------------------------------------------------------------+
```

### 3.2 Recommendation
**Build a bespoke, lightweight Vanilla JavaScript State Machine Engine (`tour-engine.js`).**
*Rationale:*
1. **Zero License Friction:** No AGPLv3 or commercial licensing traps.
2. **First-Class View Orchestration:** Traditional tour libraries assume all target elements already exist in the DOM on page load. A custom engine effortlessly coordinates the sequence: *Highlight button on View A $\to$ wait for click $\to$ switch DOM to View B $\to$ prefill form $\to$ highlight element on View B*.
3. **Exact Visual Identity:** The tooltip card, progress indicators, step pills, and backdrop mask use the exact colors, typography (`font-display: 'Satoshi'`), and borders defined in `custom.css`.

---

## 4. Tour Guide Engine Technical Architecture

### 4.1 State Machine Architecture Diagram

```
                                  +-----------------------------+
                                  |     INITIAL STATE (IDLE)    |
                                  |   Manual loaded at Step 0   |
                                  +--------------+--------------+
                                                 | User clicks "Start Tour"
                                                 v
  +----------------------------------------------------------------------------------------------+
  |                                   ACTIVE STEP EXECUTION                                      |
  |  1. Check target view -> If currentView != step.view, switchView(step.view)                  |
  |  2. Calculate target element bounding rect (DOM spotlight)                                  |
  |  3. Position tooltip card relative to target (top / bottom / left / right)                   |
  |  4. Lock down background interactions (Pointer events mask)                                 |
  |  5. Bind step trigger listener (e.g. click on target, or "Next" button click)                |
  +----------------------------------------------+-----------------------------------------------+
                                                 |
                   +-----------------------------+-----------------------------+
                   |                                                           |
                   v Trigger: Element Click                                    v Trigger: "Next" or Form Submit
     +---------------------------+                               +---------------------------+
     |   INTERCEPT USER CLICK    |                               |    SIMULATE WORKFLOW      |
     | - e.preventDefault()      |                               | - Trigger QR modal / save |
     | - Execute step action     |                               | - Play subtle animation   |
     | - Transition to next step |                               | - Transition to next step |
     +-------------+-------------+                               +-------------+-------------+
                   |                                                           |
                   +-----------------------------+-----------------------------+
                                                 |
                                                 v
                                  +-----------------------------+
                                  |    ADVANCE STEP INDEX++     |
                                  |  Next step or Complete Tour |
                                  +-----------------------------+
```

---

### 4.2 Tour Step Definition Schema

```javascript
const TOUR_STEPS = [
    {
        id: 'welcome',
        view: 'dashboard',
        target: '#quickActionUploadBtn',
        title: 'Step 1: Initiating Document Upload',
        content: 'Welcome to the Document Tracking System! To send a physical document through campus offices, click "Upload Document".',
        placement: 'bottom',
        advanceOn: 'click', // Advances automatically when user clicks this button
        beforeEnter: () => {
            // Ensure we are on the dashboard
            DTSManual.switchView('dashboard');
        }
    },
    {
        id: 'fill_form',
        view: 'upload',
        target: '#uploadForm',
        title: 'Step 2: Document Metadata & Route Builder',
        content: 'Fill in the document title and select departments in the ordered workflow. Click "Simulate Auto-Fill" or click "Upload Document" to proceed.',
        placement: 'right',
        advanceOn: 'manual', // Advance via Next or custom button
        beforeEnter: () => {
            DTSManual.switchView('upload');
            // Auto-populate realistic sample data
            document.getElementById('title').value = 'PO-2026-10-0042: Server Hardware Procurement';
            document.getElementById('documentType').value = '1';
            DTSManual.addDummyRouteSteps(['Finance Department', 'Executive Office']);
        }
    },
    {
        id: 'submit_upload',
        view: 'upload',
        target: 'button[type="submit"]',
        title: 'Step 3: Generating Tracking QR Code',
        content: 'Click "Upload Document" to register the file and generate its physical tracking QR label.',
        placement: 'top',
        advanceOn: 'click',
        action: () => {
            // Show QR modal instead of an HTTP POST request
            const qrModal = new bootstrap.Modal(document.getElementById('qrCodeModal'));
            qrModal.show();
            // Render QR Code in modal
            new QRCode(document.getElementById('modalQrCode'), {
                text: 'PO-2026-10-0042',
                width: 140,
                height: 140
            });
            document.getElementById('generatedDocId').textContent = 'PO-2026-10-0042';
        }
    },
    {
        id: 'view_details_step',
        view: 'upload',
        target: '#modalViewDetailsBtn',
        title: 'Step 4: Inspecting Live Custody',
        content: 'Great! The document is registered. Click "View Details" to inspect its routing timeline and current custody.',
        placement: 'top',
        advanceOn: 'click',
        action: () => {
            const qrModalEl = document.getElementById('qrCodeModal');
            bootstrap.Modal.getInstance(qrModalEl)?.hide();
            DTSManual.switchView('details');
        }
    },
    {
        id: 'details_routing',
        view: 'details',
        target: '#routeTimelineSteps',
        title: 'Step 5: Scheduled Routing Chain',
        content: 'Here you can see the sequence of departments. Finance Department is marked as "CURRENT", awaiting physical document delivery.',
        placement: 'left',
        advanceOn: 'manual'
    },
    {
        id: 'details_receive',
        view: 'details',
        target: '#markAsReceivedBtn',
        title: 'Step 6: Receiving & Custody Handover',
        content: 'When the physical paper arrives at your desk, click "Mark as Received" to log custody in the tamper-evident audit trail.',
        placement: 'bottom',
        advanceOn: 'click',
        action: () => {
            window.showConfirmModal({
                title: 'Mark Document as Received',
                message: 'Confirm receipt of PO-2026-10-0042 at Finance Department?',
                confirmLabel: 'Confirm Receipt',
                variant: 'success',
                onConfirm: () => {
                    DTSManual.showToast('Document received successfully!', 'success');
                    DTSManual.completeTour();
                }
            });
        }
    }
];
```

---

### 4.3 Engine Implementation Architecture (`tour-engine.js`)

```javascript
/**
 * DTS Standalone Interactive Manual Engine
 * Zero dependencies, pixel-perfect matching with Bootstrap 5 & custom.css
 */
const DTSManual = (function() {
    'use strict';

    let currentStepIndex = 0;
    let activeSteps = [];
    let currentActiveView = 'dashboard';

    // Core Elements
    let backdropEl, tooltipEl;

    function init(steps) {
        activeSteps = steps;
        createTourDOMElements();
        setupNavigationInterceptors();
        goToStep(0);
    }

    function createTourDOMElements() {
        // High-contrast SVG Cutout or Box-shadow Spotlight Overlay
        backdropEl = document.createElement('div');
        backdropEl.id = 'manualSpotlightOverlay';
        backdropEl.className = 'manual-spotlight-overlay';
        document.body.appendChild(backdropEl);

        // Interactive Tour Tooltip Card
        tooltipEl = document.createElement('div');
        tooltipEl.id = 'manualTooltipCard';
        tooltipEl.className = 'manual-tooltip-card card shadow-lg border-primary';
        document.body.appendChild(tooltipEl);
    }

    function switchView(viewName) {
        currentActiveView = viewName;
        document.querySelectorAll('.dts-view').forEach(el => el.classList.add('d-none'));
        const targetView = document.getElementById(`view-${viewName}`);
        if (targetView) targetView.classList.remove('d-none');

        // Update active class in sidebar
        document.querySelectorAll('.sidebar-nav .nav-link').forEach(link => {
            link.classList.toggle('active', link.getAttribute('data-view-target') === viewName);
        });

        // Update Top Navbar Title
        const titleMap = {
            dashboard: 'Dashboard',
            upload: 'Upload Document',
            details: 'Document Details'
        };
        const pageTitleEl = document.getElementById('manualPageTitle');
        if (pageTitleEl) pageTitleEl.textContent = titleMap[viewName] || 'Document Tracking System';

        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function goToStep(index) {
        if (index < 0 || index >= activeSteps.length) {
            completeTour();
            return;
        }

        currentStepIndex = index;
        const step = activeSteps[index];

        if (typeof step.beforeEnter === 'function') {
            step.beforeEnter();
        } else if (step.view && step.view !== currentActiveView) {
            switchView(step.view);
        }

        // Allow DOM to settle before calculating element bounding coordinates
        requestAnimationFrame(() => {
            setTimeout(() => {
                renderStep(step);
            }, 100);
        });
    }

    function renderStep(step) {
        const target = document.querySelector(step.target);
        if (!target) {
            console.warn(`[DTS Manual] Target selector not found: ${step.target}`);
            return;
        }

        // 1. Position Spotlight
        target.scrollIntoView({ behavior: 'smooth', block: 'center' });
        const rect = target.getBoundingClientRect();
        
        // Spotlight Cutout CSS (highlight box around target element)
        backdropEl.style.top = `${rect.top + window.scrollY - 6}px`;
        backdropEl.style.left = `${rect.left + window.scrollX - 6}px`;
        backdropEl.style.width = `${rect.width + 12}px`;
        backdropEl.style.height = `${rect.height + 12}px`;
        backdropEl.classList.add('active');

        // 2. Build Tooltip HTML with DTS Satoshi Styling
        const isFirst = currentStepIndex === 0;
        const isLast = currentStepIndex === activeSteps.length - 1;
        const stepCounter = `Step ${currentStepIndex + 1} of ${activeSteps.length}`;

        tooltipEl.innerHTML = `
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center py-2 px-3">
                <span class="badge bg-white text-primary fw-bold">${stepCounter}</span>
                <button type="button" class="btn-close btn-close-white btn-sm" id="manualCloseBtn"></button>
            </div>
            <div class="card-body p-3">
                <h6 class="fw-bold mb-1" style="font-family: 'Satoshi', sans-serif;">${step.title}</h6>
                <p class="small text-muted mb-3">${step.content}</p>
                <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                    <button class="btn btn-sm btn-outline-secondary" id="manualPrevBtn" ${isFirst ? 'disabled' : ''}>
                        <i class="bi bi-chevron-left"></i> Back
                    </button>
                    <div class="d-flex gap-2">
                        ${step.advanceOn === 'click' 
                            ? `<span class="badge bg-warning text-dark align-self-center py-2 px-2"><i class="bi bi-cursor me-1"></i> Click highlighted item to continue</span>` 
                            : `<button class="btn btn-sm btn-primary" id="manualNextBtn">${isLast ? 'Finish Tour' : 'Next <i class="bi bi-chevron-right"></i>'}</button>`
                        }
                    </div>
                </div>
            </div>
        `;

        // 3. Position Tooltip Box relative to Target
        positionTooltip(rect, step.placement || 'bottom');
        tooltipEl.classList.add('active');

        // 4. Bind Control Listeners
        document.getElementById('manualCloseBtn')?.addEventListener('click', exitTour);
        document.getElementById('manualPrevBtn')?.addEventListener('click', () => goToStep(currentStepIndex - 1));
        document.getElementById('manualNextBtn')?.addEventListener('click', () => {
            if (typeof step.action === 'function') step.action();
            goToStep(currentStepIndex + 1);
        });

        // 5. Intercept Click on the Highlighted Target
        if (step.advanceOn === 'click') {
            const clickHandler = function(e) {
                target.removeEventListener('click', clickHandler);
                if (typeof step.action === 'function') step.action();
                goToStep(currentStepIndex + 1);
            };
            target.addEventListener('click', clickHandler, { once: true });
        }
    }

    function positionTooltip(rect, placement) {
        const tooltipWidth = 360;
        let top = 0;
        let left = 0;

        switch (placement) {
            case 'top':
                top = rect.top + window.scrollY - 180;
                left = rect.left + window.scrollX + (rect.width / 2) - (tooltipWidth / 2);
                break;
            case 'bottom':
                top = rect.bottom + window.scrollY + 12;
                left = rect.left + window.scrollX + (rect.width / 2) - (tooltipWidth / 2);
                break;
            case 'left':
                top = rect.top + window.scrollY;
                left = rect.left + window.scrollX - tooltipWidth - 12;
                break;
            case 'right':
                top = rect.top + window.scrollY;
                left = rect.right + window.scrollX + 12;
                break;
        }

        // Clamp inside window boundaries
        left = Math.max(16, Math.min(left, window.innerWidth - tooltipWidth - 20));
        tooltipEl.style.top = `${top}px`;
        tooltipEl.style.left = `${left}px`;
        tooltipEl.style.width = `${tooltipWidth}px`;
    }

    function setupNavigationInterceptors() {
        // Prevent full page navigation when clicking sidebar links; switch internal views instead
        document.addEventListener('click', function(e) {
            const link = e.target.closest('a[data-view-target]');
            if (link) {
                e.preventDefault();
                const view = link.getAttribute('data-view-target');
                switchView(view);
            }
        });
    }

    function completeTour() {
        backdropEl.classList.remove('active');
        tooltipEl.classList.remove('active');
        showToast('You have completed the Document Tracking System interactive guide!', 'success');
    }

    function exitTour() {
        backdropEl.classList.remove('active');
        tooltipEl.classList.remove('active');
    }

    return {
        init,
        switchView,
        goToStep,
        addDummyRouteSteps: (depts) => {
            const routeList = document.getElementById('routeList');
            const placeholder = document.getElementById('route-placeholder');
            if (!routeList) return;
            placeholder?.classList.add('d-none');
            routeList.classList.remove('d-none');
            routeList.innerHTML = depts.map((d, i) => `
                <li class="list-group-item d-flex justify-content-between align-items-center text-xs p-2 bg-light shadow-2xs mb-1 rounded border">
                    <div class="d-flex align-items-center">
                        <span class="badge bg-primary index-counter-badge me-2">${i + 1}</span>
                        <span class="text-dark font-medium">${d}</span>
                    </div>
                </li>
            `).join('');
        },
        showToast: (msg, type = 'success') => {
            // Toast notification using Bootstrap alerts
            const toast = document.createElement('div');
            toast.className = `alert alert-${type} position-fixed top-0 end-0 m-4 shadow-lg z-3`;
            toast.style.zIndex = '9999';
            toast.innerHTML = `<i class="bi bi-check-circle me-2"></i> ${msg}`;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }
    };
})();
```

---

### 4.4 Overlay CSS Specifications (`tour-overlay.css`)

```css
/* Spotlight Backdrop: Soft shadow cutout overlay without clipping */
.manual-spotlight-overlay {
    position: absolute;
    pointer-events: none;
    z-index: 1040;
    border-radius: 8px;
    box-shadow: 0 0 0 9999px rgba(15, 23, 42, 0.65);
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    opacity: 0;
}

.manual-spotlight-overlay.active {
    opacity: 1;
}

/* Tour Tooltip Card: Adheres to Satoshi & DTS tokens */
.manual-tooltip-card {
    position: absolute;
    z-index: 1050;
    max-width: 380px;
    border-radius: 12px;
    background-color: #ffffff;
    box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.25);
    animation: fadeInTooltip 0.25s ease-out;
}

@keyframes fadeInTooltip {
    from { opacity: 0; transform: translateY(6px); }
    to { opacity: 1; transform: translateY(0); }
}
```

---

## 5. File Delivery Manifest & Implementation Plan

### 5.1 Target Directory Structure
The standalone manual will be assembled inside a dedicated, isolated root folder (e.g., `user-manual/` or `public/manual/`):

```
user-manual/
├── index.html                    <-- Primary SPA interactive manual shell containing all 3 views
├── assets/
│   ├── css/
│   │   ├── custom.css            <-- Copied directly from public/css/custom.css
│   │   └── tour-overlay.css      <-- Spotlight cutout and tour card styling
│   └── js/
│       ├── core/
│       │   └── format.js         <-- Copied directly from public/js/core/format.js
│       ├── modules/
│       │   ├── confirm-modal.js  <-- Copied directly from public/js/modules/confirm-modal.js
│       │   └── timeline-renderer.js <-- Copied directly from public/js/modules/timeline-renderer.js
│       ├── main-static.js        <-- Adapted from public/js/main.js (sidebar & mobile toggling)
│       ├── tour-engine.js        <-- Click-to-advance state machine engine
│       └── tour-steps.js         <-- Defined workflow step array for PO-2026-10-0042
```

### 5.2 Next Steps / Execution Sequence (When Ready to Generate)
1. **Directory Setup:** Create `user-manual/` structure with `assets/css` and `assets/js`.
2. **Asset Extraction:** Copy `custom.css`, `format.js`, `confirm-modal.js`, and `timeline-renderer.js` directly from `public/`.
3. **HTML Shell Assembly:** Combine `app.blade.php`, `sidebar-nav.blade.php`, and `confirm-modal.blade.php` into the root of `index.html`.
4. **View Flattening Injection:** Paste the flattened static HTML for `dashboard.blade.php`, `upload.blade.php`, and `document-details.blade.php` inside `#view-dashboard`, `#view-upload`, and `#view-details`.
5. **Engine Binding:** Integrate `tour-engine.js` and `tour-steps.js` to enable interactive click-to-advance guidance.
