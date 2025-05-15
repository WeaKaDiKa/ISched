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
    <style>
        /* Collapsed sidebar styles */
        #sidebar.collapsed {
            width: 4.5rem !important;
            min-width: 0 !important;
            padding-left: 0.5rem;
            padding-right: 0.5rem;
        }

        #sidebar.collapsed .sidebar-label,
        #sidebar.collapsed .text-center,
        #sidebar.collapsed .text-xs,
        #sidebar.collapsed nav span,
        #sidebar.collapsed .mt-auto,
        #sidebar.collapsed .flex.flex-col.items-center.mb-8>h3,
        #sidebar.collapsed .flex.flex-col.items-center.mb-8>p {
            display: none !important;
        }

        #sidebar.collapsed .flex.flex-col.items-center.mb-8 {
            align-items: flex-start !important;
        }

        #sidebar.collapsed img.w-24 {
            margin-bottom: 0 !important;
        }

        .active-sidebar-link {
            background-color: #f4f6f8;
            /* light gray */
            color: #1e3a8a !important;
            /* dark blue text */
            font-weight: bold;
            position: relative;
        }

        .active-sidebar-link::before {
            content: "";
            position: absolute;
            left: 0;
            top: 8px;
            bottom: 8px;
            width: 4px;
            background: #2563eb;
            /* blue-600 */
            border-radius: 8px;
        }
    </style>
</head>

<body class="bg-white text-gray-900">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <aside id="sidebar"
            class="flex flex-col bg-white border-r border-gray-200 w-64 min-w-[256px] py-6 px-4 transition-all duration-300">
            <div class="flex items-center justify-between mb-10">
                <div class="flex items-center space-x-2">
                    <img alt="M&A Oida Dental Clinic logo" class="w-8 h-8" src="assets/photo/logo.jpg" />
                    <span class="sidebar-label text-sm font-semibold text-gray-900 whitespace-nowrap">
                        M&A Oida Dental Clinic
                    </span>
                </div>
                <button id="sidebarToggle" aria-label="Toggle menu"
                    class="text-blue-600 hover:text-blue-700 focus:outline-none">
                    <i class="fas fa-bars fa-lg"></i>
                </button>
            </div>

            <!-- Profile Section -->
            <div class="flex flex-col items-center mb-8">
                <img alt="Profile photo" class="rounded-full w-24 h-24 object-cover mb-2"
                    src="<?php echo (!empty($_SESSION['profile_photo']) && file_exists($_SESSION['profile_photo'])) ? htmlspecialchars($_SESSION['profile_photo']) : 'assets/photo/default_avatar.png'; ?>" />
                <h3 class="text-center text-sm font-semibold text-gray-900 leading-tight">
                    <?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?>
                </h3>
                <p class="text-center text-xs text-gray-500 mt-1">
                    Professional Dentist
                </p>
            </div>

            <!-- Navigation -->
            <?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
            <nav class="flex flex-col space-y-2 text-gray-700 text-sm font-medium">
                <a class="relative flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-100 <?php echo $currentPage == 'dashboard.php' ? 'active-sidebar-link' : ''; ?>"
                    href="dashboard.php">
                    <div class="flex items-center justify-center w-8 h-8 bg-gray-200 rounded-lg text-gray-700">
                        <i class="fas fa-home"></i>
                    </div>
                    <span>Dashboard</span>
                </a>
                <a class="relative flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-100 <?php echo $currentPage == 'appointments.php' ? 'active-sidebar-link' : ''; ?>"
                    href="appointments.php">
                    <div class="flex items-center justify-center w-8 h-8 bg-gray-200 rounded-lg text-gray-700">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <span>Appointments</span>
                </a>
                <a class="relative flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-100 <?php echo $currentPage == 'patient_record.php' ? 'active-sidebar-link' : ''; ?>"
                    href="patient_record.php">
                    <div class="flex items-center justify-center w-8 h-8 bg-gray-200 rounded-lg text-gray-700">
                        <i class="fas fa-user-injured"></i>
                    </div>
                    <span>Patient Records</span>
                </a>
                <a class="relative flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-100 <?php echo $currentPage == 'patient_feedback.php' ? 'active-sidebar-link' : ''; ?>"
                    href="patient_feedback.php">
                    <div class="flex items-center justify-center w-8 h-8 bg-gray-200 rounded-lg text-gray-700">
                        <i class="fas fa-comment-alt"></i>
                    </div>
                    <span>Patient Feedback</span>
                </a>
                <a class="relative flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-100 <?php echo $currentPage == 'account_settings.php' ? 'active-sidebar-link' : ''; ?>"
                    href="account_settings.php">
                    <div class="flex items-center justify-center w-8 h-8 bg-gray-200 rounded-lg text-gray-700">
                        <i class="fas fa-cog"></i>
                    </div>
                    <span>Account Settings</span>
                </a>
                <a class="relative flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-100 <?php echo $currentPage == 'request_access.php' ? 'active-sidebar-link' : ''; ?>"
                    href="request_access.php">
                    <div class="flex items-center justify-center w-8 h-8 bg-gray-200 rounded-lg text-gray-700">
                        <i class="fas fa-lock"></i>
                    </div>
                    <span>Request for Access</span>
                </a>
                <a class="relative flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-100 <?php echo $currentPage == 'help_support.php' ? 'active-sidebar-link' : ''; ?>"
                    href="help_support.php">
                    <div class="flex items-center justify-center w-8 h-8 bg-gray-200 rounded-lg text-gray-700">
                        <i class="fas fa-question-circle"></i>
                    </div>
                    <span>Help & Support</span>
                </a>
            </nav>

            <a href="admin_login.php"
                class="mt-auto flex justify-center items-center space-x-2 text-red-600 hover:text-red-700 font-semibold text-sm">
                <i class="fas fa-sign-out-alt fa-lg"></i>
                <span>Logout</span>
            </a>
        </aside>

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

    <script>
        // Sidebar toggle logic
        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.getElementById('sidebarToggle');
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
        });
    </script>
</body>

</html>