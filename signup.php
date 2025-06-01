<?php
require_once 'db.php'; // Include database connection
require_once 'mailfunction.php';

function isEmailRegistered($conn, $email)
{
    $stmt = $conn->prepare("SELECT id FROM patients WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();
    return $exists;
}

function isEmailPending($conn, $email)
{
    $stmt = $conn->prepare("SELECT id FROM pending_patients WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();
    return $exists;
}

function validatePhoneNumber($phone)
{
    return preg_match("/^(09|\\+639)\\d{9}$/", $phone);
}

function validateZipCode($zip)
{
    return preg_match("/^\\d{4}$/", $zip); // Philippine ZIP code format
}

// Helper function to capitalize names
function capitalizeNames($name)
{
    // Split the name by spaces
    $parts = explode(' ', trim($name));
    // Capitalize first letter of each part
    $parts = array_map(function ($part) {
        return ucfirst(strtolower($part));
    }, $parts);
    // Join the parts back together
    return implode(' ', $parts);
}

function validatePassword($password)
{
    // At least 8 characters, 1 uppercase, 1 lowercase, 1 number
    return strlen($password) >= 8 &&
        preg_match("/[A-Z]/", $password) &&
        preg_match("/[a-z]/", $password) &&
        preg_match("/[0-9]/", $password);
}

function validateAge($birthDate)
{
    $today = new DateTime();
    $diff = $today->diff(new DateTime($birthDate));
    return $diff->y >= 18; // Must be at least 18 years old
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    header('Content-Type: application/json');

    try {
        // Validate required fields
        $required = [
            "first_name",
            "last_name",
            "email",
            "phone_number",
            "region",
            "province",
            "city",
            "barangay",
            "zip_code",
            "date_of_birth",
            "password",
            "confirm_password",
            "gender"
        ];

        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                throw new Exception(ucfirst(str_replace('_', ' ', $field)) . " is required.");
            }
        }

        function sanitize_string($string)
        {
            return htmlspecialchars(strip_tags(trim($string)), ENT_QUOTES, 'UTF-8');
        }

        // Sanitize inputs
        $first_name = capitalizeNames(sanitize_string($_POST["first_name"]));
        $middle_name = !empty($_POST["middle_name"]) ? capitalizeNames(sanitize_string($_POST["middle_name"])) : '';
        $last_name = capitalizeNames(sanitize_string($_POST["last_name"]));
        $email = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
        $phone_number = trim($_POST["phone_number"]);
        $region = filter_var(trim($_POST["region"]), FILTER_SANITIZE_NUMBER_INT);
        $province = filter_var(trim($_POST["province"]), FILTER_SANITIZE_NUMBER_INT);
        $city = filter_var(trim($_POST["city"]), FILTER_SANITIZE_NUMBER_INT);
        $barangay = filter_var(trim($_POST["barangay"]), FILTER_SANITIZE_NUMBER_INT);
        $zip_code = trim($_POST["zip_code"]);
        $date_of_birth = $_POST["date_of_birth"];
        $gender = sanitize_string($_POST["gender"]);
        $password = $_POST["password"];
        $confirm_password = $_POST["confirm_password"];

        // Validations
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))
            throw new Exception("Invalid email format");
        if (isEmailRegistered($conn, $email))
            throw new Exception("Email is already registered");
        if (isEmailPending($conn, $email))
            throw new Exception("A verification email has already been sent. Please check your inbox or spam folder.");
        if (!validatePhoneNumber($phone_number))
            throw new Exception("Invalid phone number format. Use 09XXXXXXXXX or +639XXXXXXXXX");
        if (!validateZipCode($zip_code))
            throw new Exception("Invalid ZIP code format. Must be 4 digits.");
        // if (!validatePassword($password))
        //    throw new Exception("Password must be at least 8 characters and contain uppercase, lowercase, and numbers.");
        if (!validateAge($date_of_birth))
            throw new Exception("You must be at least 18 years old to register.");
        if ($password !== $confirm_password)
            throw new Exception("Passwords do not match");

        $password_hash = password_hash($password, PASSWORD_BCRYPT);

        // Generate OTP and expiration
        $otp = rand(100000, 999999);
        $stmt = $conn->prepare("SELECT DATE_ADD(NOW(), INTERVAL 10 MINUTE) as otp_expires");
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $otp_expires = (new DateTime($row['otp_expires']))->format('Y-m-d H:i:s');

        $role = 'user';

        // Insert to pending_patients
        $stmt = $conn->prepare("INSERT INTO pending_patients (first_name, middle_name, last_name, email, 
                              phone_number, region, province, city, barangay, zip_code, date_of_birth, 
                              password_hash, gender, role, otp, otp_expires) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->bind_param(
            "sssssiiiiissssss",
            $first_name,
            $middle_name,
            $last_name,
            $email,
            $phone_number,
            $region,
            $province,
            $city,
            $barangay,
            $zip_code,
            $date_of_birth,
            $password_hash,
            $gender,
            $role,
            $otp,
            $otp_expires
        );

        if ($stmt->execute()) {
            // Prepare email content
            $full_name = "$first_name $last_name";
            $subject = 'Your OTP Code';
            $message = "Dear $first_name,\n\nYour OTP code is: $otp\n\nThis code will expire in 10 minutes at: $otp_expires\n\nBest regards,\nM&A Oida Dental Clinic";

            // Try sending the email
            $emailSent = phpmailsend($email, $full_name, $subject, $message);

            if ($emailSent) {
                echo json_encode([
                    "status" => "success",
                    "message" => "Registration initiated. Please check your email for the OTP code."
                ]);
            } else {
                $stmt = $conn->prepare("DELETE FROM pending_patients WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $stmt->close();

                throw new Exception("Failed to send verification email. Please try again later.");
            }

        } else {
            throw new Exception("Database error. Please try again.");
        }

    } catch (Exception $e) {
        error_log("Error in signup process: " . $e->getMessage());
        echo json_encode([
            "status" => "error",
            "message" => $e->getMessage()
        ]);
    }

    exit();
}

