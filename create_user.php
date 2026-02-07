<?php
header('Content-Type: application/json');
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get POST data
$username = $_POST['username'] ?? '';
$email = $_POST['email'] ?? '';
$user_type = $_POST['user_type'] ?? 'customer';
$auth_provider = $_POST['auth_provider'] ?? 'email';

// Validate
if (empty($username) || empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Username and email are required']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if username or email exists
    $checkQuery = "SELECT id FROM users WHERE username = :username OR email = :email";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->execute([':username' => $username, ':email' => $email]);
    
    if ($checkStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
        exit;
    }
    
    // Insert new user
    $query = "INSERT INTO users (username, email, user_type, auth_provider) 
              VALUES (:username, :email, :user_type, :auth_provider)";
    
    $stmt = $db->prepare($query);
    $success = $stmt->execute([
        ':username' => $username,
        ':email' => $email,
        ':user_type' => $user_type,
        ':auth_provider' => $auth_provider
    ]);
    
    if ($success) {
        echo json_encode(['success' => true, 'message' => 'User created successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create user']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>