<?php
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login to continue']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['order_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$orderId = (int)$_POST['order_id'];

try {
    // Check if order belongs to user, isn't cancelled, and isn't already received
    $stmt = $conn->prepare("
        SELECT id 
        FROM orders 
        WHERE id = ? 
        AND user_id = ? 
        AND status != 'cancelled' 
        AND is_received = FALSE
    ");
    $stmt->execute([$orderId, $_SESSION['user_id']]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Order cannot be marked as received']);
        exit;
    }

    // Mark order as received
    $stmt = $conn->prepare("
        UPDATE orders 
        SET is_received = TRUE 
        WHERE id = ? AND user_id = ?
    ");
    $success = $stmt->execute([$orderId, $_SESSION['user_id']]);

    echo json_encode([
        'success' => $success,
        'message' => $success ? 'Order marked as received successfully' : 'Error updating order'
    ]);

} catch(PDOException $e) {
    error_log($e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error updating order'
    ]);
}