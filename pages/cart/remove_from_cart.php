<?php
require_once('../../includes/config.php');
require_once('../../includes/db.php');
require_once('../../includes/functions.php');

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => 'Please login to continue',
        'cartCount' => 0
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method',
        'cartCount' => 0
    ]);
    exit;
}

$cartId = isset($_POST['cart_id']) ? (int)$_POST['cart_id'] : 0;

try {
    // Verify cart item belongs to user
    $stmt = $conn->prepare("SELECT id FROM cart WHERE id = ? AND user_id = ?");
    $stmt->execute([$cartId, $_SESSION['user_id']]);
    if (!$stmt->fetch()) {
        throw new Exception('Invalid cart item');
    }

    // Delete cart item
    $stmt = $conn->prepare("DELETE FROM cart WHERE id = ?");
    $stmt->execute([$cartId]);

    // Get updated cart count
    $stmt = $conn->prepare("SELECT COALESCE(SUM(quantity), 0) as count FROM cart WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $cartCount = (int)$stmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'message' => 'Item removed from cart',
        'cartCount' => $cartCount
    ]);

} catch (Exception $e) {
    error_log("Cart Remove Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error removing item from cart',
        'cartCount' => 0
    ]);
}