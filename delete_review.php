<?php
header('Content-Type: application/json');

// Start session if needed
session_start();

// Check if user is authorized to delete
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Database connection
require_once('db.php');

// Get review ID
$reviewId = isset($_POST['id']) ? (int) $_POST['id'] : 0;

if ($reviewId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid review ID']);
    exit;
}

try {
    // Prepare delete statement
    $stmt = $conn->prepare("DELETE FROM reviews WHERE id = ?");
    $stmt->bind_param("i", $reviewId);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Review deleted']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Review not found or already deleted']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$stmt->close();
$conn->close();
