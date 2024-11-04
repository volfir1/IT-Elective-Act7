<?php
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login to continue']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$orderId = (int)($_POST['order_id'] ?? $_GET['order_id'] ?? 0);

if (!$orderId) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit;
}

try {
    switch($action) {
        case 'mark_received':
            // Check if order can be marked as received
            $stmt = $conn->prepare("
                SELECT id, status 
                FROM orders 
                WHERE id = ? 
                AND user_id = ? 
                AND status = 'pending'
                AND is_received = 0
            ");
            $stmt->execute([$orderId, $_SESSION['user_id']]);
            
            if (!$stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Order cannot be marked as received']);
                exit;
            }

            // Update status and received flag
            $stmt = $conn->prepare("
                UPDATE orders 
                SET is_received = 1,
                    status = 'completed'
                WHERE id = ? AND user_id = ?
            ");
            $success = $stmt->execute([$orderId, $_SESSION['user_id']]);
            
            echo json_encode([
                'success' => $success,
                'message' => $success ? 'Order marked as received successfully' : 'Failed to update order'
            ]);
            break;

        case 'cancel':
            // Check if order can be cancelled
            $stmt = $conn->prepare("
                SELECT status 
                FROM orders 
                WHERE id = ? AND user_id = ? AND status = 'pending'
            ");
            $stmt->execute([$orderId, $_SESSION['user_id']]);
            
            if (!$stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Order cannot be cancelled']);
                exit;
            }

            // Cancel the order
            $stmt = $conn->prepare("
                UPDATE orders 
                SET status = 'cancelled' 
                WHERE id = ? AND user_id = ?
            ");
            $success = $stmt->execute([$orderId, $_SESSION['user_id']]);
            
            echo json_encode([
                'success' => $success,
                'message' => $success ? 'Order cancelled successfully' : 'Failed to cancel order'
            ]);
            break;

        case 'get_products':
            // Get order products for review
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
            
            foreach ($products as &$product) {
                $product['is_reviewed'] = (bool)$product['is_reviewed'];
            }
            
            echo json_encode([
                'success' => true,
                'products' => $products
            ]);
            break;

        case 'submit_review':
            if (!isset($_POST['rating'], $_POST['product_id'])) {
                echo json_encode(['success' => false, 'message' => 'Missing required fields']);
                exit;
            }

            $productId = (int)$_POST['product_id'];
            $rating = (int)$_POST['rating'];
            $comment = $_POST['comment'] ?? '';

            // Validate rating
            if ($rating < 1 || $rating > 5) {
                echo json_encode(['success' => false, 'message' => 'Invalid rating']);
                exit;
            }

            // Check if review exists
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
            $success = $stmt->execute([$orderId, $_SESSION['user_id'], $productId, $rating, $comment]);

            echo json_encode([
                'success' => $success,
                'message' => $success ? 'Review submitted successfully' : 'Error submitting review'
            ]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }

} catch(PDOException $e) {
    error_log($e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
}