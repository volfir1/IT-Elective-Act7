<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Fetch featured products
try {
    $stmt = $conn->prepare("
        SELECT p.*, 
               COALESCE(AVG(r.rating), 0) as average_rating,
               COUNT(r.id) as review_count
        FROM products p
        LEFT JOIN reviews r ON p.id = r.product_id
        GROUP BY p.id
        ORDER BY p.created_at DESC
        LIMIT 6
    ");
    $stmt->execute();
    $featured_products = $stmt->fetchAll();
} catch(PDOException $e) {
    error_log($e->getMessage());
    $featured_products = [];
}

// Get bestsellers
try {
    $stmt = $conn->prepare("
        SELECT p.*, 
               COUNT(oi.id) as order_count,
               COALESCE(AVG(r.rating), 0) as average_rating
        FROM products p
        LEFT JOIN order_items oi ON p.id = oi.product_id
        LEFT JOIN reviews r ON p.id = r.product_id
        GROUP BY p.id
        ORDER BY order_count DESC
        LIMIT 3
    ");
    $stmt->execute();
    $bestsellers = $stmt->fetchAll();
} catch(PDOException $e) {
    error_log($e->getMessage());
    $bestsellers = [];
}

include_once '../includes/header.php';
?>



<!-- Hero Section -->
<div class="hero-section text-white py-5 mb-5">
    <div class="container text-center">
        <h1 class="display-4 mb-4 animate__animated animate__fadeInDown">Welcome to <?php echo SITE_NAME; ?></h1>
        <p class="lead mb-4 animate__animated animate__fadeInUp">Discover your signature scent from our exclusive collection</p>
        <a href="products/products.php" class="btn btn-light btn-lg animate__animated animate__fadeInUp">
            <i class="fas fa-shopping-bag me-2"></i>Shop Now
        </a>
    </div>
</div>

<!-- Featured Products Section -->
<section class="featured-products py-5">
    <div class="container">
        <h2 class="text-center mb-5">Featured Products</h2>
        
        <div id="featuredProductsCarousel" class="carousel slide" data-bs-ride="carousel">
            <!-- Carousel indicators -->
            <div class="carousel-indicators">
                <?php 
                $totalSlides = ceil(count($featured_products) / 3);
                for($i = 0; $i < $totalSlides; $i++): 
                ?>
                    <button type="button" 
                            data-bs-target="#featuredProductsCarousel" 
                            data-bs-slide-to="<?php echo $i; ?>" 
                            <?php echo $i === 0 ? 'class="active"' : ''; ?>
                            aria-label="Slide <?php echo $i + 1; ?>">
                    </button>
                <?php endfor; ?>
            </div>

            <!-- Carousel content -->
            <div class="carousel-inner">
                <?php 
                $chunks = array_chunk($featured_products, 3);
                foreach($chunks as $index => $productGroup): 
                ?>
                <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                    <div class="row g-4">
                        <?php foreach($productGroup as $product): ?>
                        <div class="col-12 col-md-4">
                            <div class="card h-100 shadow-sm product-card">
                                <div class="position-relative">
                                    <img src="../images/products/<?php echo htmlspecialchars($product['image']); ?>" 
                                         class="card-img-top"
                                         style="object-fit: cover; height: 200px;"
                                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                                         onerror="this.src='../images/products/placeholder.jpg'">
                                    <?php if($product['stock'] < 1): ?>
                                        <div class="position-absolute top-0 end-0 bg-danger text-white px-2 py-1 m-2 rounded">
                                            Out of Stock
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                    <p class="card-text flex-grow-1"><?php echo htmlspecialchars(substr($product['description'], 0, 100)) . '...'; ?></p>
                                    <div class="mt-auto">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="h5 mb-0">$<?php echo number_format($product['price'], 2); ?></span>
                                            <div class="rating-stars">
                                                <?php
                                                $rating = round($product['average_rating']);
                                                for($i = 1; $i <= 5; $i++) {
                                                    if($i <= $rating) {
                                                        echo '<i class="fas fa-star"></i>';
                                                    } else {
                                                        echo '<i class="far fa-star"></i>';
                                                    }
                                                }
                                                ?>
                                                <small class="text-muted">(<?php echo $product['review_count']; ?>)</small>
                                            </div>
                                        </div>
                                        <a href="products/products.php?id=<?php echo $product['id']; ?>" 
                                           class="btn btn-dark w-100">
                                            <i class="fas fa-eye me-2"></i>View Details
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Carousel controls -->
            <button class="carousel-control-prev" type="button" 
                    data-bs-target="#featuredProductsCarousel" 
                    data-bs-slide="prev">
                <span class="carousel-control-prev-icon bg-dark rounded-circle p-2" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" 
                    data-bs-target="#featuredProductsCarousel" 
                    data-bs-slide="next">
                <span class="carousel-control-next-icon bg-dark rounded-circle p-2" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            </button>
        </div>
    </div>
</section>

<!-- Bestsellers Section -->
<section class="bestsellers py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-5">Bestsellers</h2>
        <div class="row g-4">
            <?php foreach($bestsellers as $product): ?>
            <div class="col-md-4">
                <div class="card h-100 shadow-sm product-card">
                    <img src="../images/products/<?php echo htmlspecialchars($product['image']); ?>" 
                         class="card-img-top"
                         style="object-fit: cover; height: 200px;"
                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                         onerror="this.src='../images/products/placeholder.jpg'">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                        <div class="rating-stars mb-2">
                            <?php
                            $rating = round($product['average_rating']);
                            for($i = 1; $i <= 5; $i++) {
                                echo $i <= $rating ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                            }
                            ?>
                        </div>
                        <p class="h5 mb-3">$<?php echo number_format($product['price'], 2); ?></p>
                        <a href="products/products.php?id=<?php echo $product['id']; ?>" 
                           class="btn btn-dark w-100">Shop Now</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- About Section -->
<section class="about-section py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6 mb-4 mb-md-0">
                <h2>About <?php echo SITE_NAME; ?></h2>
                <p class="lead">Experience luxury fragrances crafted with passion</p>
                <p>At <?php echo SITE_NAME; ?>, we believe that every scent tells a story. Our collection features carefully curated fragrances that capture moments and emotions.</p>
                <div class="mt-4">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-shipping-fast fa-2x text-primary me-3"></i>
                                <div>
                                    <h6 class="mb-0">Free Shipping</h6>
                                    <small class="text-muted">On orders over $50</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-undo fa-2x text-primary me-3"></i>
                                <div>
                                    <h6 class="mb-0">Easy Returns</h6>
                                    <small class="text-muted">30-day return policy</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <img src="../../images/bg/about.jpg" 
                     alt="About Us" 
                     class="img-fluid rounded shadow"
                     onerror="this.src='../images/products/shop.jpg'">
            </div>
        </div>
    </div>
</section>

<!-- Newsletter Section -->
<section class="newsletter-section py-5 text-white">
    <div class="container text-center">
        <h2>Stay Updated</h2>
        <p class="mb-4">Subscribe to receive updates about new collections and special offers</p>
        <div class="row justify-content-center">
            <div class="col-md-6">
                <form id="newsletter-form" class="newsletter-form">
                    <div class="input-group">
                        <input type="email" class="form-control" placeholder="Enter your email" required>
                        <button class="btn btn-light" type="submit">
                            <i class="fas fa-paper-plane me-2"></i>Subscribe
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap carousel
    const carousel = document.getElementById('featuredProductsCarousel');
    if (carousel) {
        const carouselInstance = new bootstrap.Carousel(carousel, {
            interval: 3000,  // Time between slides in milliseconds
            wrap: true,      // Allow continuous cycling
            keyboard: true,  // Allow keyboard control
            pause: 'hover',  // Pause on mouse hover
            touch: true      // Allow touch/swipe on mobile
        });

        // Add event listeners for controls if needed
        const prevButton = carousel.querySelector('.carousel-control-prev');
        const nextButton = carousel.querySelector('.carousel-control-next');

        if (prevButton) {
            prevButton.addEventListener('click', function() {
                carouselInstance.prev();
            });
        }

        if (nextButton) {
            nextButton.addEventListener('click', function() {
                carouselInstance.next();
            });
        }
    }

    // Newsletter form handling
    const newsletterForm = document.getElementById('newsletter-form');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const email = this.querySelector('input[type="email"]').value;
            const button = this.querySelector('button');
            
            button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Subscribing...';
            button.disabled = true;
            
            setTimeout(() => {
                button.innerHTML = '<i class="fas fa-check me-2"></i>Subscribed!';
                button.classList.add('btn-success');
                
                setTimeout(() => {
                    button.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Subscribe';
                    button.disabled = false;
                    button.classList.remove('btn-success');
                    this.reset();
                }, 2000);
            }, 1500);
        });
    }

    // Add animation to cards on scroll
    const cards = document.querySelectorAll('.product-card');
    const observerOptions = {
        threshold: 0.2,
        rootMargin: '50px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

cards.forEach(card => {
    card.style.opacity = '0';
    card.style.transform = 'translateY(20px)';
    card.style.transition = 'all 0.6s ease-out';
    observer.observe(card);
});

// Add hover effect to product cards
cards.forEach(card => {
    card.addEventListener('mouseenter', function() {
        this.querySelector('.btn').classList.remove('btn-dark');
        this.querySelector('.btn').classList.add('btn-primary');
    });

    card.addEventListener('mouseleave', function() {
        this.querySelector('.btn').classList.remove('btn-primary');
        this.querySelector('.btn').classList.add('btn-dark');
    });
});

// Initialize tooltips
const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
});

