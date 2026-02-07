-- Create database
CREATE DATABASE IF NOT EXISTS sport_shop;
USE sport_shop;

-- 1. Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255),
    auth_provider ENUM('email', 'google') DEFAULT 'email',
    google_id VARCHAR(100) UNIQUE,
    user_type ENUM('customer', 'admin') DEFAULT 'customer',
    verification_token VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Colors table
CREATE TABLE colors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    color_name VARCHAR(50) NOT NULL UNIQUE,
    color_code VARCHAR(7),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. Sizes table
CREATE TABLE sizes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    size_name VARCHAR(20) NOT NULL UNIQUE,
    size_type ENUM('clothing', 'shoes', 'unisex') DEFAULT 'unisex',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 4. Products table
CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    base_price DECIMAL(10, 2) NOT NULL,
    product_type ENUM('clothing', 'accessories') NOT NULL,
    sub_type VARCHAR(50) NOT NULL,
    image_url VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 5. Product_variants table
CREATE TABLE product_variants (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    color_id INT,
    size_id INT,
    sku VARCHAR(50) UNIQUE NOT NULL,
    price_adjustment DECIMAL(10, 2) DEFAULT 0,
    stock_quantity INT DEFAULT 0,
    image_url VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (color_id) REFERENCES colors(id) ON DELETE SET NULL,
    FOREIGN KEY (size_id) REFERENCES sizes(id) ON DELETE SET NULL,
    UNIQUE KEY unique_product_variant (product_id, color_id, size_id)
);

-- 6. Favorites table
CREATE TABLE favorites (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_product (user_id, product_id)
);

-- 7. Cart table
CREATE TABLE cart (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 8. Cart items table
CREATE TABLE cart_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cart_id INT NOT NULL,
    variant_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    
    FOREIGN KEY (cart_id) REFERENCES cart(id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE CASCADE,
    UNIQUE KEY unique_cart_variant (cart_id, variant_id)
);

-- 9. Orders table
CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total_amount DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 10. Order items table
CREATE TABLE order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    variant_id INT NOT NULL,
    quantity INT NOT NULL,
    price_at_purchase DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE RESTRICT
);

-- 11. Ratings table
CREATE TABLE ratings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    order_id INT NOT NULL,
    rating TINYINT NOT NULL CHECK (rating >= 0 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_product_rating (user_id, product_id, order_id)
);
--12 Table pour les informations de livraison des commandes avec clé étrangère vers users
CREATE TABLE IF NOT EXISTS orders_info (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    user_id INT NOT NULL,  -- AJOUT: Clé étrangère vers la table users
    customer_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    wilaya VARCHAR(50) NOT NULL,
    commune VARCHAR(50) NOT NULL,
    address TEXT NOT NULL,
    instructions TEXT,
    delivery_type VARCHAR(20) DEFAULT 'standard',
    payment_method VARCHAR(20) DEFAULT 'cash_on_delivery',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE  -- AJOUT: Clé étrangère
);

--13 Table pour l'historique des statuts de commande
CREATE TABLE IF NOT EXISTS order_status_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    user_id INT NOT NULL,  -- AJOUT: Pour savoir qui a changé le statut
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'processing',
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    changed_by VARCHAR(50) DEFAULT 'system',
    notes TEXT,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE  -- AJOUT: Clé étrangère
);

-- Now insert all data in correct order:

-- 1. Insert users
INSERT INTO users (username, email, password, auth_provider, google_id, user_type, verification_token) VALUES
('john_doe', 'john@example.com', 'hashed_pass1', 'email', NULL, 'customer', 'token123abc'),
('admin_user', 'admin@example.com', 'hashed_pass2', 'email', NULL, 'admin', 'admin_token456');

-- 2. Insert colors
INSERT INTO colors (color_name, color_code) VALUES
('Red', '#FF0000'),
('Blue', '#0000FF'),
('Black', '#000000'),
('White', '#FFFFFF'),
('Green', '#00FF00'),
('Yellow', '#FFFF00'),
('Gray', '#808080');

-- 3. Insert sizes (CORRECTED)
INSERT INTO sizes (size_name, size_type) VALUES
('XS', 'clothing'),
('S', 'clothing'),
('M', 'clothing'),
('L', 'clothing'),
('XL', 'clothing'),
('XXL', 'clothing'),
('38', 'shoes'),
('39', 'shoes'),
('40', 'shoes'),
('41', 'shoes'),
('42', 'shoes'),
('43', 'shoes'),
('44', 'shoes'),
('One Size', 'unisex');

