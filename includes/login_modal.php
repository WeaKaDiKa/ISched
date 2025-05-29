<?php
// Login modal component
?>

<!-- Login Modal -->
<div id="loginModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
    <div class="bg-white rounded-lg shadow-lg w-80 max-w-md overflow-hidden">
        <div id="modalContent" class="text-center p-6">
            <!-- Success Content -->
            <div id="successContent" class="hidden">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4">
                    <svg class="h-10 w-10 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">SUCCESS</h3>
                <p id="successMessage" class="text-sm text-gray-500">We are delighted to inform you that we received your payment.</p>
            </div>
            
            <!-- Error Content -->
            <div id="errorContent" class="hidden">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-4">
                    <svg class="h-10 w-10 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">ERROR</h3>
                <p id="errorMessage" class="text-sm text-gray-500">Unfortunately we have an issue with your payment, try again later.</p>
            </div>
        </div>
        
        <div class="bg-gray-50 px-4 py-3 flex justify-center">
            <button id="modalContinueBtn" type="button" class="w-full max-w-xs inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-500 text-base font-medium text-white hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Continue
            </button>
        </div>
    </div>
</div>

<script>
    function showLoginModal(type, message, redirectUrl = null) {
        // Hide both content types initially
        document.getElementById('successContent').classList.add('hidden');
        document.getElementById('errorContent').classList.add('hidden');
        
        // Show the appropriate content
        if (type === 'success') {
            document.getElementById('successContent').classList.remove('hidden');
            document.getElementById('successMessage').textContent = message;
            document.getElementById('modalContinueBtn').classList.remove('bg-red-500', 'hover:bg-red-600');
            document.getElementById('modalContinueBtn').classList.add('bg-green-500', 'hover:bg-green-600');
        } else {
            document.getElementById('errorContent').classList.remove('hidden');
            document.getElementById('errorMessage').textContent = message;
            document.getElementById('modalContinueBtn').classList.remove('bg-green-500', 'hover:bg-green-600');
            document.getElementById('modalContinueBtn').classList.add('bg-red-500', 'hover:bg-red-600');
        }
        
        // Show the modal
        document.getElementById('loginModal').classList.remove('hidden');
        
        // Set up the continue button
        const continueBtn = document.getElementById('modalContinueBtn');
        continueBtn.onclick = function() {
            document.getElementById('loginModal').classList.add('hidden');
            if (redirectUrl) {
                window.location.href = redirectUrl;
            }
        };
    }
</script>
