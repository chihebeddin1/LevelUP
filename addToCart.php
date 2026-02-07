<?php
session_start();
header('Content-Type: application/json');

$host = "localhost";
$dbname = "sport_shop";
$user = "root";
$password = "";
$port = 3306;

/**
 * 1. CHECK USER SESSION
 */
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'User not authenticated'
    ]);
    exit;
}

$userId = (int)$_SESSION['user_id'];

try {
    $dsn = "mysql:host=$host;dbname=$dbname;port=$port;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    /**
     * 2. READ JSON INPUT (CRITICAL FIX)
     */
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);

    if (!is_array($data)) {
        throw new Exception('Invalid request payload');
    }

    $variantId = isset($data['variant_id']) ? (int)$data['variant_id'] : 0;
    $quantity  = isset($data['quantity']) ? (int)$data['quantity'] : 1;

    if ($variantId <= 0 || $quantity < 1) {
        throw new Exception('Invalid variant or quantity');
    }

    $pdo->beginTransaction();

    /**
     * 3. CHECK VARIANT EXISTS & STOCK
     */
    $stmtVariant = $pdo->prepare("
        SELECT pv.id, pv.stock_quantity, p.name AS product_name
        FROM product_variants pv
        INNER JOIN products p ON pv.product_id = p.id
        WHERE pv.id = :variant_id
          AND pv.is_active = 1
        LIMIT 1
    ");
    $stmtVariant->execute([
        ':variant_id' => $variantId
    ]);

    $variant = $stmtVariant->fetch();

    if (!$variant) {
        throw new Exception('Product variant not found');
    }

    if ($variant['stock_quantity'] < $quantity) {
        throw new Exception(
            'Insufficient stock. Only ' . $variant['stock_quantity'] . ' available.'
        );
    }

    /**
     * 4. GET OR CREATE CART
     */
    $stmtCart = $pdo->prepare("SELECT id FROM cart WHERE user_id = :user_id LIMIT 1");
    $stmtCart->execute([
        ':user_id' => $userId
    ]);
    $cart = $stmtCart->fetch();

    if (!$cart) {
        $stmtCreateCart = $pdo->prepare("INSERT INTO cart (user_id) VALUES (:user_id)");
        $stmtCreateCart->execute([
            ':user_id' => $userId
        ]);
        $cartId = (int)$pdo->lastInsertId();
    } else {
        $cartId = (int)$cart['id'];
    }

    /**
     * 5. CHECK EXISTING CART ITEM
     */
    $stmtCheck = $pdo->prepare("
        SELECT id, quantity
        FROM cart_items
        WHERE cart_id = :cart_id
          AND variant_id = :variant_id
        LIMIT 1
    ");
    $stmtCheck->execute([
        ':cart_id'   => $cartId,
        ':variant_id'=> $variantId
    ]);

    $existingItem = $stmtCheck->fetch();

    if ($existingItem) {
        $newQuantity = $existingItem['quantity'] + $quantity;

        if ($newQuantity > $variant['stock_quantity']) {
            throw new Exception(
                'Cannot add more. Maximum available: ' . $variant['stock_quantity']
            );
        }

        $stmtUpdate = $pdo->prepare("
            UPDATE cart_items
            SET quantity = :quantity
            WHERE id = :id
        ");
        $stmtUpdate->execute([
            ':quantity' => $newQuantity,
            ':id'       => $existingItem['id']
        ]);

        $message = 'Cart updated! Quantity increased to ' . $newQuantity;
    } else {
        $stmtInsert = $pdo->prepare("
            INSERT INTO cart_items (cart_id, variant_id, quantity)
            VALUES (:cart_id, :variant_id, :quantity)
        ");
        $stmtInsert->execute([
            ':cart_id'   => $cartId,
            ':variant_id'=> $variantId,
            ':quantity'  => $quantity
        ]);

        $message = 'Product added to cart successfully!';
    }

    /**
     * 6. CART PRODUCT COUNT
     */
    $stmtCount = $pdo->prepare("
        SELECT COUNT(DISTINCT pv.product_id) AS product_count
        FROM cart_items ci
        INNER JOIN product_variants pv ON ci.variant_id = pv.id
        WHERE ci.cart_id = :cart_id
    ");
    $stmtCount->execute([
        ':cart_id' => $cartId
    ]);

    $cartCount = $stmtCount->fetch();

    $pdo->commit();

    echo json_encode([
        'success'      => true,
        'message'      => $message,
        'product_name'=> $variant['product_name'],
        'cart_count'  => (int)$cartCount['product_count']
    ]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('AddToCart error: ' . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
