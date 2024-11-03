<?php
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login to continue']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (!isset($_POST['order_id'], $_POST['product_id'], $_POST['rating'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$orderId = (int)$_POST['order_id'];
$productId = (int)$_POST['product_id'];
$rating = (int)$_POST['rating'];
$comment = $_POST['comment'] ?? '';

// Validate rating
if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Invalid rating']);
    exit;
}

try {
    // Verify order belongs to user and is received
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

    // Check if review already exists
    $stmt = $conn->prepare("
        SELECT id FROM reviews 
        WHERE order_id = ? AND user_id = ? AND product_id = ?
    ");
    $stmt->execute([$orderId, $_SESSION['user_id'], $productId]);
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'You have already reviewed this product']);
        exit;
    }

    // Insert review
    $stmt = $conn->prepare("
        INSERT INTO reviews (order_id, user_id, product_id, rating, comment) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$orderId, $_SESSION['user_id'], $productId, $rating, $comment]);

    echo json_encode([
        'success' => true,
        'message' => 'Review submitted successfully'
    ]);

} catch(PDOException $e) {
    error_log($e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error submitting review'
    ]);
}