<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Fetch featured products
try {
    $stmt = $conn->prepare("SELECT * FROM products LIMIT 3");
    $stmt->execute();
    $featured_products = $stmt->fetchAll();
} catch(PDOException $e) {
    $featured_products = [];
}

include_once '../includes/header.php';
?>

<!-- Hero Section -->
<div class="hero-section bg-dark text-white py-5 mb-5">
    <div class="container text-center">
        <h1 class="display-4 mb-4">Welcome to Elixia Parfumerie</h1>
        <p class="lead mb-4">Discover your signature scent from our exclusive collection</p>
        <a href="products/products.php" class="btn btn-light btn-lg">Shop Now</a>
    </div>
</div>

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
                            <?php echo $i === 0 ? 'class="active" aria-current="true"' : ''; ?>
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
                    <div class="row">
                        <?php foreach($productGroup as $product): ?>
                        <div class="col-md-4">
                            <div class="card h-100 mx-2">
                                <img src="../images/products/<?php echo htmlspecialchars($product['image']); ?>" 
                                     class="card-img-top" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>"
                                     onerror="this.src='../images/products/placeholder.jpg'">
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                    <p class="card-text"><?php echo htmlspecialchars($product['description']); ?></p>
                                    <p class="card-text mt-auto">
                                        <strong>$<?php echo number_format($product['price'], 2); ?></strong>
                                    </p>
                                    <a href="products/products.php?id=<?php echo $product['id']; ?>" 
                                       class="btn btn-dark mt-2">View Details</a>
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
                    data-bs-target="#featuredProductsCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" 
                    data-bs-target="#featuredProductsCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            </button>
        </div>
    </div>
</section>

<!-- About Section -->
<section class="about-section py-5 bg-light">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h2>About Elixia Parfumerie</h2>
                <p class="lead">Experience luxury fragrances crafted with passion</p>
                <p>At Elixia Parfumerie, we believe that every scent tells a story. Our collection features carefully curated fragrances that capture moments and emotions.</p>
                <a href="products/products.php" class="btn btn-dark">Explore Our Collection</a>
            </div>
            <div class="col-md-6">
                <img src="../images/about-bg.jpg" 
                     alt="About Us" 
                     class="img-fluid rounded shadow"
                     onerror="this.src='../images/products/shop.jpg'">
            </div>
        </div>
    </div>
</section>

<!-- Newsletter Section -->
<section class="newsletter-section py-5 bg-dark text-white">
    <div class="container text-center">
        <h2>Stay Updated</h2>
        <p class="mb-4">Subscribe to receive updates about new collections and special offers</p>
        <div class="row justify-content-center">
            <div class="col-md-6">
                <form id="newsletter-form" class="newsletter-form">
                    <div class="input-group">
                        <input type="email" class="form-control" placeholder="Enter your email" required>
                        <button class="btn btn-light" type="submit">Subscribe</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<?php include_once '../includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap dropdowns
    var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
    var dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
        return new bootstrap.Dropdown(dropdownToggleEl);
    });

    // Newsletter form handling
    const newsletterForm = document.getElementById('newsletter-form');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const email = this.querySelector('input[type="email"]').value;
            
            // Animation class for user feedback
            const button = this.querySelector('button');
            button.innerHTML = 'Subscribing...';
            button.disabled = true;
            
            // Simulate API call (replace with actual API call)
            setTimeout(() => {
                button.innerHTML = 'Subscribed!';
                button.classList.add('btn-success');
                
                // Reset after delay
                setTimeout(() => {
                    button.innerHTML = 'Subscribe';
                    button.disabled = false;
                    button.classList.remove('btn-success');
                    this.reset();
                }, 2000);
            }, 1500);
        });
    }

    // Add fade-in animation to products
    const cards = document.querySelectorAll('.card');
    if (cards.length > 0) {
        const observerOptions = {
            threshold: 0.2
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        cards.forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'all 0.6s ease-out';
            observer.observe(card);
        });
    }


    document.addEventListener('DOMContentLoaded', function() {
        var myCarousel = new bootstrap.Carousel(document.getElementById('featuredProductsCarousel'), {
            interval: 5000, // 5 seconds per slide
            wrap: true,     // Continuous loop
            touch: true     // Enable touch swiping on mobile
        });
    });
});
</script>