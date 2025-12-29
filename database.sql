-- KDesk Helpdesk System Database Schema
-- Run this SQL to create the database and tables

CREATE DATABASE IF NOT EXISTS kdesk_helpdesk CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE kdesk_helpdesk;

-- Users table (staff members)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('staff', 'admin') DEFAULT 'staff',
    department_id INT NULL,
    avatar_url VARCHAR(500) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    must_change_password BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_department (department_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Departments table
CREATE TABLE departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tickets table
CREATE TABLE tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_number INT NOT NULL UNIQUE,
    subject VARCHAR(500) NOT NULL,
    requester_email VARCHAR(255) NOT NULL,
    requester_name VARCHAR(255) NOT NULL,
    status ENUM('new', 'open', 'pending', 'solved', 'closed') DEFAULT 'new',
    priority ENUM('low', 'normal', 'high') DEFAULT 'normal',
    channel ENUM('email', 'web') DEFAULT 'web',
    assigned_to_id INT NULL,
    department_id INT NULL,
    work_order_id VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_ticket_number (ticket_number),
    INDEX idx_status (status),
    INDEX idx_assigned_to (assigned_to_id),
    INDEX idx_department (department_id),
    INDEX idx_requester_email (requester_email),
    FOREIGN KEY (assigned_to_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Messages table (ticket conversation)
CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    type ENUM('inbound', 'outbound', 'note') NOT NULL,
    from_address VARCHAR(255) NOT NULL,
    to_address VARCHAR(255) NULL,
    subject VARCHAR(500) NULL,
    body_text TEXT NOT NULL,
    body_html TEXT NULL,
    is_internal BOOLEAN DEFAULT FALSE,
    created_by_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ticket (ticket_id),
    INDEX idx_type (type),
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tags table
CREATE TABLE tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ticket tags junction table
CREATE TABLE ticket_tags (
    ticket_id INT NOT NULL,
    tag_id INT NOT NULL,
    PRIMARY KEY (ticket_id, tag_id),
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Settings table
CREATE TABLE settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin user (password: admin123)
INSERT INTO users (email, name, password, role, must_change_password) VALUES 
('admin@kdesk.local', 'Administrator', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', TRUE);

-- Insert default departments
INSERT INTO departments (name, description) VALUES 
('Support', 'Customer support department'),
('Technical', 'Technical support and IT'),
('Sales', 'Sales inquiries and billing');

-- Insert sample tickets
INSERT INTO tickets (ticket_number, subject, requester_email, requester_name, status, priority, channel) VALUES 
(1001, 'Cannot login to my account', 'customer1@example.com', 'John Doe', 'new', 'high', 'email'),
(1002, 'Billing question about invoice', 'customer2@example.com', 'Jane Smith', 'open', 'normal', 'web'),
(1003, 'Feature request: Dark mode', 'customer3@example.com', 'Bob Johnson', 'pending', 'low', 'email');

-- Insert sample messages
INSERT INTO messages (ticket_id, type, from_address, to_address, subject, body_text, is_internal) VALUES 
(1, 'inbound', 'customer1@example.com', 'support@kdesk.local', 'Cannot login to my account', 'Hi, I have been trying to login for the past hour but keep getting an error message. Can you help?', FALSE),
(2, 'inbound', 'customer2@example.com', 'support@kdesk.local', 'Billing question', 'I received invoice #12345 but the amount seems incorrect. Can you review this?', FALSE),
(3, 'inbound', 'customer3@example.com', 'support@kdesk.local', 'Feature request', 'Would love to see a dark mode option in the app!', FALSE);

-- Insert default settings
INSERT INTO settings (setting_key, setting_value) VALUES 
('company_name', 'KDesk'),
('theme', 'light'),
('tickets_per_page', '20');
