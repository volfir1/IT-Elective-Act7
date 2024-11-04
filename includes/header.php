<?php
require_once __DIR__ . '/functions.php';

// Add this at the top of your header.php file, after the database connection
$cartCount = 0;
if (isLoggedIn()) {
    try {
        $stmt = $conn->prepare("SELECT COALESCE(SUM(quantity), 0) as count FROM cart WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $cartCount = (int)$stmt->fetchColumn();
    } catch(PDOException $e) {
        $cartCount = 0;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    <link rel="icon" type="image/x-icon" href="../../images/logo/rounded-logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;500;600&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="/act7/pages/css/index.css" rel="stylesheet">
    <link href="/act7/pages/css/footer.css" rel="stylesheet">
</head>
<body>
    <!-- Toast for notifications -->
    <div class="toast-container position-fixed top-0 end-0 p-3">
        <div id="cartToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-body"></div>
        </div>
    </div>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="/act7/pages/index.php">
                
                <i class="fas fa-spray-can-sparkles me-2"></i>
                <?php echo SITE_NAME; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/act7/pages/index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/act7/pages/products/products.php">Products</a>
                    </li>
                    <?php if(isLoggedIn()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/act7/pages/orders/orders.php">Orders</a>
                    </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <?php if(isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/act7/pages/cart/cart.php">
                                <i class="fas fa-shopping-cart"></i>
                                <span class="badge bg-danger rounded-pill cart-count"><?php echo $cartCount; ?></span>
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" 
                               data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user me-1"></i>
                                <?php echo $_SESSION['username']; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                <li><a class="dropdown-item" href="/act7/pages/auth/logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/act7/pages/auth/login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/act7/pages/auth/signup.php">Sign Up</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container py-5 mt-5">
    
    <!-- Add this JavaScript for real-time cart count updates -->
    <script>
    function updateCartCount() {
        if (document.querySelector('.cart-count')) {
            fetch('/act7/pages/cart/get-cart-count.php')
                .then(response => response.json())
                .then(data => {
                    document.querySelectorAll('.cart-count').forEach(element => {
                        element.textContent = data.count;
                    });
                })
                .catch(error => console.error('Error:', error));
        }
    }

    // Update cart count every 5 seconds
    setInterval(updateCartCount, 5000);

    // Initialize Bootstrap toasts
    document.addEventListener('DOMContentLoaded', function() {
        window.showToast = function(message, type = 'success') {
            const toast = document.getElementById('cartToast');
            toast.querySelector('.toast-body').textContent = message;
            toast.classList.remove('bg-success', 'bg-danger');
            toast.classList.add(type === 'success' ? 'bg-success' : 'bg-danger', 'text-white');
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();
        };
    });

    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 3000);
    });
    </script>