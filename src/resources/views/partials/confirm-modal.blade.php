<!-- Reusable confirmation modal (shared component, single global include). -->
<div class="modal fade" id="globalConfirmModal" tabindex="-1" aria-labelledby="globalConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header confirm-modal-header">
                <div class="d-flex align-items-center gap-2">
                    <span class="confirm-modal-icon variant-success">
                        <i class="bi bi-check-circle"></i>
                    </span>
                    <h5 class="modal-title mb-0" id="globalConfirmModalLabel"></h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body confirm-modal-body variant-success">
                <p class="mb-0 confirm-modal-message"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success confirm-modal-btn">Confirm</button>
            </div>
        </div>
    </div>
</div>