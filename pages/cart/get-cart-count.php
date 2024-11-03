<?php
// get-cart-count.php
require_once('../../includes/config.php');
require_once('../../includes/db.php');
require_once('../../includes/functions.php');

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['count' => 0]);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT SUM(quantity) as count FROM cart WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch();
    echo json_encode(['count' => (int)($result['count'] ?? 0)]);
} catch(PDOException $e) {
    echo json_encode(['count' => 0]);
}