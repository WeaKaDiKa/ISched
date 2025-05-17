<?php
require 'db.php';
$success = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $age = trim($_POST['age'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    if (!$name || !$email || !$password || !$first_name || !$last_name || !$age || !$mobile || !$gender) {
        $error = 'All fields are required.';
    } elseif (strlen($password) < 8 || strlen($password) > 20) {
        $error = 'Password must be 8-20 characters long.';
    } else {
        // Check if email already exists
        $stmt = $conn->prepare('SELECT id FROM admin_logins WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $error = 'Email is already registered.';
        } else {
            // Insert first to get the auto-increment id
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('INSERT INTO admin_logins (email, password, name, first_name, last_name, age, mobile, gender, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())');
            $stmt->bind_param('ssssssss', $email, $hash, $name, $first_name, $last_name, $age, $mobile, $gender);
            if ($stmt->execute()) {
                $last_id = $conn->insert_id;
                $admin_id = 'ADM-' . str_pad($last_id, 3, '0', STR_PAD_LEFT);
                $stmt2 = $conn->prepare('UPDATE admin_logins SET admin_id=? WHERE id=?');
                $stmt2->bind_param('si', $admin_id, $last_id);
                $stmt2->execute();
                $stmt2->close();
                header('Location: request_access.php');
                exit;
            } else {
                $error = 'Signup failed. Please try again.';
            }
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Signup</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body class="bg-blue-100 min-h-screen flex items-center justify-center">
    <div class="flex flex-col md:flex-row w-full min-h-screen items-center justify-center">
        <!-- Left Illustration -->
        <div class="relative flex justify-center items-center w-full md:w-1/2 p-8">
            <div
                class="w-full max-w-xl mx-auto bg-white bg-opacity-40 rounded-2xl shadow-lg p-10 flex items-center justify-center">
                <img src="assets/photo/dentist_patient.png" alt="Hospital Illustration"
                    class="w-full h-auto select-none pointer-events-none">
            </div>
        </div>
        <!-- Right Signup Card -->
        <div class="flex justify-center items-center w-full md:w-1/2 p-8">
            <div class="w-full max-w-md mx-auto bg-white rounded-2xl shadow-xl p-10 border border-blue-300">
                <div class="flex items-center mb-6">
                    <img src="assets/photo/logo.jpg" alt="Logo"
                        class="w-12 h-12 rounded-full mr-3 border-2 border-blue-200">
                    <span class="text-2xl font-bold text-gray-900 tracking-tight">M&A Oida Dental Clinic</span>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 mb-2">Signup</h2>
                <p class="text-gray-500 mb-6">Create your admin account</p>
                <?php if ($error): ?>
                    <p class="bg-red-100 text-red-700 px-4 py-2 rounded mb-4 text-sm text-center">
                        <?= htmlspecialchars($error) ?></p>
                <?php elseif ($success): ?>
                    <p class="bg-green-100 text-green-700 px-4 py-2 rounded mb-4 text-sm text-center">
                        <?= htmlspecialchars($success) ?></p>
                <?php endif; ?>
                <form action="admin_signup.php" method="POST" class="space-y-6">
                    <div>
                        <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">First Name <span
                                class="text-red-500">*</span></label>
                        <input type="text" name="first_name" id="first_name"
                            value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" required
                            class="block w-full border border-gray-300 rounded-lg bg-gray-50 px-4 py-2 text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                            placeholder="Enter your first name" />
                    </div>
                    <div>
                        <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Last Name <span
                                class="text-red-500">*</span></label>
                        <input type="text" name="last_name" id="last_name"
                            value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>" required
                            class="block w-full border border-gray-300 rounded-lg bg-gray-50 px-4 py-2 text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                            placeholder="Enter your last name" />
                    </div>
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Full Name <span
                                class="text-red-500">*</span></label>
                        <input type="text" name="name" id="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                            required
                            class="block w-full border border-gray-300 rounded-lg bg-gray-50 px-4 py-2 text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                            placeholder="Enter your full name" />
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Your email <span
                                class="text-red-500">*</span></label>
                        <input type="email" name="email" id="email"
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required
                            class="block w-full border border-gray-300 rounded-lg bg-gray-50 px-4 py-2 text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                            placeholder="Enter your email" />
                    </div>
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Your password <span
                                class="text-red-500">*</span></label>
                        <input type="password" name="password" id="password"
                            value="<?= htmlspecialchars($_POST['password'] ?? '') ?>" required
                            class="block w-full border border-gray-300 rounded-lg bg-gray-50 px-4 py-2 text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                            placeholder="Enter password" />
                        <p class="text-xs text-gray-500 mt-1">Your password must be 8-20 characters long.</p>
                    </div>
                    <div>
                        <label for="age" class="block text-sm font-medium text-gray-700 mb-1">Age <span
                                class="text-red-500">*</span></label>
                        <input type="number" name="age" id="age" value="<?= htmlspecialchars($_POST['age'] ?? '') ?>"
                            required
                            class="block w-full border border-gray-300 rounded-lg bg-gray-50 px-4 py-2 text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                            placeholder="Enter your age" />
                    </div>
                    <div>
                        <label for="mobile" class="block text-sm font-medium text-gray-700 mb-1">Mobile Number <span
                                class="text-red-500">*</span></label>
                        <input type="text" name="mobile" id="mobile"
                            value="<?= htmlspecialchars($_POST['mobile'] ?? '') ?>" required
                            class="block w-full border border-gray-300 rounded-lg bg-gray-50 px-4 py-2 text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                            placeholder="Enter your mobile number" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Gender <span
                                class="text-red-500">*</span></label>
                        <div class="flex space-x-4">
                            <label class="inline-flex items-center">
                                <input type="radio" name="gender" value="Male" required <?= (($_POST['gender'] ?? '') === 'Male') ? 'checked' : '' ?>>
                                <span class="ml-2">Male</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="gender" value="Female" required <?= (($_POST['gender'] ?? '') === 'Female') ? 'checked' : '' ?>>
                                <span class="ml-2">Female</span>
                            </label>
                        </div>
                    </div>
                    <button type="submit"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 rounded-lg shadow-sm transition">Signup</button>
                    <button type="button" onclick="window.location.href='admin_login.php'"
                        class="w-full mt-3 bg-gray-200 text-gray-800 font-semibold py-2 rounded-lg shadow-sm transition cursor-pointer">Already
                        have an account? Login</button>
                </form>
            </div>
        </div>
    </div>
    <script>
        function showModal() {
            const modal = document.getElementById('successModal');
            modal.classList.remove('hidden');
        }

        function redirectToRequestAccess() {
            window.location.href = 'request_access.php';
        }

        document.addEventListener('DOMContentLoaded', () => {
            if ('<?= $success ?>') {
                showModal();
            }
        });
    </script>
    <div id="successModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-30 z-50 hidden">
        <div class="bg-white rounded-xl shadow-lg p-8 max-w-xs w-full text-center">
            <h2 class="text-lg font-semibold text-blue-700 mb-2">Thanks</h2>
            <hr class="my-2 border-blue-100">
            <p class="text-gray-700 mb-6">Your response has been submitted!</p>
            <button onclick="redirectToRequestAccess()"
                class="bg-green-500 text-white font-semibold py-2 px-4 rounded">OK</button>
        </div>
    </div>
</body>

</html>