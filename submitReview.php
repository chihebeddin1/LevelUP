<?php
session_start();
header('Content-Type: application/json');

// Get data from POST request
$data = json_decode(file_get_contents('php://input'), true);


//$productId = isset($data['product_id']) ? $data['product_id'] : null;
$rating = isset($data['rating']) ? $data['rating'] : null;
$comment = isset($data['comment']) ? $data['comment'] : '';

// Get product ID from POST data
//$productId = isset($data['product_id']) ? $data['product_id'] : null;
// Get user ID from session
//$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$userId = 1;
$productId = 2;
// Check if user is logged in
if (!$userId) {
    echo json_encode([
        'success' => false,
        'message' => 'You must be logged in to submit a review'
    ]);
    exit;
}

// Validate inputs
if (!$productId || !$rating || $rating < 1 || $rating > 5) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid data provided'
    ]);
    exit;
}

// Database connection
$host = "localhost";
$dbname = "sport_shop";
$user = "root";
$password = "";
$port = 3306;

try {
    $dsn = "mysql:host=$host;dbname=$dbname;port=$port";
    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    /* ========================================
       STEP 1: Check if user already reviewed this product
    ======================================== */
    $checkExistingReview = "SELECT id FROM ratings 
                           WHERE user_id = :user_id 
                           AND product_id = :product_id 
                           LIMIT 1";
    
    $stmtCheck = $pdo->prepare($checkExistingReview);
    $stmtCheck->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmtCheck->bindValue(':product_id', $productId, PDO::PARAM_INT);
    $stmtCheck->execute();
    
    if ($stmtCheck->fetch()) {
        echo json_encode([
            'success' => false,
            'message' => 'You have already reviewed this product'
        ]);
        exit;
    }
    
    /* ========================================
       STEP 2: Check if user purchased the product
       This checks if the user has an order containing this product
    ======================================== */
    $checkPurchaseSql = "
        SELECT o.id as order_id
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN product_variants pv ON oi.variant_id = pv.id
        WHERE o.user_id = :user_id
        AND pv.product_id = :product_id
        AND o.status = 'delivered'
        LIMIT 1
    ";
    
    $stmtPurchase = $pdo->prepare($checkPurchaseSql);
    $stmtPurchase->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmtPurchase->bindValue(':product_id', $productId, PDO::PARAM_INT);
    $stmtPurchase->execute();
    
    $purchase = $stmtPurchase->fetch(PDO::FETCH_ASSOC);
    
    if (!$purchase) {
        echo json_encode([
            'success' => false,
            'message' => 'You can only review products you have purchased and received'
        ]);
        exit;
    }
    
    $orderId = $purchase['order_id'];
    
    /* ========================================
       STEP 3: Insert the review
    ======================================== */
    $insertSql = "INSERT INTO ratings 
                  (user_id, product_id, order_id, rating, comment) 
                  VALUES (:user_id, :product_id, :order_id, :rating, :comment)";
    
    $stmtInsert = $pdo->prepare($insertSql);
    $stmtInsert->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmtInsert->bindValue(':product_id', $productId, PDO::PARAM_INT);
    $stmtInsert->bindValue(':order_id', $orderId, PDO::PARAM_INT);
    $stmtInsert->bindValue(':rating', $rating, PDO::PARAM_INT);
    $stmtInsert->bindValue(':comment', $comment, PDO::PARAM_STR);
    
    if ($stmtInsert->execute()) {
        // Get the newly created review with user info
        $getNewReviewSql = "
            SELECT r.*, u.username 
            FROM ratings r
            JOIN users u ON u.id = r.user_id
            WHERE r.id = :review_id
        ";
        
        $stmtGetReview = $pdo->prepare($getNewReviewSql);
        $stmtGetReview->bindValue(':review_id', $pdo->lastInsertId(), PDO::PARAM_INT);
        $stmtGetReview->execute();
        $newReview = $stmtGetReview->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'message' => 'Review submitted successfully!',
            'review' => $newReview
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to submit review'
        ]);
    }
    
} catch(PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    
    // Check for duplicate entry error
    if ($e->getCode() == '23000') {
        echo json_encode([
            'success' => false,
            'message' => 'You have already reviewed this product for this order'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Database error occurred'
        ]);
    }
}
?>