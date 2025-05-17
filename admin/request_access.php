<?php
require_once('session_handler.php');
require_once('db.php');

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

// Load admin data
load_admin_data($conn);

// Check if access_requests table exists
$table_exists = false;
$result = $conn->query("SHOW TABLES LIKE 'access_requests'");
if ($result && $result->num_rows > 0) {
    $table_exists = true;
}

// If table doesn't exist, show a message
if (!$table_exists) {
    echo "<div class='flex-1 flex flex-col items-center justify-center bg-gray-100 w-full min-h-0'>
            <div class='text-center py-8'>
                <h2 class='text-2xl font-bold text-gray-900'>Access Requests</h2>
                <p class='mt-4 text-gray-600'>The access requests table is not set up yet. Please contact your system administrator to create the table.</p>
            </div>
        </div>";
    exit;
}

// Get pending requests
date_default_timezone_set('Asia/Manila');
$sql = "SELECT * FROM access_requests WHERE status = 'Pending' ORDER BY submitted_on DESC";
$result = $conn->query($sql);
$pending_requests = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// Get all requests (for history)
$sql = "SELECT * FROM access_requests ORDER BY submitted_on DESC";
$result = $conn->query($sql);
$all_requests = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request for Access - M&A Oida Dental Clinic</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet" />

</head>

<body class="bg-white text-gray-900">
    <div class="flex h-screen overflow-hidden">
        <?php require_once 'nav.php' ?>
        <main class="flex-1 flex flex-col overflow-hidden">
            <!-- Top bar -->
            <header class="flex items-center justify-between bg-blue-300 px-6 py-3 border-b border-gray-300">
                <div class="flex items-center space-x-3 text-gray-900 text-sm font-normal">
                    <span class="font-semibold">North Fairview Branch</span>
                </div>
                <div class="flex items-center space-x-4 ml-auto">
                    <button
                        class="bg-purple-700 text-white text-xs font-semibold rounded-md px-4 py-1 hover:bg-purple-800">
                        Walk-in Appointment Form
                    </button>
                    <button aria-label="Notifications" class="text-gray-900 hover:text-gray-700 focus:outline-none">
                        <i class="fas fa-bell fa-lg"></i>
                    </button>
                    <img alt="Profile photo of <?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?>"
                        class="rounded-full w-10 h-10 object-cover"
                        src="<?php echo !empty($_SESSION['profile_photo']) ? htmlspecialchars($_SESSION['profile_photo']) : 'assets/photo/default_avatar.png'; ?>" />
                </div>
            </header>

            <!-- Breadcrumb Navigation -->
            <?php
            $breadcrumbLabel = 'Request Access';
            include 'breadcrumb.php';
            ?>

            <div class="flex-1 flex flex-col items-center justify-start bg-gray-100 w-full min-h-0">
                <!-- Request Access Table Section -->
                <section class="w-full max-w-5xl mx-auto bg-white rounded-lg border border-gray-300 shadow-md p-4 mt-6">
                    <div class="flex justify-between items-center mb-3">
                        <h1 class="text-blue-900 font-bold text-lg select-none">Request for Access</h1>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Full Name</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Email Address</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Contact Number</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Role Requested</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Submitted On</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($pending_requests as $request): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?= htmlspecialchars($request['full_name']) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?= htmlspecialchars($request['email']) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?= htmlspecialchars($request['contact_number']) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?= htmlspecialchars($request['role_requested']) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= date('F j, Y g:i A', strtotime($request['submitted_on'])) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <div class="flex space-x-2">
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                                                    <input type="hidden" name="action" value="approve">
                                                    <button type="submit"
                                                        class="px-3 py-1.5 bg-green-600 text-white rounded-md hover:bg-green-700">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                                                    <input type="hidden" name="action" value="deny">
                                                    <button type="submit"
                                                        class="px-3 py-1.5 bg-red-600 text-white rounded-md hover:bg-red-700">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </form>
                                                <button
                                                    class="px-3 py-1.5 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                                    Details
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </main>
    </div>

</body>

</html>