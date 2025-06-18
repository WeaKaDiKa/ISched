<?php
require_once('db.php');
require_once('session_handler.php');

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

// Check for upcoming appointments and create notifications
require_once('upcoming_appointments_notifications.php');

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


// Get current year for appointments chart
$currentYear = isset($_GET['year']) ? (int) $_GET['year'] : date('Y');
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

$monthAbbr = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

$monthlyAppointments = [];
$monthlyPatients = [];

for ($i = 1; $i <= 12; $i++) {
    $monthName = $monthAbbr[$i];

    $sql = "SELECT COUNT(*) as count FROM appointments WHERE MONTH(appointment_date) = $i AND YEAR(appointment_date) = $currentYear";
    $result = $conn->query($sql);
    $monthlyAppointments[$monthName] = ($result && $row = $result->fetch_assoc()) ? (int) $row['count'] : 0;

    $sql = "SELECT COUNT(*) as count FROM patients WHERE MONTH(created_at) = $i AND YEAR(created_at) = $currentYear";
    $result = $conn->query($sql);
    $monthlyPatients[$monthName] = ($result && $row = $result->fetch_assoc()) ? (int) $row['count'] : 0;
}


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
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport" />
    <title>M&amp;A Oida Dental Clinic Dashboard</title>
    <?php require_once 'head.php' ?>

    <style>
        @media (max-width: 768px) {

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
        <?php require_once 'nav.php' ?>


        <!-- Main content -->
        <main class="flex-1 flex flex-col overflow-x-hidden">
            <!-- Top bar -->
            <?php require_once 'header.php'; ?>
            <!-- Content area -->
            <div class="flex flex-1 overflow-hidden">
                <!-- Main content -->
                <section class="flex-1 p-4 overflow-y-auto">
                    <!-- Breadcrumb Navigation -->
                    <?php
                    $breadcrumbLabel = ' Dashboard';

                    include 'breadcrumb.php';
                    ?>

                    <section
                        class="w-full bg-blue-400 rounded-xl shadow-md mb-8 flex flex-col md:flex-row items-center justify-between p-6 relative overflow-hidden"
                        style="background: linear-gradient(90deg, #60a5fa 60%, #3b82f6 100%); min-height: 200px;">
                        <div class="z-10 flex-1">
                            <p class="text-white text-sm mb-1"><?php echo $greeting; ?></p>
                            <h2 class="text-2xl md:text-3xl font-bold text-white mb-1">
                                <?php echo htmlspecialchars($admin_name); ?>
                            </h2>
                            <div class="flex flex-col md:flex-row md:space-x-6 space-y-6 md:space-y-0 mt-6">

                                <!-- Appointments card -->
                                <a href="appointments.php"
                                    class="block rounded-lg p-6 bg-white bg-opacity-90 shadow hover:shadow-lg transition-all duration-300 hover:scale-105 min-w-[200px]">
                                    <div class="flex items-center space-x-4">
                                        <div
                                            class="flex items-center justify-center w-12 h-12 bg-blue-100 rounded-full">
                                            <i class="fas fa-calendar-check fa-lg text-blue-600"></i>
                                        </div>
                                        <div>
                                            <div class="text-2xl font-bold text-gray-900">
                                                <?php echo $appointmentCount; ?>
                                            </div>
                                            <div class="text-base text-gray-700 font-semibold">Appointments</div>
                                            <div class="text-sm text-gray-500 mt-1">
                                                +<?php echo $appointmentThisMonth; ?></div>
                                            <span
                                                class="inline-block bg-blue-200 text-blue-800 text-xs rounded px-3 py-1 mt-2">This
                                                Month</span>
                                        </div>
                                    </div>
                                </a>
                                <!-- Patients card -->
                                <a href="patient_record.php"
                                    class="block rounded-lg p-6 bg-white bg-opacity-90 shadow hover:shadow-lg transition-all duration-300 hover:scale-105 min-w-[200px]">
                                    <div class="flex items-center space-x-4">
                                        <div
                                            class="flex items-center justify-center w-12 h-12 bg-blue-100 rounded-full">
                                            <i class="fas fa-users fa-lg text-blue-600"></i>
                                        </div>
                                        <div>
                                            <div class="text-2xl font-bold text-gray-900"><?php echo $patientCount; ?>
                                            </div>
                                            <div class="text-base text-gray-700 font-semibold">Patients</div>
                                            <div class="text-sm text-gray-500 mt-1">+<?php echo $patientThisMonth; ?>
                                            </div>
                                            <span
                                                class="inline-block bg-blue-200 text-blue-800 text-xs rounded px-3 py-1 mt-2">This
                                                Month</span>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </div>
                        <div class="hidden md:block z-0 absolute right-10 bottom-0 h-64">
                            <img src="assets/photo/tooths.png" alt="Tooth Illustration"
                                class="h-full object-contain select-none pointer-events-none"
                                style="min-width:260px;" />
                        </div>
                    </section>

                    <div
                        class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex justify-between items-start mb-4">
                            <h3 class="text-lg font-bold text-gray-800">Appointments Overview</h3>
                            <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
                                Total: <?php echo $appointmentCount; ?>
                            </span>
                        </div>
                        <?php
                        // For counting all appointments (past and future) in current period
                        $weeklyAppointments = $conn->query("SELECT COUNT(*) FROM appointments 
                                   WHERE status != 'cancelled'
                                   AND YEARWEEK(appointment_date, 1) = YEARWEEK(CURDATE(), 1)")->fetch_row()[0];

                        $monthlyAppointments = $conn->query("SELECT COUNT(*) FROM appointments 
                                    WHERE status != 'cancelled'
                                    AND MONTH(appointment_date) = MONTH(CURDATE()) 
                                    AND YEAR(appointment_date) = YEAR(CURDATE())")->fetch_row()[0];

                        $yearlyAppointments = $conn->query("SELECT COUNT(*) FROM appointments 
                                   WHERE status != 'cancelled'
                                   AND YEAR(appointment_date) = YEAR(CURDATE())")->fetch_row()[0];
                        ?>
                        <!-- Time Period Stats -->
                        <div class="grid grid-cols-3 gap-4 mb-6">
                            <!-- Weekly -->
                            <div class="text-center p-3 bg-blue-50 rounded-lg border border-blue-100">
                                <div class="text-2xl font-bold text-blue-700"><?php echo $weeklyAppointments; ?></div>
                                <div class="text-xs text-blue-600 font-medium mt-1">THIS WEEK</div>

                            </div>

                            <!-- Monthly -->
                            <div class="text-center p-3 bg-blue-50 rounded-lg border border-blue-100">
                                <div class="text-2xl font-bold text-blue-700"><?php echo $monthlyAppointments; ?></div>
                                <div class="text-xs text-blue-600 font-medium mt-1">THIS MONTH</div>

                            </div>

                            <!-- Yearly -->
                            <div class="text-center p-3 bg-blue-50 rounded-lg border border-blue-100">
                                <div class="text-2xl font-bold text-blue-700"><?php echo $yearlyAppointments; ?></div>
                                <div class="text-xs text-blue-600 font-medium mt-1">THIS YEAR</div>

                            </div>
                        </div>

                        <!-- Upcoming Appointments -->
                        <div class="border-t border-gray-200 pt-4">
                            <h4 class="text-sm font-semibold text-gray-700 mb-3">UPCOMING APPOINTMENTS</h4>
                            <div class="space-y-3">
                                <?php

                                $sql = "SELECT a.*, 
        p.first_name, 
        p.middle_name, 
        p.last_name, 
        CONCAT(al.first_name, ' ', al.last_name) AS dentistname
        FROM appointments a 
        LEFT JOIN patients p ON a.patient_id = p.id 
        LEFT JOIN admin_logins al ON a.dental_id = al.id
        WHERE a.status IN ('booked')
        ORDER BY a.appointment_date ASC, a.appointment_time ASC";
                                $result = $conn->query($sql);
                                if ($result && $result->num_rows > 0) {
                                    $count = 0;
                                    $today = date('Y-m-d');
                                    $oneWeekLater = date('Y-m-d', strtotime('+1 week'));

                                    while ($row = $result->fetch_assoc()) {
                                        if ($count >= 3)
                                            break;

                                        $appointmentDate = $row['appointment_date'];

                                        // Only show appointments within the next week
                                        if ($appointmentDate >= $today && $appointmentDate <= $oneWeekLater) {
                                            $count++;
                                            $patientName = htmlspecialchars(trim($row['first_name'] . ' ' . ($row['middle_name'] ? $row['middle_name'] . ' ' : '') . $row['last_name']));
                                            $dentistName = htmlspecialchars($row['dentistname'] ?? 'Not assigned');
                                            $service = htmlspecialchars($row['services'] ?? 'General Consultation');
                                            $time = !empty($row['appointment_time']) ? date('g:i A', strtotime($row['appointment_time'])) : 'Time not set';

                                            // Format date display
                                            if ($appointmentDate == $today) {
                                                $dateDisplay = 'Today';
                                            } elseif ($appointmentDate == date('Y-m-d', strtotime('+1 day'))) {
                                                $dateDisplay = 'Tomorrow';
                                            } else {
                                                $dateDisplay = date('D, M j', strtotime($appointmentDate));
                                            }
                                            ?>
                                            <div
                                                class="flex items-center p-3 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                                                <div
                                                    class="flex-shrink-0 w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                                    <i class="fas fa-tooth text-blue-600"></i>
                                                </div>
                                                <div class="ml-3 flex-1 min-w-0">
                                                    <p class="text-sm font-medium text-gray-900 truncate"><?php echo $service; ?>
                                                    </p>
                                                    <div class="flex justify-between flex-wrap">
                                                        <p class="text-xs text-gray-500"><?php echo $patientName; ?></p>
                                                        <p class="text-xs text-gray-500">Dr. <?php echo $dentistName; ?></p>
                                                        <p class="text-xs font-medium text-blue-600 w-full mt-1">
                                                            <?php echo $dateDisplay . ', ' . $time; ?>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php
                                        }
                                    }

                                    if ($count === 0) {
                                        echo '<p class="text-sm text-gray-500 text-center py-4">No upcoming appointments this week</p>';
                                    }
                                } else {
                                    echo '<p class="text-sm text-gray-500 text-center py-4">No booked appointments found</p>';
                                }
                                ?>
                            </div>
                        </div>

                        <a href="appointments.php"
                            class="mt-4 inline-flex items-center text-blue-600 hover:text-blue-800 text-sm font-medium">
                            View all appointments
                            <i class="fas fa-chevron-right ml-1"></i>
                        </a>
                    </div>


                    <!-- End Dashboard Header Section -->
                    <!-- Chart Section -->
                    <div class="bg-white rounded-xl shadow-md p-6 mt-6" style="display:none;">
                        <!-- Year navigation -->
                        <div class="flex items-center justify-end mb-2 space-x-2">
                            <a href="?year=<?php echo $prevYear; ?>"
                                class="px-3 py-1 rounded bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium">Previous</a>
                            <span
                                class="px-4 py-1 rounded bg-blue-100 text-blue-700 font-semibold"><?php echo $currentYear; ?></span>
                            <a href="?year=<?php echo $nextYear; ?>"
                                class="px-3 py-1 rounded bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium">Next</a>
                        </div>
                        <h2 class="text-xl font-bold mb-4">Appointments & Patients (Monthly)</h2>
                        <canvas id="revenueChart" height="100"></canvas>
                    </div>
                    <!-- End Chart Section -->
                </section>
            </div>
        </main>
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

        const appointmentsData = <?php echo json_encode($monthlyAppointments); ?>;
        const patientsData = <?php echo json_encode($monthlyPatients); ?>;


        console.log(appointmentsData);
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