document.addEventListener("DOMContentLoaded", function () {
    const loginForm = document.querySelector("#login-form");
    
    loginForm.addEventListener("submit", async function (e) {
        e.preventDefault();
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.disabled = true;

        try {
            const formData = new FormData(this);
            const response = await fetch("login.php", {
                method: "POST",
                body: formData
            });

            // Handle empty responses
            const text = await response.text();
            if (!text) {
                throw new Error("Empty response from server");
            }

            // Parse response once
            const data = JSON.parse(text);
            console.log("Server Response:", data);

            if (data.status === "success") {
                // Show success modal
                showLoginSuccessModal(data.message, data.redirect || "homepage.php");
            } else {
                // Show error modal instead of alert
                showLoginErrorModal(data.message || "Login failed");
            }
        } catch (error) {
            console.error("Error:", error);
            showLoginErrorModal("Login error: " + error.message);
        } finally {
            submitBtn.disabled = false;
        }
    });
});

// Login Success Modal Function
function showLoginSuccessModal(message, redirectUrl) {
    // Create modal elements
    const successModal = document.createElement('div');
    successModal.style.position = 'fixed';
    successModal.style.zIndex = '2000';
    successModal.style.left = '0';
    successModal.style.top = '0';
    successModal.style.width = '100%';
    successModal.style.height = '100%';
    successModal.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
    successModal.style.display = 'flex';
    successModal.style.alignItems = 'center';
    successModal.style.justifyContent = 'center';
    
    const modalContent = document.createElement('div');
    modalContent.style.backgroundColor = 'white';
    modalContent.style.color = '#333';
    modalContent.style.padding = '30px 20px';
    modalContent.style.borderRadius = '8px';
    modalContent.style.width = '300px';
    modalContent.style.textAlign = 'center';
    
    // Create success icon (green circle with checkmark)
    const successIcon = document.createElement('div');
    successIcon.innerHTML = `<div style="width: 60px; height: 60px; border-radius: 50%; background-color: #00C851; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px;">
      <svg width="30" height="30" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M5 12L10 17L20 7" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </div>`;
    
    const title = document.createElement('h3');
    title.textContent = 'Success!';
    title.style.marginTop = '0';
    title.style.marginBottom = '10px';
    title.style.fontSize = '24px';
    title.style.fontWeight = 'bold';
    title.style.color = '#333';
    
    const messageText = document.createElement('p');
    messageText.textContent = 'You have successfully logged in to your account';
    messageText.style.marginBottom = '20px';
    messageText.style.color = '#666';
    messageText.style.fontSize = '14px';
    messageText.style.lineHeight = '1.5';
    
    const okButton = document.createElement('button');
    okButton.textContent = 'OKAY';
    okButton.style.padding = '10px 0';
    okButton.style.backgroundColor = '#00C851';
    okButton.style.color = 'white';
    okButton.style.border = 'none';
    okButton.style.borderRadius = '4px';
    okButton.style.cursor = 'pointer';
    okButton.style.width = '100%';
    okButton.style.fontWeight = 'bold';
    okButton.style.textTransform = 'uppercase';
    okButton.style.letterSpacing = '1px';
    
    okButton.onclick = function() {
      document.body.removeChild(successModal);
      // Redirect after clicking OK
      window.location.href = redirectUrl;
    };
    
    modalContent.appendChild(successIcon);
    modalContent.appendChild(title);
    modalContent.appendChild(messageText);
    modalContent.appendChild(okButton);
    successModal.appendChild(modalContent);
    
    document.body.appendChild(successModal);
}

// Login Error Modal Function
function showLoginErrorModal(message) {
    // Create modal elements
    const errorModal = document.createElement('div');
    errorModal.style.position = 'fixed';
    errorModal.style.zIndex = '2000';
    errorModal.style.left = '0';
    errorModal.style.top = '0';
    errorModal.style.width = '100%';
    errorModal.style.height = '100%';
    errorModal.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
    errorModal.style.display = 'flex';
    errorModal.style.alignItems = 'center';
    errorModal.style.justifyContent = 'center';
    
    const modalContent = document.createElement('div');
    modalContent.style.backgroundColor = 'white';
    modalContent.style.color = '#333';
    modalContent.style.padding = '30px 20px';
    modalContent.style.borderRadius = '8px';
    modalContent.style.width = '300px';
    modalContent.style.textAlign = 'center';
    
    // Create error icon (red circle with X)
    const errorIcon = document.createElement('div');
    errorIcon.innerHTML = `<div style="width: 60px; height: 60px; border-radius: 50%; background-color: #FF3547; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px;">
      <svg width="30" height="30" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M18 6L6 18" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M6 6L18 18" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </div>`;
    
    const title = document.createElement('h3');
    title.textContent = 'Sorry :(';
    title.style.marginTop = '0';
    title.style.marginBottom = '10px';
    title.style.fontSize = '24px';
    title.style.fontWeight = 'bold';
    title.style.color = '#FF3547';
    
    const messageText = document.createElement('p');
    messageText.textContent = 'Something went wrong\nplease try again!!';
    messageText.style.whiteSpace = 'pre-line';
    messageText.style.marginBottom = '20px';
    messageText.style.color = '#666';
    messageText.style.fontSize = '14px';
    messageText.style.lineHeight = '1.5';
    
    const tryAgainButton = document.createElement('button');
    tryAgainButton.textContent = 'TRY AGAIN';
    tryAgainButton.style.padding = '10px 0';
    tryAgainButton.style.backgroundColor = '#FF3547';
    tryAgainButton.style.color = 'white';
    tryAgainButton.style.border = 'none';
    tryAgainButton.style.borderRadius = '4px';
    tryAgainButton.style.cursor = 'pointer';
    tryAgainButton.style.width = '100%';
    tryAgainButton.style.fontWeight = 'bold';
    tryAgainButton.style.textTransform = 'uppercase';
    tryAgainButton.style.letterSpacing = '1px';
    
    tryAgainButton.onclick = function() {
      document.body.removeChild(errorModal);
      // Focus on the first input field
      const firstInput = document.querySelector('#email');
      if (firstInput) {
        firstInput.focus();
      }
    };
    
    modalContent.appendChild(errorIcon);
    modalContent.appendChild(title);
    modalContent.appendChild(messageText);
    modalContent.appendChild(tryAgainButton);
    errorModal.appendChild(modalContent);
    
    document.body.appendChild(errorModal);
}

function togglePassword(inputId) {
    const passwordInput = document.getElementById(inputId);
    const eyeIcon = document.getElementById(inputId + '-eye');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        eyeIcon.classList.remove('fa-eye');
        eyeIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        eyeIcon.classList.remove('fa-eye-slash');
        eyeIcon.classList.add('fa-eye');
    }
}