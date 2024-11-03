<?php
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('/act7/pages/auth/login.php');
}

// Get order ID
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if (!$order_id) {
    redirect('/act7/pages/cart/index.php');
}

try {
    // Fetch order details with items
    $stmt = $conn->prepare("
        SELECT 
            o.*,
            GROUP_CONCAT(CONCAT(oi.quantity, ' x ', p.name) SEPARATOR ', ') as items_list
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE o.id = ? AND o.user_id = ?
        GROUP BY o.id
    ");
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        setMessage('Order not found', 'danger');
        redirect('/act7/pages/cart/index.php');
    }

} catch(PDOException $e) {
    error_log($e->getMessage());
    setMessage('Error retrieving order details', 'danger');
    redirect('/act7/pages/cart/index.php');
}

include_once '../../includes/header.php';
?>

<div class="container mt-5">
<link rel="stylesheet" href="success.css">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <!-- Success Card -->
            <div class="card shadow border-0">
                <div class="card-body text-center p-5">
                    <!-- Success Icon -->
                    <div class="mb-4">
                        <i class="fas fa-check-circle text-success" style="font-size: 5rem;"></i>
                    </div>
                    
                    <!-- Main Message -->
                    <h1 class="display-4 mb-4">Order Confirmed!</h1>
                    
                    <div class="alert alert-success mb-4">
                        Thank you for your order! Your order number is: <strong>#<?php echo $order_id; ?></strong>
                    </div>
                    
                    <!-- Order Summary Box -->
                    <div class="bg-light rounded p-4 mb-4 text-start">
                        <h4 class="border-bottom pb-2 mb-3">Order Summary</h4>
                        
                        <!-- Customer Details -->
                        <div class="mb-3">
                            <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($order['name']); ?></p>
                            <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($order['email']); ?></p>
                            <p class="mb-1"><strong>Shipping Address:</strong> <?php echo htmlspecialchars($order['address']); ?></p>
                        </div>

                        <!-- Order Items -->
                        <div class="mb-3">
                            <strong>Items Ordered:</strong>
                            <p class="mb-1"><?php echo htmlspecialchars($order['items_list']); ?></p>
                        </div>

                        <!-- Order Totals -->
                        <div class="border-top pt-3">
                            <div class="row">
                                <div class="col-6">Subtotal:</div>
                                <div class="col-6 text-end">$<?php echo number_format($order['subtotal'], 2); ?></div>
                                
                                <div class="col-6">Shipping:</div>
                                <div class="col-6 text-end">$<?php echo number_format($order['shipping'], 2); ?></div>
                                
                                <div class="col-6">Tax:</div>
                                <div class="col-6 text-end">$<?php echo number_format($order['tax'], 2); ?></div>
                                
                                <div class="col-6"><strong>Total:</strong></div>
                                <div class="col-6 text-end"><strong>$<?php echo number_format($order['total'], 2); ?></strong></div>
                            </div>
                        </div>
                    </div>

                    <!-- Confirmation Message -->
                    <p class="text-muted mb-4">
                        A confirmation email has been sent to <?php echo htmlspecialchars($order['email']); ?>
                    </p>

                    <!-- Action Button -->
                    <a href="/act7/pages/products/products.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-shopping-cart me-2"></i>Continue Shopping
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../../includes/footer.php'; ?>