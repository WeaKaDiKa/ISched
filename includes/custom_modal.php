<?php
// custom-modal.php
?>
<!-- Bootstrap 5 Modal -->
<div class="modal fade" id="customModal" tabindex="-1" aria-labelledby="modalTitle" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Notification</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <p id="modalMessage" class="modal-message"></p>
            </div>

            <div class="modal-footer">
                <button id="modalOkButton" type="button" class="btn btn-primary">OK</button>
                <button id="modalCancelButton" type="button" class="btn btn-secondary d-none">Cancel</button>
            </div>

        </div>
    </div>
</div>

<script>
    const customModal = (() => {
        const modalEl = document.getElementById('customModal');
        const bsModal = new bootstrap.Modal(modalEl);

        const titleEl = document.getElementById('modalTitle');
        const messageEl = document.getElementById('modalMessage');
        const okBtn = document.getElementById('modalOkButton');
        const cancelBtn = document.getElementById('modalCancelButton');

        return {
            show: function (title, message, options = {}) {
                // Set title and message
                titleEl.textContent = title || 'Notification';
                messageEl.textContent = message || '';

                // Setup OK button
                okBtn.textContent = options.okText || 'OK';
                okBtn.onclick = () => {
                    if (typeof options.onOk === 'function') {
                        options.onOk();
                    }
                    if (options.redirectUrl) {
                        window.location.href = options.redirectUrl;
                    } else {
                        bsModal.hide();
                    }
                };

                // Setup Cancel button
                if (options.showCancel) {
                    cancelBtn.classList.remove('d-none');
                    cancelBtn.textContent = options.cancelText || 'Cancel';
                    cancelBtn.onclick = () => {
                        if (typeof options.onCancel === 'function') {
                            options.onCancel();
                        }
                        bsModal.hide();
                    };
                } else {
                    cancelBtn.classList.add('d-none');
                    cancelBtn.onclick = null;
                }

                bsModal.show();
            },
            close: function () {
                bsModal.hide();
            }
        };
    })();

    // Make globally available
    window.customModal = customModal;
</script>