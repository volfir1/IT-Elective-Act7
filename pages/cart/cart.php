<?php
require_once('../../includes/config.php');
require_once('../../includes/db.php');
require_once('../../includes/functions.php');

// Check if user is logged in
if (!isLoggedIn()) {
    setMessage('Please login to view your cart', 'warning');
    redirect('/act7/pages/auth/login.php');
}

// Fetch cart items with product details
try {
    $stmt = $conn->prepare("
        SELECT c.id as cart_id, c.quantity, p.* 
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = ?
        ORDER BY c.id DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $cart_items = $stmt->fetchAll();
    
    // Calculate total
    $total = 0;
    foreach($cart_items as $item) {
        $total += $item['price'] * $item['quantity'];
    }
} catch(PDOException $e) {
    error_log($e->getMessage());
    $cart_items = [];
    $total = 0;
}

include_once '../../includes/header.php';
?>

<div class="container mt-5">
<link href="cart.css" rel="stylesheet">
    <div class="cart-header mb-4">
        <h2 class="cart-title">Shopping Cart</h2>
        <?php if(!empty($cart_items)): ?>
            <p class="text-muted"><?php echo count($cart_items); ?> item(s) in your cart</p>
        <?php endif; ?>
    </div>
    
    <?php echo getMessage(); ?>
    
    <!-- Alert Container for Dynamic Messages -->
    <div id="alert-container"></div>
    
    <?php if(empty($cart_items)): ?>
        <div class="empty-cart-container text-center py-5">
            <div class="empty-cart-icon mb-4">
                <i class="fas fa-shopping-cart fa-4x text-muted"></i>
            </div>
            <h3 class="mb-3">Your Cart is Empty</h3>
            <p class="text-muted mb-4">Looks like you haven't added anything to your cart yet.</p>
            <a href="/act7/pages/products/products.php" class="btn btn-primary btn-lg">
                Start Shopping <i class="fas fa-arrow-right ms-2"></i>
            </a>
        </div>
    <?php else: ?>
        <div class="card shadow-sm">
            <div class="card-body">
                <?php foreach($cart_items as $item): ?>
                    <div class="row cart-item mb-4 align-items-center" id="cart-item-<?php echo $item['cart_id']; ?>">
                        <!-- Product Image -->
                        <div class="col-md-2">
                            <img src="../../images/products/<?php echo htmlspecialchars($item['image']); ?>" 
                                 class="img-fluid rounded product-image" 
                                 alt="<?php echo htmlspecialchars($item['name']); ?>"
                                 loading="lazy"
                                 onerror="this.src='../../images/placeholder.jpg'">
                        </div>
                        
                        <!-- Product Details -->
                        <div class="col-md-4">
                            <h5 class="product-name mb-1"><?php echo htmlspecialchars($item['name']); ?></h5>
                            <p class="product-description text-muted mb-0"><?php echo htmlspecialchars($item['description']); ?></p>
                        </div>
                        
                        <!-- Price -->
                        <div class="col-md-2 text-center">
                            <span class="price-label">Price:</span>
                            <span class="price">$<?php echo number_format($item['price'], 2); ?></span>
                        </div>
                        
                        <!-- Quantity Controls -->
                        <div class="col-md-2">
                            <div class="quantity-controls d-flex align-items-center justify-content-center">
                                <button class="btn btn-sm btn-outline-secondary quantity-btn" 
                                        data-action="decrease" 
                                        data-cart-id="<?php echo $item['cart_id']; ?>"
                                        <?php echo $item['quantity'] <= 1 ? 'disabled' : ''; ?>>
                                    <i class="fas fa-minus"></i>
                                </button>
                                <input type="number" 
                                       class="form-control form-control-sm mx-2 text-center quantity-input" 
                                       value="<?php echo $item['quantity']; ?>" 
                                       min="1" 
                                       max="10"
                                       data-cart-id="<?php echo $item['cart_id']; ?>">
                                <button class="btn btn-sm btn-outline-secondary quantity-btn" 
                                        data-action="increase" 
                                        data-cart-id="<?php echo $item['cart_id']; ?>"
                                        <?php echo $item['quantity'] >= 10 ? 'disabled' : ''; ?>>
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Subtotal and Remove -->
                        <div class="col-md-2 text-end">
                            <div class="d-flex flex-column align-items-end">
                                <span class="subtotal mb-2">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                                <button class="btn btn-sm btn-outline-danger remove-item" 
                                        data-cart-id="<?php echo $item['cart_id']; ?>">
                                    <i class="fas fa-trash me-1"></i> Remove
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <hr class="my-4">
                
                <!-- Cart Summary -->
                <div class="row">
                    <div class="col-md-6">
                        <a href="/act7/pages/products/products.php" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left me-2"></i>Continue Shopping
                        </a>
                    </div>
                    <div class="col-md-6">
                        <div class="cart-summary text-end">
                            <div class="cart-totals mb-3">
                                <div class="d-flex justify-content-end align-items-center mb-2">
                                    <span class="text-muted me-3">Subtotal:</span>
                                    <span class="fw-bold">$<?php echo number_format($total, 2); ?></span>
                                </div>
                            </div>
                            <a href="/act7/pages/checkout/index.php" class="btn btn-primary btn-lg checkout-btn">
                                Proceed to Checkout <i class="fas fa-arrow-right ms-2"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Helper function to update quantity
    async function updateQuantity(cartId, quantity) {
        try {
            const response = await fetch('/act7/pages/cart/update_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `cart_id=${cartId}&quantity=${quantity}`
            });

            const data = await response.json();
            
            if (data.success) {
                // Update cart count in header
                document.querySelectorAll('.cart-count').forEach(el => {
                    el.textContent = data.cartCount;
                });
                
                // Show success message and reload page
                showAlert(data.message, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showAlert(data.message, 'danger');
            }
        } catch (error) {
            console.error('Error:', error);
            showAlert('Error updating cart', 'danger');
        }
    }

    // Quantity buttons click handlers
    document.querySelectorAll('.quantity-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const input = this.parentElement.querySelector('.quantity-input');
            const currentValue = parseInt(input.value);
            const cartId = input.dataset.cartId;
            const action = this.dataset.action;
            
            let newValue = currentValue;
            if (action === 'increase' && currentValue < 10) {
                newValue = currentValue + 1;
            } else if (action === 'decrease' && currentValue > 1) {
                newValue = currentValue - 1;
            }

            if (newValue !== currentValue) {
                updateQuantity(cartId, newValue);
            }
        });
    });

    // Direct quantity input handler
    document.querySelectorAll('.quantity-input').forEach(input => {
        input.addEventListener('change', function() {
            const cartId = this.dataset.cartId;
            let quantity = parseInt(this.value);
            
            // Validate quantity
            quantity = Math.max(1, Math.min(10, quantity));
            this.value = quantity;
            
            updateQuantity(cartId, quantity);
        });
    });

    // Remove item handler
    document.querySelectorAll('.remove-item').forEach(btn => {
        btn.addEventListener('click', async function() {
            if (!confirm('Are you sure you want to remove this item?')) return;
            
            const cartId = this.dataset.cartId;
            
            try {
                const response = await fetch('/act7/pages/cart/remove_from_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `cart_id=${cartId}`
                });

                const data = await response.json();
                
                if (data.success) {
                    showAlert('Item removed from cart', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showAlert(data.message, 'danger');
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('Error removing item', 'danger');
            }
        });
    });

    // Helper function to show alerts
    function showAlert(message, type = 'success') {
        const alertContainer = document.getElementById('alert-container');
        const alert = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                <i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}-circle me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        
        alertContainer.innerHTML = alert;
        
        setTimeout(() => {
            const alertElement = alertContainer.querySelector('.alert');
            if (alertElement) {
                const bsAlert = new bootstrap.Alert(alertElement);
                bsAlert.close();
            }
        }, 3000);
    }
});
</script>

<?php include_once '../../includes/footer.php'; ?>