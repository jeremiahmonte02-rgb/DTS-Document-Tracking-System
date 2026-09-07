/**
 * Document Upload & Dynamic Routing Module - Document Tracking System
 * Handles dynamic route orchestration, template building, and resilient uploads.
 */
document.addEventListener('DOMContentLoaded', function() {
    // 1. Core DOM Component Selectors
    const uploadForm = document.getElementById('uploadForm');
    const receiverDeptSelect = document.getElementById('receiverDepartments');
    const addToRouteBtn = document.getElementById('addToRouteBtn');
    const clearRouteBtn = document.getElementById('clearRouteBtn');
    const routeListContainer = document.getElementById('routeList');
    const routesHiddenInput = document.getElementById('routesInput');
    const documentDropdownSelect = document.getElementById('documentSelect');
    const documentTypeSelect = document.getElementById('documentType');
    const doneQrBtn = document.getElementById('doneQrBtn');

    // Policy-aware state used to drive the added-department SLA warning
    let currentPolicyExists = false;
    let currentPolicyIsImmutable = true;
    let currentPolicyPredefinedDeptIds = [];
    let addedDeptWarningDismissed = false;

    if (!uploadForm) return;

    // 2. Extract Data Collections from HTML Canvas attributes
    const storeEndpointUrl = uploadForm.getAttribute('data-store-url');
    let historicalDocumentsCollection = [];
    try {
        historicalDocumentsCollection = JSON.parse(uploadForm.getAttribute('data-existing-documents') || '[]');
    } catch (e) {
        console.error('[Upload Module] Failed parsing data-existing-documents collection', e);
    }

    // Initialize View Dependencies
    populateTemplatesDropdown();
    attachInteractiveListeners();

    /**
     * Map Interactive Click Listeners
     */
    function attachInteractiveListeners() {
        if (addToRouteBtn) addToRouteBtn.addEventListener('click', appendSelectedDepartmentsToChain);
        if (clearRouteBtn) clearRouteBtn.addEventListener('click', wipeRouteChainCanvas);

        // Dismiss the added-department SLA warning without removing it permanently;
        // it will reappear if the route is changed to add departments again.
        const addedDeptWarningClose = document.getElementById('addedDeptWarningClose');
        if (addedDeptWarningClose) {
            addedDeptWarningClose.addEventListener('click', function() {
                addedDeptWarningDismissed = true;
                const warningEl = document.getElementById('addedDeptWarning');
                if (warningEl) warningEl.classList.add('d-none');
            });
        }
        
        // Handle post-upload completion redirection loop
        if (doneQrBtn) {
            doneQrBtn.addEventListener('click', function() {
                window.location.href = '/dashboard';
            });
        }

        // Handle delegated item management actions (Up, Down, Remove) inside the route sequence list container
        if (routeListContainer) {
            routeListContainer.addEventListener('click', function(event) {
                const targetBtn = event.target.closest('button');
                if (!targetBtn) return;

                const listItem = targetBtn.closest('li');
                if (!listItem) return;

                if (targetBtn.classList.contains('remove-step-btn')) {
                    if (listItem.dataset.predefined === 'true') return;
                    const deptId = listItem.getAttribute('data-dept-id');
                    listItem.remove();
                    if (receiverDeptSelect && deptId) {
                        const opt = Array.from(receiverDeptSelect.options).find(o => String(o.value) === String(deptId));
                        if (opt) opt.selected = false;
                        const visualItem = document.querySelector(`#visual-dept-pool li[data-value="${CSS.escape(deptId)}"]`);
                        if (visualItem) {
                            visualItem.classList.remove('bg-success', 'bg-opacity-10', 'text-success', 'fw-semibold');
                            visualItem.style.backgroundColor = '';
                            visualItem.style.color = '';
                        }
                    }
                } else if (targetBtn.classList.contains('move-up-btn')) {
                    if (listItem.dataset.predefined === 'true') return;
                    const previousSibling = listItem.previousElementSibling;
                    if (previousSibling && previousSibling.dataset.predefined === 'true') return;
                    if (previousSibling) listItem.parentNode.insertBefore(listItem, previousSibling);
                } else if (targetBtn.classList.contains('move-down-btn')) {
                    if (listItem.dataset.predefined === 'true') return;
                    const nextSibling = listItem.nextElementSibling;
                    if (nextSibling) listItem.parentNode.insertBefore(listItem, nextSibling);
                }

                synchronizeSerializedRouteInputs();
            });
        }

        if (documentDropdownSelect) {
            documentDropdownSelect.addEventListener('change', function() {
                const docId = this.value;
                const matchedDoc = historicalDocumentsCollection.find(d => String(d.id) === String(docId));
                if (matchedDoc) populateFormFieldsFromTemplate(matchedDoc);
            });
        }

        if (documentTypeSelect) {
            documentTypeSelect.addEventListener('change', async function() {
                wipeRouteChainCanvas();

                const typeId = this.value;
                resetPolicyState();
                if (!typeId) {
                    applyImmutableLockState(false);
                    updateAddedDeptWarning();
                    return;
                }

                try {
                    const response = await fetch(`/api/document-types/${typeId}/policy`);
                    if (!response.ok) throw new Error('Fetch failed');
                    const data = await response.json();

                    currentPolicyExists = data.has_policy === true;
                    currentPolicyIsImmutable = data.is_immutable === true;
                    currentPolicyPredefinedDeptIds = (data.predefined_route || []).map(s => String(s.department_id));

                    if (currentPolicyExists && data.predefined_route && data.predefined_route.length > 0) {
                        wipeRouteChainCanvas();
                        data.predefined_route.forEach(step => {
                            const li = document.createElement('li');
                            li.className = 'list-group-item d-flex justify-content-between align-items-center text-xs p-2 bg-light shadow-2xs mb-1 rounded border';
                            li.setAttribute('data-dept-id', step.department_id);
                            li.setAttribute('data-predefined', 'true');
                            li.innerHTML = `
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-primary index-counter-badge me-2">0</span>
                                    <span class="text-dark font-medium font-mono">${escapeHtml(step.department_name || 'Department')}</span>
                                </div>
                            `;
                            routeListContainer.appendChild(li);
                        });
                        synchronizeSerializedRouteInputs();
                        applyImmutableLockState(currentPolicyIsImmutable);
                    } else {
                        applyImmutableLockState(false);
                    }
                } catch (err) {
                    applyImmutableLockState(false);
                }
                updateAddedDeptWarning();
            });
        }

        uploadForm.addEventListener('submit', executeMultipartFormUpload);
    }

    /**
     * Resilient Full-Screen Loading Overlay Controllers
     */
    function showLoadingSpinner() {
        if (typeof window.showSpinner === 'function') {
            window.showSpinner();
        } else {
            const spinner = document.getElementById('loadingOverlay') || document.getElementById('spinner') || document.querySelector('.spinner-overlay');
            if (spinner) spinner.classList.remove('d-none');
        }
    }

    function hideLoadingSpinner() {
        if (typeof window.hideSpinner === 'function') {
            window.hideSpinner();
        } else {
            const spinner = document.getElementById('loadingOverlay') || document.getElementById('spinner') || document.querySelector('.spinner-overlay');
            if (spinner) spinner.classList.add('d-none');
        }
    }

    /**
     * Parse Selected Dropdown Options into UI Elements
     */
    function appendSelectedDepartmentsToChain() {
        if (!receiverDeptSelect || !routeListContainer) return;

        const selectedOptions = Array.from(receiverDeptSelect.selectedOptions);
        if (selectedOptions.length === 0) {
            alert('Please select one or more departments from the listbox first.');
            return;
        }

        selectedOptions.forEach(option => {
            const existingMatch = routeListContainer.querySelector(`li[data-dept-id="${option.value}"]`);
            if (existingMatch) return;

            const li = document.createElement('li');
            li.className = 'list-group-item d-flex justify-content-between align-items-center text-xs p-2 bg-light shadow-2xs mb-1 rounded border';
            li.setAttribute('data-dept-id', option.value);
            li.setAttribute('data-predefined', 'false');
            li.innerHTML = `
                <div class="d-flex align-items-center">
                    <span class="badge bg-primary index-counter-badge me-2">0</span>
                    <span class="text-dark font-medium font-mono">${escapeHtml(option.text)}</span>
                </div>
                <div class="d-flex gap-1" role="group">
                    <button type="button" class="btn btn-white btn-sm move-up-btn" title="Move Up"><i class="bi bi-arrow-up"></i></button>
                    <button type="button" class="btn btn-white btn-sm move-down-btn" title="Move Down"><i class="bi bi-arrow-down"></i></button>
                    <button type="button" class="btn btn-danger btn-sm remove-step-btn" title="Remove"><i class="bi bi-trash"></i></button>
                </div>
            `;
            routeListContainer.appendChild(li);
        });

        synchronizeSerializedRouteInputs();
    }

    function wipeRouteChainCanvas() {
        if (routeListContainer) routeListContainer.innerHTML = '';
        if (routesHiddenInput) routesHiddenInput.value = '';
        if (receiverDeptSelect) {
            Array.from(receiverDeptSelect.options).forEach(opt => opt.selected = false);
        }
        document.querySelectorAll('#visual-dept-pool .list-group-item').forEach(item => {
            item.classList.remove('bg-success', 'bg-opacity-10', 'text-success', 'fw-semibold');
            item.style.backgroundColor = '';
            item.style.color = '';
        });
    }

    function synchronizeSerializedRouteInputs() {
        if (!routeListContainer || !routesHiddenInput) return;

        const listItems = routeListContainer.querySelectorAll('li');
        const serializedDataArr = [];

        listItems.forEach((li, index) => {
            const deptId = li.getAttribute('data-dept-id');
            const stepOrderNumber = index + 1;
            
            const counterBadge = li.querySelector('.index-counter-badge');
            if (counterBadge) counterBadge.innerText = stepOrderNumber;

            serializedDataArr.push({
                department_id: parseInt(deptId),
                route_order: stepOrderNumber
            });
        });

        routesHiddenInput.value = JSON.stringify(serializedDataArr);
        updateAddedDeptWarning();
    }

    function resetPolicyState() {
        currentPolicyExists = false;
        currentPolicyIsImmutable = true;
        currentPolicyPredefinedDeptIds = [];
        addedDeptWarningDismissed = false;
    }

    // Shows an informational warning when the current route contains departments that were
    // added beyond the document type's predefined route and the policy is Mutable. Added
    // departments fall back to standard processing-time settings (a 30-minute default unless a
    // specific DepartmentDocumentSla override exists), not the document type's lifecycle SLA.
    function updateAddedDeptWarning() {
        const warningEl = document.getElementById('addedDeptWarning');
        if (!warningEl) return;

        if (!routeListContainer) {
            warningEl.classList.add('d-none');
            return;
        }

        let hasAddedDept = false;
        routeListContainer.querySelectorAll('li').forEach(li => {
            const deptId = li.getAttribute('data-dept-id');
            if (deptId && currentPolicyPredefinedDeptIds.indexOf(String(deptId)) === -1) {
                hasAddedDept = true;
            }
        });

        const shouldShow = hasAddedDept && currentPolicyExists && !currentPolicyIsImmutable;
        if (!shouldShow) {
            warningEl.classList.add('d-none');
            addedDeptWarningDismissed = false;
        } else if (!addedDeptWarningDismissed) {
            warningEl.classList.remove('d-none');
        }
    }

    function applyImmutableLockState(isImmutable) {
        const deptPool = document.getElementById('visual-dept-pool');
        const policyNotice = document.getElementById('immutablePolicyNotice');
        if (deptPool) deptPool.style.display = isImmutable ? 'none' : '';
        if (policyNotice) policyNotice.classList.toggle('d-none', !isImmutable);
        if (addToRouteBtn) addToRouteBtn.style.display = isImmutable ? 'none' : '';
        if (clearRouteBtn) clearRouteBtn.style.display = isImmutable ? 'none' : '';

        if (routeListContainer) {
            routeListContainer.querySelectorAll('.move-up-btn, .move-down-btn, .remove-step-btn').forEach(btn => {
                btn.style.display = isImmutable ? 'none' : '';
            });
        }
    }

    function populateTemplatesDropdown() {
        if (!documentDropdownSelect) return;
        documentDropdownSelect.innerHTML = '<option value="">-- Choose Existing Template File --</option>';
        
        historicalDocumentsCollection.forEach(doc => {
            const opt = document.createElement('option');
            opt.value = doc.id;
            opt.textContent = `${doc.document_number || 'DOC'} - ${doc.title}`;
            documentDropdownSelect.appendChild(opt);
        });
    }

    function populateFormFieldsFromTemplate(doc) {
        const titleEl = document.getElementById('title');
        const typeEl = document.getElementById('documentType');
        const descEl = document.getElementById('description');

        if (titleEl) titleEl.value = doc.title || '';
        if (typeEl) typeEl.value = doc.document_type_id || '';
        if (descEl) descEl.value = doc.description || '';

        wipeRouteChainCanvas();

        if (doc.routes && doc.routes.length > 0 && routeListContainer) {
            const sortedRoutes = [].concat(doc.routes).sort((a, b) => a.route_order - b.route_order);
            
            sortedRoutes.forEach(route => {
                const li = document.createElement('li');
                li.className = 'list-group-item d-flex justify-content-between align-items-center text-xs p-2 bg-light shadow-2xs mb-1 rounded border';
                li.setAttribute('data-dept-id', route.department_id);
                li.innerHTML = `
                    <div class="d-flex align-items-center">
                        <span class="badge bg-primary index-counter-badge me-2">${route.route_order}</span>
                        <span class="text-dark font-medium font-mono">${escapeHtml(route.department_name || 'Department Ref')}</span>
                    </div>
                    <div class="d-flex gap-1" role="group">
                        <button type="button" class="btn btn-white btn-sm move-up-btn"><i class="bi bi-arrow-up"></i></button>
                        <button type="button" class="btn btn-white btn-sm move-down-btn"><i class="bi bi-arrow-down"></i></button>
                        <button type="button" class="btn btn-danger btn-sm remove-step-btn"><i class="bi bi-trash"></i></button>
                    </div>
                `;
                routeListContainer.appendChild(li);
            });
            synchronizeSerializedRouteInputs();
        }
    }

    /**
     * Execute high-integrity multi-part binary file uploads to the server
     */
    function executeMultipartFormUpload(event) {
        event.preventDefault();

        const submitBtn = uploadForm.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Uploading...';
        }

        if (!routesHiddenInput || !routesHiddenInput.value || JSON.parse(routesHiddenInput.value).length === 0) {
            alert('Validation Denied: You must attach at least one department destination routing step to this tracking sequence.');
            return;
        }

        showLoadingSpinner();
        const payloadFormDataStream = new FormData(uploadForm);

        fetch(storeEndpointUrl, {
            method: 'POST',
            body: payloadFormDataStream,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(async response => {
            const contentType = response.headers.get('content-type') || '';
            
            // Defensively catch server crashes (like 500 error logs or HTML redirects)
            if (!response.ok) {
                if (contentType.includes('application/json')) {
                    const errData = await response.json();
                    throw new Error(errData.message || 'Server rejected transaction processing request.');
                } else {
                    const textError = await response.text();
                    console.error('[Backend Crash Log]', textError);
                    throw new Error(`Server Fault Exception [${response.status}]. Check your local docker logs.`);
                }
            }

            if (!contentType.includes('application/json')) {
                throw new Error('Invalid Server Response: Expected clean JSON object data streams but received HTML layout payload blocks.');
            }

            return response.json();
        })
        .then(data => {
            if (data.success) {
                const docId = data.document_number || data.id;
                const gDocIdField = document.getElementById('generatedDocId');
                if (gDocIdField) gDocIdField.innerText = docId;

                const docNumber = data.document_number;

                const qrCanvasTarget = document.getElementById('modalQrCode');
                if (qrCanvasTarget && typeof QRCode === 'function') {
                    qrCanvasTarget.innerHTML = '';
                    new QRCode(qrCanvasTarget, {
                        text: data.document_number || data.id,
                        width: 160,
                        height: 160
                    });
                }

                const qrModalElement = document.getElementById('qrCodeModal');
                if (qrModalElement) {
                    try {
                        const bootstrapContext = window.bootstrap || bootstrap;
                        const modalInstance = bootstrapContext.Modal.getOrCreateInstance(qrModalElement);
                        modalInstance.show();
                    } catch (mErr) {
                        qrModalElement.style.display = 'block';
                        qrModalElement.classList.add('show');
                        document.body.classList.add('modal-open');
                    }
                } else {
                    alert(`Document Saved! Tracking Number assigned: ${data.document_number || data.id}`);
                    window.location.href = '/dashboard';
                }
            } else {
                alert(`Transaction Error: ${data.message || 'Unknown backend validation state.'}`);
            }
        })
        .catch(err => {
            console.error('[Upload System Fault Trace]', err);
            alert(`Upload Denied: ${err.message}`);
        })
        .finally(() => {
            hideLoadingSpinner();
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="bi bi-cloud-upload"></i> Upload Document';
            }
        });
    }

    function escapeHtml(str) {
        return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }
});