// Non-POST requests show signup page
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - ISched of M&A Oida Dental Clinic</title>
    <?php require_once 'includes/head.php' ?>
    <script src="assets/js/signup.js"></script>
    <link rel="stylesheet" href="assets/css/signup.css?v=2.1">

</head>

<body>
    <div class="container">
        <div class="signup-box">
            <!-- HEADER: logo + title -->
            <div class="signup-header">
                <img src="assets/photos/logo-2.png" alt="Clinic Logo" class="signup-logo">
                <h2>Sign Up</h2>
            </div>

            <form id="signupForm" action="signup.php" method="POST">
                <div class="input-group">
                    <div class="input-box">
                        <label><strong style="color: red;">*</strong>First Name:</label>
                        <input type="text" name="first_name" placeholder="ex. Juan" required>
                    </div>
                    <div class="input-box">
                        <label>Middle Name:</label>
                        <input type="text" name="middle_name" placeholder="ex. Medrano">
                    </div>
                </div>

                <div class="input-group">
                    <div class="input-box">
                        <label><strong style="color: red;">*</strong>Last Name:</label>
                        <input type="text" name="last_name" placeholder="ex. Dela Cruz" required>
                    </div>
                    <div class="input-box">
                        <label><strong style="color: red;">*</strong>Email:</label>
                        <input type="email" name="email" placeholder="ex. Juandelacruz@gmail.com" required>
                    </div>
                </div>

                <div class="input-group">
                    <div class="input-box">
                        <label><strong style="color: red;">*</strong>Phone Number:</label>
                        <input type="text" name="phone_number" placeholder="ex. 09123456789" required>
                    </div>
                    <div class="input-box">
                        <label for="region"><strong style="color: red;">*</strong>Region:</label>
                        <select id="region" name="region">
                            <option value="">Select a Region</option>
                        </select>
                    </div>
                </div>

                <div class="input-group">
                    <div class="input-box">
                        <label for="province"><strong style="color: red;">*</strong>Province:</label>
                        <select id="province" name="province">
                            <option value="">Select a Province</option>
                        </select>
                    </div>
                    <div class="input-box">
                        <label for="city"><strong style="color: red;">*</strong>City/Municipality:</label>
                        <select id="city" name="city">
                            <option value="">Select a City/Municipality</option>
                        </select>
                    </div>
                </div>

                <div class="input-group">
                    <div class="input-box">
                        <label for="barangay"><strong style="color: red;">*</strong>Barangay:</label>
                        <select id="barangay" name="barangay">
                            <option value="">Select a Barangay</option>
                        </select>
                    </div>
                    <div class="input-box">
                        <label><strong style="color: red;">*</strong>Zip Code:</label>
                        <input type="text" name="zip_code" placeholder="Enter a Zip Code" required>
                    </div>
                </div>

                <div class="input-group">
                    <div class="input-box">
                        <label><strong style="color: red;">*</strong>Date of Birth:</label>
                        <input type="date" name="date_of_birth" required>
                    </div>

                    <div class="input-box">
                        <label><strong style="color: red;">*</strong>Gender:</label>
                        <select name="gender" required>
                            <option value="">Select a Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Prefer not to say">Prefer not to say</option>
                        </select>
                    </div>

                    <div class="input-box">
                        <label><strong style="color: red;">*</strong>Password:</label>
                        <div class="password-container">
                            <input type="password" id="signup-password" name="password"
                                placeholder="Enter your Password" required>
                            <span class="toggle-password" onclick="togglePassword('signup-password')">
                                <i class="fas fa-eye" id="signup-password-eye"></i>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="input-group">
                    <div class="input-box">
                        <label><strong style="color: red;">*</strong>Confirm Password:</label>
                        <div class="password-container">
                            <input type="password" id="confirm-password" name="confirm_password"
                                placeholder="Re-type your Password" required>
                            <span class="toggle-password" onclick="togglePassword('confirm-password')">
                                <i class="fas fa-eye" id="confirm-password-eye"></i>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="terms">
                    <input type="checkbox" required>
                    <label>
                        I agree to the
                        <a href="#" onclick="openTermsModal(); return false;">Terms &amp; Conditions</a>
                    </label>
                </div>

                <button type="submit">Sign Up</button>
                <p class="login-link">Already have an account? <a href="login.php">Log In</a></p>
            </form>
        </div>
    </div>

    <!-- Terms Modal -->
    <div id="termsModal" class="modal">
        <div class="modal-content terms-modal">
            <span class="close" onclick="closeTermsModal()">&times;</span>
            <h1>Terms and Conditions</h1>
            <div class="terms-content">

                <p>Welcome to <strong stylesheet="font-weight: bold;">ISched of M&A Oida Dental Clinic</strong>. By
                    accessing our services, you agree to comply with the following terms and conditions. Please read
                    them carefully.</p>

                <h2>1. General Terms</h2>
                <p>1.1 These terms govern the use of <strong style="font-weight: bold;">M&A Oida Dental
                        Clinic</strong>'s services, including appointments and treatments.</p>
                <p>1.2 The clinic reserves the right to update these terms without prior notice.</p>

                <h2>2. Appointments and Scheduling</h2>
                <p>2.1 Patients must book appointments in advance through our web-based system, phone, or walk-in
                    process.</p>
                <p>2.2 Late arrivals of more than 20 minutes may result in rescheduling or cancellation.</p>
                <p>2.3 Cancellations should be made at least 24 hours before the scheduled appointment. Failure to
                    cancel on time may result in a cancellation fee.</p>

                <h2>3. Patient Records and Privacy</h2>
                <p>3.1 Patient records are confidential and stored securely within our system.</p>
                <p>3.2 Personal information will only be used for medical and administrative purposes and will not be
                    shared without patient consent, except as required by law.</p>

                <h2>4. Payments and Fees</h2>
                <p>4.1 Payment is required upon completion of services unless a prior arrangement has been made.</p>
                <p>4.2 The clinic accepts cash, credit/debit cards, and e-wallets (e.g., GCash, Maya).</p>
                <p>4.3 For treatments requiring a down payment, the total fees will be discussed with the dentist before
                    the procedure.</p>
                <p>4.4 Refunds for pre-paid treatments will only be considered under special circumstances and are
                    subject to clinic approval.</p>

                <h2>5. Treatment Plans and Responsibility</h2>
                <p>5.1 Treatment recommendations are based on professional assessments. Patients are responsible for
                    following post-treatment care instructions.</p>
                <p>5.2 The clinic is not liable for complications arising from failure to follow medical advice or from
                    procedures performed elsewhere.</p>

                <h2>6. Code of Conduct</h2>
                <p>6.1 Patients and visitors must maintain respectful behavior towards staff and other patients.</p>
                <p>6.2 The clinic reserves the right to refuse service to anyone who displays inappropriate or
                    disruptive behavior.</p>

                <h2>7. Liability and Disclaimers</h2>
                <p>7.1 While we ensure high-quality care, results may vary based on individual health conditions.</p>
                <p>7.2 The clinic is not responsible for personal belongings lost within the premises.</p>

                <h2>8. Consent to Orthodontic Treatment</h2>
                <p>Orthodontic treatment remains an elective procedure. It, like other treatments of the body, has some
                    inherent risk and limitations.
                    These seldom prevent treatment, but should be considered in making the decision to undergo
                    treatment.</p>

                <h2>PREDICTABLE FACTORS THAT CAN AFFECT THE OUTCOME OF ORTHODONTIC TREATMENT:</h2>
                <p><strong style="font-weight: bold">Cooperation:</strong> In the vast majority of orthodontic cases,
                    significant improvement can be achieved with the patient's cooperation.
                    Thus, patient's care and discipline is a great factor of success in orthodontic treatment.
                    The dentist may in anytime terminate the treatment if the patient is uncooperative.</p>

                <p><strong style="font-weight: bold">Caring for appliances:</strong> Poor brushing increases the risk of
                    decay when wearing braces. Excellent oral hygiene., reduction in sugar, being selective in diet, and
                    reporting any loose bands as soon as noticed, will help minimize decay, white spots,
                    decalcification, and gum diseases/problems.
                    *Routine visits every 3-6 months to your dentist for cleaning and cavity checks are vital during
                    treatment and are NOT inclusive in the orthodontic procedure fee/package.</p>

                <p><strong style="font-weight: bold">Wearing mouthguards/Elastics:</strong> These are forces
                    placed/applied on the teeth so they will move into their proper positions.
                    The amount of time mouth guard worn affects the results. Loss, damage to mouth guard are subject to
                    fees for replacements.
                    Non replacements of elastics causes plaque build up and procedure no corrective forces to the teeth.
                </p>

                <p><strong style="font-weight: bold">Dislodged Brackets/ Wire Breakage/ Loss of Appliance:</strong> Any
                    loss, damage, breakage, or need of replacements
                    due to patients neglect or other factors are subject for a fee of <strong
                        style="font-weight: bold; text-decoration: underline;">₱500</strong> thoroughly explained by the
                    dentist.</p>

                <p><strong style="font-weight: bold">Appointments MUST be kept:</strong> Missed appointments create many
                    scheduling problems and lengthen treatment time.
                    The dentist has the right to terminate orthodontic treatment if a patient missed his/her
                    appointments within a period of 3 months.</p>

                <p><strong style="font-weight: bold">Going to ANOTHER DENTIST:</strong> At any event that the patient
                    goes to another dentist during the course of treatment without the knowledge/proper endorsement of
                    the attending dentist and doesn't return for check-ups and adjustments thereafter.
                    The attending dentist has the right to claim against patient for breach in the course of treatment.
                </p>

                <h2>UNPREDICTABLE FACTORS THAT CAN AFFECT THE OUTCOME OF ORTHODONTIC TREATMENT:</h2>
                <p><strong style="font-weight: bold">Muscle habits:</strong> Mouth breathing, thumb, finger or lip
                    sucking, tongue thrusting and other unusual habits can prevent teeth from moving to their corrected
                    positions or cause relapse after braces are removed.</p>

                <p><strong style="font-weight: bold">Facial Growth Patterns:</strong> Unusual skeletal patterns and
                    insufficient or undesirable facial growth can compromise the dental results, affect a facial change
                    and causes shifting of teeth during retention. Surgical assistance may be recommended in these
                    situations.</p>

                <p><strong style="font-weight: bold">Post Treatment Tooth Movement:</strong> Teeth have a tendency to
                    shift or settle after treatment as well as after retention. Some changes are desirable; others are
                    not.
                    Rotations and crowding of the lower front teeth or slight space in the extraction site are common
                    examples.</p>

                <p><strong style="font-weight: bold">Temporomandibular Problems (TMJ):</strong> Possible TMJ or jaw
                    joint problems may develop before, during, or after orthodontic treatment.
                    Tooth positions, bite, or pre-existing TMJ in this condition.</p>

                <p><strong style="font-weight: bold">Impacted teeth:</strong> In an attempt to move impacted teeth,
                    (teeth unable to erupt normally), especially cuspids and third molars (wisdom teeth, various
                    problems are sometimes encountered which may lead to periodontal problems, relapse, or loss of
                    tooth.</p>

                <p><strong style="font-weight: bold">Nonvital or Dead Tooth:</strong> A traumatized or other causes can
                    die over a long period of time or without orthodontic treatment.
                    This tooth may discolor or flared up during orthodontic treatment. It could deteriorate during
                    treatment causing loss of bone around the tooth. Excellent oral hygiene and frequent scaling and
                    polishing by your dentist can help control this situation.</p>

                <h2>9. Contact Information </h2>
                <p>For questions or concerns, you may contact us at:</p>

                <p><strong style="font-weight: bold">Clinic Address:</strong> Unit A - Lot 30 Blk 9 Regalado Hi-way
                    North Fairview.</p>

                <p><strong style="font-weight: bold">Contact Number:</strong> 0918 578 2346</p>

                <p>
                    <strong style="font-weight: bold">Official Email Address:</strong>
                    <a href="mailto:naioby_2007@yahoo.ph"><strong style="font-weight: bold; text-decoration: none;">
                            naioby_2007@yahoo.ph</strong></a>
                </p>

                <p>
                    <strong style="font-weight: bold">Facebook Page:</strong>
                    <a href="https://www.facebook.com/mandaoidadental?mibextid=ZbWKwL" target="_blank"><strong
                            style="font-weight: bold; text-decoration: none;"> M and A Oida Dental</strong></a>
                </p>

                <p><strong style="text-align: center; font-style: italic; align-items: center;"> By using our services,
                        you acknowledge that you have read, understood, and agreed to these terms and
                        conditions.</strong></p>
            </div>
        </div>
    </div>

    <!-- OTP Modal -->
    <div id="otpModal" class="modal" style="display:none;">
        <div class="modal-content">
            <span class="close" id="closeOtpModal">&times;</span>
            <h2>Verify Your Email</h2>
            <div id="otpMessage"></div>
            <form id="otpForm">
                <input type="hidden" name="email" id="otpEmail">
                <label for="otpInput">OTP:</label>
                <input type="text" name="otp" id="otpInput" required placeholder="Enter the code sent to your email">
                <button type="submit">Verify</button>
            </form>
            <a href="#" id="resendOtpLink"><i class="fas fa-sync-alt"></i> Resend OTP</a>
            <p class="login-link" style="text-align:center;">Already verified? <a href="login.php">Log In</a></p>
        </div>
    </div>
    <style>
        .modal {
            display: none;
            position: fixed;
            left: 0;
            top: 0;
            width: 100vw;
            height: 100vh;
            overflow: auto;
            background: rgba(0, 0, 0, 0.4);
        }

        .modal-content {
            background: #fff;
            margin: 10% auto;
            padding: 30px 20px;
            border-radius: 8px;
            max-width: 400px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.12);
            position: relative;
        }

        .modal-content h2 {
            text-align: left;
            color: #6b42a5;
        }

        .modal-content h1 {
            text-align: center;
        }

        .modal-content form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .modal-content input[type=text] {
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .modal-content button {
            padding: 10px;
            background: #007bff;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .modal-content button:hover {
            background: #0056b3;
        }

        .modal-content .close {
            position: absolute;
            right: 15px;
            top: 10px;
            font-size: 1.5em;
            color: #888;
            cursor: pointer;
        }

        .modal-content .close:hover {
            color: #333;
        }

        #otpMessage {
            margin-bottom: 10px;
            text-align: center;
        }

        #resendOtpLink {
            display: block;
            margin-top: 10px;
            text-align: center;
        }

        /* Terms modal specific styles */
        .terms-modal {
            max-width: 800px;
            max-height: 80vh;
            margin: 5vh auto;
        }

        .terms-content {
            overflow-y: auto;
            max-height: calc(80vh - 100px);
            padding: 10px;
            font-size: 14px;
            line-height: 1.6;
        }

        .terms-content h3 {
            color: #6a5acd;
            margin: 20px 0 10px;
        }

        .terms-content p {
            margin-bottom: 10px;
            text-align: justify;
        }

        .terms-content strong {
            font-weight: bold;
        }
    </style>

    <script>
        // Add these functions to handle the terms modal
        function openTermsModal() {
            document.getElementById('termsModal').style.display = 'block';
        }

        function closeTermsModal() {
            document.getElementById('termsModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function (event) {
            if (event.target.id === 'termsModal') {
                closeTermsModal();
            }
        }

        // Function to toggle password visibility
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const eyeIcon = document.getElementById(inputId + '-eye');
            if (input.type === 'password') {
                input.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>

</html>