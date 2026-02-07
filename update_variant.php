<?php
header('Content-Type: application/json');
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$field = isset($_POST['field']) ? $_POST['field'] : '';
$value = isset($_POST['value']) ? $_POST['value'] : '';

// Allowed fields for variant update
$allowedFields = ['sku', 'price_adjustment', 'stock_quantity', 'is_active', 'image_url'];
if ($id <= 0 || empty($field) || !in_array($field, $allowedFields)) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Validate specific fields
    if ($field === 'stock_quantity') {
        $value = intval($value);
        if ($value < 0) {
            echo json_encode(['success' => false, 'message' => 'Stock quantity cannot be negative']);
            exit;
        }
    }
    
    if ($field === 'price_adjustment') {
        $value = floatval($value);
    }
    
    if ($field === 'is_active') {
        $value = $value === '1' || $value === 'true' ? 1 : 0;
    }
    
    // For SKU updates, check if SKU already exists (excluding current variant)
    if ($field === 'sku') {
        $checkSkuQuery = "SELECT id FROM product_variants WHERE sku = :sku AND id != :id";
        $checkSkuStmt = $db->prepare($checkSkuQuery);
        $checkSkuStmt->execute([':sku' => $value, ':id' => $id]);
        
        if ($checkSkuStmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'SKU already exists']);
            exit;
        }
    }
    
    // Update query
    $query = "UPDATE product_variants SET {$field} = :value WHERE id = :id";
    $stmt = $db->prepare($query);
    
    $success = $stmt->execute([
        ':value' => $value,
        ':id' => $id
    ]);
    
    if ($success && $stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Variant updated successfully']);
    } else {
        $checkVariantQuery = "SELECT id FROM product_variants WHERE id = :id";
        $checkVariantStmt = $db->prepare($checkVariantQuery);
        $checkVariantStmt->execute([':id' => $id]);
        
        if (!$checkVariantStmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Variant not found']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No changes made or update failed']);
        }
    }
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    
    if ($e->getCode() == 23000) {
        echo json_encode(['success' => false, 'message' => 'Database constraint violation']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>