// Target the specific structural modal container element to bypass parent event bubbling traps
var successModalContainer = document.getElementById('qrCodeModal');

if (successModalContainer) {
    successModalContainer.addEventListener('click', function (e) {
        // Gracefully find the button element even if the user clicks on an internal text or icon layer
        const viewBtn = e.target.closest('#modalViewDetailsBtn');
        const printBtn = e.target.closest('#modalPrintQrBtn');

        // --- FIX BUG #3: View Details Button ---
        if (viewBtn) {
            e.preventDefault();

            const docRefElement = document.querySelector('.modal-body strong, #generatedDocId');
            let docNumber = '';

            if (docRefElement) {
                docNumber = docRefElement.textContent.replace('Document Reference:', '').trim();
            }

            if (docNumber) {
                window.location.href = `/document-details/${encodeURIComponent(docNumber)}`;
            } else {
                console.error("Failed to read document registration sequence ID from layout.");
            }
        }

        // --- FIX BUG #2: Print QR Code Button ---
        if (printBtn) {
            e.preventDefault();

            const qrContainer = document.querySelector('.qr-code-container') || document.querySelector('.modal-body .text-center');
            if (!qrContainer) {
                console.error("Print source container element was not found in the modal window context.");
                return;
            }

            const printWindow = window.open('', '_blank', 'width=600,height=600');
            if (!printWindow) {
                alert("Please enable window popups to print.");
                return;
            }

            printWindow.document.write(`
                <html>
                <head>
                    <title>Print QR Code</title>
                    <style>
                        body { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100vh; margin: 0; font-family: sans-serif; }
                        img, canvas { max-width: 250px; height: auto; margin-bottom: 15px; }
                        div { font-size: 20px; font-weight: bold; color: #333; }
                    </style>
                </head>
                <body>
                    ${qrContainer.innerHTML}
                    <script>
                        window.onload = function() { window.print(); window.close(); };
                    <\/script>
                </body>
                </html>
            `);
            printWindow.document.close();
        }
    });
} else {
    console.error("CRITICAL ERROR: Could not find any modal wrapper structure target in the DOM layout.");
}
