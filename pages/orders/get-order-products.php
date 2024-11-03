<?php
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login to continue']);
    exit;
}

if (!isset($_GET['order_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$orderId = (int)$_GET['order_id'];

try {
    // First verify the order belongs to the user and is received
    $stmt = $conn->prepare("
        SELECT id 
        FROM orders 
        WHERE id = ? AND user_id = ? AND is_received = TRUE
    ");
    $stmt->execute([$orderId, $_SESSION['user_id']]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Invalid order']);
        exit;
    }

    // Get products from the order with their review status
    $stmt = $conn->prepare("
        SELECT 
            p.id as product_id,
            p.name as product_name,
            p.image as product_image,
            oi.quantity,
            oi.price,
            (SELECT COUNT(*) FROM reviews r 
             WHERE r.order_id = oi.order_id 
             AND r.product_id = p.id 
             AND r.user_id = ?) as is_reviewed
        FROM order_items oi
        JOIN products p ON p.id = oi.product_id
        WHERE oi.order_id = ?
    ");
    
    $stmt->execute([$_SESSION['user_id'], $orderId]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Convert is_reviewed to boolean
    foreach ($products as &$product) {
        $product['is_reviewed'] = (bool)$product['is_reviewed'];
    }

    echo json_encode([
        'success' => true,
        'products' => $products
    ]);

} catch(PDOException $e) {
    error_log($e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error retrieving products'
    ]);
}