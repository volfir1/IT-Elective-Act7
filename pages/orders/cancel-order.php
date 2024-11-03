<?php
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login to continue']);
    exit;
}

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if (!$order_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit;
}

try {
    // First check if the order belongs to the user and is in pending status
    $stmt = $conn->prepare("
        SELECT status 
        FROM orders 
        WHERE id = ? AND user_id = ? AND status = 'pending'
    ");
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Order cannot be cancelled']);
        exit;
    }

    // Update order status to cancelled
    $stmt = $conn->prepare("
        UPDATE orders 
        SET status = 'cancelled' 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$order_id, $_SESSION['user_id']]);

    echo json_encode([
        'success' => true,
        'message' => 'Order cancelled successfully'
    ]);

} catch(PDOException $e) {
    error_log($e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error cancelling order'
    ]);
}
