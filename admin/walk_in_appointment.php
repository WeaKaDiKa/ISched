<?php

require_once 'db.php';
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

// Fetch all patients for the dropdown
$sql = "SELECT id, CONCAT(first_name, ' ', middle_name, ' ', last_name) as full_name 
        FROM patients 
        ORDER BY first_name, middle_name, last_name";
$result = $conn->query($sql);
$patients = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $patients[] = $row;
    }
}

// Available services
$services = [
    'Dental Checkup',
    'Teeth Cleaning',
    'Tooth Extraction',
    'Dental Filling',
    'Root Canal',
    'Dental Crown',
    'Dental Bridge',
    'Dental Implant',
    'Orthodontics',
    'Teeth Whitening'
];

// Available time slots (9 AM to 5 PM, 1-hour intervals)
$timeSlots = [];
for ($hour = 9; $hour <= 17; $hour++) {
    $timeSlots[] = sprintf('%02d:00:00', $hour);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Walk-in Appointment - M&A Oida Dental Clinic</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet" />
</head>

<body class="bg-gray-100">
    <div class="min-h-screen p-6">
        <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-md p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Walk-in Appointment Form</h1>
                <a href="appointments.php" class="text-blue-600 hover:text-blue-800">
                    <i class="fas fa-arrow-left"></i> Back to Appointments
                </a>
            </div>

            <form id="appointmentForm" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="patient">
                        Patient
                    </label>
                    <select class="w-full border border-gray-300 rounded-md px-3 py-2" id="patient" name="patient_id"
                        required>
                        <option value="">Select a patient</option>
                        <?php foreach ($patients as $patient): ?>
                            <option value="<?php echo htmlspecialchars($patient['id']); ?>">
                                <?php echo htmlspecialchars($patient['full_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="services">
                        Services
                    </label>
                    <select class="w-full border border-gray-300 rounded-md px-3 py-2" id="services" name="services"
                        required>
                        <option value="">Select a service</option>
                        <?php foreach ($services as $service): ?>
                            <option value="<?php echo htmlspecialchars($service); ?>">
                                <?php echo htmlspecialchars($service); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="appointment_date">
                        Appointment Date
                    </label>
                    <input class="w-full border border-gray-300 rounded-md px-3 py-2" type="date" id="appointment_date"
                        name="appointment_date" min="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="appointment_time">
                        Appointment Time
                    </label>
                    <select class="w-full border border-gray-300 rounded-md px-3 py-2" id="appointment_time"
                        name="appointment_time" required>
                        <option value="">Select a time</option>
                        <?php foreach ($timeSlots as $time): ?>
                            <option value="<?php echo htmlspecialchars($time); ?>">
                                <?php echo date('g:i A', strtotime($time)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="pt-4">
                    <button type="submit" class="w-full bg-blue-600 text-white rounded-md px-4 py-2 hover:bg-blue-700">
                        Create Appointment
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="successModal"
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
            <p class="text-gray-600 mb-6">Appointment created successfully!<br>Reference Number: <span id="successRef"
                    class="font-semibold"></span></p>
            <button onclick="closeSuccessModal()"
                class="w-full bg-green-400 text-white font-semibold py-2 rounded hover:bg-green-500 transition">OK</button>
        </div>
    </div>
    <script>
        function showSuccessModal(ref) {
            document.getElementById('successRef').textContent = ref;
            const modal = document.getElementById('successModal');
            const content = document.getElementById('successModal').querySelector('div.bg-white');
            modal.classList.remove('hidden');
            setTimeout(() => {
                content.classList.remove('scale-90', 'opacity-0');
                content.classList.add('scale-100', 'opacity-100');
            }, 10);
        }
        function closeSuccessModal() {
            const modal = document.getElementById('successModal');
            const content = document.getElementById('successModal').querySelector('div.bg-white');
            content.classList.remove('scale-100', 'opacity-100');
            content.classList.add('scale-90', 'opacity-0');
            setTimeout(() => {
                modal.classList.add('hidden');
                window.location.href = 'appointments.php';
            }, 300);
        }
        document.getElementById('appointmentForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('create_appointment.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showSuccessModal(data.reference_number);
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while creating the appointment.');
                });
        });
    </script>
</body>

</html>