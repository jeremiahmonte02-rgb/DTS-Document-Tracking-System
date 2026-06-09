/**
 * Advanced Document QR Scanning & Verification Module - DTS
 * Complies fully with openRules.md. Zero inline dependencies.
 */
document.addEventListener('DOMContentLoaded', function() {
    const searchForm = document.getElementById('manualLookupForm');
    const lookupInput = document.getElementById('manualDocId');
    const resultWrapper = document.getElementById('scanResultWrapper');
    const cardContainer = document.getElementById('scanCardResultContainer');
    const routeMapContainer = document.getElementById('routeStepMapContainer');
    const timelineContainer = document.getElementById('trackingTimeline');
    const testBtns = document.querySelectorAll('.quick-test-btn');

    let html5QrEngine = null;

    if (!searchForm) return;

    const queryEndpointBaseUrl = searchForm.getAttribute('data-lookup-url');

    // 1. Map Quick Test Button Click Events safely
    testBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target-id');
            if (lookupInput) {
                lookupInput.value = targetId;
                executeDocumentLookupQuery(targetId);
            }
        });
    });

    // 1b. Camera Scan Button
    const startScanBtn = document.getElementById('startScanBtn');
    if (startScanBtn) {
        startScanBtn.addEventListener('click', function() {
            handleCameraScannerLifecycle();
        });
    }

    // 2. Intercept manual submission form loop
    searchForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const rawValue = lookupInput ? lookupInput.value.trim() : '';
        if (!rawValue) {
            alert('Please input or scan a valid Tracking Number sequence.');
            return;
        }
        executeDocumentLookupQuery(rawValue);
    });

    /**
     * Dispatch Secure Fetch Operations to backend endpoints
     */
    function executeDocumentLookupQuery(trackingNumber) {
        if (typeof window.showSpinner === 'function') window.showSpinner();

        const standardUrl = `${queryEndpointBaseUrl}?document_number=${encodeURIComponent(trackingNumber)}`;

        fetch(standardUrl, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(async response => {
            const contentType = response.headers.get('content-type') || '';
            if (!response.ok) {
                if (contentType.includes('application/json')) {
                    const err = await response.json();
                    throw new Error(err.message || 'Lookup target evaluation failed.');
                } else {
                    throw new Error(`Server execution exception code: ${response.status}`);
                }
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                renderUnifiedTrackingDashboard(data);
            } else {
                alert(data.message || 'No tracking history matched that identifier.');
            }
        })
        .catch(err => {
            console.error('[Scan Engine Exception]', err);
            alert(`Lookup Denied: ${err.message}`);
        })
        .finally(() => {
            if (typeof window.hideSpinner === 'function') window.hideSpinner();
        });
    }

    /**
     * Main Core Orchestrator: Renders Card layout + Timeline logs simultaneously
     */
    function renderUnifiedTrackingDashboard(data) {
        const userDepartmentId = String(resultWrapper ? resultWrapper.getAttribute('data-user-dept-id') : '');
        const doc = data.document;
        const routes = data.routes || [];
        const events = data.events || [];

        // Reveal the main layout container wrapper grid
        if (resultWrapper) resultWrapper.classList.remove('d-none');

        // --- STRICT SEQUENTIAL VERIFICATION FLAGS ---
        const hasUserDeptProcessed = routes.some(function(route) {
            return String(route.department_id) === userDepartmentId && route.status === 'received';
        });
        const isAuthorizedDepartment = routes.some(function(route) {
            return String(route.department_id) === userDepartmentId && route.status === 'current';
        });

        // --- PART A: BUILD PROTOTYPE CONTEXT ACTION CARD ---
        if (cardContainer && doc) {
            let headerClass, headerIcon, headerTitle, borderClass, statusBadgeClass;
            let actionSlot = '';

            if (hasUserDeptProcessed) {
                headerClass = 'bg-light-warning text-warning-dark';
                headerIcon = 'bi-exclamation-triangle-fill';
                headerTitle = 'Document Already Received';
                borderClass = 'border-warning';
                statusBadgeClass = 'bg-warning text-dark';
                actionSlot = `
                    <div class="alert alert-warning d-flex align-items-center m-0 py-1 px-2 text-xs w-100 border border-warning-subtle rounded">
                        <i class="bi bi-info-circle-fill me-2 text-warning"></i> This sequence tracking step is completed. Ready for downstream routing transfers.
                    </div>
                `;
            } else if (isAuthorizedDepartment) {
                headerClass = 'bg-success text-white';
                headerIcon = 'bi-check-circle-fill';
                headerTitle = 'Document Found';
                borderClass = 'border-success';
                statusBadgeClass = 'bg-light-success text-success';
                actionSlot = `
                    <button type="button" class="btn btn-success btn-sm px-3 shadow-3xs" id="actionConfirmReceiptBtn">
                        <i class="bi bi-check-circle me-1"></i> Confirm Receipt
                    </button>
                `;
            } else {
                headerClass = 'bg-success text-white';
                headerIcon = 'bi-check-circle-fill';
                headerTitle = 'Document Found';
                borderClass = 'border-success';
                statusBadgeClass = 'bg-light-success text-success';
                actionSlot = `
                    <div class="alert alert-info d-flex align-items-center m-0 mb-3 py-2 px-3 text-xs border border-info-subtle rounded">
                        <i class="bi bi-info-circle-fill me-2 text-info"></i> This document requires routing approval. Your department may not be authorized to receive it directly.
                    </div>
                    <button type="button" class="btn btn-warning btn-sm px-3 shadow-3xs text-dark font-semibold" id="actionAttemptReceiptBtn">
                        <i class="bi bi-shield-x me-1"></i> Attempt Receipt
                    </button>
                `;
            }

            cardContainer.innerHTML = `
                <div class="card shadow-sm border-start border-4 ${borderClass} rounded-3">
                    <div class="card-header ${headerClass} d-flex align-items-center justify-content-between py-2">
                        <h6 class="mb-0 font-sans font-bold">
                            <i class="bi ${headerIcon} me-2"></i>
                            ${headerTitle}
                        </h6>
                        <span class="badge font-mono bg-dark text-white text-xxs">${escapeHtml(doc.document_number || doc.id)}</span>
                    </div>
                    <div class="card-body bg-white p-3 text-sm">
                        <div class="row g-2">
                            <div class="col-sm-6"><strong>Title:</strong> <span class="text-secondary">${escapeHtml(doc.title)}</span></div>
                            <div class="col-sm-6"><strong>Type:</strong> <span class="text-secondary">${escapeHtml(doc.document_type_name || 'General File')}</span></div>
                            <div class="col-sm-6"><strong>Sender:</strong> <span class="text-secondary">${escapeHtml(doc.sender_department_name || 'Origin Slot')}</span></div>
                            <div class="col-sm-6"><strong>Current Status:</strong> <span class="badge ${statusBadgeClass} text-xs font-semibold px-2 py-0.5">${escapeHtml(doc.status)}</span></div>
                            <div class="col-11 border-top pt-2 mt-2"><strong>Description:</strong> <p class="text-muted text-xs mb-0 mt-1">${escapeHtml(doc.description || 'No descriptive context log attached.')}</p></div>
                        </div>
                        <div class="d-flex align-items-center gap-2 mt-3 pt-2 border-top">
                            ${actionSlot}
                            <button type="button" class="btn btn-outline-primary btn-sm px-3" id="actionViewFullDetailsBtn">
                                <i class="bi bi-eye me-1"></i> View Full Details
                            </button>
                        </div>
                    </div>
                </div>
            `;

            // Bind Event Listeners to injected card elements
            const confirmBtn = document.getElementById('actionConfirmReceiptBtn');
            if (confirmBtn) {
                confirmBtn.addEventListener('click', function() {
                    executeReceiptTransaction(doc.id || doc.document_number);
                });
            }

            const attemptBtn = document.getElementById('actionAttemptReceiptBtn');
            if (attemptBtn) {
                attemptBtn.addEventListener('click', function() {
                    const modalElement = document.getElementById('routedDocumentErrorModal');
                    if (modalElement) {
                        const bsContext = window.bootstrap || bootstrap;
                        const modalInstance = bsContext.Modal.getOrCreateInstance(modalElement);
                        modalInstance.show();
                    }
                });
            }

            const viewDetailsBtn = document.getElementById('actionViewFullDetailsBtn');
            if (viewDetailsBtn) {
                viewDetailsBtn.addEventListener('click', function() {
                    const targetTrackingNum = doc.document_number || doc.id;
                    if (targetTrackingNum) {
                        window.location.href = '/document-details/' + encodeURIComponent(targetTrackingNum);
                    } else {
                        alert('Unable to extract a valid tracking identifier from this document sequence data matrix.');
                    }
                });
            }
        }

        // --- PART B: RENDER ROUTING PATH MAP ---
        if (routeMapContainer) {
            routeMapContainer.innerHTML = '';
            if (routes.length > 0) {
                routes.forEach(route => {
                    const stepRow = document.createElement('div');
                    let badgeStyle = 'bg-secondary text-white';
                    let rowModifier = 'border-light opacity-75';

                    if (route.status === 'current') {
                        badgeStyle = 'bg-warning text-dark font-bold';
                        rowModifier = 'border-warning bg-light-warning';
                    } else if (route.status === 'received' || route.status === 'completed') {
                        badgeStyle = 'bg-success text-white';
                        rowModifier = 'border-success';
                    }

                    stepRow.className = `d-flex align-items-center justify-content-between p-2 mb-2 rounded border shadow-3xs bg-white ${rowModifier}`;
                    stepRow.innerHTML = `
                        <div class="d-flex align-items-center">
                            <span class="badge ${badgeStyle} rounded-circle me-2 font-mono" style="width:22px; height:22px; display:inline-flex; align-items:center; justify-content:center; text-align:center; font-size:10px;">${route.route_order}</span>
                            <span class="font-medium text-xs text-dark">${escapeHtml(route.department_name)}</span>
                        </div>
                        <span class="badge text-uppercase text-xxs px-2 py-0.5 ${badgeStyle}">${escapeHtml(route.status)}</span>
                    `;
                    routeMapContainer.appendChild(stepRow);
                });
            } else {
                routeMapContainer.innerHTML = '<div class="text-muted text-xs p-2 bg-light rounded text-center">No structural routing paths defined.</div>';
            }
        }

        // --- PART C: RENDER TRANSACTION HISTORY TIMELINE ---
        if (timelineContainer) {
            timelineContainer.innerHTML = '';
            if (events.length > 0) {
                events.forEach(ev => {
                    const card = document.createElement('div');
                    card.className = 'timeline-item border-start ps-3 pb-3 position-relative';
                    card.innerHTML = `
                        <span class="position-absolute start-0 top-0 translate-middle-x badge rounded-circle bg-primary p-1" style="margin-left:-1px; margin-top:4px;"><span class="visually-hidden">.</span></span>
                        <div class="text-xxs text-muted font-mono">${escapeHtml(ev.formatted_date || ev.created_at)}</div>
                        <div class="text-xs font-semibold text-dark mt-0.5">${escapeHtml(ev.event_label)} - <span class="text-primary font-normal">${escapeHtml(ev.execution_department)}</span></div>
                        <p class="text-muted text-xxs mb-0 mt-0.5 bg-light p-1 rounded border">Note: ${escapeHtml(ev.note || 'No transaction notes added.')} <br><span class="text-dark font-medium">By: ${escapeHtml(ev.processed_by_user)}</span></p>
                    `;
                    timelineContainer.appendChild(card);
                });
            } else {
                timelineContainer.innerHTML = '<div class="text-muted text-xs p-3 bg-light rounded text-center">No transactional logging history logs discovered.</div>';
            }
        }
    }

    /**
     * Dispatch Receipt Confirmation to server pipeline
     */
    function executeReceiptTransaction(docId) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        fetch('/documents/confirm-receipt', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ document_id: docId })
        })
        .then(function(response) {
            return response.json();
        })
        .then(function(data) {
            if (data.success) {
                alert('Receipt successfully verified and saved to database!');
                if (lookupInput && lookupInput.value) {
                    executeDocumentLookupQuery(lookupInput.value);
                }
            } else {
                alert('Transaction Error: ' + data.message);
            }
        })
        .catch(function(err) {
            console.error('Network execution failure:', err);
            alert('Critical connection failure during document state modification.');
        });
    }

    /**
     * Build and Trigger Full Document Manifest Modal Details
     */
    function displayFullManifestModal(doc, routes, events) {
        const modalBody = document.getElementById('fullDetailsModalBody');
        const modalElement = document.getElementById('fullDetailsModal');
        
        if (!modalBody || !modalElement) return;

        modalBody.innerHTML = `
            <div class="table-responsive">
                <table class="table table-bordered table-striped text-sm">
                    <tbody>
                        <tr><th style="width:30%">Document Key ID</th><td>${escapeHtml(String(doc.id))}</td></tr>
                        <tr><th>System Identifier</th><td class="font-mono text-primary font-bold">${escapeHtml(doc.document_number || 'N/A')}</td></tr>
                        <tr><th>Document Title</th><td>${escapeHtml(doc.title)}</td></tr>
                        <tr><th>Description</th><td>${escapeHtml(doc.description || 'None')}</td></tr>
                        <tr><th>Total Routing Milestones</th><td>${routes.length} Assigned Departments</td></tr>
                        <tr><th>Total Event Traces Logged</th><td>${events.length} System Records</td></tr>
                    </tbody>
                </table>
            </div>
        `;

        try {
            const bsContext = window.bootstrap || bootstrap;
            const modalInstance = bsContext.Modal.getOrCreateInstance(modalElement);
            modalInstance.show();
        } catch (err) {
            modalElement.style.display = 'block';
            modalElement.classList.add('show');
            document.body.classList.add('modal-open');
        }
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }

    function handleCameraScannerLifecycle() {
        const readerContainer = document.getElementById('qr-reader');
        const placeholderMsg = document.getElementById('qr-placeholder-message');
        if (!readerContainer) return;

        if (html5QrEngine && html5QrEngine.isScanning) {
            html5QrEngine.stop().then(function() {
                readerContainer.innerHTML = '';
                readerContainer.innerHTML = `
                    <div id="qr-placeholder-message" class="text-center p-3 text-muted">
                        <i class="bi bi-camera fs-1 d-block mb-2"></i>
                        <span class="fw-semibold d-block">Camera Stream Ready</span>
                        <small class="text-muted">Click "Start Scan" to initiate device hardware lens</small>
                    </div>`;
            }).catch(function(err) {
                console.error('Error pausing camera engine:', err);
            });
            return;
        }

        readerContainer.innerHTML = '';

        if (!html5QrEngine) {
            html5QrEngine = new Html5Qrcode('qr-reader');
        }

        html5QrEngine.start(
            { facingMode: 'environment' },
            { fps: 25, qrbox: { width: 220, height: 220 } },
            onQrCodeDetected,
            onQrCodeScanError
        )
        .catch(function(err) {
            console.error(err);
            readerContainer.innerHTML = '<div class="p-3 text-danger small">Hardware stream connection failed.</div>';
        });
    }

    function onQrCodeDetected(decodedText) {
        if (html5QrEngine && html5QrEngine.isScanning) {
            html5QrEngine.stop().then(function() {
                const readerContainer = document.getElementById('qr-reader');
                if (readerContainer) {
                    readerContainer.innerHTML = '';
                    readerContainer.innerHTML = `
                        <div id="qr-placeholder-message" class="text-center p-3 text-muted">
                            <i class="bi bi-camera fs-1 d-block mb-2"></i>
                            <span class="fw-semibold d-block">Camera Stream Ready</span>
                            <small class="text-muted">Click "Start Scan" to initiate device hardware lens</small>
                        </div>`;
                }
                html5QrEngine = null;
            });
        }

        if (lookupInput && decodedText) {
            lookupInput.value = decodedText.trim();
            if (typeof executeDocumentLookupQuery === 'function') {
                executeDocumentLookupQuery(decodedText.trim());
            }
        }
    }

    function onQrCodeScanError(error) {
        // Silent to prevent frame-rate stuttering
    }
});
