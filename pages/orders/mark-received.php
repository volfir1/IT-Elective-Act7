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
    // Begin transaction
    $conn->beginTransaction();

    // First check if order exists and can be marked as received
    $stmt = $conn->prepare("
        SELECT id 
        FROM orders 
        WHERE id = ? 
        AND user_id = ? 
        AND status = 'pending'
        AND is_received = 0
    ");
    $stmt->execute([$orderId, $_SESSION['user_id']]);
    
    if (!$stmt->fetch()) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Order cannot be marked as received']);
        exit;
    }

    // Update both is_received and status
    $stmt = $conn->prepare("
        UPDATE orders 
        SET is_received = 1,
            status = 'completed'
        WHERE id = ? 
        AND user_id = ?
    ");
    
    $result = $stmt->execute([$orderId, $_SESSION['user_id']]);
    
    if ($result) {
        $conn->commit();
        echo json_encode([
            'success' => true,
            'message' => 'Order marked as received successfully'
        ]);
    } else {
        $conn->rollBack();
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update order'
        ]);
    }

} catch(PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Error in mark-received.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error updating order'
    ]);
}