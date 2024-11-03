<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Improved redirect function with fallback
function redirect($path) {
    $fullPath = SITE_URL . $path;
    if (!headers_sent()) {
        header("Location: " . $fullPath);
        exit();
    } else {
        // JavaScript fallback if headers already sent
        echo '<script>window.location.href="' . $fullPath . '";</script>';
        echo '<noscript><meta http-equiv="refresh" content="0;url=' . $fullPath . '"></noscript>';
        echo 'If you are not redirected, please <a href="' . $fullPath . '">click here</a>.';
        exit();
    }
}

// Clean input data
function clean($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Set flash message with auto-expiry
function setMessage($message, $type = 'success') {
    $_SESSION['message'] = clean($message);
    $_SESSION['message_type'] = in_array($type, ['success', 'danger', 'warning', 'info']) ? $type : 'info';
    $_SESSION['message_time'] = time(); // Add timestamp for potential expiry check
}

// Get flash message with Bootstrap styling
function getMessage() {
    if(isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        $type = $_SESSION['message_type'];
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
        return "<div class='alert alert-{$type} alert-dismissible fade show' role='alert'>
                    {$message}
                    <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                </div>";
    }
    return '';
}

// Function to check if a message exists
function hasMessage() {
    return isset($_SESSION['message']) && isset($_SESSION['message_type']);
}

// Function to validate cart items
function validateCart() {
    if (!isLoggedIn()) {
        setMessage('Please login to access cart', 'warning');
        return false;
    }
    
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM cart WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $count = $stmt->fetchColumn();
        
        if ($count === 0) {
            setMessage('Your cart is empty', 'warning');
            return false;
        }
        return true;
    } catch (PDOException $e) {
        error_log("Cart validation error: " . $e->getMessage());
        setMessage('Error checking cart status', 'danger');
        return false;
    }
}

// Function to get cart count
function getCartCount() {
    if (!isLoggedIn()) return 0;
    
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT COALESCE(SUM(quantity), 0) FROM cart WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Error getting cart count: " . $e->getMessage());
        return 0;
    }
}

// Function to update cart count
function updateCartCount() {
    $_SESSION['cart_count'] = getCartCount();
}

// Function to check current page
function isCurrentPage($path) {
    $currentScript = $_SERVER['SCRIPT_NAME'];
    
    // For exact file matches (e.g., index.php)
    if ($path === basename($currentScript)) {
        return true;
    }
    
    // For directory matches (e.g., 'products', 'cart')
    if (strpos($currentScript, '/' . $path . '/') !== false) {
        return true;
    }
    
    // For files in directories (e.g., 'auth/login.php')
    if (strpos($currentScript, $path) !== false) {
        return true;
    }
    
    return false;
}

// Debug function (only use in development)
function debug($data) {
    echo "<pre>";
    var_dump($data);
    echo "</pre>";
}