-- 4. Insert main products
INSERT INTO products (id, name, description, base_price, product_type, sub_type, image_url, is_active, created_at) VALUES
(1, 'Running Sneakers', 'Lightweight running shoes', 89.99, 'clothing', 'sneakers', 'Running Sneakers.jpg', 1, '2025-12-24 22:08:43'),
(2, 'Sports T-shirt', 'Cotton t-shirt', 24.99, 'clothing', 'shirts', 'Sports T-shirt.jpg', 1, '2025-12-24 22:08:43'),
(3, 'Basketball Uniform', 'Team uniform set', 129.99, 'clothing', 'complete_uniforms', 'Basketball Uniform.jpg', 1, '2025-12-24 22:08:43'),
(4, 'Protein Powder', 'Whey protein supplement', 39.99, 'accessories', 'nutrition', 'Protein Powder.jpg', 1, '2025-12-24 22:08:43'),
(5, 'Football', 'Official size football', 29.99, 'accessories', 'tools', 'Football.jpg', 1, '2025-12-24 22:08:43');

-- 5. Insert product variants (WITH CORRECT SIZE IDs)
INSERT INTO product_variants (product_id, color_id, size_id, sku, price_adjustment, stock_quantity, image_url) VALUES
-- Sneakers (shoe sizes: 38=7, 39=8, 40=9)
(1, 1, 7, 'SNEAK-RED-38', 0, 10, 'sneakers_red.jpg'),
(1, 1, 8, 'SNEAK-RED-39', 0, 8, 'sneakers_red.jpg'),
(1, 1, 9, 'SNEAK-RED-40', 0, 5, 'sneakers_red.jpg'),
(1, 3, 7, 'SNEAK-BLK-38', 5.00, 15, 'sneakers_black.jpg'),
(1, 3, 8, 'SNEAK-BLK-39', 5.00, 12, 'sneakers_black.jpg'),
(1, 4, 9, 'SNEAK-WHT-40', 0, 0, 'sneakers_white.jpg'),

-- T-shirts (clothing sizes: S=2, M=3, L=4)
(2, 1, 2, 'TSHIRT-RED-S', 0, 20, 'tshirt_red.jpg'),
(2, 1, 3, 'TSHIRT-RED-M', 0, 25, 'tshirt_red.jpg'),
(2, 2, 2, 'TSHIRT-BLUE-S', 0, 15, 'tshirt_blue.jpg'),
(2, 2, 3, 'TSHIRT-BLUE-M', 0, 18, 'tshirt_blue.jpg'),
(2, 3, 4, 'TSHIRT-BLACK-L', -2.00, 30, 'tshirt_black.jpg'),

-- Basketball uniforms
(3, 1, 3, 'UNI-RED-M', 0, 5, 'uniform_red.jpg'),
(3, 2, 4, 'UNI-BLUE-L', 0, 3, 'uniform_blue.jpg'),

-- Protein powder (One Size = 14)
(4, NULL, 14, 'PROTEIN-001', 0, 50, 'protein.jpg'),

-- Football (One Size = 14)
(5, 1, 14, 'BALL-RED', 0, 20, 'football_red.jpg'),
(5, 3, 14, 'BALL-BLACK', 3.00, 15, 'football_black.jpg'),
(5, 4, 14, 'BALL-WHITE', 0, 0, 'football_white.jpg');

-- 6. Insert favorites
INSERT INTO favorites (user_id, product_id) VALUES
(1, 1), (1, 4), (2, 2), (2, 5);

-- 7. Create carts
INSERT INTO cart (user_id) VALUES (1), (2);

-- 8. Add items to carts (using valid variant_ids)
INSERT INTO cart_items (cart_id, variant_id, quantity) VALUES
(1, 1, 1),  -- variant_id 1: Red size 38 sneakers
(1, 8, 2),  -- variant_id 8: Red size S t-shirt x2
(2, 15, 1); -- variant_id 15: Protein powder

-- 9. Create orders
INSERT INTO orders (user_id, total_amount, status) VALUES
(1, 114.98, 'delivered'),
(2, 54.98, 'delivered');

-- 10. Create order items
INSERT INTO order_items (order_id, variant_id, quantity, price_at_purchase) VALUES
(1, 1, 1, 89.99),   -- Red size 38 sneakers
(1, 8, 1, 24.99),   -- Red size S t-shirt
(2, 15, 1, 39.99);  -- Protein powder

-- 11. Insert ratings
INSERT INTO ratings (user_id, product_id, order_id, rating, comment) VALUES
(1, 1, 1, 5, 'Great sneakers!'),
(2, 4, 2, 4, 'Good protein quality');