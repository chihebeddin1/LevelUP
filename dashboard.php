<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sport_shop');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

class DashboardStats {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    // Get all orders with details
    public function getAllOrders() {
        $query = "
            SELECT 
                o.id,
                o.order_date,
                o.total_amount,
                o.status,
                u.username,
                u.email,
                COUNT(oi.id) as items_count,
                GROUP_CONCAT(DISTINCT p.name SEPARATOR ', ') as products
            FROM orders o
            JOIN users u ON o.user_id = u.id
            LEFT JOIN order_items oi ON o.id = oi.order_id
            LEFT JOIN product_variants pv ON oi.variant_id = pv.id
            LEFT JOIN products p ON pv.product_id = p.id
            WHERE o.status NOT IN ('cancelled')
            GROUP BY o.id, o.order_date, o.total_amount, o.status, u.username, u.email
            ORDER BY o.order_date DESC
        ";
        
        $result = $this->conn->query($query);
        
        $orders = [];
        while ($row = $result->fetch_assoc()) {
            $orders[] = [
                'id' => $row['id'],
                'order_date' => $row['order_date'],
                'total_amount' => $row['total_amount'],
                'status' => $row['status'],
                'username' => $row['username'],
                'email' => $row['email'],
                'items_count' => $row['items_count'],
                'products' => $row['products'] ? substr($row['products'], 0, 50) . '...' : 'No products'
            ];
        }
        
        return $orders;
    }

    // 1. REAL Daily Performance with actual high/low/average
    public function getDailyOrderStats($days = 30) {
        $query = "
            SELECT 
                DATE(order_date) as order_date,
                COUNT(*) as daily_orders
            FROM orders
            WHERE order_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                AND status NOT IN ('cancelled')
            GROUP BY DATE(order_date)
            ORDER BY order_date ASC
        ";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $days);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        $allOrders = [];
        
        while ($row = $result->fetch_assoc()) {
            $dailyOrders = (int)$row['daily_orders'];
            $data[] = [
                'date' => $row['order_date'],
                'orders' => $dailyOrders
            ];
            $allOrders[] = $dailyOrders;
        }
        
        // Calculate REAL statistics from actual data
        if (!empty($allOrders)) {
            $high = max($allOrders);
            $low = min($allOrders);
            $avg = array_sum($allOrders) / count($allOrders);
            
            return [
                'data' => $data,
                'stats' => [
                    'high' => $high,
                    'low' => $low,
                    'avg' => round($avg, 1)
                ]
            ];
        }
        
        return [
            'data' => [],
            'stats' => ['high' => 0, 'low' => 0, 'avg' => 0]
        ];
    }

    // 2. Inventory & Product Management
    public function getInventoryStats() {
        // Main inventory stats query
        $query = "
            SELECT 
                (SELECT COUNT(*) FROM products WHERE is_active = TRUE) as total_products,
                (SELECT COUNT(*) FROM product_variants WHERE is_active = TRUE) as total_variants,
                (SELECT COUNT(*) FROM product_variants 
                 WHERE stock_quantity <= 10 AND stock_quantity > 0) as low_stock_items,
                (SELECT COUNT(*) FROM product_variants 
                 WHERE stock_quantity = 0) as out_of_stock_items,
                COALESCE((SELECT SUM(p.base_price * pv.stock_quantity) 
                 FROM product_variants pv
                 JOIN products p ON pv.product_id = p.id
                 WHERE pv.stock_quantity > 0), 0) as total_inventory_value
            FROM dual
        ";
        
        // Best seller query - REAL data with proper grouping
        $bestSellerQuery = "
            SELECT p.name, SUM(oi.quantity) as total_sold
            FROM products p
            JOIN product_variants pv ON p.id = pv.product_id
            JOIN order_items oi ON pv.id = oi.variant_id
            JOIN orders o ON oi.order_id = o.id
            WHERE o.status NOT IN ('cancelled')
            GROUP BY p.id, p.name
            ORDER BY total_sold DESC
            LIMIT 1
        ";
        
        $result = $this->conn->query($query);
        $stats = $result->fetch_assoc();
        
        $bestSellerResult = $this->conn->query($bestSellerQuery);
        $bestSeller = $bestSellerResult->fetch_assoc();
        
        if (!$stats) {
            $stats = [
                'total_products' => 0,
                'total_variants' => 0,
                'low_stock_items' => 0,
                'out_of_stock_items' => 0,
                'total_inventory_value' => 0
            ];
        }
        
        // Add best seller if exists
        $stats['best_seller'] = $bestSeller ? $bestSeller['name'] : 'No sales yet';
        $stats['best_seller_count'] = $bestSeller ? (int)$bestSeller['total_sold'] : 0;
        
        return $stats;
    }

