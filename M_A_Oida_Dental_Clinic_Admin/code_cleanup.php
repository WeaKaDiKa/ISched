<?php
// Code and File Cleanup Utility
require_once('db.php');
session_start();

echo "<h1>Code and File Cleanup Utility</h1>";

// List of redundant files that can be safely removed
$redundantFiles = [
    'add_is_seen_column.php',      // Replaced by db_cleanup.php
    'fix_appointments.php',        // Replaced by db_cleanup.php
    'fix_appointments_direct.php', // Replaced by db_cleanup.php
    'add_reference_number.php',    // Functionality merged into db_cleanup.php
    'fix_password.php',            // Can be merged into account_settings.php
    'hash_password.php',           // One-time utility, no longer needed
    'test_appointments.php',       // Test file, not needed in production
    'check_appointments.php'       // Redundant with appointments.php
];

echo "<h2>Redundant Files (Safe to Delete)</h2>";
echo "<p>These files can be safely deleted as their functionality has been merged or is no longer needed:</p>";
echo "<ul>";
foreach ($redundantFiles as $file) {
    echo "<li>$file";
    if (file_exists($file)) {
        echo " - <span style='color:orange'>Exists</span>";
    } else {
        echo " - <span style='color:gray'>Not found</span>";
    }
    echo "</li>";
}
echo "</ul>";

// List of files with SQL queries that need standardization
$filesToStandardize = [
    'dashboard.php',
    'appointments.php',
    'patient_record.php',
    'account_settings.php',
    'patient_feedback.php',
    'manage_access_requests.php'
];

echo "<h2>Files with SQL Queries to Standardize</h2>";
echo "<p>These files contain SQL queries that should be standardized for better performance and security:</p>";
echo "<ul>";
foreach ($filesToStandardize as $file) {
    echo "<li>$file";
    if (file_exists($file)) {
        echo " - <span style='color:green'>Exists</span>";
    } else {
        echo " - <span style='color:red'>Not found</span>";
    }
    echo "</li>";
}
echo "</ul>";

// Recommendations for code improvement
echo "<h2>Code Improvement Recommendations</h2>";
echo "<ol>";
echo "<li><strong>Create a functions.php file</strong> - Move common functions to a central file to avoid code duplication</li>";
echo "<li><strong>Implement prepared statements</strong> - Replace direct SQL queries with prepared statements for better security</li>";
echo "<li><strong>Standardize error handling</strong> - Create a consistent approach to error handling across all files</li>";
echo "<li><strong>Use a template system</strong> - Separate HTML from PHP logic for better maintainability</li>";
echo "<li><strong>Add input validation</strong> - Ensure all user inputs are properly validated</li>";
echo "</ol>";

// Create functions.php file with common functions
echo "<h2>Creating functions.php</h2>";

$functionsContent = '<?php
// Common functions for M&A Oida Dental Clinic Admin
require_once(\'db.php\');

/**
 * Get count of pending appointments
 * @return int Number of pending appointments
 */
function getPendingAppointmentsCount() {
    global $conn;
    $count = 0;
    $sql = "SELECT COUNT(*) as total FROM appointments WHERE status = \'pending\'";
    $result = $conn->query($sql);
    if ($result && $row = $result->fetch_assoc()) {
        $count = $row[\'total\'];
    }
    return $count;
}

/**
 * Get count of unseen feedback
 * @return int Number of unseen feedback entries
 */
function getUnseenFeedbackCount() {
    global $conn;
    $count = 0;
    $sql = "SELECT COUNT(*) as total FROM reviews WHERE is_seen = 0";
    $result = $conn->query($sql);
    if ($result && $row = $result->fetch_assoc()) {
        $count = $row[\'total\'];
    }
    return $count;
}

/**
 * Get greeting based on time of day
 * @return string Appropriate greeting
 */
function getTimeBasedGreeting() {
    date_default_timezone_set(\'Asia/Manila\');
    $hour = (int)date(\'G\');
    if ($hour >= 5 && $hour < 12) {
        return \'Good Morning,\';
    } elseif ($hour >= 12 && $hour < 18) {
        return \'Good Afternoon,\';
    } else {
        return \'Good Evening,\';
    }
}

/**
 * Secure database query with prepared statement
 * @param string $sql SQL query with placeholders
 * @param string $types Parameter types (i: integer, s: string, d: double, b: blob)
 * @param array $params Array of parameters
 * @return mysqli_stmt|false Prepared statement or false on failure
 */
function secureQuery($sql, $types = "", $params = []) {
    global $conn;
    $stmt = $conn->prepare($sql);
    
    if ($stmt && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    if ($stmt) {
        $stmt->execute();
        return $stmt;
    }
    
    return false;
}

/**
 * Get patient name by ID
 * @param int $patientId Patient ID
 * @return string Full patient name
 */
function getPatientName($patientId) {
    global $conn;
    $name = "Unknown Patient";
    
    $sql = "SELECT first_name, middle_name, last_name FROM patients WHERE id = ?";
    $stmt = secureQuery($sql, "i", [$patientId]);
    
    if ($stmt) {
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $name = $row[\'first_name\'];
            if (!empty($row[\'middle_name\'])) {
                $name .= " " . $row[\'middle_name\'];
            }
            $name .= " " . $row[\'last_name\'];
        }
        $stmt->close();
    }
    
    return $name;
}

/**
 * Validate and sanitize input
 * @param string $data Input data
 * @return string Sanitized data
 */
function sanitizeInput($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $conn->real_escape_string($data);
}
?>';

if (file_put_contents('functions.php', $functionsContent)) {
    echo "<p style='color:green'>✓ Created functions.php with common functions</p>";
} else {
    echo "<p style='color:red'>✗ Failed to create functions.php</p>";
}

// Instructions for updating files
echo "<h2>Next Steps</h2>";
echo "<p>To complete the cleanup process:</p>";
echo "<ol>";
echo "<li>Run <a href='db_cleanup.php'>db_cleanup.php</a> to fix database structure issues</li>";
echo "<li>Update your code to use the new functions.php file</li>";
echo "<li>Delete the redundant files listed above</li>";
echo "<li>Implement prepared statements in all files with database queries</li>";
echo "</ol>";

echo "<br><a href='dashboard.php' style='display: inline-block; padding: 10px 15px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 4px;'>Return to Dashboard</a>";
?>
