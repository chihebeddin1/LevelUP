<?php
session_start();
header('Content-Type: application/json');

// Database connection
$host = "localhost";
$dbname = "sport_shop";
$user = "root";
$password = "";
$port = 3306;

// Get data from POST
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'No data received']);
    exit;
}

// Récupérer l'ID utilisateur de la session
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated. Please login.']);
    exit;
}

try {
    $dsn = "mysql:host=$host;dbname=$dbname;port=$port";
    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Récupérer l'email de l'utilisateur depuis la base de données
    $userStmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
    $userStmt->execute([$userId]);
    $user = $userStmt->fetch();
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    $userEmail = $user['email'];
    
    // Utiliser l'email de l'utilisateur connecté 
    if (!empty($userEmail) && isset($data['customer']['email'])) {
        $data['customer']['email'] = $userEmail;
    }
    
    // Vérifier que l'email est valide
    if (empty($data['customer']['email']) || !filter_var($data['customer']['email'], FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email address']);
        exit;
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    // 1. Create order avec status 'processing' par défaut
    $orderSql = "INSERT INTO orders (user_id, total_amount, status) 
                 VALUES (:user_id, :total_amount, 'processing')";
    
    $stmtOrder = $pdo->prepare($orderSql);
    $stmtOrder->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmtOrder->bindValue(':total_amount', $data['order']['total'], PDO::PARAM_STR);
    $stmtOrder->execute();
    
    $orderId = $pdo->lastInsertId();
    
    // 2. Save customer information dans orders_info avec user_id (SANS EMAIL COLUMN)
    $customerSql = "INSERT INTO orders_info (order_id, user_id, customer_name, phone, wilaya, commune, address, instructions, delivery_type, payment_method)
                    VALUES (:order_id, :user_id, :customer_name, :phone, :wilaya, :commune, :address, :instructions, :delivery_type, :payment_method)";
    
    $stmtCustomer = $pdo->prepare($customerSql);
    $customerName = $data['customer']['firstName'] . ' ' . $data['customer']['lastName'];
    $stmtCustomer->bindValue(':order_id', $orderId, PDO::PARAM_INT);
    $stmtCustomer->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmtCustomer->bindValue(':customer_name', $customerName, PDO::PARAM_STR);
    $stmtCustomer->bindValue(':phone', $data['customer']['phone'], PDO::PARAM_STR);
    $stmtCustomer->bindValue(':wilaya', $data['customer']['wilaya'], PDO::PARAM_STR);
    $stmtCustomer->bindValue(':commune', $data['customer']['commune'], PDO::PARAM_STR);
    $stmtCustomer->bindValue(':address', $data['customer']['address'], PDO::PARAM_STR);
    $stmtCustomer->bindValue(':instructions', $data['customer']['instructions'], PDO::PARAM_STR);
    $stmtCustomer->bindValue(':delivery_type', $data['order']['deliveryType'], PDO::PARAM_STR);
    $stmtCustomer->bindValue(':payment_method', $data['order']['paymentMethod'], PDO::PARAM_STR);
    $stmtCustomer->execute();
    
    // 3. Create order items (for each product in the cart)
    $orderItemSql = "INSERT INTO order_items (order_id, variant_id, quantity, price_at_purchase)
                     VALUES (:order_id, :variant_id, :quantity, :price_at_purchase)";
    
    $updateStockSql = "UPDATE product_variants 
                       SET stock_quantity = stock_quantity - :quantity 
                       WHERE id = :variant_id AND stock_quantity >= :quantity";
    
    $stmtItem = $pdo->prepare($orderItemSql);
    $stmtStock = $pdo->prepare($updateStockSql);
    
    $allItemsInStock = true;
    $outOfStockItems = [];
    
    foreach ($data['order']['items'] as $item) {
        // Vérifier si le stock est suffisant
        $checkStockSql = "SELECT stock_quantity FROM product_variants WHERE id = :variant_id";
        $stmtCheck = $pdo->prepare($checkStockSql);
        $stmtCheck->bindValue(':variant_id', $item['variantId'], PDO::PARAM_INT);
        $stmtCheck->execute();
        $stock = $stmtCheck->fetch(PDO::FETCH_ASSOC);
        
        if (!$stock || $stock['stock_quantity'] < $item['quantity']) {
            $allItemsInStock = false;
            $outOfStockItems[] = $item['productName'];
            continue;
        }
        
        // Insert order item
        $stmtItem->bindValue(':order_id', $orderId, PDO::PARAM_INT);
        $stmtItem->bindValue(':variant_id', $item['variantId'], PDO::PARAM_INT);
        $stmtItem->bindValue(':quantity', $item['quantity'], PDO::PARAM_INT);
        $stmtItem->bindValue(':price_at_purchase', $item['unitPrice'], PDO::PARAM_STR);
        $stmtItem->execute();
        
        // Update stock
        $stmtStock->bindValue(':quantity', $item['quantity'], PDO::PARAM_INT);
        $stmtStock->bindValue(':variant_id', $item['variantId'], PDO::PARAM_INT);
        $stmtStock->execute();
    }
    
    if (!$allItemsInStock) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'message' => 'Some items are out of stock: ' . implode(', ', $outOfStockItems)
        ]);
        exit;
    }
    
    // 4. Create order status history avec user_id
    $statusHistorySql = "INSERT INTO order_status_history (order_id, user_id, status, notes)
                         VALUES (:order_id, :user_id, 'processing', 'Order placed by customer')";
    
    $stmtStatus = $pdo->prepare($statusHistorySql);
    $stmtStatus->bindValue(':order_id', $orderId, PDO::PARAM_INT);
    $stmtStatus->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmtStatus->execute();
    
    // 5. Save order confirmation avec user_id
    $orderNumber = 'CMD-' . str_pad($orderId, 8, '0', STR_PAD_LEFT);
    $confirmationSql = "INSERT INTO order_confirmations (order_number, order_id, user_id, customer_email, total_amount, status, confirmation_data)
                        VALUES (:order_number, :order_id, :user_id, :customer_email, :total_amount, 'processing', :confirmation_data)";
    
    $stmtConfirmation = $pdo->prepare($confirmationSql);
    $stmtConfirmation->bindValue(':order_number', $orderNumber, PDO::PARAM_STR);
    $stmtConfirmation->bindValue(':order_id', $orderId, PDO::PARAM_INT);
    $stmtConfirmation->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmtConfirmation->bindValue(':customer_email', $data['customer']['email'], PDO::PARAM_STR);
    $stmtConfirmation->bindValue(':total_amount', $data['order']['total'], PDO::PARAM_STR);
    $stmtConfirmation->bindValue(':confirmation_data', json_encode($data), PDO::PARAM_STR);
    $stmtConfirmation->execute();
    
    // Commit transaction
    $pdo->commit();
    
    // 6. Clear cart from database after successful order
    try {
        // Clear cart items
        $clearCartQuery = "
            DELETE ci FROM cart_items ci
            INNER JOIN cart c ON ci.cart_id = c.id
            WHERE c.user_id = :user_id
        ";
        $stmtClear = $pdo->prepare($clearCartQuery);
        $stmtClear->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmtClear->execute();
        
        // Delete cart
        $deleteCartQuery = "DELETE FROM cart WHERE user_id = :user_id";
        $stmtDeleteCart = $pdo->prepare($deleteCartQuery);
        $stmtDeleteCart->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmtDeleteCart->execute();
    } catch (Exception $e) {
        // Log error but don't fail the order
        error_log("Failed to clear cart after order: " . $e->getMessage());
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Order created successfully with status: processing',
        'order_id' => $orderId,
        'order_number' => $orderNumber,
        'user_id' => $userId,
        'user_email' => $data['customer']['email'],
        'status' => 'processing',
        'item_count' => count($data['order']['items']),
        'total_amount' => $data['order']['total']
    ]);
    
} catch(PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>