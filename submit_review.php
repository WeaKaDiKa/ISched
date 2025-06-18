<?php
session_start();
require_once 'db.php';

// Enable detailed error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

// Log the raw input for debugging
$rawInput = file_get_contents('php://input');
file_put_contents('request.log', date('Y-m-d H:i:s') . " - " . $rawInput . "\n", FILE_APPEND);

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed', 405);
    }

    $data = json_decode($rawInput, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON: ' . json_last_error_msg(), 400);
    }

    // Log received data
    file_put_contents('debug.log', print_r([
        'received_data' => $data,
        'session' => $_SESSION,
        'server' => $_SERVER
    ], true), FILE_APPEND);

    // Validate required fields
    $required = ['rating', 'text', 'services'];
    $missing = array_diff($required, array_keys($data));

    if (!empty($missing)) {
        throw new Exception('Missing required fields: ' . implode(', ', $missing), 400);
    }

    // Prepare data
    $patient_id = $_SESSION['user_id'] ?? null;
    $name = !empty($data['anon'])
        ? 'Anonymous'
        : htmlspecialchars(trim($data['name'] ?? "Unknown"));
    $rating = (int) $data['rating'];
    $text = htmlspecialchars(trim($data['text']));
    $services = json_encode($data['services']);

    // Database operation
    $stmt = $conn->prepare("
        INSERT INTO reviews 
            (patient_id, name, rating, text, services, date) 
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param('isiss', $patient_id, $name, $rating, $text, $services);

    if (!$stmt->execute()) {
        throw new Exception('Database error: ' . $conn->error, 500);
    }

    echo json_encode([
        'success' => true,
        'debug' => [
            'patient_id' => $patient_id,
            'name' => $name,
            'rating' => $rating,
            'text' => $text,
            'services' => $services
        ]
    ]);

} catch (Exception $e) {
    $statusCode = $e->getCode() >= 400 ? $e->getCode() : 500;
    http_response_code($statusCode);

    $response = [
        'success' => false,
        'error' => $e->getMessage(),
        'debug' => [
            'input' => $rawInput,
            'decoded_data' => $data ?? null,
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ];

    file_put_contents('error.log', print_r($response, true), FILE_APPEND);
    echo json_encode($response);
}