-- Database: coupon_panel
CREATE DATABASE coupon_panel;
USE coupon_panel;

-- Tabel users untuk login admin
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel coupons untuk menyimpan data kupon
CREATE TABLE coupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    discount_type ENUM('percentage', 'fixed') NOT NULL,
    discount_value DECIMAL(10,2) NOT NULL,
    min_order DECIMAL(10,2) DEFAULT 0,
    max_usage INT DEFAULT 1,
    current_usage INT DEFAULT 0,
    status ENUM('pending', 'approved', 'rejected', 'expired') DEFAULT 'pending',
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    approved_by INT NULL,
    approved_at TIMESTAMP NULL,
    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (approved_by) REFERENCES users(id)
);

-- Tabel coupon_usage untuk tracking penggunaan kupon
CREATE TABLE coupon_usage (
    id INT AUTO_INCREMENT PRIMARY KEY,
    coupon_id INT NOT NULL,
    user_email VARCHAR(100),
    order_amount DECIMAL(10,2),
    discount_amount DECIMAL(10,2),
    used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (coupon_id) REFERENCES coupons(id)
);

-- Insert user admin default
INSERT INTO users (username, password, email) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@example.com');
-- Password: password

-- Insert sample data
INSERT INTO coupons (code, title, description, discount_type, discount_value, min_order, max_usage, status, start_date, end_date, created_by) VALUES
('WELCOME10', 'Welcome Discount', 'Diskon 10% untuk pelanggan baru', 'percentage', 10.00, 50000, 100, 'approved', '2024-01-01', '2024-12-31', 1),
('FLASH50', 'Flash Sale', 'Diskon 50rb untuk pembelian min 200rb', 'fixed', 50000.00, 200000, 50, 'pending', '2024-08-01', '2024-08-31', 1),
('EXPIRED20', 'Expired Coupon', 'Kupon yang sudah hangus', 'percentage', 20.00, 100000, 10, 'expired', '2024-01-01', '2024-07-31', 1);
