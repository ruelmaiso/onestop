-- OneStop Booking System Database Schema
-- Drop existing tables if they exist
DROP TABLE IF EXISTS activity_logs;
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS services;
DROP TABLE IF EXISTS companies;
DROP TABLE IF EXISTS users;

-- Users table with role-based access
CREATE TABLE users(
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user','company','admin') NOT NULL DEFAULT 'user',
    status TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Companies table
CREATE TABLE companies(
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    info TEXT,
    address TEXT,
    phone VARCHAR(20),
    approved TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Services table
CREATE TABLE services(
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    capacity INT DEFAULT 1,
    available_from DATE,
    available_to DATE,
    location VARCHAR(200),
    image_url VARCHAR(500),
    approved TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY(company_id) REFERENCES companies(id) ON DELETE CASCADE
);

-- Bookings table
CREATE TABLE bookings(
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    service_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    pax INT DEFAULT 1,
    total_price DECIMAL(10,2),
    status ENUM('pending','approved','declined','cancelled') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(service_id) REFERENCES services(id) ON DELETE CASCADE
);

-- Activity logs table for auditing
CREATE TABLE activity_logs(
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    role VARCHAR(20),
    action VARCHAR(255) NOT NULL,
    meta JSON NULL,
    ip VARCHAR(45),
    ua TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Insert default admin user
INSERT INTO users (name, email, password, role, status) VALUES 
('Admin', 'admin@onestop.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1);

-- Insert sample company
INSERT INTO users (name, email, password, role, status) VALUES 
('Sample Company', 'company@onestop.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'company', 1);

INSERT INTO companies (user_id, name, info, address, phone, approved) VALUES 
(2, 'Sample Company Ltd', 'We provide excellent services', '123 Main St, City', '123-456-7890', 1);

-- Insert sample services
INSERT INTO services (company_id, title, description, price, capacity, available_from, available_to, location, approved) VALUES 
(1, 'Hotel Room - Standard', 'Comfortable standard room with all amenities', 99.99, 2, '2024-01-01', '2024-12-31', 'Downtown Area', 1),
(1, 'Hotel Room - Deluxe', 'Luxurious deluxe room with premium amenities', 199.99, 4, '2024-01-01', '2024-12-31', 'Downtown Area', 1),
(1, 'Conference Hall', 'Large conference hall for business meetings', 299.99, 50, '2024-01-01', '2024-12-31', 'Business District', 1);