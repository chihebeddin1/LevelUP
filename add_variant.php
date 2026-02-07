<?php
header('Content-Type: application/json');
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get POST data
$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
$color_id = isset($_POST['color_id']) && !empty($_POST['color_id']) ? intval($_POST['color_id']) : null;
$size_id = isset($_POST['size_id']) && !empty($_POST['size_id']) ? intval($_POST['size_id']) : null;
$sku = isset($_POST['sku']) ? trim($_POST['sku']) : '';
$price_adjustment = isset($_POST['price_adjustment']) ? floatval($_POST['price_adjustment']) : 0.00;
$stock_quantity = isset($_POST['stock_quantity']) ? intval($_POST['stock_quantity']) : 0;
$image_url = isset($_POST['image_url']) ? trim($_POST['image_url']) : null;

// Validate
if ($product_id <= 0 || empty($sku)) {
    echo json_encode(['success' => false, 'message' => 'Product ID and SKU are required']);
    exit;
}

if ($stock_quantity < 0) {
    echo json_encode(['success' => false, 'message' => 'Stock quantity cannot be negative']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if product exists
    $checkProductQuery = "SELECT id FROM products WHERE id = :product_id";
    $checkProductStmt = $db->prepare($checkProductQuery);
    $checkProductStmt->execute([':product_id' => $product_id]);
    
    if (!$checkProductStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }
    
    // Check if SKU already exists
    $checkSkuQuery = "SELECT id FROM product_variants WHERE sku = :sku";
    $checkSkuStmt = $db->prepare($checkSkuQuery);
    $checkSkuStmt->execute([':sku' => $sku]);
    
    if ($checkSkuStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'SKU already exists']);
        exit;
    }
    
    // Check if variant combination already exists
    $checkVariantQuery = "SELECT id FROM product_variants 
                         WHERE product_id = :product_id 
                         AND ((color_id IS NULL AND :color_id IS NULL) OR color_id = :color_id)
                         AND ((size_id IS NULL AND :size_id IS NULL) OR size_id = :size_id)";
    
    $checkVariantStmt = $db->prepare($checkVariantQuery);
    $checkVariantStmt->execute([
        ':product_id' => $product_id,
        ':color_id' => $color_id,
        ':size_id' => $size_id
    ]);
    
    if ($checkVariantStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Variant with this color/size combination already exists']);
        exit;
    }
    
    // Insert new variant
    $query = "INSERT INTO product_variants 
              (product_id, color_id, size_id, sku, price_adjustment, stock_quantity, image_url) 
              VALUES (:product_id, :color_id, :size_id, :sku, :price_adjustment, :stock_quantity, :image_url)";
    
    $stmt = $db->prepare($query);
    $success = $stmt->execute([
        ':product_id' => $product_id,
        ':color_id' => $color_id,
        ':size_id' => $size_id,
        ':sku' => $sku,
        ':price_adjustment' => $price_adjustment,
        ':stock_quantity' => $stock_quantity,
        ':image_url' => $image_url
    ]);
    
    if ($success) {
        $variant_id = $db->lastInsertId();
        echo json_encode([
            'success' => true, 
            'message' => 'Variant added successfully',
            'variant_id' => $variant_id
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add variant']);
    }
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    
    if ($e->getCode() == 23000) {
        echo json_encode(['success' => false, 'message' => 'Database constraint violation. SKU might already exist.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>