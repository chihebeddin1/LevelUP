
<?php
// Include database connection
require_once 'config/database.php';
// Create database connection
$database = new Database();
$db = $database->getConnection();
// Query to get users data
$query = "SELECT
            u.*,
            COUNT(o.id) as total_orders,
            MAX(o.order_date) as last_order_date
          FROM users u
          LEFT JOIN orders o ON u.id = o.user_id
          GROUP BY u.id
          ORDER BY u.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
// Count total users and admins for stats
$statsQuery = "SELECT
                COUNT(*) as total_users,
                SUM(CASE WHEN user_type = 'admin' THEN 1 ELSE 0 END) as total_admins
               FROM users";
$statsStmt = $db->prepare($statsQuery);
$statsStmt->execute();
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
// Check if there are users
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sport Shop - Users Management</title>
      <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Toast container for notifications -->
    <div class="toast-container" id="toastContainer"></div>
    <div class="container">
        <!-- Users Table -->
        <div class="users-table-container">
<div class="table-header">
    <div class="table-actions">
        <button class="btn-primary" onclick="refreshTable()">
            <i class="fas fa-sync-alt"></i> Refresh
        </button>
        <!-- REMOVED: Add User button -->
        <button class="btn-primary" onclick="exportToCSV()">
            <i class="fas fa-file-export"></i> Export CSV
        </button>
    </div>
</div>
            <div class="table-responsive" id="tableContainer" style="width: 93%; margin-left: 10px;">
                <?php if (count($users) > 0): ?>
                    <table class="users-table" id="usersTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>User Type</th>
                                <th>Auth Provider</th>
                                <th>Orders</th>
                                <th>Last Order</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="usersTableBody">
                            <?php foreach ($users as $index => $user): ?>
                                <?php
                                // Get first letter of username for avatar
                                $avatarLetter = strtoupper(substr($user['username'], 0, 1));
                               
                                // Format dates
                                $joinedDate = date('M d, Y', strtotime($user['created_at']));
                                $lastOrder = $user['last_order_date'] ?
                                    date('M d, Y', strtotime($user['last_order_date'])) :
                                    'No orders';
                                ?>
                                <tr id="userRow-<?php echo $user['id']; ?>" data-user-id="<?php echo $user['id']; ?>">
                                    <td class="user-id">#<?php echo str_pad($user['id'], 4, '0', STR_PAD_LEFT); ?></td>
                                   
                                    <!-- Username Cell (Editable) -->
                                    <td class="editable-cell username-cell"
                                        data-field="username"
                                        data-value="<?php echo htmlspecialchars($user['username']); ?>"
                                        onclick="startEdit(this, <?php echo $user['id']; ?>)">
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <div class="avatar">
                                                <?php echo $avatarLetter; ?>
                                            </div>
                                            <div>
                                                <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                                <div style="font-size: 0.875rem; color: #666;">
                                                    ID: <?php echo $user['id']; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                   
                                    <!-- Email Cell (Editable) -->
                                    <td class="editable-cell email-cell"
                                        data-field="email"
                                        data-value="<?php echo htmlspecialchars($user['email']); ?>"
                                        onclick="startEdit(this, <?php echo $user['id']; ?>)">
                                        <?php echo htmlspecialchars($user['email']); ?>
                                    </td>
                                   
                                    <!-- User Type Cell (Editable) -->
                                    <td class="editable-cell user-type-cell"
                                        data-field="user_type"
                                        data-value="<?php echo $user['user_type']; ?>"
                                        onclick="startEdit(this, <?php echo $user['id']; ?>)">
                                        <span class="role-badge role-<?php echo strtolower($user['user_type']); ?>">
                                            <?php echo ucfirst($user['user_type']); ?>
                                        </span>
                                    </td>
                                   
                                    <!-- Auth Provider Cell (Editable) -->
                                    <td class="editable-cell auth-provider-cell"
                                        data-field="auth_provider"
                                        data-value="<?php echo $user['auth_provider']; ?>"
                                        onclick="startEdit(this, <?php echo $user['id']; ?>)">
                                        <?php if ($user['auth_provider'] == 'google'): ?>
                                            <span style="color: #DB4437;">
                                                <i class="fab fa-google"></i> Google
                                            </span>
                                        <?php else: ?>
                                            <span style="color: #4285F4;">
                                                <i class="fas fa-envelope"></i> Email
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                   
                                    <!-- Orders Cell (Read-only) -->
                                    <td>
                                        <span style="font-weight: 600; color: #2d3748;">
                                            <?php echo $user['total_orders']; ?>
                                        </span>
                                    </td>
                                   
                                    <!-- Last Order Cell (Read-only) -->
                                    <td><?php echo $lastOrder; ?></td>
                                   
                                    <!-- Joined Date Cell (Read-only) -->
                                    <td><?php echo $joinedDate; ?></td>
                                   
                                    <!-- Actions Cell -->
                                   <td class="actions-cell">
    <?php if ($user['user_type'] != 'admin'): ?>
        <button class="btn btn-danger btn-sm"
                onclick="deleteUser(<?php echo $user['id']; ?>)"
                title="Delete User">
            <i class="fas fa-trash"></i>
        </button>
    <?php else: ?>
        <span class="text-muted" style="font-size: 0.875rem;">Admin</span>
    <?php endif; ?>
</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-user-slash"></i>
                        <h3>No Users Found</h3>
                        <p>There are no registered users in the system yet.</p>
                        <button class="btn btn-primary" onclick="addNewUserInline()" style="margin-top: 15px;">
                            <i class="fas fa-user-plus"></i> Add First User
                        </button>
                    </div>
                <?php endif; ?>
            </div>
          
        </div>
    </div>
    <!-- Hidden template for edit mode -->
<template id="editTemplate">
    <div class="edit-wrapper">
        <input type="text" class="edit-input" onkeypress="handleEnterKey(event, this)">
        <div class="edit-actions">
            <button class="save-btn" onclick="saveEdit(this)">
                <span class="save-text">Save</span>
            </button>
            <!-- CANCEL BUTTON REMOVED -->
        </div>
    </div>
</template>
    <!-- Template for select fields -->
   <template id="selectTemplate">
    <div class="edit-wrapper">
        <select class="edit-select" onchange="handleSelectChange(this)">
            <!-- Options will be added dynamically -->
        </select>
        <div class="edit-actions">
            <button class="save-btn" onclick="saveSelectEdit(this)">
                <span class="save-text">Save</span>
            </button>
        </div>
    </div>
</template>
    <script src="script.js"></script>
</body>
</html>