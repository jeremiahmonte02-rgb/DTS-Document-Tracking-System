<!-- Routed Document Error Modal -->
<div class="modal fade" id="routedDocumentErrorModal" tabindex="-1" aria-labelledby="routedDocumentErrorLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="routedDocumentErrorLabel">
                    <i class="bi bi-shield-x"></i> Access Denied
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <i class="bi bi-exclamation-triangle-fill text-danger" style="font-size: 3rem;"></i>
                    <h5 class="mt-3">Department Not Involved</h5>
                    <p class="text-muted">
                        Your department is not involved in the routing process for this document.
                        This document requires specific departmental approval before it can be received.
                    </p>
                    <div class="alert alert-info">
                        <strong>Note:</strong> Please contact the appropriate department or document sender for routing approval.
                    </div>
                    <p class="mb-0">
                        You can still view the document details but cannot confirm receipt at this time.
                    </p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle"></i> Close
                </button>
            </div>
        </div>
    </div>
</div>
