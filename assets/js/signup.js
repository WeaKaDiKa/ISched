// --- Cascading Address Dropdown Integration with Debugging ---
// Uses jQuery for AJAX and DOM manipulation
// JSON files are in assets/js/integrate/ relative to signup.php

$(document).ready(function () {
    // Load Regions
    function loadRegions() {
        $.getJSON('assets/js/integrate/refregion.json', function (data) {
            const regionSelect = $('#region');
            regionSelect.empty().append('<option value="">Select a Region</option>');
            $.each(data, function (index, region) {
                regionSelect.append('<option value="' + region.region_id + '">' + region.region_description + '</option>');
            });
        }).fail(function (jqxhr, textStatus, error) {
            console.error('Failed to load regions:', textStatus, error);
        });
    }
    // Load Provinces based on Region
    function loadProvinces(regionId) {
        $('#province').empty().append('<option value="">Select a Province</option>');
        $('#city').empty().append('<option value="">Select a City/Municipality</option>');
        $('#barangay').empty().append('<option value="">Select a Barangay</option>');
        if (regionId) {
            $.getJSON('assets/js/integrate/refprovince.json', function (data) {
                let found = false;
                console.log('Selected regionId:', regionId);
                console.log('Provinces loaded:', data);
                $.each(data, function (index, province) {
                    console.log('Checking province:', province);
                    if (String(province.region_id) === String(regionId)) {
                        $('#province').append('<option value="' + province.province_id + '">' + province.province_name + '</option>');
                        found = true;
                    }
                });
                if (!found) { console.warn('No provinces found for region:', regionId); }
            }).fail(function (jqxhr, textStatus, error) {
                console.error('Failed to load provinces:', textStatus, error);
            });
        }
    }
    // Load Cities based on Province
    function loadCities(provinceId) {
        $('#city').empty().append('<option value="">Select a City/Municipality</option>');
        $('#barangay').empty().append('<option value="">Select a Barangay</option>');
        if (provinceId) {
            $.getJSON('assets/js/integrate/refcity.json', function (data) {
                let found = false;
                console.log('Selected provinceId:', provinceId);
                console.log('Cities loaded:', data);
                $.each(data, function (index, city) {
                    console.log('Checking city:', city);
                    if (String(city.province_id) === String(provinceId)) {
                        $('#city').append('<option value="' + city.municipality_id + '">' + city.municipality_name + '</option>');
                        found = true;
                    }
                });
                if (!found) { console.warn('No cities found for province:', provinceId); }
            }).fail(function (jqxhr, textStatus, error) {
                console.error('Failed to load cities:', textStatus, error);
            });
        }
    }
    // Load Barangays based on City
    function loadBarangays(cityId) {
        $('#barangay').empty().append('<option value="">Select a Barangay</option>');
        if (cityId) {
            $.getJSON('assets/js/integrate/refbrgy.json', function (data) {
                let found = false;
                console.log('Selected cityId:', cityId);
                console.log('Barangays loaded:', data);
                $.each(data, function (index, brgy) {
                    console.log('Checking barangay:', brgy);
                    if (String(brgy.municipality_id) === String(cityId)) {//from brgyid
                        $('#barangay').append('<option value="' + brgy.barangay_id + '">' + brgy.barangay_name + '</option>');
                        found = true;
                    }
                });
                if (!found) { console.warn('No barangays found for city:', cityId); }
            }).fail(function (jqxhr, textStatus, error) {
                console.error('Failed to load barangays:', textStatus, error);
            });
        }
    }
    // Event listeners
    $('#region').on('change', function () {
        const val = $(this).val();
        console.log('Region selected:', val);
        loadProvinces(val);
    });
    $('#province').on('change', function () {
        const val = $(this).val();
        console.log('Province selected:', val);
        loadCities(val);
    });
    $('#city').on('change', function () {
        const val = $(this).val();
        console.log('City selected:', val);
        loadBarangays(val);
    });
    // Initial load
    loadRegions();

    // // Helper for AJAX requests
    // function postJSON(url, data, success, error) {
    //     $.ajax({
    //         url: url,
    //         type: 'POST',
    //         data: data,
    //         dataType: 'json',
    //         success: success,
    //         error: error || function(xhr) {
    //             alert('An error occurred: ' + (xhr.responseJSON?.message || xhr.statusText));
    //         }
    //     });
    // }

    // --- OTP AJAX Modal Logic ---
    // Handle signup form submit via AJAX
    $('#signupForm').on('submit', function (e) {
        e.preventDefault();

        // Client-side validation
        if (!validateForm()) {
            return false;
        }

        $.ajax({
            url: 'signup.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function (response) {
                if (response.status === 'success') {
                    $('#otpEmail').val($('#signupForm input[name="email"]').val());
                    $('#otpMessage').html('<div class="alert alert-success">OTP sent successfully! Please check your email.</div>');
                    $('#otpModal').modal('show');
                } else {
                    // If signup fails, show error message without the modal
                    alert(response.message); 
                    // You might want to display this message in a dedicated error area on the signup page
                    // instead of using alert for a better user experience.
                }
            },
            error: function (jqxhr, textStatus, errorThrown) {
                 // Improved error handling for signup AJAX
                let errorMessage = 'An error occurred during signup.';
                 if (jqxhr.responseJSON && jqxhr.responseJSON.message) {
                     errorMessage = jqxhr.responseJSON.message;
                 } else if (textStatus) {
                     errorMessage = textStatus;
                 } else if (errorThrown) {
                     errorMessage = errorThrown;
                 }
                alert(errorMessage);
                 // You might want to display this message in a dedicated error area on the signup page.
            }
        });
    });

    // OTP Modal handling
    $('#closeOtpModal').click(function () {
        $('#otpModal').modal('hide'); // Use Bootstrap's hide method
    });

    $(window).click(function (e) {
        if (e.target == $('#otpModal')[0]) {
            $('#otpModal').modal('hide'); // Use Bootstrap's hide method
        }
    });

    // Timer variable
    let countdownTimer;
    const timerDuration = 120; // 120 seconds

    // Function to start the timer
    function startTimer(duration, display) {
        let timer = duration;
        countdownTimer = setInterval(function () {
            const minutes = parseInt(timer / 60, 10);
            const seconds = parseInt(timer % 60, 10);

            display.text(minutes + ":" + (seconds < 10 ? "0" : "") + seconds);

            if (--timer < 0) {
                clearInterval(countdownTimer);
                display.text("OTP Expired");
                // Optionally disable the verify button and show a message
                $('#otpForm button[type="submit"]').prop('disabled', true).text('Expired');
                $('#resendOtpLink').show(); // Show resend link
            }
        }, 1000);
    }

    // When the OTP modal is shown
    $('#otpModal').on('shown.bs.modal', function () {
        console.log('OTP modal shown event triggered.');
        // Reset and start the timer when modal is fully visible
        $('#otpForm button[type="submit"]').prop('disabled', false).text('Verify');
        $('#resendOtpLink').hide(); // Hide resend link initially
        clearInterval(countdownTimer); // Clear any existing timer
        const display = $('#otpTimer');
        console.log('Starting timer with display element:', display);
        startTimer(timerDuration, display);
    });

    // Clear timer when modal is hidden
    $('#otpModal').on('hidden.bs.modal', function () {
        console.log('OTP modal hidden event triggered. Clearing timer.');
        clearInterval(countdownTimer);
    });

    $('#otpForm').on('submit', function (e) {
        e.preventDefault();

        $.ajax({
            url: 'verify_otp.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function (response) {
                if (response.status === 'success') {
                    $('#otpMessage').html('<div class="alert alert-success">Email verified successfully! Redirecting to login...</div>');
                    // Hide modal before redirecting
                    $('#otpModal').modal('hide'); 
                    setTimeout(function () {
                        window.location.href = 'login.php';
                    }, 2000);
                } else {
                    $('#otpMessage').html('<div class="alert alert-danger">' + response.message + '</div>');
                }
            },
            error: function (jqxhr, textStatus, errorThrown) {
                // Access error message from jqxhr.responseJSON or textStatus
                let errorMessage = 'An error occurred during OTP verification.';
                if (jqxhr.responseJSON && jqxhr.responseJSON.message) {
                    errorMessage = jqxhr.responseJSON.message;
                } else if (textStatus) {
                    errorMessage = textStatus;
                } else if (errorThrown) {
                     errorMessage = errorThrown;
                 }
                $('#otpMessage').html('<div class="alert alert-danger">' + errorMessage + '</div>');
            }
        });
    });

    $('#resendOtpLink').click(function (e) {
        e.preventDefault();

        $.ajax({
            url: 'resend_otp.php',
            type: 'POST',
            data: { email: $('#otpEmail').val() },
            dataType: 'json',
            success: function (response) {
                if (response.status === 'success') {
                    $('#otpMessage').html('<div class="alert alert-success">New OTP sent successfully!</div>');
                } else {
                    $('#otpMessage').html('<div class="alert alert-danger">' + response.message + '</div>');
                }
            },
            error: function () {
                $('#otpMessage').html('<div class="alert alert-danger">Failed to resend OTP. Please try again.</div>');
            }
        });
    });
});

