<?php
// create_test_user.php
session_start();

function getDBConnection() {
    $host = 'localhost';
    $user = 'root';
    $password = '';
    $database = 'sport_shop';
    
    try {
        $pdo = new PDO(
            "mysql:host=$host;dbname=$database;charset=utf8mb4", 
            $user, 
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = getDBConnection();
        
        // Créer un utilisateur test
        $username = 'testuser';
        $email = 'test@test.com';
        $password = 'test123'; // Mot de passe en clair
        
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password, auth_provider, user_type) 
            VALUES (?, ?, ?, 'email', 'customer')
        ");
        
        $stmt->execute([$username, $email, $password]);
        
        $user_id = $pdo->lastInsertId();
        
        // Créer panier
        $stmt = $pdo->prepare("INSERT INTO cart (user_id) VALUES (?)");
        $stmt->execute([$user_id]);
        
        $message = "✅ Utilisateur test créé !<br>Username: $username<br>Email: $email<br>Password: $password<br>ID: $user_id";
        
    } catch (PDOException $e) {
        $message = "❌ Erreur: " . $e->getMessage();
    }
}

// Afficher les utilisateurs existants
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT id, username, email, password FROM users WHERE password IS NOT NULL");
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    $users = [];
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Créer Utilisateur Test</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .message { padding: 15px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        form { margin: 20px 0; }
        input[type="submit"] { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; }
    </style>
</head>
<body>
    <h1>Créer Utilisateur Test (Sans Hachage)</h1>
    
    <?php if ($message): ?>
        <div class="message <?php echo strpos($message, '✅') !== false ? 'success' : 'error'; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <input type="submit" value="Créer Utilisateur Test">
    </form>
    
    <h2>Utilisateurs existants :</h2>
    <?php if ($users): ?>
        <table>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Email</th>
                <th>Password (en clair)</th>
            </tr>
            <?php foreach ($users as $user): ?>
            <tr>
                <td><?php echo htmlspecialchars($user['id']); ?></td>
                <td><?php echo htmlspecialchars($user['username']); ?></td>
                <td><?php echo htmlspecialchars($user['email']); ?></td>
                <td style="font-family: monospace;"><?php echo htmlspecialchars($user['password']); ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>Aucun utilisateur trouvé</p>
    <?php endif; ?>
    
    <p><a href="index_login.php">← Retour à la connexion</a></p>
</body>
</html>