    // 3. Line Chart: Revenue Trend - REAL DATA
    public function getRevenueTrend($days = 30) {
        $query = "
            SELECT 
                DATE(o.order_date) as date,
                COALESCE(SUM(oi.quantity * oi.price_at_purchase), 0) as daily_revenue
            FROM orders o
            JOIN order_items oi ON o.id = oi.order_id
            WHERE o.order_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                AND o.status NOT IN ('cancelled')
            GROUP BY DATE(o.order_date)
            ORDER BY date ASC
        ";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $days);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = [
                'date' => $row['date'],
                'revenue' => (float)$row['daily_revenue']
            ];
        }
        
        return $data;
    }

    // 4. Donut Chart: Sales by Product Type - REAL DATA
    public function getSalesByProductType($days = 30) {
        $query = "
            SELECT 
                p.product_type,
                COALESCE(SUM(oi.quantity * oi.price_at_purchase), 0) as revenue,
                COALESCE(SUM(oi.quantity), 0) as units_sold
            FROM orders o
            JOIN order_items oi ON o.id = oi.order_id
            JOIN product_variants pv ON oi.variant_id = pv.id
            JOIN products p ON pv.product_id = p.id
            WHERE o.order_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                AND o.status NOT IN ('cancelled')
            GROUP BY p.product_type
            ORDER BY revenue DESC
        ";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $days);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        $total_revenue = 0;
        
        while ($row = $result->fetch_assoc()) {
            $data[] = [
                'type' => $row['product_type'],
                'revenue' => (float)$row['revenue'],
                'units_sold' => (int)$row['units_sold']
            ];
            $total_revenue += $row['revenue'];
        }
        
        // Calculate percentages from REAL totals
        foreach ($data as &$item) {
            $item['percentage'] = $total_revenue > 0 ? round(($item['revenue'] / $total_revenue) * 100, 1) : 0;
        }
        
        return [
            'data' => $data,
            'total_revenue' => $total_revenue
        ];
    }

    // Get recent orders
    public function getRecentOrders($limit = 5) {
        $query = "
            SELECT 
                o.id,
                o.order_date,
                o.total_amount,
                o.status,
                u.username
            FROM orders o
            JOIN users u ON o.user_id = u.id
            WHERE o.status NOT IN ('cancelled')
            ORDER BY o.order_date DESC
            LIMIT ?
        ";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $orders = [];
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
        
        return $orders;
    }
}

// Initialize Dashboard
$dashboard = new DashboardStats($conn);

// Fetch all REAL data
$orderStatsResult = $dashboard->getDailyOrderStats();
$orderStats = $orderStatsResult['data'];
$orderStatsSummary = $orderStatsResult['stats'];
$inventoryStats = $dashboard->getInventoryStats();
$revenueTrend = $dashboard->getRevenueTrend();
$salesByType = $dashboard->getSalesByProductType();
$allOrders = $dashboard->getAllOrders();
$totalOrders = count($allOrders);
$totalRevenue = array_sum(array_column($allOrders, 'total_amount'));

// Convert PHP data to JSON for JavaScript
$orderStatsJson = json_encode($orderStats);
$orderStatsSummaryJson = json_encode($orderStatsSummary);
$revenueTrendJson = json_encode($revenueTrend);
$salesByTypeJson = json_encode($salesByType);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Sport Shop Dashboard</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- CSS -->
    <link rel="stylesheet" href="style.css">
    
    <!-- Pass PHP data to JavaScript -->
    <script>
        const orderStatsData = <?php echo $orderStatsJson; ?>;
        const orderStatsSummary = <?php echo $orderStatsSummaryJson; ?>;
        const revenueTrendData = <?php echo $revenueTrendJson; ?>;
        const salesByTypeData = <?php echo $salesByTypeJson; ?>;
    </script>
