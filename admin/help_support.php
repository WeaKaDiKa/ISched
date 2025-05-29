<?php
require_once('db.php');
require_once('session_handler.php');

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

// Load admin data
load_admin_data($conn);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Help & Support - M&A Oida Dental Clinic</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <?php require_once 'head.php' ?>
    <style>
        .accordion-header {
            cursor: pointer;
            padding: 15px;
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 5px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
        }

        .accordion-header:hover {
            background-color: #e9ecef;
        }

        .accordion-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #3b82f6;
            color: white;
            border-radius: 50%;
            margin-right: 15px;
        }

        .accordion-title {
            font-weight: 600;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
        }

        .accordion-content {
            padding: 0 15px 15px 15px;
            display: none;
            border: 1px solid #e9ecef;
            border-top: none;
            border-radius: 0 0 5px 5px;
            margin-top: -10px;
            margin-bottom: 15px;
            background-color: white;
        }

        .accordion-content.active {
            display: block;
        }

        .help-item {
            margin-bottom: 10px;
            padding: 10px;
            border-bottom: 1px solid #f0f0f0;
        }

        .help-item h4 {
            color: #3b82f6;
            margin-bottom: 5px;
        }

        .help-item p {
            color: #4b5563;
        }

        /* Mobile optimizations */
        @media (max-width: 768px) {
            .accordion-header {
                padding: 12px;
            }

            .accordion-title {
                font-size: 1rem;
            }

            .accordion-icon {
                width: 32px;
                height: 32px;
            }
        }
    </style>
</head>

