<?php
header('Content-Type: application/json');
require_once 'config/database.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get POST data
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$field = isset($_POST['field']) ? $_POST['field'] : '';
$value = isset($_POST['value']) ? $_POST['value'] : '';

// Validate input
if ($id <= 0 || empty($field) || !in_array($field, ['username', 'email', 'user_type', 'auth_provider'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

try {
    // Create database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if username or email already exists (except for current user)
    if ($field === 'username' || $field === 'email') {
        $checkQuery = "SELECT id FROM users WHERE {$field} = :value AND id != :id";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->execute([':value' => $value, ':id' => $id]);
        
        if ($checkStmt->fetch()) {
            $message = $field === 'username' ? 'Username already exists' : 'Email already exists';
            echo json_encode(['success' => false, 'message' => $message]);
            exit;
        }
    }
    
    // Update query
    $query = "UPDATE users SET {$field} = :value WHERE id = :id";
    $stmt = $db->prepare($query);
    
    // Execute update
    $success = $stmt->execute([
        ':value' => $value,
        ':id' => $id
    ]);
    
    if ($success && $stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'User updated successfully']);
    } else {
        // Check if user exists
        $checkUserQuery = "SELECT id FROM users WHERE id = :id";
        $checkUserStmt = $db->prepare($checkUserQuery);
        $checkUserStmt->execute([':id' => $id]);
        
        if (!$checkUserStmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'User not found']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No changes made or update failed']);
        }
    }
    
} catch (PDOException $e) {
    // Catch database errors
    $errorCode = $e->getCode();
    $errorMessage = $e->getMessage();
    
    // Log the error
    error_log("Database error [$errorCode]: $errorMessage");
    
    // Return user-friendly message
    if ($errorCode == 23000) { // Integrity constraint violation
        echo json_encode(['success' => false, 'message' => 'Database constraint violation. The value might already exist.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>