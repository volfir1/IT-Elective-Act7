<?php
require_once('../../includes/config.php');

require_once('../../includes/db.php');

require_once('../../includes/functions.php');


// Check if user is logged in
if (!isLoggedIn()) {
    setMessage('Please login to add items to cart', 'danger');
    redirect('/pages/auth/login.php');
}

// Check if it's a POST request with required data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
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
            setMessage('Product not found', 'danger');
            redirect('/pages/products/products.php');
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
            setMessage('Cart updated successfully', 'success');
        } else {
            // Add new item to cart
            $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $product_id, $quantity]);
            setMessage('Product added to cart', 'success');
        }
        
        redirect('/pages/cart.php');
        
    } catch(PDOException $e) {
        error_log($e->getMessage());
        setMessage('Error adding product to cart', 'danger');
        redirect('/pages/products/products.php');
    }
} else {
    redirect('/pages/products/products.php');
}