<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

try {
    // Fetch cart items with product details
    $stmt = $conn->prepare("
        SELECT c.id as cart_id, c.quantity, p.* 
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $cart_items = $stmt->fetchAll();
    
    // Calculate total
    $total = 0;
    foreach($cart_items as $item) {
        $total += $item['price'] * $item['quantity'];
    }
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}

include 'includes/header.php';
?>

<div class="container mt-5">
    <h2 class="mb-4">Shopping Cart</h2>
    
    <?php if(empty($cart_items)): ?>
        <div class="alert alert-info">Your cart is empty.</div>
        <a href="products.php" class="btn btn-dark">Continue Shopping</a>
    <?php else: ?>
        <div class="card">
            <div class="card-body">
                <?php foreach($cart_items as $item): ?>
                    <div class="row cart-item mb-3">
                        <div class="col-md-2">
                            <img src="images/products/<?php echo htmlspecialchars($item['image']); ?>" 
                                 class="img-fluid rounded" 
                                 alt="<?php echo htmlspecialchars($item['name']); ?>"
                                 onerror="this.src='images/placeholder.jpg'">
                        </div>
                        <div class="col-md-4">
                            <h5><?php echo htmlspecialchars($item['name']); ?></h5>
                            <p class="text-muted"><?php echo htmlspecialchars($item['description']); ?></p>
                        </div>
                        <div class="col-md-2">
                            $<?php echo number_format($item['price'], 2); ?>
                        </div>
                        <div class="col-md-2">
                            <form action="update_cart.php" method="POST" class="d-flex align-items-center">
                                <input type="hidden" name="cart_id" value="<?php echo $item['cart_id']; ?>">
                                <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" 
                                       class="form-control form-control-sm me-2" min="1" max="10"
                                       onchange="this.form.submit()">
                            </form>
                        </div>
                        <div class="col-md-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <span>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                                <form action="remove_from_cart.php" method="POST" class="d-inline">
                                    <input type="hidden" name="cart_id" value="<?php echo $item['cart_id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <hr>
                <div class="row">
                    <div class="col-md-8">
                        <a href="products.php" class="btn btn-outline-dark">Continue Shopping</a>
                    </div>
                    <div class="col-md-4">
                        <div class="text-end">
                            <h4>Total: $<?php echo number_format($total, 2); ?></h4>
                            <a href="checkout.php" class="btn btn-dark mt-2">Proceed to Checkout</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>