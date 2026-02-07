<?php
header('Content-Type: application/json');
require_once 'config/database.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$field = isset($_POST['field']) ? $_POST['field'] : '';
$value = isset($_POST['value']) ? $_POST['value'] : '';

// Allowed fields for update
$allowedFields = ['name', 'description', 'base_price', 'product_type', 'sub_type', 'is_active'];
if ($id <= 0 || empty($field) || !in_array($field, $allowedFields)) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // For numeric fields, validate
    if ($field === 'base_price') {
        $value = floatval($value);
        if ($value < 0) {
            echo json_encode(['success' => false, 'message' => 'Price cannot be negative']);
            exit;
        }
    }
    
    // For boolean field
    if ($field === 'is_active') {
        $value = $value === '1' || $value === 'true' ? 1 : 0;
    }
    
    // Update query
    $query = "UPDATE products SET {$field} = :value WHERE id = :id";
    $stmt = $db->prepare($query);
    
    $success = $stmt->execute([
        ':value' => $value,
        ':id' => $id
    ]);
    
    if ($success && $stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Product updated successfully']);
    } else {
        // Check if product exists
        $checkProductQuery = "SELECT id FROM products WHERE id = :id";
        $checkProductStmt = $db->prepare($checkProductQuery);
        $checkProductStmt->execute([':id' => $id]);
        
        if (!$checkProductStmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Product not found']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No changes made or update failed']);
        }
    }
    
} catch (PDOException $e) {
    error_log("Database error [{$e->getCode()}]: {$e->getMessage()}");
    
    if ($e->getCode() == 23000) {
        echo json_encode(['success' => false, 'message' => 'Database constraint violation. The value might already exist.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>