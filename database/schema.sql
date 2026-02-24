-- Mabini Inventory System Database Schema
-- Created: 2026-02-23

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS mabini_inventory CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mabini_inventory;

-- Drop existing tables if they exist (for clean reinstall)
DROP TABLE IF EXISTS stock_movements;
DROP TABLE IF EXISTS inventory_transactions;
DROP TABLE IF EXISTS items;
DROP TABLE IF EXISTS suppliers;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS offices;
DROP TABLE IF EXISTS users;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    role ENUM('Admin', 'Staff', 'Viewer') DEFAULT 'Staff',
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_role (role),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- Offices/Departments table
CREATE TABLE offices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    office_code VARCHAR(20) UNIQUE NOT NULL,
    office_name VARCHAR(100) NOT NULL,
    head_of_office VARCHAR(100),
    contact_number VARCHAR(20),
    email VARCHAR(100),
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_office_code (office_code),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- Categories table
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category_name (category_name),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- Suppliers table
CREATE TABLE suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_code VARCHAR(20) UNIQUE NOT NULL,
    supplier_name VARCHAR(100) NOT NULL,
    contact_person VARCHAR(100),
    contact_number VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_supplier_code (supplier_code),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- Items/Products table
CREATE TABLE items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_code VARCHAR(50) UNIQUE NOT NULL,
    item_name VARCHAR(200) NOT NULL,
    description TEXT,
    category_id INT,
    unit VARCHAR(20) NOT NULL DEFAULT 'piece',
    unit_cost DECIMAL(10,2) DEFAULT 0.00,
    quantity_on_hand INT DEFAULT 0,
    reorder_level INT DEFAULT 10,
    supplier_id INT,
    location VARCHAR(100),
    expiration_date DATE,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_item_code (item_code),
    INDEX idx_item_name (item_name),
    INDEX idx_category (category_id),
    INDEX idx_status (status),
    INDEX idx_expiration_date (expiration_date)
) ENGINE=InnoDB;

-- Inventory Transactions table
CREATE TABLE inventory_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_type ENUM('Issue', 'Receive', 'Adjustment', 'Return') NOT NULL,
    transaction_date DATE NOT NULL,
    reference_number VARCHAR(50) UNIQUE NOT NULL,
    office_id INT,
    item_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_cost DECIMAL(10,2),
    total_cost DECIMAL(10,2),
    remarks TEXT,
    processed_by INT,
    status ENUM('Pending', 'Approved', 'Completed', 'Cancelled') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (office_id) REFERENCES offices(id) ON DELETE SET NULL,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_transaction_type (transaction_type),
    INDEX idx_transaction_date (transaction_date),
    INDEX idx_reference (reference_number),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- Stock Movements table (detailed log)
CREATE TABLE stock_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    transaction_id INT,
    movement_type ENUM('IN', 'OUT', 'ADJUST') NOT NULL,
    quantity INT NOT NULL,
    balance_after INT NOT NULL,
    reference VARCHAR(100),
    remarks TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
    FOREIGN KEY (transaction_id) REFERENCES inventory_transactions(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_item (item_id),
    INDEX idx_movement_type (movement_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB;

-- Insert default admin user (password: admin123)
INSERT INTO users (username, password, full_name, email, role, status) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin@mabini.gov.ph', 'Admin', 'Active');

-- Insert default categories
INSERT INTO categories (category_name, description, status) VALUES
('Office Supplies', 'General office supplies and materials', 'Active'),
('Writing Instruments', 'Pens, pencils, markers, and related items', 'Active'),
('Fastening & Adhesives', 'Tapes, glues, clips, staplers and fastening materials', 'Active'),
('Cleaning Supplies', 'Cleaning materials and janitorial supplies', 'Active'),
('Electronics', 'Electronic devices and accessories', 'Active'),
('General Supplies', 'Miscellaneous supplies', 'Active');

-- Insert default office
INSERT INTO offices (office_code, office_name, head_of_office, status) VALUES
('ADMIN', 'Administrative Office', 'Municipal Administrator', 'Active'),
('TREASURY', 'Treasury Office', 'Municipal Treasurer', 'Active'),
('ENGINEER', 'Engineering Office', 'Municipal Engineer', 'Active');

-- Create view for inventory status
CREATE OR REPLACE VIEW v_inventory_status AS
SELECT 
    i.id,
    i.item_code,
    i.item_name,
    c.category_name,
    i.unit,
    i.quantity_on_hand,
    i.reorder_level,
    i.unit_cost,
    (i.quantity_on_hand * i.unit_cost) as total_value,
    CASE 
        WHEN i.quantity_on_hand <= i.reorder_level THEN 'Low Stock'
        WHEN i.quantity_on_hand = 0 THEN 'Out of Stock'
        ELSE 'Available'
    END as stock_status,
    i.status,
    i.updated_at
FROM items i
LEFT JOIN categories c ON i.category_id = c.id
WHERE i.status = 'Active';

-- Create view for transaction history
CREATE OR REPLACE VIEW v_transaction_history AS
SELECT 
    t.id,
    t.transaction_type,
    t.transaction_date,
    t.reference_number,
    o.office_name,
    i.item_code,
    i.item_name,
    t.quantity,
    t.unit_cost,
    t.total_cost,
    u.full_name as processed_by,
    t.status,
    t.created_at
FROM inventory_transactions t
LEFT JOIN offices o ON t.office_id = o.id
LEFT JOIN items i ON t.item_id = i.id
LEFT JOIN users u ON t.processed_by = u.id
ORDER BY t.transaction_date DESC, t.created_at DESC;
