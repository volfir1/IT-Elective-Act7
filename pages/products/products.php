<?php
// products.php
require_once('../../includes/config.php');
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// Get search query and category filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';

// Fetch categories for filter dropdown
try {
    $catStmt = $conn->query("SELECT DISTINCT category FROM products ORDER BY category");
    $categories = $catStmt->fetchAll(PDO::FETCH_COLUMN);
} catch(PDOException $e) {
    $categories = [];
}

// Fetch products with search and category filter
// Modify the products query to include ratings
try {
    $query = "SELECT p.*, 
              COALESCE(AVG(r.rating), 0) as average_rating,
              COUNT(r.id) as review_count
              FROM products p
              LEFT JOIN reviews r ON p.id = r.product_id
              LEFT JOIN users u ON r.user_id = u.id
              WHERE 1=1";
    
    // ... rest of the query conditions ...
    
    $query .= " GROUP BY p.id ORDER BY p.name";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $products = $stmt->fetchAll();

    // Fetch reviews separately for each product
    foreach($products as &$product) {
        $reviewQuery = "SELECT r.rating, r.comment, u.username, r.created_at 
                       FROM reviews r 
                       JOIN users u ON r.user_id = u.id 
                       WHERE r.product_id = ?
                       ORDER BY r.created_at DESC";
        $reviewStmt = $conn->prepare($reviewQuery);
        $reviewStmt->execute([$product['id']]);
        $product['reviews'] = $reviewStmt->fetchAll();
    }
} catch(PDOException $e) {
    $products = [];
}

include_once '../../includes/header.php';
?>

<!-- Toast container -->
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

