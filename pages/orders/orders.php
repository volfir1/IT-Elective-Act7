<?php
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

if (!isLoggedIn()) {
    setMessage('Please login to view your orders', 'warning');
    redirect('/act7/pages/auth/login.php');
}

try {
    // Get all orders with their items and product details
    $stmt = $conn->prepare("
        SELECT 
            o.*,
            GROUP_CONCAT(
                CONCAT(oi.quantity, ' x ', p.name, ' ($', FORMAT(oi.price, 2), ')')
                SEPARATOR '\n'
            ) as items_list,
            COUNT(DISTINCT oi.id) as items_count,
            MAX(oi.created_at) as last_updated,
            COALESCE(o.is_received, 0) as is_received  
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE o.user_id = ?
        GROUP BY o.id
        ORDER BY o.created_at DESC
    ");
    
    $stmt->execute([$_SESSION['user_id']]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    error_log($e->getMessage());
    setMessage('Error retrieving orders', 'danger');
    $orders = [];
}

include_once '../../includes/header.php';
?>

<div class="container mt-5">
    <link href="order.css" rel="stylesheet">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>My Orders</h2>
        <a href="/act7/pages/products/products.php" class="btn btn-dark">
            <i class="fas fa-shopping-bag me-2"></i>Continue Shopping
        </a>
    </div>

    <?php if (empty($orders)): ?>
        <div class="text-center py-5">
            <div class="mb-4">
                <i class="fas fa-shopping-bag fa-4x text-muted"></i>
            </div>
            <h3>No Orders Yet</h3>
            <p class="text-muted mb-4">Looks like you haven't made any orders yet.</p>
            <a href="/act7/pages/products/products.php" class="btn btn-dark">
                Start Shopping
            </a>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach($orders as $order): ?>
                <div class="col-12 mb-4">
                    <div class="card order-card">
                        <div class="card-header bg-light">
                            <div class="row align-items-center">
                                <div class="col-md-4">
                                    <h5 class="mb-0">
                                        Order #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?>
                                    </h5>
                                </div>
                                <div class="col-md-4 text-md-center">
                                    <span class="badge <?php echo getStatusBadgeClass($order['status']); ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </div>
                                <div class="col-md-4 text-md-end">
                                    <small class="text-muted">
                                        <?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?>
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="card-body">
                            <div class="row">
                                <!-- Shipping Details -->
                                <div class="col-md-6 mb-4 mb-md-0">
                                    <h6 class="fw-bold mb-3">Shipping Details</h6>
                                    <div class="bg-light p-3 rounded">
                                        <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($order['name']); ?></p>
                                        <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($order['email']); ?></p>
                                        <p class="mb-1"><strong>Phone:</strong> <?php echo htmlspecialchars($order['phone']); ?></p>
                                        <p class="mb-0"><strong>Address:</strong> <?php echo htmlspecialchars($order['address']); ?></p>
                                    </div>
                                </div>

                                <!-- Order Items -->
                                <div class="col-md-6">
                                    <h6 class="fw-bold mb-3">Order Items</h6>
                                    <div class="bg-light p-3 rounded">
                                        <?php 
                                        $items = explode("\n", $order['items_list']);
                                        foreach($items as $item): 
                                        ?>
                                        <div class="mb-2"><?php echo htmlspecialchars($item); ?></div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Order Summary -->
                            <div class="row mt-4">
                                <div class="col-md-6 offset-md-6">
                                    <div class="bg-light p-3 rounded">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Subtotal:</span>
                                            <span>$<?php echo number_format($order['subtotal'], 2); ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Shipping:</span>
                                            <span>$<?php echo number_format($order['shipping'], 2); ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Tax:</span>
                                            <span>$<?php echo number_format($order['tax'], 2); ?></span>
                                        </div>
                                        <hr>
                                        <div class="d-flex justify-content-between fw-bold">
                                            <span>Total:</span>
                                            <span>$<?php echo number_format($order['total'], 2); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer bg-white">
                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" 
                                        class="btn btn-outline-secondary btn-sm"
                                        onclick="window.print()">
                                    <i class="fas fa-print me-2"></i>Print Order
                                </button>

                                <?php if (!$order['is_received']): ?>
                                    <button type="button" 
                                            class="btn btn-success btn-sm"
                                            onclick="markAsReceived(<?php echo $order['id']; ?>)">
                                        <i class="fas fa-check me-2"></i>Mark as Received
                                    </button>
                                <?php endif; ?>

                                <?php if ($order['is_received']): ?>
                                    <button type="button" 
                                            class="btn btn-primary btn-sm"
                                            onclick="showReviewModal(<?php echo $order['id']; ?>)">
                                        <i class="fas fa-star me-2"></i>Review Items
                                    </button>
                                <?php endif; ?>

                                <?php if ($order['status'] === 'pending'): ?>
                                    <button type="button" 
                                            class="btn btn-outline-danger btn-sm"
                                            onclick="cancelOrder(<?php echo $order['id']; ?>)">
                                        <i class="fas fa-times me-2"></i>Cancel Order
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Review Modal -->
    <div class="modal fade" id="reviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Review Items</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="reviewModalBody">
                    <!-- Products will be loaded here -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function cancelOrder(orderId) {
    if (confirm('Are you sure you want to cancel this order?')) {
        fetch(`/act7/pages/orders/cancel-order.php?order_id=${orderId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Error cancelling order');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error cancelling order');
            });
    }
}

function markAsReceived(orderId) {
    if (confirm('Have you received all items in this order?')) {
        fetch('/act7/pages/orders/mark-received.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `order_id=${orderId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Error updating order');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error updating order');
        });
    }
}

function showReviewModal(orderId) {
    fetch(`/act7/pages/orders/get-order-products.php?order_id=${orderId}`)
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                alert(data.message || 'Error loading products');
                return;
            }

            const modalBody = document.getElementById('reviewModalBody');
            modalBody.innerHTML = data.products.map(product => `
                <div class="card mb-3 product-review-card">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-4">
                                <img src="../../images/products/${product.product_image}" 
                                     class="img-fluid rounded" 
                                     alt="${product.product_name}">
                            </div>
                            <div class="col-md-8">
                                <h6 class="mb-3">${product.product_name}</h6>
                                ${product.is_reviewed ? `
                                    <div class="text-success">
                                        <i class="fas fa-check-circle"></i> Review submitted
                                    </div>
                                ` : `
                                    <form onsubmit="submitReview(event, ${orderId}, ${product.product_id})">
                                        <div class="mb-3">
                                            <label class="mb-2">Rating</label>
                                            <div class="rating">
                                                ${[5,4,3,2,1].map(num => `
                                                    <input type="radio" id="star${num}-${product.product_id}" 
                                                           name="rating-${product.product_id}" 
                                                           value="${num}" required>
                                                    <label for="star${num}-${product.product_id}">
                                                        <i class="fas fa-star"></i>
                                                    </label>
                                                `).join('')}
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <textarea class="form-control" 
                                                      name="comment" 
                                                      placeholder="Your review (optional)" 
                                                      rows="2"></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-primary btn-sm">
                                            Submit Review
                                        </button>
                                    </form>
                                `}
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');
            
            new bootstrap.Modal(document.getElementById('reviewModal')).show();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading products');
        });
}

function submitReview(event, orderId, productId) {
    event.preventDefault();
    const form = event.target;
    const rating = form.querySelector(`input[name="rating-${productId}"]:checked`).value;
    const comment = form.querySelector('textarea[name="comment"]').value;

    fetch('/act7/pages/orders/submit-review.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `order_id=${orderId}&product_id=${productId}&rating=${rating}&comment=${comment}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showReviewModal(orderId);
        } else {
            alert(data.message || 'Error submitting review');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error submitting review');
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

<?php
// Helper function for status badge classes
function getStatusBadgeClass($status) {
    return match($status) {
        'pending' => 'bg-warning text-dark',
        'completed' => 'bg-success',
        'cancelled' => 'bg-danger',
        default => 'bg-secondary'
    };
}

include_once '../../includes/footer.php';
?>