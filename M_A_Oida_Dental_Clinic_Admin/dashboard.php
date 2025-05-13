<?php
require_once('db.php');
require_once('session_handler.php');

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

// Get admin information
$admin_id = $_SESSION['admin_id'];
$admin_name = "Admin";
$sql = "SELECT name FROM admin_logins WHERE admin_id = '$admin_id'";
$result = $conn->query($sql);
if ($result && $row = $result->fetch_assoc()) {
    $admin_name = $row['name'];
}

// Get counts for dashboard stats
$totalPatients = 0;
$sql = "SELECT COUNT(*) as total FROM patients";
$result = $conn->query($sql);
if ($result && $row = $result->fetch_assoc()) {
    $totalPatients = $row['total'];
}

$totalAppointments = 0;
$sql = "SELECT COUNT(*) as total FROM appointments";
$result = $conn->query($sql);
if ($result && $row = $result->fetch_assoc()) {
    $totalAppointments = $row['total'];
}

$pendingAppointments = 0;
$sqlPending = "SELECT COUNT(*) as total FROM appointments WHERE status = 'pending'";
$resultPending = $conn->query($sqlPending);
if ($resultPending && $rowPending = $resultPending->fetch_assoc()) {
    $pendingAppointments = $rowPending['total'];
}

$unseenFeedback = 0;
$sqlFeedback = "SELECT COUNT(*) as total FROM reviews";
$resultFeedback = $conn->query($sqlFeedback);
if ($resultFeedback && $rowFeedback = $resultFeedback->fetch_assoc()) {
    $unseenFeedback = $rowFeedback['total'];
}

$greeting = 'Good Morning,';
date_default_timezone_set('Asia/Manila');
$hour = (int)date('G');
if ($hour >= 5 && $hour < 12) {
    $greeting = 'Good Morning,';
} elseif ($hour >= 12 && $hour < 18) {
    $greeting = 'Good Afternoon,';
} else {
    $greeting = 'Good Evening,';
}

// Get current year for appointments chart
$currentYear = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$prevYear = $currentYear - 1;
$nextYear = $currentYear + 1;

// Get appointment counts by month for the current year
$monthlyAppointments = array_fill(0, 12, 0);
$sql = "SELECT MONTH(appointment_date) as month, COUNT(*) as count 
        FROM appointments 
        WHERE YEAR(appointment_date) = $currentYear 
        GROUP BY MONTH(appointment_date)";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $monthlyAppointments[$row['month'] - 1] = $row['count'];
    }
}

// Dynamic patient count
$patientCount = 0;
$patientThisMonth = 0;
// Total patients
$sql = "SELECT COUNT(*) as total FROM patients";
$result = $conn->query($sql);
if ($result && $row = $result->fetch_assoc()) {
    $patientCount = $row['total'];
}
// Patients registered this month
$currentMonth = date('m');
$currentYear = date('Y');
$sqlMonth = "SELECT COUNT(*) as month_total FROM patients WHERE MONTH(created_at) = $currentMonth AND YEAR(created_at) = $currentYear";
$resultMonth = $conn->query($sqlMonth);
if ($resultMonth && $rowMonth = $resultMonth->fetch_assoc()) {
    $patientThisMonth = $rowMonth['month_total'];
}
// Appointments count
$appointmentCount = 0;
$appointmentThisMonth = 0;
// Total appointments (exclude cancelled)
$sql = "SELECT COUNT(*) as total FROM appointments WHERE status != 'cancelled'";
$result = $conn->query($sql);
if ($result && $row = $result->fetch_assoc()) {
    $appointmentCount = $row['total'];
}
// Appointments this month (exclude cancelled)
$sqlMonth = "SELECT COUNT(*) as month_total FROM appointments WHERE status != 'cancelled' AND MONTH(appointment_date) = $currentMonth AND YEAR(appointment_date) = $currentYear";
$resultMonth = $conn->query($sqlMonth);
if ($resultMonth && $rowMonth = $resultMonth->fetch_assoc()) {
    $appointmentThisMonth = $rowMonth['month_total'];
}

// Get monthly data for Appointments and Patients
$monthlyAppointments = [];
$monthlyPatients = [];