<!-- Main Container -->
<div class="container mt-5">
    <!-- Search and Filter Section -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form method="GET" class="mb-0">
                <div class="row g-3 align-items-center">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text bg-dark text-white">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Search fragrances..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text bg-dark text-white">
                                <i class="fas fa-filter"></i>
                            </span>
                            <select name="category" class="form-select" onchange="this.form.submit()">
                                <option value="">All Categories</option>
                                <?php foreach($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat); ?>" 
                                            <?php echo $category === $cat ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-dark w-100 mb-2">
                            Search
                        </button>
                        <?php if($search || $category): ?>
                            <a href="products.php" class="btn btn-outline-secondary w-100">
                                Clear Filters
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Results Summary -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Our Products</h2>
        <span class="text-muted">
            <?php echo count($products); ?> products found
            <?php if($search || $category): ?>
                for your search
            <?php endif; ?>
        </span>
    </div>

    <!-- No Results Message -->
    <?php if(empty($products)): ?>
        <div class="alert alert-info text-center shadow-sm">
            <i class="fas fa-info-circle me-2"></i>
            No products found matching your criteria.
            <?php if($search || $category): ?>
                <br>
                <a href="products.php" class="alert-link">Clear all filters</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Products Grid -->
    <div class="row g-4">
    <?php foreach($products as $product): ?>
        <div class="col-md-4">
            <div class="card h-100 shadow-sm hover-shadow transition-shadow">
                <!-- Product Image Section -->
                <div class="position-relative">
                    <img src="../../images/products/<?php echo htmlspecialchars($product['image']); ?>" 
                         class="card-img-top" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                         onerror="this.src='../../images/placeholder.jpg'"
                         style="height: 300px; object-fit: cover;">
                    <span class="position-absolute top-0 end-0 m-2 badge bg-dark">
                        <?php echo htmlspecialchars($product['category']); ?>
                    </span>
                </div>
                
                <!-- Product Details Section -->
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                    <p class="card-text text-muted"><?php echo htmlspecialchars($product['description']); ?></p>
                    
                    <!-- Price and Actions Section -->
                    <div class="mt-auto">
                        <!-- Price -->
                        <p class="card-text mb-2">
                            <span class="h4 text-dark">$<?php echo number_format($product['price'], 2); ?></span>
                        </p>
                        
                        <!-- Ratings Section -->
                        <div class="rating-stars mb-3">
                            <div class="d-flex align-items-center">
                                <div class="stars">
                                    <?php
                                    $rating = round($product['average_rating']);
                                    for($i = 1; $i <= 5; $i++) {
                                        if($i <= $rating) {
                                            echo '<i class="fas fa-star text-warning"></i>';
                                        } else {
                                            echo '<i class="far fa-star text-warning"></i>';
                                        }
                                    }
                                    ?>
                                </div>
                                <button type="button" 
                                        class="btn btn-link text-decoration-none ms-2" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#reviewModal<?php echo $product['id']; ?>">
                                    <small class="text-muted">
                                        (<?php echo $product['review_count']; ?> reviews)
                                    </small>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Cart Actions -->
                        <?php if(isLoggedIn()): ?>
                            <form class="cart-form">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <div class="input-group">
                                    <input type="number" name="quantity" class="form-control" 
                                           value="1" min="1" max="10">
                                    <button type="submit" class="btn btn-dark">
                                        <i class="fas fa-cart-plus me-2"></i>Add to Cart
                                    </button>
                                </div>
                            </form>
                        <?php else: ?>
                            <a href="../auth/login.php" class="btn btn-dark w-100">
                                <i class="fas fa-sign-in-alt me-2"></i>Login to Purchase
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Review Modal -->
            <div class="modal fade" id="reviewModal<?php echo $product['id']; ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Reviews for <?php echo htmlspecialchars($product['name']); ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <!-- Overall Rating Summary -->
                            <div class="d-flex align-items-center mb-4">
                                <div class="rating-stars me-3">
                                    <?php
                                    for($i = 1; $i <= 5; $i++) {
                                        if($i <= $rating) {
                                            echo '<i class="fas fa-star text-warning"></i>';
                                        } else {
                                            echo '<i class="far fa-star text-warning"></i>';
                                        }
                                    }
                                    ?>
                                </div>
                                <h4 class="mb-0"><?php echo number_format($product['average_rating'], 1); ?> out of 5</h4>
                                <span class="text-muted ms-2">(<?php echo $product['review_count']; ?> reviews)</span>
                            </div>

                            <!-- Reviews List -->
                            <div class="reviews-container">
                            <?php
    if (!empty($product['reviews'])) {
        foreach ($product['reviews'] as $review) {
            ?>
            <div class="review-item border-bottom pb-3 mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="rating-stars">
                        <?php
                        for($i = 1; $i <= 5; $i++) {
                            if($i <= $review['rating']) {
                                echo '<i class="fas fa-star text-warning"></i>';
                            } else {
                                echo '<i class="far fa-star text-warning"></i>';
                            }
                        }
                        ?>
                    </div>
                    <small class="text-muted">
                        <?php echo date('M d, Y', strtotime($review['created_at'])); ?>
                    </small>
                </div>
                <p class="mb-1"><?php echo htmlspecialchars($review['comment'], ENT_QUOTES, 'UTF-8'); ?></p>
                <small class="text-muted">By <?php echo htmlspecialchars($review['username'], ENT_QUOTES, 'UTF-8'); ?></small>
            </div>
            <?php
        }
    } else {
        echo '<p class="text-center text-muted">No reviews yet</p>';
    }
    ?>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <?php if(isLoggedIn()): ?>
                            
                            <?php else: ?>
                                <a href="../auth/login.php" class="btn btn-dark">
                                    Login to Write a Review
                                </a>
                            <?php endif; ?>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
</div>

<!-- Add this CSS to your stylesheet or in a style tag -->
<style>
.hover-shadow {
    transition: box-shadow 0.3s ease-in-out;
}

.hover-shadow:hover {
    box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
}

.transition-shadow {
    transition: all 0.3s ease;
}
</style>

<!-- JavaScript for cart functionality -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize toast
    const toastElement = document.getElementById('cartToast');
    const toast = new bootstrap.Toast(toastElement, {
        delay: 3000,
        animation: true
    });
    
    document.querySelectorAll('.cart-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const submitButton = this.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            
            const formData = new FormData(this);
            
            fetch('../../pages/cart/cart-handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const toastBody = document.querySelector('.toast-body');
                
                toastElement.classList.remove('bg-success', 'bg-danger', 'text-white');
                
                if (data.success) {
                    toastElement.classList.add('bg-success', 'text-white');
                    const cartCountElement = document.querySelector('#cartCount');
                    if (cartCountElement) {
                        cartCountElement.textContent = data.cartCount;
                    }
                } else {
                    toastElement.classList.add('bg-danger', 'text-white');
                }
                
                toastBody.innerHTML = data.message;
                toast.show();
                
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
                submitButton.disabled = false;
            });
        });
    });
});
</script>

<?php include_once '../../includes/footer.php'; ?>