</head>
<body>
    <div class="container">
        <div class="background">
            <header class="navbar-font-display">
                <div class="nav-left">
                    <div class="logo">
                        <img src="assets/Group 4.svg" alt="Logo" class="icon-img">
                    </div>
                    <nav>
                        <a href="indexhome.php">BACK HOME</a>
                    </nav>
                </div>

                <div class="nav-right font-ui">
                    <div class="search-box">
                        <input type="text" placeholder="Search here..." class="search-box" />
                        <button type="submit" class="search-button" aria-label="Search">
                            <img src="assets/Search.svg" alt="Search">
                        </button>
                    </div>
                </div>
            </header>
            
            <div class="layout">
                <aside class="sidebar font-display">
                    <img src="assets/Group 21.svg" alt="Logo" class="side-img">
                    <div class="side-item active" onclick="location.reload()">
                        <img src="assets/data.svg" alt="Dashboard" class="sidebar-img">
                        <span class="side-label">DASHBOARD</span>
                    </div>
                    <div class="side-item" onclick="showUsersView()">
                        <img src="assets/users.svg" alt="Users" class="sidebar-img">
                        <span class="side-label">USERS</span>
                    </div>
                    <div class="side-item" onclick="showProductsView()">
                        <img src="assets/pro.svg" alt="Products" class="sidebar-img">
                        <span class="side-label">PRODUCTS</span>
                    </div>
                </aside>
                
                <main class="content font-ui">
                    <div class="grid">
                        <!-- 1. Daily Order Performance Chart -->
                        <div class="Candlestick">
                            <div class="chart-header">
                                <div>
                                    <h3 class="chart-title">Daily Order Performance</h3>
                                    <p class="chart-subtitle">Real daily order counts with high/low/average (Last 30 days)</p>
                                </div>
                                <div class="chart-controls">
                                    <select class="time-filter" id="candlestick-period">
                                        <option value="7">Last 7 days</option>
                                        <option value="30" selected>Last 30 days</option>
                                        <option value="90">Last 90 days</option>
                                    </select>
                                </div>
                            </div>
                            <div class="chart-container">
                                <canvas id="candlestickChart"></canvas>
                            </div>
                            <div class="chart-legend">
                                <div class="legend-item">
                                    <span class="legend-color" style="background-color: #2ecc71;"></span>
                                    <span>Highest Day: <?php echo $orderStatsSummary['high']; ?> orders</span>
                                </div>
                                <div class="legend-item">
                                    <span class="legend-color" style="background-color: #e74c3c;"></span>
                                    <span>Lowest Day: <?php echo $orderStatsSummary['low']; ?> orders</span>
                                </div>
                                <div class="legend-item">
                                    <span class="legend-color" style="background-color: #3498db;"></span>
                                    <span>Average: <?php echo $orderStatsSummary['avg']; ?> orders/day</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 2. Inventory Management -->
                        <div class="bar-charts">
                            <div class="chart-header">
                                <div>
                                    <h3 class="chart-title font-display">Inventory Status</h3>
                                    <p class="chart-subtitle">Live inventory data from database</p>
                                </div>
                            </div>
                            <div class="inventory-grid" id="inventory-stats">
                                <div class="stat-card">
                                    <span class="stat-value"><?php echo $inventoryStats['total_products']; ?></span>
                                    <span class="stat-label">Active Products</span>
                                </div>
                                <div class="stat-card">
                                    <span class="stat-value"><?php echo $inventoryStats['total_variants']; ?></span>
                                    <span class="stat-label">Total Variants</span>
                                </div>
                                <div class="stat-card">
                                    <span class="stat-value"><?php echo $inventoryStats['low_stock_items']; ?></span>
                                    <span class="stat-label">Low Stock Items</span>
                                </div>
                                <div class="stat-card">
                                    <span class="stat-value"><?php echo $inventoryStats['out_of_stock_items']; ?></span>
                                    <span class="stat-label">Out of Stock</span>
                                </div>
                                <div class="stat-card">
                                    <span class="stat-value">$<?php echo number_format($inventoryStats['total_inventory_value'], 0); ?></span>
                                    <span class="stat-label">Inventory Value</span>
                                </div>
                                <div class="stat-card">
                                    <span class="stat-value best-seller"><?php echo htmlspecialchars($inventoryStats['best_seller']); ?></span>
                                    <span class="stat-label">Best Seller 
                                        <?php if($inventoryStats['best_seller_count'] > 0): ?>
                                            (<?php echo $inventoryStats['best_seller_count']; ?> sold)
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 3. Line Chart -->
                        <div class="Line-Chart">
                            <div class="chart-header">
                                <div>
                                    <h3 class="chart-title font-display">Revenue Trend</h3>
                                    <p class="chart-subtitle">Actual daily revenue from orders (Last 30 days)</p>
                                </div>
                                <div class="chart-controls">
                                    <select class="time-filter" id="revenue-period">
                                        <option value="7">Last 7 days</option>
                                        <option value="30" selected>Last 30 days</option>
                                        <option value="90">Last 90 days</option>
                                    </select>
                                </div>
                            </div>
                            <div class="chart-container">
                                <canvas id="lineChart"></canvas>
                            </div>
                            <div class="chart-legend">
                                <div class="legend-item">
                                    <span class="legend-color" style="background-color: #9b59b6;"></span>
                                    <span>Actual Daily Revenue</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="main-container">
                        <!-- 4. Donut Chart -->
                        <div class="Pie-Chart">
                            <div class="chart-header">
                                <div>
                                    <h3 class="chart-title font-display">Sales Distribution by Product Type</h3>
                                    <p class="chart-subtitle">Real revenue distribution from actual orders</p>
                                </div>
                                <div class="chart-controls">
                                    <select class="time-filter" id="sales-period">
                                        <option value="7">Last 7 days</option>
                                        <option value="30" selected>Last 30 days</option>
                                        <option value="90">Last 90 days</option>
                                    </select>
                                </div>
                            </div>
                            <div class="chart-container">
                                <canvas id="donutChart"></canvas>
                            </div>
                            <div class="chart-legend" id="donut-legend"></div>
                        </div>
                        
                        <!-- All Orders Table -->
                        <div class="recent-orders">
                            <div class="chart-header">
                                <div>
                                    <h3 class="chart-title font-display">All Orders</h3>
                                    <small class="chart-subtitle">
                                        Total: <?php echo $totalOrders; ?> orders | 
                                        Revenue: $<?php echo number_format($totalRevenue, 2); ?>
                                    </small>
                                </div>
                                <div class="chart-controls">
                                    <input type="text" id="order-search" placeholder="Search orders..." class="search-input">
                                    <select class="time-filter" id="status-filter">
                                        <option value="">All Status</option>
                                        <option value="pending">Pending</option>
                                        <option value="processing">Processing</option>
                                        <option value="shipped">Shipped</option>
                                        <option value="delivered">Delivered</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="table-container">
                                <table id="orders-table">
                                    <thead>
                                        <tr>
                                            <th onclick="sortTable(0)">Order ID ▲▼</th>
                                            <th onclick="sortTable(1)">Date ▲▼</th>
                                            <th onclick="sortTable(2)">Customer ▲▼</th>
                                            <th>Email</th>
                                            <th onclick="sortTable(4)">Items ▲▼</th>
                                            <th onclick="sortTable(5)">Amount ▲▼</th>
                                            <th onclick="sortTable(6)">Status ▲▼</th>
                                            <th>Products</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($allOrders as $order): ?>
                                        <tr>
                                            <td>#<?php echo $order['id']; ?></td>
                                            <td><?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($order['username']); ?></td>
                                            <td><?php echo htmlspecialchars($order['email']); ?></td>
                                            <td><?php echo $order['items_count']; ?></td>
                                            <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo $order['status']; ?>">
                                                    <?php echo ucfirst($order['status']); ?>
                                                </span>
                                            </td>
                                            <td class="products-cell" title="<?php echo htmlspecialchars($order['products']); ?>">
                                                <?php echo htmlspecialchars($order['products']); ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php if(empty($allOrders)): ?>
                                        <tr>
                                            <td colspan="8" class="no-orders">No orders found in the database.</td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="table-footer">
                                <div class="pagination-info">
                                    Showing <span id="showing-count"><?php echo min(10, $totalOrders); ?></span> of 
                                    <span id="total-count"><?php echo $totalOrders; ?></span> orders
                                </div>
                                <div class="pagination">
                                    <button id="prev-page" disabled>Previous</button>
                                    <span id="page-info">Page 1</span>
                                    <button id="next-page">Next</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </main>
            </div>
        </div>
    </div>
    
  
