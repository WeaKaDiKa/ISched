<?php
require_once('session_handler.php');
require 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

// Handle adding sample data if requested
if (isset($_POST['add_sample_data'])) {
    // First, check if we have any patients in the database
    $patientQuery = "SELECT id FROM patients LIMIT 5";
    $patientResult = $conn->query($patientQuery);
    $patientIds = [];
    
    if ($patientResult && $patientResult->num_rows > 0) {
        while ($row = $patientResult->fetch_assoc()) {
            $patientIds[] = $row['id'];
        }
    }
    
    // If no patients, create a dummy patient
    if (empty($patientIds)) {
        $conn->query("INSERT INTO patients (first_name, last_name, email) VALUES ('John', 'Doe', 'john@example.com')");
        $patientIds[] = $conn->insert_id;
    }
    
    // Sample review data
    $sampleReviews = [
        [
            'patient_id' => $patientIds[0],
            'name' => 'John Doe',
            'rating' => 5,
            'text' => 'Excellent service! The dental staff was very professional and made me feel comfortable.',
            'services' => json_encode(['Dental Check-ups & Consultation']),
            'date' => date('Y-m-d H:i:s', strtotime('-2 days'))
        ],
        [
            'patient_id' => isset($patientIds[1]) ? $patientIds[1] : $patientIds[0],
            'name' => 'Anonymous',
            'rating' => 4,
            'text' => 'Very good experience overall. The clinic is clean and modern.',
            'services' => json_encode(['Teeth Cleaning (Oral Prophylaxis)']),
            'date' => date('Y-m-d H:i:s', strtotime('-5 days'))
        ],
        [
            'patient_id' => isset($patientIds[2]) ? $patientIds[2] : $patientIds[0],
            'name' => 'Maria Garcia',
            'rating' => 5,
            'text' => 'The dentist was very gentle and explained everything clearly. Highly recommended!',
            'services' => json_encode(['Root Canal Treatment (Endodontics)']),
            'date' => date('Y-m-d H:i:s', strtotime('-10 days'))
        ],
        [
            'patient_id' => isset($patientIds[0]) ? $patientIds[0] : $patientIds[0],
            'name' => 'Anonymous',
            'rating' => 3,
            'text' => 'Decent service but had to wait longer than expected.',
            'services' => json_encode(['Dental Fillings']),
            'date' => date('Y-m-d H:i:s', strtotime('-15 days'))
        ],
        [
            'patient_id' => isset($patientIds[1]) ? $patientIds[1] : $patientIds[0],
            'name' => 'Robert Johnson',
            'rating' => 2,
            'text' => 'The procedure was more painful than I expected.',
            'services' => json_encode(['Tooth Extraction']),
            'date' => date('Y-m-d H:i:s', strtotime('-20 days'))
        ]
    ];
    
    // Insert sample reviews
    foreach ($sampleReviews as $review) {
        $stmt = $conn->prepare("INSERT INTO reviews (patient_id, name, rating, text, services, date) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('isisss', 
            $review['patient_id'], 
            $review['name'], 
            $review['rating'], 
            $review['text'], 
            $review['services'], 
            $review['date']
        );
        $stmt->execute();
    }
    
    // Redirect to refresh the page
    header('Location: patient_feedback.php?sample_data_added=1');
    exit;
}

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    $conn->query("DELETE FROM reviews WHERE id = $del_id");
    header('Location: patient_feedback.php?deleted=1');
    exit;
}

// Check if the reviews table exists
$tableCheck = $conn->query("SHOW TABLES LIKE 'reviews'");
if ($tableCheck->num_rows == 0) {
    // Create reviews table if it doesn't exist
    $conn->query("CREATE TABLE IF NOT EXISTS reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        patient_id INT,
        name VARCHAR(255) NOT NULL,
        rating INT NOT NULL,
        text TEXT,
        services TEXT,
        date DATETIME DEFAULT CURRENT_TIMESTAMP,
        is_seen TINYINT(1) DEFAULT 0
    )");
}

// Fetch all reviews sorted by rating (highest to lowest)
$sql = "SELECT * FROM reviews ORDER BY rating DESC, date DESC";
$result = $conn->query($sql);

// Debug information
$debug_message = '';
if (!$result) {
    $debug_message = 'Error: ' . $conn->error;
} else if ($result->num_rows === 0) {
    $debug_message = 'No reviews found in database.';
}

// Mark all unseen feedback as seen
$conn->query("UPDATE reviews SET is_seen = 1 WHERE is_seen = 0");

// Dynamic greeting based on time of day (Asia/Manila)
date_default_timezone_set('Asia/Manila');
$hour = date('H');
if ($hour < 12) {
    $greeting = "Good morning";
} else if ($hour < 18) {
    $greeting = "Good afternoon";
} else {
    $greeting = "Good evening";
}

