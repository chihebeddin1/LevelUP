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
    die(json_encode(['error' => 'Connection failed: ' . $conn->connect_error]));
}

// Set JSON header
header('Content-Type: application/json');

class DashboardAjax {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function getOrderStats($days) {
        $query = "
            SELECT 
                DATE(o.order_date) as order_date,
                COUNT(DISTINCT o.id) as total_orders,
                MAX(daily_count) as high_orders,
                MIN(daily_count) as low_orders,
                AVG(daily_count) as avg_orders
            FROM orders o
            JOIN (
                SELECT 
                    DATE(order_date) as date,
                    COUNT(*) as daily_count
                FROM orders
                WHERE order_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                    AND status NOT IN ('cancelled')
                GROUP BY DATE(order_date)
            ) daily ON DATE(o.order_date) = daily.date
            WHERE o.order_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                AND o.status NOT IN ('cancelled')
            GROUP BY DATE(o.order_date)
            ORDER BY order_date ASC
            LIMIT ?
        ";
        
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            return ['error' => 'Prepare failed: ' . $this->conn->error];
        }
        
        $stmt->bind_param("iii", $days, $days, $days);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = [
                'date' => $row['order_date'],
                'high' => (float)$row['high_orders'],
                'low' => (float)$row['low_orders'],
                'avg' => (float)$row['avg_orders']
            ];
        }
        
        return $data;
    }

    public function getRevenueTrend($days) {
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
        if (!$stmt) {
            return ['error' => 'Prepare failed: ' . $this->conn->error];
        }
        
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

    public function getSalesByProductType($days) {
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
        if (!$stmt) {
            return ['error' => 'Prepare failed: ' . $this->conn->error];
        }
        
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
        
        // Calculate percentages
        foreach ($data as &$item) {
            $item['percentage'] = $total_revenue > 0 ? round(($item['revenue'] / $total_revenue) * 100, 1) : 0;
        }
        
        return [
            'data' => $data,
            'total_revenue' => $total_revenue
        ];
    }
}

// Process request
$action = $_GET['action'] ?? '';
$days = intval($_GET['days'] ?? 30);

$dashboard = new DashboardAjax($conn);

switch($action) {
    case 'order_stats':
        echo json_encode($dashboard->getOrderStats($days));
        break;
    case 'revenue_trend':
        echo json_encode($dashboard->getRevenueTrend($days));
        break;
    case 'sales_by_type':
        echo json_encode($dashboard->getSalesByProductType($days));
        break;
    default:
        echo json_encode(['error' => 'Invalid action. Available: order_stats, revenue_trend, sales_by_type']);
        break;
}

$conn->close();
?>