<script>

function showUsersView() {
    const mainContent = document.querySelector('.content.font-ui');
    const sideImg = document.querySelector('.side-img');
    
    if (!mainContent) return;
    
    // Animate side-img
    if (sideImg) {
        sideImg.style.transition = 'top 0.5s ease';
        sideImg.style.top = '120px';
        sideImg.style.clipPath = 'none';
    }
    
    // Create a loading indicator
    mainContent.innerHTML = `
        <div style="width: 100%; padding: 20px; text-align: center;">
            <div class="loading-spinner" style="
                border: 4px solid #f3f3f3;
                border-top: 4px solid #3498db;
                border-radius: 50%;
                width: 50px;
                height: 50px;
                animation: spin 2s linear infinite;
                margin: 0 auto 20px auto;
            "></div>
            <p>Loading Users Management...</p>
        </div>
        
        <style>
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        </style>
    `;
    
    // Load users management HTML via AJAX
    fetch('users_management.php')
        .then(response => response.text())
        .then(html => {
            // Replace the main content with the users management HTML
            mainContent.innerHTML = html;
            
            // Now load your external JavaScript file
            const script = document.createElement('script');
            script.src = 'users_management.js';
            script.onload = function() {
                console.log('Users management JS loaded');
                
                // IMPORTANT: Wait for the DOM to be ready
                setTimeout(() => {
                    // Now that the users_management.js is loaded AND the HTML is inserted,
                    // we can initialize it
                    if (typeof loadUsersData === 'function') {
                        console.log('Calling loadUsersData()');
                        loadUsersData();
                    } else {
                        console.error('loadUsersData function not found!');
                    }
                }, 100);
            };
            
            // Append the script to head
            document.head.appendChild(script);
            
            // Update menu active states
            document.querySelectorAll('.side-item').forEach(item => {
                item.classList.remove('active');
            });
            document.querySelector('.side-item[onclick*="showUsersView"]').classList.add('active');
        })
        .catch(error => {
            console.error('Error loading users view:', error);
            mainContent.innerHTML = `
                <div class="error" style="color: red; padding: 20px;">
                    Error loading users management. Please try again.
                </div>
                <button onclick="location.reload()">Back to Dashboard</button>
            `;
            
        });
  const links = document.querySelectorAll('link[rel="stylesheet"]');
    links.forEach(link => {
        if (link.href.includes('style.css')) {
            link.href = link.href.replace(/\?.*|$/, '?' + new Date().getTime());
        }
    });
}
// In your users_management.js file, update loadUsersData function:
function loadUsersData() {
    fetch('get_users.php')
        .then(response => response.json())
        .then(data => {
            console.log('Data received:', data);
            
            // Safely update elements - check if they exist first
            const totalUsersEl = document.getElementById('total-users-count');
            const totalAdminsEl = document.getElementById('total-admins-count');
            const tableContentEl = document.getElementById('users-table-content');
            
            if (totalUsersEl && totalAdminsEl) {
                totalUsersEl.textContent = data.total_users || 0;
                totalAdminsEl.textContent = data.total_admins || 0;
            } else {
                console.warn('Stats elements not found');
            }
            
            if (tableContentEl && data.users) {
                // Render the table
                renderUsersTable(data.users);
            } else if (tableContentEl) {
                tableContentEl.innerHTML = '<div class="error">No users data received</div>';
            } else {
                console.warn('Table content element not found');
            }
        })
        .catch(error => {
            console.error('Error loading users data:', error);
            const tableContentEl = document.getElementById('users-table-content');
            if (tableContentEl) {
                tableContentEl.innerHTML = '<div class="error">Error loading users. Please try again.</div>';
            }
        });
}
// Inject all the user management JavaScript functions
function injectUsersManagementJS() {
    // Create a script element with all your functions
    const script = document.createElement('script');
    script.textContent = `
        // User Management JavaScript Functions
        let currentEditCell = null;
        let originalValue = null;
        let originalHTML = null;
        
        // Load users data via AJAX
        function loadUsersData() {
            fetch('get_users.php')
                .then(response => response.json())
                .then(data => {
                    // Update stats
                    document.getElementById('total-users-count').textContent = data.total_users;
                    document.getElementById('total-admins-count').textContent = data.total_admins;
                    
                    // Render users table
                    renderUsersTable(data.users);
                })
                .catch(error => {
                    console.error('Error loading users data:', error);
                    document.getElementById('users-table-content').innerHTML = 
                        '<div class="error">Error loading users. Please try again.</div>';
                });
        }
        
        // Function to render users table
        function renderUsersTable(users) {
            let html = '';
            
            if (users.length > 0) {
                html = \`
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
                \`;
                
                users.forEach(user => {
                    const avatarLetter = user.username.charAt(0).toUpperCase();
                    const joinedDate = new Date(user.created_at).toLocaleDateString('en-US', {
                        month: 'short',
                        day: 'numeric',
                        year: 'numeric'
                    });
                    const lastOrder = user.last_order_date 
                        ? new Date(user.last_order_date).toLocaleDateString('en-US', {
                            month: 'short',
                            day: 'numeric',
                            year: 'numeric'
                        })
                        : 'No orders';
                    
                    html += \`
                        <tr id="userRow-\${user.id}" data-user-id="\${user.id}">
                            <td class="user-id">#\${String(user.id).padStart(4, '0')}</td>
                            <td class="editable-cell username-cell"
                                data-field="username"
                                data-value="\${escapeHtml(user.username)}"
                                onclick="startEdit(this, \${user.id})">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div class="avatar">\${avatarLetter}</div>
                                    <div>
                                        <strong>\${escapeHtml(user.username)}</strong>
                                        <div style="font-size: 0.875rem; color: #666;">ID: \${user.id}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="editable-cell email-cell"
                                data-field="email"
                                data-value="\${escapeHtml(user.email)}"
                                onclick="startEdit(this, \${user.id})">
                                \${escapeHtml(user.email)}
                            </td>
                            <td class="editable-cell user-type-cell"
                                data-field="user_type"
                                data-value="\${user.user_type}"
                                onclick="startEdit(this, \${user.id})">
                                <span class="role-badge role-\${user.user_type.toLowerCase()}">
                                    \${user.user_type.charAt(0).toUpperCase() + user.user_type.slice(1)}
                                </span>
                            </td>
                            <td class="editable-cell auth-provider-cell"
                                data-field="auth_provider"
                                data-value="\${user.auth_provider}"
                                onclick="startEdit(this, \${user.id})">
                                \${user.auth_provider === 'google' 
                                    ? '<span style="color: #DB4437;"><i class="fab fa-google"></i> Google</span>'
                                    : '<span style="color: #4285F4;"><i class="fas fa-envelope"></i> Email</span>'}
                            </td>
                            <td><span style="font-weight: 600; color: #2d3748;">\${user.total_orders}</span></td>
                            <td>\${lastOrder}</td>
                            <td>\${joinedDate}</td>
                            <td class="actions-cell">
                                \${user.user_type !== 'admin' 
                                    ? \`<button class="btn btn-danger btn-sm" onclick="deleteUser(\${user.id})" title="Delete User">
                                        <i class="fas fa-trash"></i>
                                       </button>\`
                                    : '<span class="text-muted" style="font-size: 0.875rem;">Admin</span>'}
                            </td>
                        </tr>
                    \`;
                });
                
                html += \`
                        </tbody>
                    </table>
                \`;
            } else {
                html = \`
                    <div class="no-data">
                        <i class="fas fa-user-slash"></i>
                        <h3>No Users Found</h3>
                        <p>There are no registered users in the system yet.</p>
                        <button class="btn btn-primary" onclick="addNewUserInline()" style="margin-top: 15px;">
                            <i class="fas fa-user-plus"></i> Add First User
                        </button>
                    </div>
                \`;
            }
            
            document.getElementById('users-table-content').innerHTML = html;
            
            // Add double-click support for editing
            setTimeout(() => {
                document.querySelectorAll('.editable-cell').forEach(cell => {
                    cell.addEventListener('dblclick', function(e) {
                        if (!this.classList.contains('editing')) {
                            const userId = this.closest('tr').dataset.userId;
                            startEdit(this, userId);
                        }
                    });
                });
            }, 100);
        }
        
        // Start editing a cell
        function startEdit(cell, userId) {
            // If already editing, do nothing
            if (cell.classList.contains('editing')){
                return;
            }
            
            // Save current editing state
            if (currentEditCell) {
                cancelEdit(currentEditCell);
            }
            
            currentEditCell = cell;
            originalValue = cell.dataset.value;
            originalHTML = cell.innerHTML;
            
            const field = cell.dataset.field;
            
            // Add edit mode to row
            const row = cell.closest('tr');
            row.classList.add('row-edit-mode');
            
            // Mark cell as editing
            cell.classList.add('editing');
            
            // Clear cell content
            cell.innerHTML = '';
            
            // Create edit interface based on field type
            if (field === 'user_type' || field === 'auth_provider') {
                createSelectEditor(cell, field, originalValue, userId);
            } else {
                createTextEditor(cell, field, originalValue, userId);
            }
        }
        
        // Create text input editor
        function createTextEditor(cell, field, value, userId) {
            const template = document.getElementById('editTemplate').content.cloneNode(true);
            const input = template.querySelector('.edit-input');
            const saveBtn = template.querySelector('.save-btn');
            const editWrapper = template.querySelector('.edit-wrapper');
            
            // Configure input
            input.value = value;
            input.dataset.field = field;
            input.dataset.userId = userId;
            
            // Configure save button
            saveBtn.dataset.cell = cell.className;
            saveBtn.dataset.userId = userId;
            saveBtn.dataset.originalHtml = originalHTML;
            
            // Focus input
            cell.appendChild(template);
            input.focus();
            input.select();
        }
        
        // Create select dropdown editor
        function createSelectEditor(cell, field, value, userId) {
            const template = document.getElementById('selectTemplate').content.cloneNode(true);
            const select = template.querySelector('.edit-select');
            const saveBtn = template.querySelector('.save-btn');
            
            // Configure select
            select.dataset.field = field;
            select.dataset.userId = userId;
            
            // Add options based on field
            if (field === 'user_type') {
                select.innerHTML = \`
                    <option value="customer" \${value === 'customer' ? 'selected' : ''}>Customer</option>
                    <option value="admin" \${value === 'admin' ? 'selected' : ''}>Admin</option>
                \`;
            } else if (field === 'auth_provider') {
                select.innerHTML = \`
                    <option value="email" \${value === 'email' ? 'selected' : ''}>Email</option>
                    <option value="google" \${value === 'google' ? 'selected' : ''}>Google</option>
                \`;
            }
            
            // Configure save button
            saveBtn.dataset.cell = cell.className;
            saveBtn.dataset.userId = userId;
            saveBtn.dataset.originalHtml = originalHTML;
            
            cell.appendChild(template);
            select.focus();
        }
        
        // Cancel edit
        function cancelEdit(button) {
            if (!button) return;
            
            const wrapper = button.closest('.edit-wrapper');
            if (!wrapper) return;
            
            const cell = wrapper.closest('.editable-cell');
            if (!cell) return;
            
            // Restore original HTML
            const saveBtn = wrapper.querySelector('.save-btn');
            const originalHtml = saveBtn ? saveBtn.dataset.originalHtml : originalHTML;
            
            if (originalHtml) {
                cell.innerHTML = originalHtml;
            } else {
                restoreCellFromOriginalValue(cell);
            }
            
            // Exit edit mode
            exitEditMode(cell);
        }
        
        // Restore cell from original value
        function restoreCellFromOriginalValue(cell) {
            const field = cell.dataset.field;
            const value = cell.dataset.value;
            const userId = cell.closest('tr').dataset.userId;
            
            switch(field) {
                case 'username':
                    const avatarLetter = value ? value.charAt(0).toUpperCase() : '?';
                    cell.innerHTML = \`
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div class="avatar">\${avatarLetter}</div>
                            <div>
                                <strong>\${escapeHtml(value || '')}</strong>
                                <div style="font-size: 0.875rem; color: #666;">
                                    ID: \${userId}
                                </div>
                            </div>
                        </div>
                    \`;
                    break;
                    
                case 'email':
                    cell.innerHTML = escapeHtml(value || '');
                    break;
                    
                case 'user_type':
                    cell.innerHTML = \`
                        <span class="role-badge role-\${value || 'customer'}">
                            \${(value || 'customer').charAt(0).toUpperCase() + (value || 'customer').slice(1)}
                        </span>
                    \`;
                    break;
                    
                case 'auth_provider':
                    if (value === 'google') {
                        cell.innerHTML = \`
                            <span style="color: #DB4437;">
                                <i class="fab fa-google"></i> Google
                            </span>
                        \`;
                    } else {
                        cell.innerHTML = \`
                            <span style="color: #4285F4;">
                                <i class="fas fa-envelope"></i> Email
                            </span>
                        \`;
                    }
                    break;
            }
        }
        
        // Exit edit mode
        function exitEditMode(cell) {
            if (!cell) return;
            
            const row = cell.closest('tr');
            if (row) {
                row.classList.remove('row-edit-mode');
            }
            
            cell.classList.remove('editing');
            currentEditCell = null;
            originalValue = null;
            originalHTML = null;
        }
        
        // Save text edit
        function saveEdit(button) {
            const wrapper = button.closest('.edit-wrapper');
            const input = wrapper.querySelector('.edit-input');
            const value = input.value.trim();
            const field = input.dataset.field;
            const userId = input.dataset.userId;
            
            if (!value) {
                showToast('Error', 'Value cannot be empty', 'error');
                input.focus();
                return;
            }
            
            button.disabled = true;
            button.innerHTML = '<div class="spinner"></div>';
            
            updateUser(userId, field, value, wrapper);
        }
        
        // Save select edit
        function saveSelectEdit(button) {
            const wrapper = button.closest('.edit-wrapper');
            const select = wrapper.querySelector('.edit-select');
            const value = select.value;
            const field = select.dataset.field;
            const userId = select.dataset.userId;
            
            button.disabled = true;
            button.innerHTML = '<div class="spinner"></div>';
            
            updateUser(userId, field, value, wrapper);
        }
        
        // Update user via AJAX
        function updateUser(userId, field, value, wrapper) {
            const formData = new FormData();
            formData.append('id', userId);
            formData.append('field', field);
            formData.append('value', value);
            
            fetch('update_user.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Success', data.message || 'User updated successfully', 'success');
                    setTimeout(() => {
                        loadUsersData();
                    }, 1000);
                } else {
                    showToast('Error', data.message || 'Update failed', 'error');
                    const saveBtn = wrapper.querySelector('.save-btn');
                    if (saveBtn) {
                        saveBtn.disabled = false;
                        saveBtn.innerHTML = '<span class="save-text">Save</span>';
                    }
                }
            })
            .catch(error => {
                showToast('Error', 'Network error. Please try again.', 'error');
                const saveBtn = wrapper.querySelector('.save-btn');
                if (saveBtn) {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '<span class="save-text">Save</span>';
                }
            });
        }
        
        // Handle Enter key
        function handleEnterKey(event, input) {
            if (event.key === 'Enter') {
                event.preventDefault();
                const saveBtn = input.closest('.edit-wrapper').querySelector('.save-btn');
                if (saveBtn) {
                    saveBtn.click();
                }
            }
        }
        
        // Handle select change
        function handleSelectChange(select) {
            // Optional: Add any immediate feedback
        }
        
        // Delete user
        function deleteUser(userId) {
            if (confirm(\`Are you sure you want to delete user #\${userId}?\`)) {
                fetch(\`delete_user.php?id=\${userId}\`, { method: 'DELETE' })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showToast('Success', 'User deleted successfully', 'success');
                            setTimeout(() => {
                                loadUsersData();
                            }, 1000);
                        } else {
                            showToast('Error', data.message || 'Deletion failed', 'error');
                        }
                    })
                    .catch(error => {
                        showToast('Error', 'Network error. Please try again.', 'error');
                    });
            }
        }
        
        // Refresh users table
        function refreshUsersTable() {
            loadUsersData();
            showToast('Info', 'Refreshing users data...', 'info');
        }
        
        // Add new user inline
        function addNewUserInline() {
            // You can implement this based on your needs
            alert('Add new user functionality would go here');
        }
        
        // Export to CSV
        function exportToCSV() {
            alert('Export to CSV functionality would go here');
        }
        
        // Show toast notification
        function showToast(title, message, type = 'info') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = \`toast \${type}\`;
            toast.innerHTML = \`
                <div class="toast-icon">
                    \${type === 'success' ? '✓' : type === 'error' ? '✗' : 'i'}
                </div>
                <div class="toast-content">
                    <div class="toast-title">\${title}</div>
                    <div class="toast-message">\${message}</div>
                </div>
            \`;
            
            container.appendChild(toast);
            
            setTimeout(() => {
                toast.style.animation = 'slideIn 0.3s ease reverse forwards';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
        
        // Escape HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    `;
    
    document.head.appendChild(script);
}

