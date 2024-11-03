<?php
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

if (!isLoggedIn()) {
    setMessage('Please login to continue', 'danger');
    redirect('/auth/login.php');
}

if (!validateCart()) {
    redirect('/cart/index.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/checkout/index.php');
}

// Validate required fields
$required_fields = ['name', 'email', 'phone', 'address'];
$order_data = [];

foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        setMessage("Please fill in all required fields", 'danger');
        redirect('/checkout/index.php');
    }
    $order_data[$field] = clean($_POST[$field]);
}

try {
    $conn->beginTransaction();

    // Get cart items
    $stmt = $conn->prepare("
        SELECT c.quantity, p.id, p.name, p.price
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($cart_items)) {
        throw new Exception('Your cart is empty');
    }

    // Calculate totals
    $subtotal = 0;
    foreach($cart_items as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }
    $shipping = 10.00;
    $tax = $subtotal * 0.10;
    $total = $subtotal + $shipping + $tax;

    // Create order
    $stmt = $conn->prepare("
        INSERT INTO orders (
            user_id, name, email, phone, address,
            subtotal, shipping, tax, total, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
    ");

    $stmt->execute([
        $_SESSION['user_id'],
        $order_data['name'],
        $order_data['email'],
        $order_data['phone'],
        $order_data['address'],
        $subtotal,
        $shipping,
        $tax,
        $total
    ]);

    $order_id = $conn->lastInsertId();

    // Create order items
    $stmt = $conn->prepare("
        INSERT INTO order_items (order_id, product_id, quantity, price)
        VALUES (?, ?, ?, ?)
    ");

    foreach($cart_items as $item) {
        $stmt->execute([
            $order_id,
            $item['id'],
            $item['quantity'],
            $item['price']
        ]);
    }

    // Clear cart
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);

    $conn->commit();
    
    // Update cart count in session
    updateCartCount();
    
    // Set success message and flag
    $_SESSION['order_success'] = true;
    setMessage('Order placed successfully!', 'success');
    
    redirect('/pages/checkout/success.php?order_id=' . $order_id);

} catch (Exception $e) {
    $conn->rollBack();
    error_log("Order Error: " . $e->getMessage());
    setMessage('Error processing your order. Please try again.', 'danger');
    redirect('/checkout/index.php');
}