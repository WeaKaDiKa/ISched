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
    <?php require_once 'head.php' ?>
</head>

<body class="bg-white text-gray-900">
    <div class="flex h-screen">
        <?php require_once 'nav.php' ?>
        <!-- Main content -->
        <main class="flex-1 flex flex-col overflow-x-hidden">
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

            <section class="mx-5 bg-white rounded-lg border border-gray-300 shadow-md p-4 mt-6">

                <div class="flex-col md:flex-row flex justify-between items-center mb-3">
                    <h1 class="text-[#0B2E61] text-xl font-semibold">Patient Records</h1>
                    <div class="flex items-center space-x-4">
                        <div class="relative">
                            <input type="text" id="searchInput" placeholder="Search patients..."
                                class="w-64 px-4 py-2 pr-10 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white text-gray-800"
                                style="background-color: white !important; color: #333 !important;">
                            <i class="fas fa-search absolute right-3 top-3 text-gray-400"></i>
                        </div>
                    </div>
                </div>

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

                <div class="my-4 w-full overflow-x-scroll">
                    <table id="patientTable" class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Patient Name</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Gender</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Contact Number</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($patients as $patient): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div
                                                class="flex-shrink-0 h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                                                <?php if (!empty($patient['profile_picture'])): ?>
                                                    <img src="<?php echo htmlspecialchars($patient['profile_picture']); ?>"
                                                        alt="Profile" class="h-10 w-10 rounded-full">
                                                <?php else: ?>
                                                    <i class="fas fa-user text-gray-400"></i>
                                                <?php endif; ?>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    <?php echo htmlspecialchars($patient['email'] ?? 'No email'); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                            <?php echo htmlspecialchars($patient['gender'] ?? 'Not specified'); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($patient['phone_number'] ?? 'No phone number'); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button
                                            class="text-blue-600 hover:text-blue-900 bg-blue-100 hover:bg-blue-200 px-3 py-1 rounded-md mr-2"
                                            onclick="viewPatient('<?= $patient['id'] ?>')">
                                            <i class="fas fa-eye mr-1"></i> View
                                        </button>

                                        <!--       <a href="edit_patient.php?id=<?php //echo $patient['id']; ?>"
                                                class="text-green-600 hover:text-green-900 bg-green-100 hover:bg-green-200 px-3 py-1 rounded-md">
                                                <i class="fas fa-edit mr-1"></i> Edit
                                            </a> -->
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <script>
                    $(document).ready(function () {
                        // Initialize DataTable with custom options
                        $('#patientTable').DataTable({
                            "pageLength": 10,
                            "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
                            "order": [[0, "asc"]],
                            "language": {
                                "search": "_INPUT_",
                                "searchPlaceholder": "Search patients...",
                                "lengthMenu": "Show _MENU_ entries",
                                "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                                "infoEmpty": "Showing 0 to 0 of 0 entries",
                                "infoFiltered": "(filtered from _MAX_ total entries)"
                            },
                            "dom": '<"flex justify-between items-center mb-4"<"flex-1"l><"flex-1 text-right"f>>rt<"flex justify-between items-center"<"flex-1"i><"flex-1 text-right"p>>',
                            "responsive": true,
                            "initComplete": function () {
                                // Replace the default search box with our custom one
                                $('.dataTables_filter').hide();
                                $('#searchInput').on('keyup', function () {
                                    $('#patientTable').DataTable().search($(this).val()).draw();
                                });
                            }
                        });
                    });
                    function viewPatient(patientId) {
                        fetch('getpatient.php?id=' + encodeURIComponent(patientId))
                            .then(response => response.json())
                            .then(data => {
                                if (data.error) {
                                    alert(data.error);
                                    return;
                                }

                                document.getElementById('modalPatientName').textContent = data.name;
                                document.getElementById('modalPatientId').textContent = 'Patient ID: ' + data.id;
                                document.getElementById('modalPatientImage').src = data.image || 'assets/photo/default_avatar.png';

                                document.getElementById('upcomingAppointments').innerHTML =
                                    (data.upcoming.length > 0)
                                        ? data.upcoming.map(a => `<div class="bg-blue-100 p-2 rounded">${a}</div>`).join('')
                                        : '<div class="text-gray-500 italic">No upcoming appointments.</div>';

                                document.getElementById('appointmentHistory').innerHTML =
                                    (data.past.length > 0)
                                        ? data.past.map(a => `<div class="bg-gray-100 p-2 rounded">${a}</div>`).join('')
                                        : '<div class="text-gray-500 italic">No past appointments.</div>';

                                const med = data.medical || {};

                                document.getElementById('med_patient_id').value = med.patient_id || patientId;
                                document.getElementById('blood_type').value = med.blood_type || '';
                                document.getElementById('allergies').value = med.allergies || '';
                                document.getElementById('blood_pressure').value = med.blood_pressure || '';
                                document.getElementById('heart_disease').value = med.heart_disease || '';
                                document.getElementById('diabetes').value = med.diabetes || '';
                                document.getElementById('current_medications').value = med.current_medications || '';
                                document.getElementById('medical_conditions').value = med.medical_conditions || '';
                                document.getElementById('last_physical_exam').value = med.last_physical_exam || '';

                                document.getElementById('patientModal').classList.remove('hidden');
                            })
                            .catch(err => {
                                console.error(err);
                                alert('Failed to load patient data.');
                            });
                    }

                    // Close modal
                    document.getElementById('closeModal').addEventListener('click', function () {
                        document.getElementById('patientModal').classList.add('hidden');
                    });
                </script>


            </section>

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
                    <button
                        class="tab-button border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm"
                        data-tab="dentals">
                        Dental Information
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

                        </div>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h5 class="font-medium text-gray-900 mb-3">Past Appointments</h5>
                        <div class="space-y-3" id="appointmentHistory">

                        </div>
                    </div>
                </div>

                <!-- Medical History Tab -->
                <div id="medical-tab" class="tab-pane hidden space-y-6">
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h5 class="font-medium text-gray-900 mb-3">Medical Information</h5>
                        <form id="medicalHistoryForm" class="space-y-6 bg-white p-6 rounded-lg shadow-md">
                            <input type="hidden" name="patient_id" id="med_patient_id">

                            <div>
                                <label for="blood_type" class="block text-sm font-medium text-gray-700">Blood
                                    Type</label>
                                <input type="text" name="blood_type" id="blood_type"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm p-2" />
                            </div>

                            <div>
                                <label for="allergies" class="block text-sm font-medium text-gray-700">Allergies</label>
                                <input type="text" name="allergies" id="allergies"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm p-2" />
                            </div>

                            <div>
                                <label for="blood_pressure" class="block text-sm font-medium text-gray-700">Blood
                                    Pressure</label>
                                <input type="text" name="blood_pressure" id="blood_pressure"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm p-2" />
                            </div>

                            <div>
                                <label for="heart_disease" class="block text-sm font-medium text-gray-700">Heart
                                    Disease</label>
                                <input type="text" name="heart_disease" id="heart_disease"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm p-2" />
                            </div>

                            <div>
                                <label for="diabetes" class="block text-sm font-medium text-gray-700">Diabetes</label>
                                <input type="text" name="diabetes" id="diabetes"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm p-2" />
                            </div>

                            <div>
                                <label for="current_medications" class="block text-sm font-medium text-gray-700">Current
                                    Medications</label>
                                <textarea name="current_medications" id="current_medications" rows="2"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm p-2"></textarea>
                            </div>

                            <div>
                                <label for="medical_conditions" class="block text-sm font-medium text-gray-700">Medical
                                    Conditions</label>
                                <textarea name="medical_conditions" id="medical_conditions" rows="2"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm p-2"></textarea>
                            </div>

                            <div>
                                <label for="last_physical_exam" class="block text-sm font-medium text-gray-700">Last
                                    Physical Exam</label>
                                <input type="date" name="last_physical_exam" id="last_physical_exam"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm p-2" />
                            </div>

                            <div class="pt-4">
                                <button type="submit"
                                    class="w-full bg-blue-600 text-white font-medium py-2 px-4 rounded-md hover:bg-blue-700 transition duration-200">
                                    Save
                                </button>
                            </div>
                        </form>
                        <script>
                            document.getElementById('medicalHistoryForm').addEventListener('submit', function (e) {
                                e.preventDefault();

                                const formData = new FormData(this);

                                fetch('update_medical.php', {
                                    method: 'POST',
                                    body: formData
                                })
                                    .then(res => res.json())
                                    .then(data => {
                                        if (data.success) {
                                            alert("Medical history updated!");
                                        } else {
                                            alert("Failed to update.");
                                        }
                                    })
                                    .catch(err => {
                                        console.error(err);
                                        alert("Something went wrong.");
                                    });
                            });

                        </script>
                    </div>
                </div>


                <div id="dentals-tab" class="tab-pane hidden space-y-6">
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h5 class="font-medium text-gray-900 mb-3">Dental Information</h5>
                        <div class="space-y-4" id="dentalInfo">
                            <style>
                                .section-title {
                                    margin-top: 30px;
                                    font-weight: bold;
                                }

                                .legend-table,
                                .occlusion-table {
                                    width: 100%;
                                    margin-top: 20px;
                                }

                                .legend-table td,
                                .occlusion-table td {
                                    padding: 3px 6px;
                                }

                                .teeth-row {
                                    display: flex;
                                    justify-content: center;
                                    margin: 10px 0;
                                }

                                .tooth {
                                    width: 28px;
                                    height: 28px;
                                    border: 1px solid #000;
                                    border-radius: 50%;
                                    line-height: 28px;
                                    font-size: 12px;
                                    margin: 2px;
                                }

                                .input-label {
                                    font-weight: bold;
                                    margin-right: 10px;
                                }
                            </style>

                            <h3>LAST INTRAORAL EXAMINATION</h3>

                            <form class="space-y-8 bg-white p-6 rounded-lg shadow">

                                <div>
                                    <h2 class="text-lg font-semibold mb-2">Temporary Teeth (Upper)</h2>
                                    <div class="grid grid-cols-10 gap-2">
                                        <template id="tooth-input-template"></template>

                                        <input type="text" name="tooth[55]" placeholder="55"
                                            class="text-center border rounded p-1" />
                                        <input type="text" name="tooth[54]" placeholder="54"
                                            class="text-center border rounded p-1" />
                                        <input type="text" name="tooth[53]" placeholder="53"
                                            class="text-center border rounded p-1" />
                                        <input type="text" name="tooth[52]" placeholder="52"
                                            class="text-center border rounded p-1" />
                                        <input type="text" name="tooth[51]" placeholder="51"
                                            class="text-center border rounded p-1" />
                                        <input type="text" name="tooth[61]" placeholder="61"
                                            class="text-center border rounded p-1" />
                                        <input type="text" name="tooth[62]" placeholder="62"
                                            class="text-center border rounded p-1" />
                                        <input type="text" name="tooth[63]" placeholder="63"
                                            class="text-center border rounded p-1" />
                                        <input type="text" name="tooth[64]" placeholder="64"
                                            class="text-center border rounded p-1" />
                                        <input type="text" name="tooth[65]" placeholder="65"
                                            class="text-center border rounded p-1" />
                                    </div>
                                </div>

                                <div>
                                    <h2 class="text-lg font-semibold mb-2">Permanent Teeth (Upper)</h2>
                                    <div class="grid grid-cols-8 md:grid-cols-16 gap-2">
                                        <input type="text" name="tooth[18]" placeholder="18"
                                            class="text-center border rounded p-1" />
                                        <input type="text" name="tooth[17]" placeholder="17"
                                            class="text-center border rounded p-1" />
                                        <input type="text" name="tooth[16]" placeholder="16"
                                            class="text-center border rounded p-1" />
                                        <input type="text" name="tooth[15]" placeholder="15"
                                            class="text-center border rounded p-1" />
                                        <input type="text" name="tooth[14]" placeholder="14"
                                            class="text-center border rounded p-1" />
                                        <input type="text" name="tooth[13]" placeholder="13"
                                            class="text-center border rounded p-1" />
                                        <input type="text" name="tooth[12]" placeholder="12"
                                            class="text-center border rounded p-1" />
                                        <input type="text" name="tooth[11]" placeholder="11"
                                            class="text-center border rounded p-1" />
                                        <input type="text" name="tooth[21]" placeholder="21"
                                            class="text-center border rounded p-1" />
                                        <input type="text" name="tooth[22]" placeholder="22"
                                            class="text-center border rounded p-1" />
                                        <input type="text" name="tooth[23]" placeholder="23"
                                            class="text-center border rounded p-1" />
                                        <input type="text" name="tooth[24]" placeholder="24"
                                            class="text-center border rounded p-1" />
                                        <input type="text" name="tooth[25]" placeholder="25"
                                            class="text-center border rounded p-1" />
                                        <input type="text" name="tooth[26]" placeholder="26"
                                            class="text-center border rounded p-1" />
                                        <input type="text" name="tooth[27]" placeholder="27"
                                            class="text-center border rounded p-1" />
                                        <input type="text" name="tooth[28]" placeholder="28"
                                            class="text-center border rounded p-1" />
                                    </div>
                                </div>

                                <div>
                                    <h2 class="text-lg font-semibold mb-2">Permanent Teeth (Lower)</h2>
                                    <div class="grid grid-cols-8 md:grid-cols-16 gap-2">
                                        <input type="text" name="tooth[48]" placeholder="48"
                                            class="text-center border rounded p-1" />
                                        <input type="text" name="tooth[47]" placeholder="47"
                                            class="text-center border rounded p-1" />
                                        <input type="text" name="tooth[46]" placeholder="46"
                                            class="text-center border rounded p-1" />
                                        <input type="text" name="tooth[45]" placeholder="45"
                                            class="text-center border rounded p-1" />
                                        <input type="text" name="tooth[44]" placeholder="44"
                                            class="text-center border rounded p-1" />
                                        <input type="text" name="tooth[43]" placeholder="43"
                                            class="text-center border rounded p-1" />
                                        <input type="text" name="tooth[42]" placeholder="42"
                                            class="text-center border rounded p-1" />
                                        <input type="text" name="tooth[41]" placeholder="41"
                                            class="text-center border rounded p-1" />
                                        <input type="text" name="tooth[31]" placeholder="31"
                                            class="text-center border rounded p-1" />
                                        <input type="text" name="tooth[32]" placeholder="32"
                                            class="text-center border rounded p-1" />
                                        <input type="text" name="tooth[33]" placeholder="33"
                                            class="text-center border rounded p-1" />
                                        <input type="text" name="tooth[34]" placeholder="34"
                                            class="text-center border rounded p-1" />
                                        <input type="text" name="tooth[35]" placeholder="35"
                                            class="text-center border rounded p-1" />
                                        <input type="text" name="tooth[36]" placeholder="36"
                                            class="text-center border rounded p-1" />
                                        <input type="text" name="tooth[37]" placeholder="37"
                                            class="text-center border rounded p-1" />
                                        <input type="text" name="tooth[38]" placeholder="38"
                                            class="text-center border rounded p-1" />
                                    </div>
                                </div>

                                <div>
                                    <h2 class="text-lg font-semibold mb-2">Temporary Teeth (Lower)</h2>
                                    <div class="grid grid-cols-10 gap-2">
                                        <input type="text" name="tooth[85]" placeholder="85"
                                            class="text-center border rounded p-1" />
                                        <input type="text" name="tooth[84]" placeholder="84"
                                            class="text-center border rounded p-1" />
                                        <input type="text" name="tooth[83]" placeholder="83"
                                            class="text-center border rounded p-1" />
                                        <input type="text" name="tooth[82]" placeholder="82"
                                            class="text-center border rounded p-1" />
                                        <input type="text" name="tooth[81]" placeholder="81"
                                            class="text-center border rounded p-1" />
                                        <input type="text" name="tooth[71]" placeholder="71"
                                            class="text-center border rounded p-1" />
                                        <input type="text" name="tooth[72]" placeholder="72"
                                            class="text-center border rounded p-1" />
                                        <input type="text" name="tooth[73]" placeholder="73"
                                            class="text-center border rounded p-1" />
                                        <input type="text" name="tooth[74]" placeholder="74"
                                            class="text-center border rounded p-1" />
                                        <input type="text" name="tooth[75]" placeholder="75"
                                            class="text-center border rounded p-1" />
                                    </div>
                                </div>

                                <div>
                                    <h2 class="text-lg font-semibold mb-2">Other Notes</h2>
                                    <textarea name="notes" rows="4" class="w-full border rounded p-2"></textarea>
                                </div>


                                <div class="section-title">Legend</div>
                                <table class="legend-table">
                                    <tr>
                                        <td><strong>D</strong> - Decayed</td>
                                        <td><strong>J</strong> - Jacket Crown</td>
                                        <td><strong>X</strong> - Extraction due to Caries</td>
                                    </tr>
                                    <tr>
                                        <td><strong>M</strong> - Missing due to Caries</td>
                                        <td><strong>A</strong> - Amalgam Filling</td>
                                        <td><strong>XO</strong> - Extraction due to other causes</td>
                                    </tr>
                                    <tr>
                                        <td><strong>F</strong> - Filled</td>
                                        <td><strong>A-B</strong> - Abutment</td>
                                        <td><strong>✓</strong> - Present Teeth</td>
                                    </tr>
                                    <tr>
                                        <td><strong>I</strong> - Caries for Extraction</td>
                                        <td><strong>P</strong> - Pontic</td>
                                        <td><strong>Cn</strong> - Congenitally Missing</td>
                                    </tr>
                                    <tr>
                                        <td><strong>RF</strong> - Root Fragment</td>
                                        <td><strong>In</strong> - Inlay</td>
                                        <td><strong>Sp</strong> - Supernumerary</td>
                                    </tr>
                                    <tr>
                                        <td><strong>MO</strong> - Missing Other Causes</td>
                                        <td><strong>FX</strong> - Fixed Cure Composite</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Im</strong> - Impacted Tooth</td>
                                        <td><strong>Rm</strong> - Removable Denture</td>
                                    </tr>
                                </table>


                                <div class="section-title">Additional Notes</div>
                                <ul>
                                    <li><strong>Periodontal Screening:</strong>
                                        <ul>
                                            <li><input type="checkbox"> Gingivitis</li>
                                            <li><input type="checkbox"> Early Periodontics</li>
                                            <li><input type="checkbox"> Moderate Periodontics</li>
                                            <li><input type="checkbox"> Advanced Periodontics</li>
                                        </ul>
                                    </li>
                                    <li><strong>Occlusion:</strong>
                                        <ul>
                                            <li><input type="checkbox"> Class (Molar)</li>
                                            <li><input type="checkbox"> Overjet</li>
                                            <li><input type="checkbox"> Overbite</li>
                                            <li><input type="checkbox"> Midline Deviation</li>
                                            <li><input type="checkbox"> Crossbite</li>
                                        </ul>
                                    </li>
                                    <li><strong>Appliances:</strong>
                                        <ul>
                                            <li><input type="checkbox"> Orthodontic</li>
                                            <li><input type="checkbox"> Stayplate</li>
                                            <li><input type="checkbox"> Others</li>
                                        </ul>
                                    </li>
                                    <li><strong>TMD:</strong>
                                        <ul>
                                            <li><input type="checkbox"> Clenching</li>
                                            <li><input type="checkbox"> Clicking</li>
                                            <li><input type="checkbox"> Trismus</li>
                                            <li><input type="checkbox"> Muscle Spasm</li>
                                        </ul>
                                    </li>
                                </ul>

                                <div>
                                    <button type="submit"
                                        class="w-full bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700">
                                        Save Dental Chart
                                    </button>
                                </div>
                            </form>

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
        const searchInput = document.getElementById('searchInput');
        const patientCards = document.querySelectorAll('.patient-card');

        searchInput.addEventListener('input', function (e) {
            const searchTerm = e.target.value.toLowerCase();
            patientCards.forEach(card => {
                const patientName = card.querySelector('h3').textContent.toLowerCase();
                const patientId = card.querySelector('p').textContent.toLowerCase();
                if (patientName.includes(searchTerm) || patientId.includes(searchTerm)) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        });

        const modal = document.getElementById('patientModal');
        const closeModal = document.getElementById('closeModal');
        const tabButtons = document.querySelectorAll('.tab-button');
        const tabPanes = document.querySelectorAll('.tab-pane');

        function viewPatientDetails(patientId) {
            modal.classList.remove('hidden');
        }


        closeModal.addEventListener('click', () => modal.classList.add('hidden'));
        window.addEventListener('click', (e) => {
            if (e.target === modal) modal.classList.add('hidden');
        });

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