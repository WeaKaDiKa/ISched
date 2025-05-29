<?php
// This file contains a reusable custom modal component
// It should be included in pages that need to show modal dialogs
?>

<!-- Custom Modal -->
<div id="customModal" class="custom-modal">
    <div class="modal-content">
        <h3 id="modalTitle" class="modal-title">Notification</h3>
        <p id="modalMessage" class="modal-message"></p>
        <div class="modal-buttons">
            <button id="modalOkButton" class="modal-btn btn-ok" onclick="closeCustomModal()">OK</button>
        </div>
    </div>
</div>

<style>
    /* Modal styles */
    .custom-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 1000;
        justify-content: center;
        align-items: center;
    }
    
    .modal-content {
        background-color: #fff;
        border-radius: 8px;
        max-width: 500px;
        width: 90%;
        padding: 20px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        text-align: center;
    }
    
    .modal-title {
        font-size: 20px;
        margin-bottom: 15px;
        color: #2196F3;
    }
    
    .modal-message {
        margin-bottom: 20px;
        font-size: 16px;
    }
    
    .modal-buttons {
        display: flex;
        justify-content: center;
    }
    
    .modal-btn {
        padding: 10px 20px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-weight: bold;
        margin: 0 5px;
        transition: background-color 0.3s;
    }
    
    .btn-ok {
        background-color: #2196F3;
        color: white;
    }
    
    .btn-ok:hover {
        background-color: #0b7dda;
    }
</style>

<script>
    // Function to show the custom modal
    function showCustomModal(title, message, redirectUrl = null) {
        document.getElementById('modalTitle').textContent = title || 'Notification';
        document.getElementById('modalMessage').textContent = message;
        document.getElementById('customModal').style.display = 'flex';
        
        // Set up redirect if URL is provided
        if (redirectUrl) {
            document.getElementById('modalOkButton').onclick = function() {
                closeCustomModal();
                window.location.href = redirectUrl;
            };
        } else {
            document.getElementById('modalOkButton').onclick = closeCustomModal;
        }
    }
    
    // Function to close the custom modal
    function closeCustomModal() {
        document.getElementById('customModal').style.display = 'none';
    }
    
    // Close the modal if clicked outside
    window.addEventListener('click', function(event) {
        const modal = document.getElementById('customModal');
        if (event.target === modal) {
            closeCustomModal();
        }
    });
</script>
