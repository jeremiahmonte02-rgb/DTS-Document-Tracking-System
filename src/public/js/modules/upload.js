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
    const doneQrBtn = document.getElementById('doneQrBtn');

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
                    listItem.remove();
                } else if (targetBtn.classList.contains('move-up-btn')) {
                    const previousSibling = listItem.previousElementSibling;
                    if (previousSibling) listItem.parentNode.insertBefore(listItem, previousSibling);
                } else if (targetBtn.classList.contains('move-down-btn')) {
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
            li.innerHTML = `
                <div class="d-flex align-items-center">
                    <span class="badge bg-primary index-counter-badge me-2">0</span>
                    <span class="text-dark font-medium font-mono">${escapeHtml(option.text)}</span>
                </div>
                <div class="btn-group shadow-3xs" role="group">
                    <button type="button" class="btn btn-white btn-xs move-up-btn" title="Move Up"><i class="bi bi-arrow-up"></i></button>
                    <button type="button" class="btn btn-white btn-xs move-down-btn" title="Move Down"><i class="bi bi-arrow-down"></i></button>
                    <button type="button" class="btn btn-danger btn-xs remove-step-btn" title="Remove"><i class="bi bi-trash"></i></button>
                </div>
            `;
            routeListContainer.appendChild(li);
        });

        synchronizeSerializedRouteInputs();
    }

    function wipeRouteChainCanvas() {
        if (routeListContainer) routeListContainer.innerHTML = '';
        if (routesHiddenInput) routesHiddenInput.value = '';
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
        const deptEl = document.getElementById('department');
        const descEl = document.getElementById('description');

        if (titleEl) titleEl.value = doc.title || '';
        if (typeEl) typeEl.value = doc.document_type_id || '';
        if (deptEl) deptEl.value = doc.sender_department_id || '';
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
                    <div class="btn-group shadow-3xs" role="group">
                        <button type="button" class="btn btn-white btn-xs move-up-btn"><i class="bi bi-arrow-up"></i></button>
                        <button type="button" class="btn btn-white btn-xs move-down-btn"><i class="bi bi-arrow-down"></i></button>
                        <button type="button" class="btn btn-danger btn-xs remove-step-btn"><i class="bi bi-trash"></i></button>
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
                const gDocIdField = document.getElementById('generatedDocId');
                if (gDocIdField) gDocIdField.innerText = data.document_number || data.id;

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
                        console.warn('Bootstrap context mismatch, initializing manual visibility toggles:', mErr);
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
        });
    }

    function escapeHtml(str) {
        return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }
});
