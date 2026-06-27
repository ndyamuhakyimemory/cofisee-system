-- COFISEE Microfinance System Database Schema

CREATE DATABASE IF NOT EXISTS cofisee_db;
USE cofisee_db;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  role VARCHAR(20) DEFAULT 'staff',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_email (email)
);

-- Members Table
CREATE TABLE IF NOT EXISTS members (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  national_id VARCHAR(50) UNIQUE NOT NULL,
  email VARCHAR(100),
  address VARCHAR(255),
  status VARCHAR(20) DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_status (status),
  INDEX idx_created_at (created_at)
);

-- Loans Table
CREATE TABLE IF NOT EXISTS loans (
  id INT AUTO_INCREMENT PRIMARY KEY,
  member_id INT NOT NULL,
  amount DECIMAL(12, 2) NOT NULL,
  interest_rate DECIMAL(5, 2) NOT NULL,
  status VARCHAR(20) DEFAULT 'pending',
  disbursement_date DATE,
  due_date DATE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
  INDEX idx_member_id (member_id),
  INDEX idx_status (status),
  INDEX idx_created_at (created_at)
);

-- Repayments Table
CREATE TABLE IF NOT EXISTS repayments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  loan_id INT NOT NULL,
  member_id INT NOT NULL,
  amount DECIMAL(12, 2) NOT NULL,
  repayment_date DATE NOT NULL,
  notes VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE,
  FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
  INDEX idx_loan_id (loan_id),
  INDEX idx_member_id (member_id),
  INDEX idx_repayment_date (repayment_date)
);

-- Savings Table
CREATE TABLE IF NOT EXISTS savings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  member_id INT NOT NULL,
  amount DECIMAL(12, 2) NOT NULL,
  transaction_type VARCHAR(20) DEFAULT 'deposit',
  transaction_date DATE NOT NULL,
  notes VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
  INDEX idx_member_id (member_id),
  INDEX idx_transaction_date (transaction_date)
);

-- Audit Log Table
CREATE TABLE IF NOT EXISTS audit_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  action VARCHAR(100),
  table_name VARCHAR(50),
  record_id INT,
  old_values JSON,
  new_values JSON,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user_id (user_id),
  INDEX idx_table_name (table_name),
  INDEX idx_created_at (created_at)
);

-- Sample Users (optional - remove or modify for production)
INSERT INTO users (name, email, password, role) VALUES 
('Admin User', 'admin@cofisee.com', '$2y$10$YIjlrVyUHx.kRpI8Hl3IguGVc9sMmXs8S5D.8KJ2J8vJ8vJ8vJ8vJ', 'admin'),
('Staff User', 'staff@cofisee.com', '$2y$10$YIjlrVyUHx.kRpI8Hl3IguGVc9sMmXs8S5D.8KJ2J8vJ8vJ8vJ8vJ', 'staff')
ON DUPLICATE KEY UPDATE email=VALUES(email);

-- Notes:
-- Default passwords (change in production):
-- admin@cofisee.com: password123
-- staff@cofisee.com: password123