function showProductsView() {
    const mainContent = document.querySelector('.content.font-ui');
    const sideImg = document.querySelector('.side-img');
    
    if (!mainContent) return;
    
    // Animate side-img
    if (sideImg) {
        sideImg.style.transition = 'top 0.5s ease';
        sideImg.style.top = '190px';
        sideImg.style.clipPath = 'none';
    }
    
    // Create iframe for products management
    mainContent.innerHTML = `
        <div style="width: 100%; position: relative;">
            <!-- Iframe for products -->
            <iframe 
                src="index_products.php" 
                style="width: 100%; height: calc(100vh - 150px); border: none; border-radius: 10px; margin-top: 60px;"
                title="Products Management"
            ></iframe>
        </div>
    `;
    
    // FORCE RELOAD CSS FOR THE IFRAME
    setTimeout(() => {
        const iframe = document.querySelector('iframe');
        if (iframe && iframe.contentWindow) {
            // Try to reload CSS inside the iframe
            try {
                const iframeLinks = iframe.contentWindow.document.querySelectorAll('link[rel="stylesheet"]');
                iframeLinks.forEach(link => {
                    if (link.href.includes('style.css')) {
                        link.href = link.href.replace(/\?.*|$/, '?' + new Date().getTime());
                    }
                });
            } catch (e) {
                console.log('Cannot access iframe CSS due to CORS');
            }
        }
        
        // Also reload main page CSS
        const links = document.querySelectorAll('link[rel="stylesheet"]');
        links.forEach(link => {
            if (link.href.includes('style.css')) {
                link.href = link.href.replace(/\?.*|$/, '?' + new Date().getTime());
            }
        });
    }, 500);
    
    // Update menu active states
    document.querySelectorAll('.side-item').forEach(item => {
        item.classList.remove('active');
    });
    document.querySelector('.side-item[onclick*="showProductsView"]').classList.add('active');
    
    // Make iframe adjust to content height
    const iframe = document.querySelector('iframe');
    iframe.onload = function() {
        setTimeout(() => {
            try {
                iframe.style.height = iframe.contentWindow.document.body.scrollHeight + 'px';
            } catch (e) {
                // If CORS blocks access, set a default height
                iframe.style.height = 'calc(100vh - 150px)';
            }
        }, 500);
    };
}


