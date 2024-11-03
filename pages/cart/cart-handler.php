<?php
require_once('../../includes/config.php');
require_once('../../includes/db.php');
require_once('../../includes/functions.php');

// Ensure JSON response
header('Content-Type: application/json');

// Function to send JSON response
function sendJsonResponse($success, $message, $cartCount = 0) {
    $type = $success ? 'success' : 'danger';
    $icon = $success ? 'fa-check-circle' : 'fa-exclamation-circle';
    
    echo json_encode([
        'success' => $success,
        'message' => "
            <div class='d-flex align-items-center'>
                <i class='fas {$icon} me-2'></i>
                <span>{$message}</span>
            </div>
        ",
        'cartCount' => $cartCount
    ]);
    exit;
}

// Check if user is logged in
if (!isLoggedIn()) {
    sendJsonResponse(false, 'Please login to add items to cart');
}

// Check if it's a POST request with required data
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['product_id'])) {
    sendJsonResponse(false, 'Invalid request');
}

$product_id = (int)$_POST['product_id'];
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
$user_id = $_SESSION['user_id'];

// Validate quantity
if ($quantity < 1) $quantity = 1;
if ($quantity > 10) $quantity = 10;

try {
    // Check if product exists
    $stmt = $conn->prepare("SELECT id, price FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if (!$product) {
        sendJsonResponse(false, 'Product not found');
    }
    
    // Check if product already in cart
    $stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);
    $cart_item = $stmt->fetch();
    
    if ($cart_item) {
        // Update quantity if product already in cart
        $new_quantity = min(10, $cart_item['quantity'] + $quantity);
        $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
        $stmt->execute([$new_quantity, $cart_item['id']]);
        $message = 'Cart updated successfully';
    } else {
        // Add new item to cart
        $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $product_id, $quantity]);
        $message = 'Product added to cart';
    }
    
    // Get updated cart count
    $stmt = $conn->prepare("SELECT COALESCE(SUM(quantity), 0) as count FROM cart WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $cartCount = (int)$stmt->fetchColumn();
    
    sendJsonResponse(true, $message, $cartCount);
    
} catch(PDOException $e) {
    error_log($e->getMessage());
    sendJsonResponse(false, 'Error adding product to cart');
}