for ($i = 1; $i <= 12; $i++) {
    // Get appointment count for each month
    $sql = "SELECT COUNT(*) as count FROM appointments WHERE MONTH(appointment_date) = $i AND YEAR(appointment_date) = $currentYear";
    $result = $conn->query($sql);
    $monthlyAppointments[$i] = ($result && $row = $result->fetch_assoc()) ? $row['count'] : 0;
    
    // Get patient count for each month
    $sql = "SELECT COUNT(*) as count FROM patients WHERE MONTH(created_at) = $i AND YEAR(created_at) = $currentYear";
    $result = $conn->query($sql);
    $monthlyPatients[$i] = ($result && $row = $result->fetch_assoc()) ? $row['count'] : 0;
}

// Fetch latest admin name for sidebar/topbar
$admin_id = $_SESSION['admin_id'] ?? '';
$admin_name = 'Dr. Ardeen Dofiles Oida';
if ($admin_id) {
    $sql = "SELECT name FROM admin_logins WHERE admin_id = '$admin_id' LIMIT 1";
    $result = $conn->query($sql);
    if ($result && $row = $result->fetch_assoc()) {
        $admin_name = $row['name'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport"/>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <title>M&amp;A Oida Dental Clinic Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&amp;display=swap" rel="stylesheet"/>
    <script src="assets/js/mobile.js"></script>
    <style>
        @media (max-width: 768px) {
            #sidebar {
                position: fixed;
                left: -256px;
                top: 0;
                bottom: 0;
                z-index: 50;
                transition: left 0.3s ease;
            }
            
            #sidebar.active {
                left: 0;
            }
            
            .main-content {
                margin-left: 0 !important;
                width: 100% !important;
            }
            
            .stats-grid {
                grid-template-columns: 1fr !important;
            }
            
            .chart-container {
                width: 100% !important;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body class="bg-white text-gray-900">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <aside id="sidebar" class="flex flex-col bg-white border-r border-gray-200 w-64 min-w-[256px] py-6 px-4 transition-all duration-300">
            <div class="flex items-center justify-between mb-10">
                <div class="flex items-center space-x-2">
                    <img alt="M&A Oida Dental Clinic logo" class="w-8 h-8" src="assets/photo/logo.jpg"/>
                    <span class="sidebar-label text-sm font-semibold text-gray-900 whitespace-nowrap">
                        M&A Oida Dental Clinic
                    </span>
                </div>
                <button id="sidebarToggle" aria-label="Toggle menu" class="text-blue-600 hover:text-blue-700 focus:outline-none">
                    <i class="fas fa-bars fa-lg"></i>
                </button>
            </div>
            <!-- Profile Section -->
            <div class="flex flex-col items-center mb-8">
                <img alt="Profile photo" class="rounded-full w-24 h-24 object-cover mb-2" src="<?php echo (!empty($_SESSION['profile_photo']) && file_exists($_SESSION['profile_photo'])) ? htmlspecialchars($_SESSION['profile_photo']) : 'assets/photo/default_avatar.png'; ?>"/>
                <h3 class="text-center text-sm font-semibold text-gray-900 leading-tight">
                    <?php echo htmlspecialchars($admin_name); ?>
                </h3>
                <p class="text-center text-xs text-gray-500 mt-1">
                    Professional Dentist
                </p>
            </div>
            <nav class="flex flex-col space-y-2 text-gray-700 text-sm font-medium">
                <a class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-100" href="dashboard.php">
                    <div class="flex items-center justify-center w-8 h-8 bg-gray-200 rounded-lg text-gray-700">
                        <i class="fas fa-home"></i>
                    </div>
                    <span>Dashboard</span>
                </a>
                <a class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-100" href="appointments.php">
                    <div class="flex items-center justify-center w-8 h-8 bg-gray-200 rounded-lg text-gray-700 relative">
                        <i class="fas fa-calendar-alt"></i>
                        <?php if ($pendingAppointments > 0): ?>
                        <span class="absolute -top-1 -right-1 bg-red-600 text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center">
                            <?= $pendingAppointments ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <span>Appointments</span>
                </a>
                <a class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-100" href="patient_record.php">
                    <div class="flex items-center justify-center w-8 h-8 bg-gray-200 rounded-lg text-gray-700">
                        <i class="fas fa-user-injured"></i>
                    </div>
                    <span>Patient Records</span>
                </a>
                <a class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-100" href="patient_feedback.php">
                    <div class="flex items-center justify-center w-8 h-8 bg-gray-200 rounded-lg text-gray-700 relative">
                        <i class="fas fa-comment-alt"></i>
                        <?php if ($unseenFeedback > 0): ?>
                        <span class="absolute -top-1 -right-1 bg-red-600 text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center">
                            <?= $unseenFeedback ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <span>Patient Feedback</span>
                </a>
                <a class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-100" href="account_settings.php">
                    <div class="flex items-center justify-center w-8 h-8 bg-gray-200 rounded-lg text-gray-700">
                        <i class="fas fa-cog"></i>
                    </div>
                    <span>Account Settings</span>
                </a>
                <a class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-100" href="request_access.php">
                    <div class="flex items-center justify-center w-8 h-8 bg-gray-200 rounded-lg text-gray-700">
                        <i class="fas fa-lock"></i>
                    </div>
                    <span>Request for Access</span>
                </a>
                <a class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-100" href="#help-section">
                    <div class="flex items-center justify-center w-8 h-8 bg-gray-200 rounded-lg text-gray-700">
                        <i class="fas fa-question-circle"></i>
                    </div>
                    <span>Help & Support</span>
                </a>
            </nav>
            <a href="admin_login.php" class="mt-auto flex justify-center items-center space-x-2 text-red-600 hover:text-red-700 font-semibold text-sm">
                <i class="fas fa-sign-out-alt fa-lg"></i>
                <span>Logout</span>
            </a>
        </aside>
        <style>
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
        #sidebar.collapsed .flex.flex-col.items-center.mb-8 > h3,
        #sidebar.collapsed .flex.flex-col.items-center.mb-8 > p {
            display: none !important;
        }
        #sidebar.collapsed .flex.flex-col.items-center.mb-8 {
            align-items: flex-start !important;
        }
        #sidebar.collapsed img.w-24 {
            margin-bottom: 0 !important;
        }
        .active-sidebar-link {
            background-color: #f4f6f8; /* light gray */
            position: relative;
        }
        .active-sidebar-link::before {
            content: "";
            position: absolute;
            left: 0;
            top: 8px;
            bottom: 8px;
            width: 4px;
            background: #2563eb; /* blue-600 */
            border-radius: 8px;
        }
        .active-sidebar-link,
        .active-sidebar-link span,
        .active-sidebar-link i {
            color: #1e3a8a !important; /* blue-900 */
            font-weight: bold;
        }
        </style>
        <script>
        // Sidebar toggle logic
        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.getElementById('sidebarToggle');
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
        });
        </script>
        <!-- Main content -->
        <main class="flex-1 flex flex-col overflow-hidden">
            <!-- Top bar -->
            <header class="flex items-center justify-between bg-blue-300 px-6 py-3 border-b border-gray-300">
                <div class="text-gray-900 text-sm font-normal">
                    <span class="font-semibold">North Fairview Branch</span>
                </div>
                <div class="flex items-center space-x-4 ml-auto">
                    <button class="bg-purple-700 text-white text-xs font-semibold rounded-md px-4 py-1 hover:bg-purple-800">
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

            <!-- Content area -->
            <div class="flex flex-1 overflow-hidden">
                <!-- Main content -->
                <section class="flex-1 p-4 overflow-y-auto">
                    <!-- Breadcrumb Navigation -->
                    <?php 
                        $breadcrumbLabel = ' Dashboard';
                        // You can set a custom date range if needed:
                        // $breadcrumbDate = '04/01/2025 - 04/30/2025';
                        include 'breadcrumb.php'; 
                    ?>
                    <!-- End Breadcrumb Navigation -->
                    <!-- Dashboard Header Section -->
                    <section class="w-full bg-blue-400 rounded-xl shadow-md mb-8 flex flex-col md:flex-row items-center justify-between p-6 relative overflow-hidden" style="background: linear-gradient(90deg, #60a5fa 60%, #3b82f6 100%); min-height: 200px;">
                        <div class="z-10 flex-1">
                            <p class="text-white text-sm mb-1"><?php echo $greeting; ?></p>
                            <h2 class="text-2xl md:text-3xl font-bold text-white mb-1">
                                <?php echo htmlspecialchars($admin_name); ?>
                            </h2>
                            <div class="flex space-x-6 mt-6">
                                <!-- Appointments card -->
                                <a href="appointments.php" class="block rounded-lg p-6 bg-white bg-opacity-90 shadow hover:shadow-lg transition-all duration-300 hover:scale-105 min-w-[220px]">
                                    <div class="flex items-center space-x-4">
                                        <div class="flex items-center justify-center w-12 h-12 bg-blue-100 rounded-full">
                                            <i class="fas fa-calendar-check fa-lg text-blue-600"></i>
                                        </div>
                                        <div>
                                            <div class="text-2xl font-bold text-gray-900"><?php echo $appointmentCount; ?></div>
                                            <div class="text-base text-gray-700 font-semibold">Appointments</div>
                                            <div class="text-sm text-gray-500 mt-1">+<?php echo $appointmentThisMonth; ?></div>
                                            <span class="inline-block bg-blue-200 text-blue-800 text-xs rounded px-3 py-1 mt-2">This Month</span>
                                        </div>
                                    </div>
                                </a>
                                <!-- Patients card -->
                                <a href="patient_records.php" class="block rounded-lg p-6 bg-white bg-opacity-90 shadow hover:shadow-lg transition-all duration-300 hover:scale-105 min-w-[220px]">
                                    <div class="flex items-center space-x-4">
                                        <div class="flex items-center justify-center w-12 h-12 bg-blue-100 rounded-full">
                                            <i class="fas fa-users fa-lg text-blue-600"></i>
                                        </div>
                                        <div>
                                            <div class="text-2xl font-bold text-gray-900"><?php echo $patientCount; ?></div>
                                            <div class="text-base text-gray-700 font-semibold">Patients</div>
                                            <div class="text-sm text-gray-500 mt-1">+<?php echo $patientThisMonth; ?></div>
                                            <span class="inline-block bg-blue-200 text-blue-800 text-xs rounded px-3 py-1 mt-2">This Month</span>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </div>
                        <div class="hidden md:block z-0 absolute right-10 bottom-0 h-64">
                            <img src="assets/photo/tooths.png" alt="Tooth Illustration" class="h-full object-contain select-none pointer-events-none" style="min-width:260px;"/>
                        </div>
                    </section>
                    <!-- End Dashboard Header Section -->
                    <!-- Chart Section -->
                    <div class="bg-white rounded-xl shadow-md p-6 mt-6">
                        <!-- Year navigation -->
                        <div class="flex items-center justify-end mb-2 space-x-2">
                            <a href="?year=<?php echo $prevYear; ?>" class="px-3 py-1 rounded bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium">Previous</a>
                            <span class="px-4 py-1 rounded bg-blue-100 text-blue-700 font-semibold"><?php echo $currentYear; ?></span>
                            <a href="?year=<?php echo $nextYear; ?>" class="px-3 py-1 rounded bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium">Next</a>
                        </div>
                        <h2 class="text-xl font-bold mb-4">Appointments & Patients (Monthly)</h2>
                        <canvas id="revenueChart" height="100"></canvas>
                    </div>
                    <!-- End Chart Section -->
                </section>
            </div>
        </main>
    </div>
    <!-- Dotted Path Loader Overlay -->
    <div id="loaderOverlay" class="loader-overlay" style="display:none;">
        <div class="loader-dots">
            <div class="loader-dot dot1"></div>
            <div class="loader-dot dot2"></div>
            <div class="loader-dot dot3"></div> 
            <div class="loader-dot dot4"></div>
            <div class="loader-dot dot5"></div>
            <div class="loader-dot dot6"></div>
            <div class="loader-dot dot7"></div>
            <div class="loader-dot dot8"></div>
            <div class="loader-dot dot9"></div>
            <div class="loader-dot dot10"></div>
            <div class="loader-dot dot11"></div>
            <div class="loader-dot dot12"></div>
        </div>
    </div>
    <!-- Logout Confirmation Modal -->
    <div id="logoutModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-30 z-50 hidden">
      <div class="bg-white rounded-xl shadow-lg p-8 max-w-xs w-full text-center">
        <h2 class="text-lg font-semibold text-blue-700 mb-2">Confirm logout</h2>
        <hr class="my-2 border-blue-100">
        <p class="text-gray-700 mb-6">Are you sure you want to log out?</p>
        <div class="flex justify-center space-x-4">
          <button id="cancelLogout" class="px-4 py-1 rounded bg-blue-100 text-blue-700 font-semibold hover:bg-blue-200">Cancel</button>
          <button id="confirmLogout" class="px-4 py-1 rounded bg-blue-600 text-white font-semibold hover:bg-blue-700">OK</button>
        </div>
      </div>
    </div>
    <script>
    // Show loader on navigation
    document.querySelectorAll('a').forEach(function(link) {
        if (link.getAttribute('href') && !link.getAttribute('href').startsWith('#') && !link.hasAttribute('target')) {
            link.addEventListener('click', function(e) {
                // Only show loader for internal navigation
                document.getElementById('loaderOverlay').style.display = 'flex';
            });
        }
    });
    // Hide loader on page load
    window.addEventListener('DOMContentLoaded', function() {
        document.getElementById('loaderOverlay').style.display = 'none';
    });
    const appointmentsData = <?php echo json_encode($monthlyAppointments); ?>;
    const patientsData = <?php echo json_encode($monthlyPatients); ?>;

    const ctx = document.getElementById('revenueChart').getContext('2d');
    const chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [
                {
                    label: 'Appointments',
                    data: appointmentsData,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#3b82f6',
                    pointBorderColor: '#3b82f6',
                    pointRadius: 5,
                    borderWidth: 2,
                    order: 1
                },
                {
                    label: 'Patients',
                    data: patientsData,
                    borderColor: '#facc15',
                    backgroundColor: 'rgba(250, 204, 21, 0.15)',
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#facc15',
                    pointBorderColor: '#facc15',
                    pointRadius: 5,
                    borderWidth: 2,
                    order: 2
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false // We'll use a custom legend
                }
            },
            hover: {
                mode: 'nearest',
                intersect: true
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Custom legend with hover effect
    const legendContainer = document.createElement('div');
    legendContainer.className = 'flex justify-center mt-4 space-x-6';

    const legends = [
        { label: 'Appointments', color: 'bg-blue-500', datasetIndex: 0 },
        { label: 'Patients', color: 'bg-yellow-400', datasetIndex: 1 }
    ];

    legends.forEach(legend => {
        const legendItem = document.createElement('div');
        legendItem.className = 'flex items-center space-x-2 cursor-pointer';
        legendItem.innerHTML = `<span class="inline-block w-4 h-4 rounded-full ${legend.color}"></span><span>${legend.label}</span>`;
        legendItem.onmouseenter = () => {
            chart.data.datasets.forEach((ds, i) => {
                ds.borderWidth = (i === legend.datasetIndex) ? 4 : 1;
                ds.backgroundColor = (i === legend.datasetIndex)
                    ? (i === 0 ? 'rgba(59, 130, 246, 0.2)' : 'rgba(250, 204, 21, 0.3)')
                    : (i === 0 ? 'rgba(59, 130, 246, 0.05)' : 'rgba(250, 204, 21, 0.05)');
                ds.borderColor = (i === legend.datasetIndex)
                    ? (i === 0 ? '#2563eb' : '#eab308')
                    : (i === 0 ? '#93c5fd' : '#fde68a');
            });
            chart.update();
        };
        legendItem.onmouseleave = () => {
            chart.data.datasets[0].borderWidth = 2;
            chart.data.datasets[1].borderWidth = 2;
            chart.data.datasets[0].backgroundColor = 'rgba(59, 130, 246, 0.1)';
            chart.data.datasets[1].backgroundColor = 'rgba(250, 204, 21, 0.15)';
            chart.data.datasets[0].borderColor = '#3b82f6';
            chart.data.datasets[1].borderColor = '#facc15';
            chart.update();
        };
        legendContainer.appendChild(legendItem);
    });
    // Insert the custom legend after the chart
    const chartParent = document.getElementById('revenueChart').parentNode;
    const oldLegend = chartParent.querySelector('.flex.justify-center.mt-4.space-x-6');
    if (oldLegend) oldLegend.remove();
    chartParent.appendChild(legendContainer);
    document.querySelectorAll('a[href="admin_login.php"]').forEach(btn => {
      btn.addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('logoutModal').classList.remove('hidden');
      });
    });
    document.getElementById('cancelLogout').onclick = function() {
      document.getElementById('logoutModal').classList.add('hidden');
    };
    document.getElementById('confirmLogout').onclick = function() {
      window.location.href = 'admin_login.php';
    };
    </script>
</body>
</html>