<?php
// products.php
require_once('../../includes/config.php');
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// Fetch all products
try {
    $stmt = $conn->prepare("SELECT * FROM products ORDER BY name");
    $stmt->execute();
    $products = $stmt->fetchAll();
} catch(PDOException $e) {
    $products = [];
}

include_once '../../includes/header.php';
?>

<!-- Improved toast container with better styling -->
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1050">
    <div id="cartToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <i class="fas fa-shopping-cart me-2"></i>
            <strong class="me-auto">Cart Update</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body"></div>
    </div>
</div>

<div class="container mt-5">
    <h2 class="text-center mb-4">Our Products</h2>
    
    <div class="row">
        <?php foreach($products as $product): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <img src="../../images/products/<?php echo htmlspecialchars($product['image']); ?>" 
                         class="card-img-top" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                         onerror="this.src='../../images/placeholder.jpg'">
                    
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                        <p class="card-text"><?php echo htmlspecialchars($product['description']); ?></p>
                        <p class="card-text mt-auto">
                            <strong>$<?php echo number_format($product['price'], 2); ?></strong>
                        </p>
                        
                        <?php if(isLoggedIn()): ?>
                            <form class="cart-form mt-2">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <div class="input-group">
                                    <input type="number" name="quantity" class="form-control" value="1" min="1" max="10">
                                    <button type="submit" class="btn btn-dark">Add to Cart</button>
                                </div>
                            </form>
                        <?php else: ?>
                            <a href="../auth/login.php" class="btn btn-dark mt-2">Login to Purchase</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Updated JavaScript with improved notification handling -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize toast
    const toastElement = document.getElementById('cartToast');
    const toast = new bootstrap.Toast(toastElement, {
        delay: 3000, // 3 seconds display time
        animation: true
    });
    
    document.querySelectorAll('.cart-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const submitButton = this.querySelector('button[type="submit"]');
            submitButton.disabled = true; // Prevent double submission
            
            const formData = new FormData(this);
            
            fetch('../../pages/cart/cart-handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const toastBody = document.querySelector('.toast-body');
                
                // Reset any existing classes
                toastElement.classList.remove('bg-success', 'bg-danger', 'text-white');
                
                // Apply appropriate styling based on response
                if (data.success) {
                    toastElement.classList.add('bg-success', 'text-white');
                    // Update cart icon if it exists
                    const cartCountElement = document.querySelector('#cartCount');
                    if (cartCountElement) {
                        cartCountElement.textContent = data.cartCount;
                    }
                } else {
                    toastElement.classList.add('bg-danger', 'text-white');
                }
                
                // Update toast content and show it
                toastBody.innerHTML = data.message;
                toast.show();
                
                // Reset form if success
                if (data.success) {
                    this.reset();
                    this.querySelector('input[name="quantity"]').value = "1";
                }
            })
            .catch(error => {
                console.error('Error:', error);
                const toastBody = document.querySelector('.toast-body');
                toastElement.classList.remove('bg-success', 'bg-danger', 'text-white');
                toastElement.classList.add('bg-danger', 'text-white');
                toastBody.innerHTML = `
                    <div class="d-flex align-items-center">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <span>Error updating cart. Please try again.</span>
                    </div>`;
                toast.show();
            })
            .finally(() => {
                submitButton.disabled = false; // Re-enable submit button
            });
        });
    });
});
</script>

<?php include_once '../../includes/footer.php'; ?>