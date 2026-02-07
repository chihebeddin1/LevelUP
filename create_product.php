<?php
header('Content-Type: application/json');
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get POST data
$name = $_POST['name'] ?? '';
$description = $_POST['description'] ?? '';
$base_price = isset($_POST['base_price']) ? floatval($_POST['base_price']) : 0.00;
$product_type = $_POST['product_type'] ?? 'clothing';
$sub_type = $_POST['sub_type'] ?? '';
$image_url = $_POST['image_url'] ?? null;
$is_active = isset($_POST['is_active']) ? ($_POST['is_active'] === '1' ? 1 : 0) : 1;

// Validate
if (empty($name) || $base_price <= 0) {
    echo json_encode(['success' => false, 'message' => 'Product name and valid price are required']);
    exit;
}

if (!in_array($product_type, ['clothing', 'accessories'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid product type']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if product with same name exists
    $checkQuery = "SELECT id FROM products WHERE name = :name";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->execute([':name' => $name]);
    
    if ($checkStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Product with this name already exists']);
        exit;
    }
    
    // Insert new product
    $query = "INSERT INTO products (name, description, base_price, product_type, sub_type, image_url, is_active) 
              VALUES (:name, :description, :base_price, :product_type, :sub_type, :image_url, :is_active)";
    
    $stmt = $db->prepare($query);
    $success = $stmt->execute([
        ':name' => $name,
        ':description' => $description,
        ':base_price' => $base_price,
        ':product_type' => $product_type,
        ':sub_type' => $sub_type,
        ':image_url' => $image_url,
        ':is_active' => $is_active
    ]);
    
    if ($success) {
        $product_id = $db->lastInsertId();
        echo json_encode([
            'success' => true, 
            'message' => 'Product created successfully',
            'product_id' => $product_id
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create product']);
    }
    
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>