function validateForm() {
    const password = $('input[name="password"]').val();
    const confirmPassword = $('input[name="confirm_password"]').val();
    const phone = $('input[name="phone_number"]').val();
    const zipCode = $('input[name="zip_code"]').val();
    const email = $('input[name="email"]').val();
    const dob = new Date($('input[name="date_of_birth"]').val());
    const today = new Date();

    // Password validation temporarily disabled
    /*if (password.length < 8 || !/[A-Z]/.test(password) || !/[a-z]/.test(password) || !/[0-9]/.test(password)) {
        alert('Password must be at least 8 characters and contain uppercase, lowercase, and numbers');
        return false;
    }*/

    // Confirm password check
    if (password !== confirmPassword) {
        alert('Passwords do not match');
        return false;
    }

    // Phone number validation (Philippine format)
    if (!/^(09|\+639)\d{9}$/.test(phone)) {
        alert('Invalid phone number format. Use 09XXXXXXXXX or +639XXXXXXXXX');
        return false;
    }

    // ZIP code validation (Philippine format)
    if (!/^\d{4}$/.test(zipCode)) {
        alert('Invalid ZIP code format. Must be 4 digits');
        return false;
    }

    // Email validation
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        alert('Invalid email format');
        return false;
    }

    // Age validation (must be 18+)
    const age = today.getFullYear() - dob.getFullYear();
    const monthDiff = today.getMonth() - dob.getMonth();
    if (age < 18 || (age === 18 && monthDiff < 0)) {
        alert('You must be at least 18 years old to register');
        return false;
    }

    return true;
}
