-- ============================================
-- Wrench n Parts - Complete Database Schema
-- ============================================

CREATE DATABASE IF NOT EXISTS wrench_parts_db;
USE wrench_parts_db;

-- ============================================
-- USERS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    role ENUM('customer','shopkeeper','workshop','admin','management') DEFAULT 'customer',
    status ENUM('pending','active','inactive','banned') DEFAULT 'pending',
    profile_image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================
-- CATEGORIES TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL,
    category_image VARCHAR(255) DEFAULT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- SHOPS TABLE (Shopkeeper's shop)
-- ============================================
CREATE TABLE IF NOT EXISTS shops (
    shop_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    shop_name VARCHAR(150) NOT NULL,
    description TEXT,
    location VARCHAR(255),
    contact VARCHAR(20),
    logo VARCHAR(255) DEFAULT NULL,
    status ENUM('active','inactive','pending') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- ============================================
-- PRODUCTS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    shop_id INT NOT NULL,
    category_id INT,
    product_name VARCHAR(200) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    discount_price DECIMAL(10,2) DEFAULT NULL,
    stock INT DEFAULT 0,
    product_image VARCHAR(255) DEFAULT NULL,
    brand VARCHAR(100),
    car_brand VARCHAR(100) DEFAULT NULL,
    car_model VARCHAR(100) DEFAULT NULL,
    compatible_vehicles TEXT,
    status ENUM('available','unavailable','discontinued') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (shop_id) REFERENCES shops(shop_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE SET NULL
);

-- ============================================
-- WORKSHOPS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS workshops (
    workshop_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    workshop_name VARCHAR(150) NOT NULL,
    description TEXT,
    location VARCHAR(255),
    contact VARCHAR(20),
    services TEXT,
    logo VARCHAR(255) DEFAULT NULL,
    rating DECIMAL(3,2) DEFAULT 0.00,
    total_reviews INT DEFAULT 0,
    status ENUM('active','inactive','pending') DEFAULT 'pending',
    opening_time TIME DEFAULT '08:00:00',
    closing_time TIME DEFAULT '18:00:00',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- ============================================
-- ORDERS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    shipping_address TEXT NOT NULL,
    contact_phone VARCHAR(20) NOT NULL,
    payment_method ENUM('cod','card','upi','netbanking') DEFAULT 'cod',
    payment_status ENUM('pending','paid','failed','refunded') DEFAULT 'pending',
    order_status ENUM('pending','confirmed','processing','shipped','delivered','cancelled') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- ============================================
-- ORDER ITEMS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS order_items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
);

-- ============================================
-- APPOINTMENTS TABLE (Workshop Bookings)
-- ============================================
CREATE TABLE IF NOT EXISTS appointments (
    appointment_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    workshop_id INT NOT NULL,
    vehicle_make VARCHAR(100),
    vehicle_model VARCHAR(100),
    vehicle_year INT,
    service_type VARCHAR(200),
    description TEXT,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    status ENUM('pending','approved','in_progress','completed','cancelled') DEFAULT 'pending',
    workshop_notes TEXT,
    estimated_cost DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (workshop_id) REFERENCES workshops(workshop_id) ON DELETE CASCADE
);

-- ============================================
-- CART TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS cart (
    cart_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
);

-- ============================================
-- WISHLIST TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS wishlist (
    wishlist_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
);

-- ============================================
-- CHAT MESSAGES TABLE (User-to-User Chat)
-- ============================================
CREATE TABLE IF NOT EXISTS chat_messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- ============================================
-- CHATBOT LOGS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS chatbot_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    question TEXT NOT NULL,
    response TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
);

-- ============================================
-- REVIEWS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT DEFAULT NULL,
    workshop_id INT DEFAULT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE SET NULL,
    FOREIGN KEY (workshop_id) REFERENCES workshops(workshop_id) ON DELETE SET NULL
);

