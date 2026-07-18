-- ============================================================
-- Cup of Jude's Machinery - Database Schema
-- E-Commerce System for Heavy Machinery
-- ============================================================

CREATE DATABASE IF NOT EXISTS cup_of_judes_machinery;
USE cup_of_judes_machinery;

-- ============================================================
-- USERS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    address TEXT,
    contact_number VARCHAR(20),
    role ENUM('admin', 'buyer') NOT NULL DEFAULT 'buyer',
    is_verified TINYINT(1) NOT NULL DEFAULT 0,
    verification_token VARCHAR(255) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- CATEGORIES TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- PRODUCTS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    stock_quantity INT NOT NULL DEFAULT 0,
    image VARCHAR(255) DEFAULT 'default.jpg',
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- ORDERS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'paid', 'shipped', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    shipping_address TEXT NOT NULL,
    payment_method VARCHAR(50) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- ORDER ITEMS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- CART TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    added_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- AUDIT LOG TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    action VARCHAR(255) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- SEED DATA
-- ============================================================

-- Default Admin User (password: admin123)
INSERT INTO users (full_name, email, password, role, is_verified, created_at) VALUES
('System Administrator', 'admin@cupofjudes.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1, NOW());

-- Categories
INSERT INTO categories (name, description) VALUES
('Precision Equipment', 'High-precision industrial drilling and boring machinery for specialized operations.'),
('Heavy Equipment', 'Large-scale construction and earthmoving machinery for major projects.'),
('Compact Tools', 'Portable and compact industrial power tools for versatile applications.'),
('Demolition Equipment', 'Heavy-duty demolition and breaking machinery for construction and deconstruction.');

-- Products
INSERT INTO products (category_id, name, description, price, stock_quantity, image, status) VALUES
(1, 'Caterpillar MD6250 Drill Rig', 'The Caterpillar MD6250 is our flagship precision drilling machine, engineered for accuracy and power. Featuring advanced hydraulic systems and laser-guided alignment, this heavy-duty equipment delivers unmatched performance in demanding industrial environments. Built with aerospace-grade materials for maximum durability and reliability.', 4750.00, 25, 'caterpillar_md6250.jpg', 'active'),
(2, 'Komatsu PC390LC Excavator', 'The Komatsu PC390LC is a powerhouse excavator designed for large-scale earthmoving operations. With its reinforced steel boom and high-capacity hydraulic system, it handles the toughest terrain with ease. Features a spacious operator cabin with climate control and advanced safety systems for maximum productivity.', 1700.00, 40, 'komatsu_pc390lc.jpg', 'active'),
(3, 'Ingersoll Rand 2235TiMAX Driver', 'The Ingersoll Rand 2235TiMAX compact pneumatic impact driver combines portability with industrial-grade power. Perfect for assembly lines, maintenance operations, and field work. Ergonomic design reduces operator fatigue while delivering consistent torque output across extended operating periods.', 500.00, 100, 'ingersoll_rand_2235timax.jpg', 'active'),
(4, 'Caterpillar H115Es Breaker', 'The Caterpillar H115Es demolition breaker is built for serious deconstruction work. Featuring a high-frequency impact mechanism and vibration-dampening technology, it tears through concrete and reinforced structures efficiently. Includes multiple chisel attachments for versatile demolition applications.', 2000.00, 30, 'caterpillar_h115es.jpg', 'active');
