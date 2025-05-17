<?php
// Common functions for M&A Oida Dental Clinic Admin
require_once('db.php');

/**
 * Get count of pending appointments
 * @return int Number of pending appointments
 */
function getPendingAppointmentsCount()
{
    global $conn;
    $count = 0;
    $sql = "SELECT COUNT(*) as total FROM appointments WHERE status = 'pending'";
    $result = $conn->query($sql);
    if ($result && $row = $result->fetch_assoc()) {
        $count = $row['total'];
    }
    return $count;
}

/**
 * Get count of unseen feedback
 * @return int Number of unseen feedback entries
 */
function getUnseenFeedbackCount()
{
    global $conn;
    $count = 0;
    $sql = "SELECT COUNT(*) as total FROM reviews WHERE is_seen = 0";
    $result = $conn->query($sql);
    if ($result && $row = $result->fetch_assoc()) {
        $count = $row['total'];
    }
    return $count;
}

/**
 * Get greeting based on time of day
 * @return string Appropriate greeting
 */
function getTimeBasedGreeting()
{
    date_default_timezone_set('Asia/Manila');
    $hour = (int) date('G');
    if ($hour >= 5 && $hour < 12) {
        return 'Good Morning,';
    } elseif ($hour >= 12 && $hour < 18) {
        return 'Good Afternoon,';
    } else {
        return 'Good Evening,';
    }
}

/**
 * Secure database query with prepared statement
 * @param string $sql SQL query with placeholders
 * @param string $types Parameter types (i: integer, s: string, d: double, b: blob)
 * @param array $params Array of parameters
 * @return mysqli_stmt|false Prepared statement or false on failure
 */
function secureQuery($sql, $types = "", $params = [])
{
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
function getPatientName($patientId)
{
    global $conn;
    $name = "Unknown Patient";

    $sql = "SELECT first_name, middle_name, last_name FROM patients WHERE id = ?";
    $stmt = secureQuery($sql, "i", [$patientId]);

    if ($stmt) {
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $name = $row['first_name'];
            if (!empty($row['middle_name'])) {
                $name .= " " . $row['middle_name'];
            }
            $name .= " " . $row['last_name'];
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
function sanitizeInput($data)
{
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $conn->real_escape_string($data);
}
?>