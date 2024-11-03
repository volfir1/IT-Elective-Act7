<?php
// orders-functions.php

function markOrderAsReceived($orderId, $userId) {
    global $conn;
    try {
        $stmt = $conn->prepare("
            UPDATE orders 
            SET is_received = TRUE 
            WHERE id = ? AND user_id = ? AND status = 'delivered'
        ");
        return $stmt->execute([$orderId, $userId]);
    } catch(PDOException $e) {
        error_log($e->getMessage());
        return false;
    }
}

function submitReview($orderId, $userId, $productId, $rating, $comment) {
    global $conn;
    try {
        // Check if review already exists
        $stmt = $conn->prepare("
            SELECT id FROM reviews 
            WHERE order_id = ? AND user_id = ? AND product_id = ?
        ");
        $stmt->execute([$orderId, $userId, $productId]);
        
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Review already exists'];
        }

        // Insert new review
        $stmt = $conn->prepare("
            INSERT INTO reviews (order_id, user_id, product_id, rating, comment) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $success = $stmt->execute([$orderId, $userId, $productId, $rating, $comment]);

        return ['success' => $success, 'message' => $success ? 'Review submitted successfully' : 'Error submitting review'];
    } catch(PDOException $e) {
        error_log($e->getMessage());
        return ['success' => false, 'message' => 'Error submitting review'];
    }
}

function getOrderProducts($orderId) {
    global $conn;
    try {
        $stmt = $conn->prepare("
            SELECT 
                oi.product_id,
                p.name as product_name,
                p.image as product_image,
                oi.quantity,
                oi.price
            FROM order_items oi
            JOIN products p ON p.id = oi.product_id
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$orderId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log($e->getMessage());
        return [];
    }
}

function hasUserReviewedProduct($orderId, $userId, $productId) {
    global $conn;
    try {
        $stmt = $conn->prepare("
            SELECT id FROM reviews 
            WHERE order_id = ? AND user_id = ? AND product_id = ?
        ");
        $stmt->execute([$orderId, $userId, $productId]);
        return $stmt->fetch() !== false;
    } catch(PDOException $e) {
        error_log($e->getMessage());
        return false;
    }
}