<?php
require_once('db.php');
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

require_once 'models/Patient.php';
require_once 'models/Appointment.php';
$patientModel = new Patient();
$appointmentModel = new Appointment();
$patients = $patientModel->getAllPatients();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <title>Patient Records - M&A Oida Dental Clinic</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <?php require_once 'head.php' ?>
</head>

<body class="bg-white text-gray-900">
    <div class="flex h-screen overflow-hidden">
        <?php require_once 'nav.php' ?>
        <!-- Main content -->
        <main class="flex-1 flex flex-col overflow-hidden">
            <!-- Top bar -->
            <?php require_once 'header.php' ?>
            <!-- Breadcrumb -->
            <nav class="flex items-center space-x-2 px-6 py-3 bg-gray-50 border-b border-gray-200">
                <ol class="flex items-center space-x-2">
                    <li>
                        <a href="dashboard.php" class="text-gray-600 hover:text-gray-900">
                            <i class="fas fa-home"></i>
                        </a>
                    </li>
                    <li>
                        <i class="fas fa-chevron-right text-gray-400"></i>
                    </li>
                    <li>
                        <span class="text-gray-600">Patient Records</span>
                    </li>
                </ol>
            </nav>

            <!-- Content area -->
            <div class="flex-1 overflow-y-auto p-4">
                <div class="w-full max-w-6xl mx-auto">
                    <div class="flex justify-between items-center mb-6">
                        <h1 class="text-[#0B2E61] text-xl font-semibold">Patient Records</h1>

                    </div>

                    <!--        <?php
                    // AND a.status = 'completed'
                    /*     $query = "SELECT p.*, pp.* 
              FROM patients p 
              LEFT JOIN patient_profiles pp ON p.id = pp.patient_id
              LEFT JOIN appointments a ON p.id = a.patient_id
              GROUP BY p.id
              ORDER BY p.first_name ASC";

                        $stmt = $conn->prepare($query);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $patients = [];
                        if ($result && $result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $patients[] = $row;
                            }
                        } */
                    ?>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php //foreach ($patients as $patient): ?>
                            <?php
                            /*                         // Fetch patient's approved appointments
                                                    $query2 = "SELECT a.*, 
                               CONCAT(d.first_name, ' ', d.last_name) AS doctor_name
                        FROM appointments a 
                        LEFT JOIN doctors d ON a.doctor_id = d.id
                        WHERE a.patient_id = ? AND a.status = 'approved'
                        ORDER BY a.appointment_date DESC, a.appointment_time DESC
                        ";

                                                    $stmt2 = $conn->prepare($query2);
                                                    $stmt2->bind_param("i", $patient['id']);
                                                    $stmt2->execute();
                                                    $result2 = $stmt2->get_result();
                                                    $patientAppointments = [];
                                                    if ($result2 && $result2->num_rows > 0) {
                                                        while ($apt = $result2->fetch_assoc()) {
                                                            $patientAppointments[] = $apt;
                                                        }
                                                    } */
                            ?>

                            <div
                                class="patient-card bg-white border border-gray-200 rounded-lg p-4 hover:shadow-lg transition-all">
                                <div class="flex items-center space-x-3 mb-3">
                                    <img src="<?php echo !empty($patient['profile_photo']) ? htmlspecialchars($patient['profile_photo']) : 'assets/photo/default_avatar.png'; ?>"
                                        alt="<?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?>"
                                        class="w-12 h-12 rounded-full object-cover">
                                    <div>
                                        <h3 class="text-gray-900 font-medium">
                                            <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?>
                                        </h3>

                                        <p class="text-sm text-gray-500">
                                            Patient ID: <?php echo htmlspecialchars($patient['id']); ?>
                                        </p>
                                    </div>
                                </div>

                                <?php if (!empty($patientAppointments)): ?>
                                    <div class="mt-3">
                                        <h4 class="text-sm font-medium text-gray-700 mb-2">Approved Appointments:</h4>
                                        <div class="space-y-2">
                                            <?php foreach ($patientAppointments as $apt): ?>
                                                <div class="bg-green-50 rounded-md p-2 border border-green-200">
                                                    <div class="flex justify-between items-center">
                                                        <div class="text-sm">
                                                            <div class="font-medium text-green-800">
                                                                <?php echo date('M j, Y', strtotime($apt['appointment_date'])); ?>
                                                            </div>
                                                            <div class="text-green-700">
                                                                <?php echo date('g:i A', strtotime($apt['appointment_time'])); ?>

                                                            </div>
                                                        </div>
                                                        <div class="text-right text-sm">
                                                            <div class="text-green-800">
                                                                <?php echo htmlspecialchars($apt['service_name']); ?>
                                                            </div>
                                                            <div class="text-green-700">
                                                                Dr. <?php echo htmlspecialchars($apt['doctor_name']); ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div class="mt-4 flex justify-end">
                                    <button onclick="viewPatientDetails(<?php echo htmlspecialchars($patient['id']); ?>)"
                                        class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                        View Details <i class="fas fa-chevron-right ml-1"></i>
                                    </button>
                                </div>
                            </div>
                        <?php // endforeach; ?>
                        <?php //if (empty($patients)): ?>
                            <p class="text-red-500">No patients found.</p>
                        <?php //endif; ?> -->

                    <?php
                    $query = "SELECT p.*, pp.* 
    FROM patients p 
    LEFT JOIN patient_profiles pp ON p.id = pp.patient_id
    LEFT JOIN appointments a ON p.id = a.patient_id
    GROUP BY p.id
    ORDER BY p.first_name ASC";

                    $stmt = $conn->prepare($query);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $patients = [];
                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $patients[] = $row;
                        }
                    }
                    ?>

                    <table id="patientTable" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Patient Name</th>
                                <th>Gender</th>
                                <th>Contact Number</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($patients as $patient): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($patient['gender']); ?></td>
                                    <td><?php echo htmlspecialchars($patient['phone_number']); ?></td>
                                    <td>
                                        <button class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600"
                                            onclick="viewPatient('<?= $patient['id'] ?>')">
                                            View
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <script>
                        $(document).ready(function () {
                            $('#patientTable').DataTable();
                        });
                    </script>


                </div>
            </div>
        </main>
    </div>

    <!-- Patient Details Modal -->
    <div id="patientModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-lg bg-white">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">Patient Details</h3>
                <button id="closeModal" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Patient Info Header -->
            <div class="flex items-center space-x-4 mb-6">
                <img id="modalPatientImage" class="h-16 w-16 rounded-full object-cover" src="" alt="Patient Photo">
                <div>
                    <h4 id="modalPatientName" class="text-xl font-semibold text-gray-900"></h4>
                    <p id="modalPatientId" class="text-sm text-gray-500"></p>
                </div>
            </div>

            <!-- Tabs -->
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                    <button
                        class="tab-button active border-blue-500 text-blue-600 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm"
                        data-tab="dental">
                        Dental History
                    </button>
                    <button
                        class="tab-button border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm"
                        data-tab="medical">
                        Medical History
                    </button>
                </nav>
            </div>

            <!-- Tab Content -->
            <div class="tab-content mt-6">
                <!-- Dental History Tab -->
                <div id="dental-tab" class="tab-pane active space-y-6">
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h5 class="font-medium text-gray-900 mb-3">Upcoming Appointments</h5>
                        <div class="space-y-3" id="upcomingAppointments">
                            <!-- Will be populated dynamically -->
                        </div>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h5 class="font-medium text-gray-900 mb-3">Past Appointments</h5>
                        <div class="space-y-3" id="appointmentHistory">
                            <!-- Will be populated dynamically -->
                        </div>
                    </div>
                </div>

                <!-- Medical History Tab -->
                <div id="medical-tab" class="tab-pane hidden space-y-6">
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h5 class="font-medium text-gray-900 mb-3">Medical Information</h5>
                        <div class="space-y-4" id="medicalInfo">
                            <!-- Medical information will be populated dynamically -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Make sure all sidebar links are clickable
        document.querySelectorAll('#sidebar a').forEach(link => {
            link.addEventListener('click', function (e) {
                if (this.getAttribute('href') === 'admin_login.php') {
                    e.preventDefault();
                    if (confirm('Are you sure you want to logout?')) {
                        window.location.href = 'admin_login.php';
                    }
                }
            });
        });

        // Update the search functionality
        const patientCards = document.querySelectorAll('.patient-card');


        // Modal functionality with real data
        const modal = document.getElementById('patientModal');
        const closeModal = document.getElementById('closeModal');
        const tabButtons = document.querySelectorAll('.tab-button');
        const tabPanes = document.querySelectorAll('.tab-pane');

        function viewPatientDetails(patientId) {
            // Your existing modal opening logic
            modal.classList.remove('hidden');
            // Add any additional logic needed for viewing patient details
        }

        // Close modal
        closeModal.addEventListener('click', () => modal.classList.add('hidden'));
        window.addEventListener('click', (e) => {
            if (e.target === modal) modal.classList.add('hidden');
        });

        // Tab switching
        tabButtons.forEach(button => {
            button.addEventListener('click', () => {
                tabButtons.forEach(btn => {
                    btn.classList.remove('active', 'border-blue-500', 'text-blue-600');
                    btn.classList.add('border-transparent', 'text-gray-500');
                });
                tabPanes.forEach(pane => pane.classList.add('hidden'));

                button.classList.add('active', 'border-blue-500', 'text-blue-600');
                button.classList.remove('border-transparent', 'text-gray-500');
                document.getElementById(`${button.dataset.tab}-tab`).classList.remove('hidden');
            });
        });
    </script>
</body>

</html>