<body class="bg-white text-gray-900">
    <div class="flex h-screen overflow-hidden">
        <?php require_once 'nav.php' ?>
        <!-- Main content -->
        <main class="flex-1 flex flex-col overflow-hidden">
            <?php require_once 'header.php' ?>

            <!-- Breadcrumb Navigation -->
            <nav class="flex items-center space-x-2 px-6 py-3 bg-gray-50 border-b border-gray-200">
                <ol class="flex items-center space-x-2 text-sm">
                    <li>
                        <a href="dashboard.php" class="text-blue-600 hover:text-blue-700">Dashboard</a>
                    </li>
                    <li>
                        <span class="text-gray-500">/</span>
                    </li>
                    <li>
                        <span class="text-gray-600">Help & Support</span>
                    </li>
                </ol>
            </nav>

            <!-- Content area -->
            <div class="flex-1 overflow-auto bg-gray-100 p-6">
                <div class="max-w-4xl mx-auto">
                    <h1 class="text-2xl font-bold text-blue-900 mb-6">Help & Support</h1>

                    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                        <div class="accordion">
                            <!-- Getting Started Section -->
                            <div class="accordion-item">
                                <div class="accordion-header" onclick="toggleAccordion(this)">
                                    <div class="accordion-title">
                                        <div class="accordion-icon">
                                            <i class="fas fa-rocket"></i>
                                        </div>
                                        Getting Started
                                    </div>
                                    <i class="fas fa-plus"></i>
                                </div>
                                <div class="accordion-content">
                                    <div class="help-item">
                                        <h4>Logging In</h4>
                                        <p>Use your admin credentials to log in to the system. If you've forgotten your password, contact the system administrator.</p>
                                    </div>
                                    <div class="help-item">
                                        <h4>Dashboard Overview</h4>
                                        <p>The dashboard provides a quick overview of appointments, patient statistics, and system notifications.</p>
                                    </div>
                                    <div class="help-item">
                                        <h4>Navigation</h4>
                                        <p>Use the sidebar menu to navigate between different sections of the admin panel.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Appointment Management Section -->
                            <div class="accordion-item">
                                <div class="accordion-header" onclick="toggleAccordion(this)">
                                    <div class="accordion-title">
                                        <div class="accordion-icon">
                                            <i class="fas fa-calendar-alt"></i>
                                        </div>
                                        Appointment Management
                                    </div>
                                    <i class="fas fa-plus"></i>
                                </div>
                                <div class="accordion-content">
                                    <div class="help-item">
                                        <h4>Viewing Appointments</h4>
                                        <p>The Appointments page shows all scheduled appointments. Use the tabs to filter by status (Pending, Upcoming, Rescheduled, Cancelled).</p>
                                    </div>
                                    <div class="help-item">
                                        <h4>Approving Appointments</h4>
                                        <p>To approve a pending appointment, click the green check button. The appointment will move to the Upcoming tab.</p>
                                    </div>
                                    <div class="help-item">
                                        <h4>Declining Appointments</h4>
                                        <p>To decline an appointment, click the red X button and provide a reason. The appointment will move to the Cancelled tab.</p>
                                    </div>
                                    <div class="help-item">
                                        <h4>Appointment Details</h4>
                                        <p>Click the Details button to view complete information about an appointment, including patient details and selected services.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Patient Records Section -->
                            <div class="accordion-item">
                                <div class="accordion-header" onclick="toggleAccordion(this)">
                                    <div class="accordion-title">
                                        <div class="accordion-icon">
                                            <i class="fas fa-user-md"></i>
                                        </div>
                                        Patient Records
                                    </div>
                                    <i class="fas fa-plus"></i>
                                </div>
                                <div class="accordion-content">
                                    <div class="help-item">
                                        <h4>Patient List</h4>
                                        <p>View all registered patients in the system. Use the search function to find specific patients.</p>
                                    </div>
                                    <div class="help-item">
                                        <h4>Patient Details</h4>
                                        <p>Click on a patient's name to view their complete profile, including contact information and appointment history.</p>
                                    </div>
                                    <div class="help-item">
                                        <h4>Medical History</h4>
                                        <p>Each patient profile includes their medical history, allergies, and other health information they've provided.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Notifications Section -->
                            <div class="accordion-item">
                                <div class="accordion-header" onclick="toggleAccordion(this)">
                                    <div class="accordion-title">
                                        <div class="accordion-icon">
                                            <i class="fas fa-bell"></i>
                                        </div>
                                        Notifications
                                    </div>
                                    <i class="fas fa-plus"></i>
                                </div>
                                <div class="accordion-content">
                                    <div class="help-item">
                                        <h4>System Notifications</h4>
                                        <p>The system will notify you of new appointments, cancellations, and other important events.</p>
                                    </div>
                                    <div class="help-item">
                                        <h4>Email Notifications</h4>
                                        <p>Configure email notifications in the Settings page to receive alerts about new appointments.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Technical Help Section -->
                            <div class="accordion-item">
                                <div class="accordion-header" onclick="toggleAccordion(this)">
                                    <div class="accordion-title">
                                        <div class="accordion-icon">
                                            <i class="fas fa-wrench"></i>
                                        </div>
                                        Technical Help
                                    </div>
                                    <i class="fas fa-plus"></i>
                                </div>
                                <div class="accordion-content">
                                    <div class="help-item">
                                        <h4>System Requirements</h4>
                                        <p>This system works best with modern browsers like Chrome, Firefox, Safari, or Edge.</p>
                                    </div>
                                    <div class="help-item">
                                        <h4>Troubleshooting</h4>
                                        <p>If you encounter any issues, try clearing your browser cache or using a different browser.</p>
                                    </div>
                                    <div class="help-item">
                                        <h4>Contact Support</h4>
                                        <p>For technical assistance, please contact our support team at support@oidadentalclinic.com or call (02) 8123-4567.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function toggleAccordion(element) {
            const content = element.nextElementSibling;
            const icon = element.querySelector('.fas');
            
            // Toggle the active class on the content
            content.classList.toggle('active');
            
            // Change the icon
            if (content.classList.contains('active')) {
                icon.classList.remove('fa-plus');
                icon.classList.add('fa-minus');
            } else {
                icon.classList.remove('fa-minus');
                icon.classList.add('fa-plus');
            }
        }

        // Open the first accordion by default
        document.addEventListener('DOMContentLoaded', function() {
            const firstAccordion = document.querySelector('.accordion-header');
            if (firstAccordion) {
                toggleAccordion(firstAccordion);
            }
        });
    </script>
</body>

</html>