-- ============================================
-- NOTIFICATIONS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    link VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- ============================================
-- MECHBOT AI KNOWLEDGE BASE (RAG) TABLES
-- Import full seed data from knowledge_base.sql
-- ============================================
CREATE TABLE IF NOT EXISTS kb_articles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    category ENUM('repair_guide','service_interval','torque_spec','general') NOT NULL DEFAULT 'general',
    keywords VARCHAR(255) DEFAULT '',
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS kb_dtc_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(10) NOT NULL UNIQUE,
    `system` VARCHAR(50) NOT NULL,
    description VARCHAR(255) NOT NULL,
    causes TEXT,
    fixes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS kb_faqs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question VARCHAR(255) NOT NULL,
    answer TEXT NOT NULL,
    category VARCHAR(50) DEFAULT 'general',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS kb_problems (
    id INT AUTO_INCREMENT PRIMARY KEY,
    `system` VARCHAR(60) NOT NULL,
    problem VARCHAR(255) NOT NULL,
    symptoms TEXT,
    causes TEXT,
    solution TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (`system`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS chatbot_conversations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    session_id VARCHAR(64) NOT NULL,
    role ENUM('user','assistant') NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (session_id),
    INDEX (user_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS chatbot_state (
    session_id VARCHAR(64) NOT NULL PRIMARY KEY,
    state JSON NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS kb_embeddings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    source_type ENUM('problem','article','dtc','faq') NOT NULL,
    source_id INT NOT NULL,
    label VARCHAR(255) NOT NULL,
    embedding MEDIUMTEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_src (source_type, source_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS system_settings (
    setting_id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================
-- INSERT DEFAULT DATA
-- ============================================

-- Default Admin User (password: admin123)
INSERT IGNORE INTO users (name, email, password, phone, role, status) VALUES
('Administrator', 'admin@wrenchnparts.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '1234567890', 'admin', 'active');

-- Default Management User (password: mgmt123)
INSERT IGNORE INTO users (name, email, password, phone, role, status) VALUES
('Manager', 'manager@wrenchnparts.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '1234567891', 'management', 'active');

-- Default Shopkeeper (password: shop123)
INSERT IGNORE INTO users (name, email, password, phone, role, status) VALUES
('Parts Hub Owner', 'shop@wrenchnparts.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '1234567892', 'shopkeeper', 'active');

-- Default Workshop Owner (password: work123)
INSERT IGNORE INTO users (name, email, password, phone, role, status) VALUES
('AutoFix Workshop', 'workshop@wrenchnparts.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '1234567893', 'workshop', 'active');

-- Default Customer (password: cust123)
INSERT IGNORE INTO users (name, email, password, phone, role, status) VALUES
('John Customer', 'customer@wrenchnparts.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '1234567894', 'customer', 'active');

-- Categories
INSERT IGNORE INTO categories (category_name, description) VALUES
('Engine Parts', 'Pistons, gaskets, cylinder heads, and engine blocks'),
('Brake System', 'Brake pads, discs, calipers, and brake fluid'),
('Electrical', 'Batteries, alternators, starters, and wiring'),
('Suspension', 'Shock absorbers, springs, and control arms'),
('Filters', 'Oil filters, air filters, fuel filters, and cabin filters'),
('Exhaust', 'Mufflers, catalytic converters, and exhaust pipes'),
('Transmission', 'Clutch kits, gears, and transmission fluids'),
('Lighting', 'Headlights, taillights, indicators, and LED bars'),
('Body Parts', 'Bumpers, mirrors, fenders, and doors'),
('Tires & Wheels', 'All tire sizes and alloy wheels');

-- Default Shop
INSERT IGNORE INTO shops (user_id, shop_name, description, location, contact, status) VALUES
(3, 'Wrench n Parts Hub', 'Your one-stop shop for all automobile spare parts', '123 Auto Street, Mechanical District', '1234567892', 'active');

-- Default Workshop
INSERT IGNORE INTO workshops (user_id, workshop_name, description, location, contact, services, status) VALUES
(4, 'AutoFix Workshop', 'Professional automobile repair and maintenance services', '456 Repair Road, Service Zone', '1234567893', 'Engine Repair,Brake Service,AC Service,Wheel Alignment,Oil Change,General Servicing', 'active');

-- Sample Products
INSERT IGNORE INTO products (shop_id, category_id, product_name, description, price, stock, brand, compatible_vehicles, status) VALUES
(1, 1, 'Piston Ring Set', 'High-quality piston ring set for most sedans', 45.99, 50, 'Bosch', 'Toyota, Honda, Nissan', 'available'),
(1, 2, 'Ceramic Brake Pads (Front)', 'Premium ceramic brake pads - front pair', 32.50, 100, 'Brembo', 'Universal Fit', 'available'),
(1, 3, 'Car Battery 12V 60Ah', 'Maintenance-free car battery with 2-year warranty', 89.99, 30, 'Exide', 'Universal Fit', 'available'),
(1, 4, 'Shock Absorber Set', 'Gas-charged shock absorbers - pair', 65.00, 40, 'Monroe', 'Ford, Chevrolet', 'available'),
(1, 5, 'Oil Filter Pack (3pcs)', 'Premium oil filter 3-pack', 18.99, 200, 'Mann', 'Universal Fit', 'available'),
(1, 6, 'Muffler - Universal Fit', 'Stainless steel muffler with reduced noise', 55.00, 25, 'Walker', 'Universal Fit', 'available'),
(1, 7, 'Clutch Kit Complete', 'Full clutch kit with disc, pressure plate, and bearing', 120.00, 15, 'Sachs', 'Honda, Toyota', 'available'),
(1, 8, 'LED Headlight Bulb H7', 'Super bright LED headlight bulb pair', 28.99, 80, 'Philips', 'Universal H7', 'available'),
(1, 9, 'Side Mirror (Left)', 'Replacement side mirror with turn signal', 42.00, 20, 'DIYC', 'Toyota Camry 2018-2023', 'available'),
(1, 10, 'All-Season Tire 205/55R16', 'All-season performance tire', 75.00, 60, 'Michelin', 'Universal 16-inch', 'available');

-- System Settings
INSERT IGNORE INTO system_settings (setting_key, setting_value) VALUES
('site_name', 'Wrench n Parts'),
('site_email', 'info@wrenchnparts.com'),
('site_phone', '+1-800-WRENCH'),
('site_address', '123 Auto Street, Mechanical District'),
('currency', 'USD'),
('tax_rate', '8.5'),
('shipping_fee', '5.99'),
('chatbot_enabled', '1'),
('chatbot_name', 'MechBot'),
('gemini_api_key', ''),
('gemini_model', 'gemini-3.5-flash'),
('maintenance_mode', '0');
