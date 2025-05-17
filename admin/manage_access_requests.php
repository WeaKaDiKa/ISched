<?php
require_once('db.php');
require_once('session_handler.php');

// Check if user is logged in and is an admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

// Handle request actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $request_id = $_POST['request_id'] ?? '';

    if ($action && $request_id) {
        $status = ($action === 'approve') ? 'Approved' : 'Denied';

        $sql = "UPDATE access_requests SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('si', $status, $request_id);
            if ($stmt->execute()) {
                $success = "Request has been " . ($action === 'approve' ? 'approved' : 'denied') . " successfully.";
            } else {
                $error = "Failed to update request status. Please try again.";
            }
            $stmt->close();
        } else {
            $error = "Database error. Please try again.";
        }
    }
}

// Get all pending requests
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
    <title>Manage Access Requests - M&A Oida Dental Clinic</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <style>
        .request-card {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
    </style>
</head>

<body class="bg-gray-100">
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <?php require_once 'nav.php' ?>
        <!-- Main Content -->
        <main class="flex-1 p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold">Manage Access Requests</h1>
                <a href="dashboard.php" class="text-blue-600 hover:text-blue-800">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
                </a>
            </div>

            <?php if (isset($success)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <!-- Pending Requests -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4">Pending Requests</h2>
                <?php if (empty($pending_requests)): ?>
                    <p class="text-gray-600">No pending requests at the moment.</p>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($pending_requests as $request): ?>
                            <div class="request-card p-4 rounded-lg">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h3 class="font-semibold"><?= htmlspecialchars($request['full_name']) ?></h3>
                                        <p class="text-sm text-gray-600">
                                            <i class="fas fa-envelope mr-1"></i> <?= htmlspecialchars($request['email']) ?>
                                        </p>
                                        <p class="text-sm text-gray-600">
                                            <i class="fas fa-phone mr-1"></i>
                                            <?= htmlspecialchars($request['contact_number']) ?>
                                        </p>
                                        <p class="text-sm text-gray-600">
                                            <i class="fas fa-user-tag mr-1"></i> Role:
                                            <?= htmlspecialchars($request['role_requested']) ?>
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            Submitted: <?= date('F j, Y g:i A', strtotime($request['submitted_on'])) ?>
                                        </p>
                                    </div>
                                    <div class="space-y-2">
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit"
                                                class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                                                <i class="fas fa-check mr-1"></i> Approve
                                            </button>
                                        </form>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                                            <input type="hidden" name="action" value="deny">
                                            <button type="submit"
                                                class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                                                <i class="fas fa-times mr-1"></i> Deny
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- All Requests (History) -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">Request History</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gray-50">
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Name</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Role</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Submitted</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($all_requests as $request): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?= htmlspecialchars($request['full_name']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?= htmlspecialchars($request['role_requested']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span
                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?= $request['status'] === 'Approved' ? 'bg-green-100 text-green-800' :
                                                ($request['status'] === 'Denied' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') ?>">
                                            <?= htmlspecialchars($request['status']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= date('F j, Y g:i A', strtotime($request['submitted_on'])) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>

</html>