// Helper function to format date
function formatDate($dateString) {
    $date = new DateTime($dateString);
    return $date->format('M j, Y');
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

        /* Service tag styling */
        .service-tag {
            background-color: #EEF2FF;
            color: #4F46E5;
            border-radius: 9999px;
            padding: 0.25rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 500;
            display: inline-block;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }

        /* Search box with white background */
        #searchInput {
            background-color: white !important;
            color: #333 !important;
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
                        <div class="relative">
                            <input type="text" id="searchInput" placeholder="Search reviews..." 
                                class="w-64 px-4 py-2 pr-10 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white text-gray-800">
                            <i class="fas fa-search absolute right-3 top-3 text-gray-400"></i>
                        </div>
                    </div>
                    
                    <?php if (isset($_GET['sample_data_added'])): ?>
                        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
                            <p>Sample review data has been added successfully.</p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($debug_message)): ?>
                        <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-4">
                            <p><?php echo $debug_message; ?></p>
                            <form method="post" action="" class="mt-2">
                                <button type="submit" name="add_sample_data" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-1 px-2 rounded text-xs">
                                    Add Sample Reviews
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-xs text-left border-collapse">
                            <thead class="bg-gray-100 text-gray-700">
                                <tr>
                                    <th class="px-4 py-2">Name</th>
                                    <th class="px-4 py-2">Rating</th>
                                    <th class="px-4 py-2">Feedback</th>
                                    <th class="px-4 py-2">Services</th>
                                    <th class="px-4 py-2">Date</th>
                                    <th class="px-4 py-2 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="feedback-table-body">
                                <?php 
                                // Execute a fresh query to make sure we get the latest data
                                $result = $conn->query("SELECT r.*, p.profile_picture 
                                    FROM reviews r
                                    LEFT JOIN patients p ON r.patient_id = p.id
                                    ORDER BY r.rating DESC, r.date DESC");
                                
                                if ($result && $result->num_rows > 0):
                                    while ($row = $result->fetch_assoc()): 
                                        $isAnonymous = strtolower(trim($row['name'])) === 'anonymous';
                                        $services = json_decode($row['services'], true) ?: [];
                                ?>
                                    <tr class="border-b hover:bg-blue-50">
                                        <td class="px-4 py-2">
                                            <div class="flex items-center space-x-3">
                                                <div class="flex-shrink-0 h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center overflow-hidden">
                                                    <?php if (!empty($row['profile_picture']) && file_exists('../' . $row['profile_picture'])): ?>
                                                        <img src="../<?php echo htmlspecialchars($row['profile_picture']); ?>" alt="Profile photo" class="h-10 w-10 rounded-full object-cover">
                                                    <?php else: ?>
                                                        <img src="../assets/photos/default_avatar.png" alt="Profile photo" class="h-10 w-10 rounded-full object-cover">
                                                    <?php endif; ?>
                                                </div>
                                                <div>
                                                    <div class="font-semibold text-gray-900"><?= htmlspecialchars($row['name']) ?></div>
                                                    <?php if (isset($row['patient_id']) && $row['patient_id'] > 0): ?>
                                                    <div class="text-xs text-blue-600">
                                                        <a href="view_patient.php?id=<?= $row['patient_id'] ?>" class="hover:underline">
                                                            <i class="fas fa-user-circle mr-1"></i>View Patient
                                                        </a>
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-2">
                                            <div class="flex">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <?php if ($i <= $row['rating']): ?>
                                                        <i class="fas fa-star text-yellow-400"></i>
                                                    <?php else: ?>
                                                        <i class="far fa-star text-yellow-400"></i>
                                                    <?php endif; ?>
                                                <?php endfor; ?>
                                                <span class="ml-2 text-sm text-gray-600">(<?= $row['rating'] ?>)</span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-2">
                                            <p class="text-gray-700"><?= htmlspecialchars($row['text']) ?></p>
                                        </td>
                                        <td class="px-4 py-2">
                                            <?php if ($services && is_array($services)): ?>
                                                <?php foreach ($services as $service): ?>
                                                    <span class="service-tag"><?= htmlspecialchars($service) ?></span>
                                                <?php endforeach; ?>
                                            <?php elseif (is_string($row['services']) && !empty($row['services'])): ?>
                                                <span class="service-tag"><?= htmlspecialchars($row['services']) ?></span>
                                            <?php else: ?>
                                                <span class="text-gray-400 italic">No services specified</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap">
                                            <?= date('M j, Y', strtotime($row['date'])) ?>
                                        </td>
                                        <td class="px-4 py-2 text-right">
                                            <a href="patient_feedback.php?delete=<?= $row['id'] ?>" onclick="return confirm('Are you sure you want to delete this review?')" class="text-red-500 hover:text-red-700">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="px-4 py-8 text-center">
                                            <div class="flex flex-col items-center justify-center">
                                                <i class="fas fa-comment-slash text-gray-400 text-4xl mb-3"></i>
                                                <p class="text-gray-500 text-sm">No patient feedback found</p>
                                                <form method="post" action="" class="mt-4">
                                                    <button type="submit" name="add_sample_data" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-xs">
                                                        Add Sample Reviews
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
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
        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const tableRows = document.querySelectorAll('#feedback-table-body tr');
            
            tableRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchValue)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
        
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
        // Show success message if sample data was added
        if (window.location.search.includes('sample_data_added=1')) {
            showFeedbackSuccessModal('Sample review data has been added successfully!');
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