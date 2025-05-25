<?php
require_once('session_handler.php');
require 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}
// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    $conn->query("DELETE FROM reviews WHERE id = $del_id");
    header('Location: patient_feedback.php');
    exit;
}
// Fetch all feedbacks
$sql = "SELECT * FROM reviews ORDER BY rating DESC, date DESC";
$result = $conn->query($sql);
// Mark all unseen feedback as seen
$conn->query("UPDATE reviews SET is_seen = 1 WHERE is_seen = 0");
// Dynamic greeting based on time of day (Asia/Manila)
date_default_timezone_set('Asia/Manila');
$hour = (int) date('G');
if ($hour >= 5 && $hour < 12) {
    $greeting = 'Good Morning,';
} elseif ($hour >= 12 && $hour < 18) {
    $greeting = 'Good Afternoon,';
} else {
    $greeting = 'Good Evening,';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Patient Feedback - M&amp;A Oida Dental Clinic</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <?php require_once 'head.php' ?>
    <style>
        /* Mobile optimizations for patient feedback */
        @media (max-width: 768px) {

            /* Feedback card improvements */
            .feedback-card {
                margin: 10px 0;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                padding: 15px;
            }

            /* Stack feedback content */
            .feedback-content {
                flex-direction: column;
                gap: 15px;
            }

            /* Patient info in feedback */
            .patient-info {
                display: grid;
                grid-template-columns: auto 1fr;
                gap: 10px;
                align-items: center;
            }

            .patient-photo {
                width: 60px;
                height: 60px;
                border-radius: 50%;
            }

            /* Rating display */
            .rating-stars {
                display: flex;
                gap: 5px;
                justify-content: flex-start;
                margin: 10px 0;
            }

            .star {
                font-size: 20px;
                color: #fbbf24;
            }

            /* Feedback text */
            .feedback-text {
                font-size: 16px;
                line-height: 1.6;
                margin: 10px 0;
            }

            /* Feedback metadata */
            .feedback-meta {
                display: flex;
                flex-direction: column;
                gap: 5px;
                font-size: 14px;
                color: #666;
            }

            /* Action buttons */
            .action-buttons {
                display: grid;
                grid-template-columns: 1fr;
                gap: 10px;
                margin-top: 15px;
            }

            .action-button {
                min-height: 44px;
                width: 100%;
                justify-content: center;
                align-items: center;
                display: flex;
                gap: 8px;
                padding: 12px;
                border-radius: 4px;
                font-weight: 600;
            }

            /* Filter and search section */
            .filter-section {
                flex-direction: column;
                gap: 10px;
                padding: 15px;
                background: #f9fafb;
                border-radius: 8px;
                margin-bottom: 20px;
            }

            .search-input {
                width: 100%;
                padding: 12px;
                font-size: 16px;
                border-radius: 4px;
                border: 1px solid #e5e7eb;
            }

            .filter-group {
                width: 100%;
            }

            /* Rating filter */
            .rating-filter {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
                margin: 10px 0;
            }

            .rating-option {
                flex: 1 1 calc(20% - 8px);
                min-width: 60px;
                text-align: center;
                padding: 8px;
                border-radius: 4px;
                background: white;
                border: 1px solid #e5e7eb;
            }

            /* Modal improvements */
            .modal-content {
                width: 95% !important;
                margin: 10px auto;
                border-radius: 8px;
                max-height: 90vh;
                overflow-y: auto;
            }

            .modal-body {
                padding: 15px;
            }

            .modal-footer {
                padding: 15px;
                flex-direction: column;
                gap: 10px;
            }

            /* Empty state */
            .empty-state {
                text-align: center;
                padding: 40px 20px;
            }

            .empty-state-icon {
                font-size: 48px;
                color: #9ca3af;
                margin-bottom: 20px;
            }

            /* Loading states */
            .loading-overlay {
                background: rgba(255, 255, 255, 0.9);
            }

            /* Better scrolling */
            .scroll-container {
                -webkit-overflow-scrolling: touch;
                scroll-behavior: smooth;
            }

            /* Pagination */
            .pagination {
                display: flex;
                justify-content: center;
                gap: 5px;
                margin: 20px 0;
                flex-wrap: wrap;
            }

            .page-item {
                min-width: 40px;
                height: 40px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 4px;
                background: white;
                border: 1px solid #e5e7eb;
            }

            .page-item.active {
                background: #2563eb;
                color: white;
                border-color: #2563eb;
            }
        }
    </style>
</head>

<body class="bg-white text-gray-900">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <?php require_once 'nav.php' ?>
        <!-- Main content -->
        <main class="flex-1 flex flex-col overflow-hidden">
            <!-- Top bar -->
            <?php require_once 'header.php' ?>
            <!-- Breadcrumb Navigation -->
            <?php
            $breadcrumbLabel = 'Patient Feedback';
            include 'breadcrumb.php';
            ?>
            <!-- End Breadcrumb Navigation -->
            <!-- Content area -->
            <div class="flex-1 flex flex-col items-center justify-start bg-gray-100 w-full min-h-0">
                <section class="w-full max-w-5xl mx-auto bg-white rounded-lg border border-gray-300 shadow-md p-4 mt-6">
                    <div class="flex justify-between items-center mb-3">
                        <h1 class="text-blue-900 font-bold text-lg select-none">Patient Feedback</h1>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-xs text-left border-collapse">
                            <thead class="bg-gray-100 text-gray-700">
                                <tr>
                                    <th class="px-4 py-2">Name</th>
                                    <th class="px-4 py-2">Rating</th>
                                    <th class="px-4 py-2">Feedback</th>
                                    <th class="px-4 py-2">Services</th>
                                    <th class="px-4 py-2">Date</th>
                                </tr>
                            </thead>
                            <tbody id="feedback-table-body">
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr class="border-b hover:bg-blue-50">
                                        <td class="px-4 py-2 font-semibold text-gray-900">
                                            <?= htmlspecialchars($row['name']) ?>
                                        </td>
                                        <td class="px-4 py-2">
                                            <?php for ($i = 0; $i < $row['rating']; $i++)
                                                echo '<i class="fas fa-star text-yellow-400"></i>'; ?>
                                            <?php for ($i = $row['rating']; $i < 5; $i++)
                                                echo '<i class="far fa-star text-gray-300"></i>'; ?>
                                        </td>
                                        <td class="px-4 py-2 text-gray-700 max-w-xs truncate"
                                            title="<?= htmlspecialchars($row['text']) ?>">
                                            <?= htmlspecialchars($row['text']) ?>
                                        </td>
                                        <td class="px-4 py-2 text-gray-600">
                                            <?= htmlspecialchars($row['services']) ?>
                                        </td>
                                        <td class="px-4 py-2 text-gray-500">
                                            <?= date('Y-m-d', strtotime($row['date'])) ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </main>
    </div>
    <!-- Success Modal for Feedback Actions -->
    <div id="feedbackSuccessModal"
        class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50 hidden transition-opacity duration-300">
        <div
            class="bg-white rounded-xl shadow-lg p-8 max-w-sm w-full text-center relative transform transition-all duration-300 scale-90 opacity-0">
            <div class="flex justify-center -mt-14 mb-2">
                <div class="bg-green-400 rounded-full w-20 h-20 flex items-center justify-center">
                    <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" stroke-width="3"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
            </div>
            <h2 class="text-2xl font-bold text-gray-800 mb-2">Success!</h2>
            <p class="text-gray-600 mb-6" id="feedbackSuccessMsg">Action completed successfully!</p>
            <button onclick="closeFeedbackSuccessModal()"
                class="w-full bg-green-400 text-white font-semibold py-2 rounded hover:bg-green-500 transition">OK</button>
        </div>
    </div>
    <!-- Logout Confirmation Modal -->
    <div id="logoutModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-30 z-50 hidden">
        <div class="bg-white rounded-xl shadow-lg p-8 max-w-xs w-full text-center">
            <h2 class="text-lg font-semibold text-blue-700 mb-2">Confirm logout</h2>
            <hr class="my-2 border-blue-100">
            <p class="text-gray-700 mb-6">Are you sure you want to log out?</p>
            <div class="flex justify-center space-x-4">
                <button id="cancelLogout"
                    class="px-4 py-1 rounded bg-blue-100 text-blue-700 font-semibold hover:bg-blue-200">Cancel</button>
                <button id="confirmLogout"
                    class="px-4 py-1 rounded bg-blue-600 text-white font-semibold hover:bg-blue-700">OK</button>
            </div>
        </div>
    </div>

    <script>
        function showFeedbackSuccessModal(msg) {
            document.getElementById('feedbackSuccessMsg').textContent = msg;
            const modal = document.getElementById('feedbackSuccessModal');
            const content = modal.querySelector('div.bg-white');
            modal.classList.remove('hidden');
            setTimeout(() => {
                content.classList.remove('scale-90', 'opacity-0');
                content.classList.add('scale-100', 'opacity-100');
            }, 10);
        }
        function closeFeedbackSuccessModal() {
            const modal = document.getElementById('feedbackSuccessModal');
            const content = modal.querySelector('div.bg-white');
            content.classList.remove('scale-100', 'opacity-100');
            content.classList.add('scale-90', 'opacity-0');
            setTimeout(() => {
                modal.classList.add('hidden');
                window.location.href = 'patient_feedback.php';
            }, 300);
        }
        // Show modal if redirected after delete
        if (window.location.search.includes('deleted=1')) {
            showFeedbackSuccessModal('Feedback deleted successfully!');
        }
        // Check for profile photo updates from other pages
        window.addEventListener('load', function () {
            const newProfilePhoto = sessionStorage.getItem('newProfilePhoto');
            if (newProfilePhoto) {
                document.querySelectorAll('img[alt*="Profile photo"]').forEach(img => {
                    img.src = newProfilePhoto;
                });
            }
        });

        document.querySelectorAll('a[href="admin_login.php"]').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                document.getElementById('logoutModal').classList.remove('hidden');
            });
        });
        document.getElementById('cancelLogout').onclick = function () {
            document.getElementById('logoutModal').classList.add('hidden');
        };
        document.getElementById('confirmLogout').onclick = function () {
            window.location.href = 'admin_login.php';
        };
    </script>
</body>

</html>