// Also reset the side-img when clicking on Dashboard
function resetSideImg() {
    const sideImg = document.querySelector('.side-img');
    if (sideImg) {
        sideImg.style.transition = 'top 0.5s ease';
        sideImg.style.top = '58px'; // Reset to original position
        sideImg.style.clipPath = 'inset(10% 0 0 0)'; // Add this line
    }
}

// Make functions globally available
window.showUsersView = showUsersView;
window.showProductsView = showProductsView;
window.resetSideImg = resetSideImg;

// Add reset function to dashboard reload
document.addEventListener('DOMContentLoaded', function() {
    // Reset side-img position when page loads
    resetSideImg();
    
    // Add click event to dashboard menu to reset position
    const dashboardMenu = document.querySelector('.side-item[onclick*="location.reload()"]');
    if (dashboardMenu) {
        dashboardMenu.addEventListener('click', function() {
            resetSideImg();
        });
    }
});

// CSS animation
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    /* Side-img animation styles */
    .side-img {
        transition: top 0.5s ease !important;
    }
    
    .side-img.users-view-active {
        top: 120px !important;
    }
    
    .side-img.products-view-active {
        top: 58px !important;
    }
    
    .side-img.dashboard-view-active {
        top: 58px !important;
    }
`;
document.head.appendChild(style);
</script>

<script src="dashboard.js"></script>
</body>
</html>