// Smooth scroll for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Add loading animation for images
document.querySelectorAll('img').forEach(img => {
    img.addEventListener('load', function() {
        this.classList.add('fade-in');
    });
});
});

// Add to cart functionality (if needed)
function addToCart(productId) {
fetch('/act7/pages/cart/add-to-cart.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: `product_id=${productId}&quantity=1`
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        // Update cart count in header
        const cartCount = document.querySelector('.cart-count');
        if (cartCount) {
            cartCount.textContent = data.cart_count;
            cartCount.classList.add('animate__animated', 'animate__bounceIn');
        }

        // Show success message
        showToast('Success', 'Product added to cart!', 'success');
    } else {
        showToast('Error', data.message, 'error');
    }
})
.catch(error => {
    console.error('Error:', error);
    showToast('Error', 'Could not add product to cart', 'error');
});
}

// Toast notification function
function showToast(title, message, type = 'info') {
const toastContainer = document.getElementById('toast-container');
if (!toastContainer) {
    const container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'position-fixed bottom-0 end-0 p-3';
    document.body.appendChild(container);
}

const toastHtml = `
    <div class="toast align-items-center text-white bg-${type === 'error' ? 'danger' : 'success'} border-0" 
         role="alert" 
         aria-live="assertive" 
         aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                <strong>${title}</strong><br>
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
`;


toastContainer.insertAdjacentHTML('beforeend', toastHtml);

const toastElement = toastContainer.lastElementChild;
const toast = new bootstrap.Toast(toastElement, {
    autohide: true,
    delay: 3000
});
toast.show();

toastElement.addEventListener('hidden.bs.toast', function () {
    this.remove();
});
}
</script>

<!-- Add Toast Container -->
<div id="toast-container" class="position-fixed bottom-0 end-0 p-3"></div>

<?php
// Add any additional PHP functions needed
function formatPrice($price) {
return number_format($price, 2);
}

function getStockStatus($stock) {
if ($stock <= 0) {
    return ['Out of Stock', 'danger'];
} elseif ($stock <= 5) {
    return ['Low Stock', 'warning'];
} else {
    return ['In Stock', 'success'];
}
}

include_once '../includes/footer.php';
?>