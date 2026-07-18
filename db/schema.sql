-- Main StaffEase Pro database schema.
-- This file creates the database and the core tables.
CREATE DATABASE IF NOT EXISTS staff_ease_pro
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE staff_ease_pro;



CREATE TABLE companies (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  type ENUM('hotel', 'hospital', 'clinic', 'elderly_center', 'restaurant', 'other') NOT NULL DEFAULT 'other',
  address TEXT,
  city VARCHAR(80),
  zip_code VARCHAR(10),
  phone VARCHAR(30),
  email VARCHAR(120),
  logo_path VARCHAR(255) NULL,
  signature_ip VARCHAR(45) NULL COMMENT 'IP address allowed for digital signatures',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE departments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  company_id INT NOT NULL,
  name VARCHAR(120) NOT NULL,
  icon VARCHAR(32) NULL,
  color VARCHAR(16) NULL,
  description TEXT,
  head_user_id INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_departments_company
    FOREIGN KEY (company_id) REFERENCES companies(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_departments_head_user
    FOREIGN KEY (head_user_id) REFERENCES users(id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- For existing databases, add the new columns with:
-- ALTER TABLE departments ADD COLUMN icon VARCHAR(32) NULL AFTER name;
-- ALTER TABLE departments ADD COLUMN color VARCHAR(16) NULL AFTER icon;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  department_id INT NULL,
  first_name VARCHAR(80) NOT NULL,
  last_name VARCHAR(80) NOT NULL,
  email VARCHAR(120) NOT NULL UNIQUE,
  phone VARCHAR(30),
  password VARCHAR(255) NOT NULL,
  role ENUM('super_admin', 'admin', 'department_manager', 'employee') NOT NULL DEFAULT 'employee',
  status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_users_department
    FOREIGN KEY (department_id) REFERENCES departments(id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_department_links (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  department_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_user_department (user_id, department_id),
  KEY idx_user_department_user (user_id),
  KEY idx_user_department_department (department_id),
  CONSTRAINT fk_user_department_links_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_user_department_links_department
    FOREIGN KEY (department_id) REFERENCES departments(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_month_hours_plans (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  month_key DATE NOT NULL,
  planned_hours DECIMAL(7,2) NOT NULL DEFAULT 0,
  worked_hours_override DECIMAL(7,2) NULL,
  note VARCHAR(255) NULL,
  updated_by_user_id INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_user_month (user_id, month_key),
  KEY idx_user_month_key (month_key),
  KEY idx_user_month_updated_by (updated_by_user_id),
  CONSTRAINT fk_user_month_hours_plans_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_user_month_hours_plans_updated_by
    FOREIGN KEY (updated_by_user_id) REFERENCES users(id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE shifts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  department_id INT NOT NULL,
  name VARCHAR(120) NOT NULL,
  icon VARCHAR(32) NULL,
  color VARCHAR(16) NULL,
  description TEXT,
  kind ENUM('work', 'rest', 'vacation', 'sick', 'overtime') NOT NULL DEFAULT 'work',
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_shifts_department
    FOREIGN KEY (department_id) REFERENCES departments(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- For existing databases, run:
-- ALTER TABLE shifts ADD COLUMN icon VARCHAR(32) NULL AFTER name;
-- ALTER TABLE shifts ADD COLUMN color VARCHAR(16) NULL AFTER icon;

CREATE TABLE user_shifts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  shift_id INT NOT NULL,
  user_id INT NULL,
  work_date DATE NOT NULL,
  status ENUM('open', 'assigned', 'completed', 'cancelled', 'in_progress') NOT NULL DEFAULT 'assigned',
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_user_shifts_shift
    FOREIGN KEY (shift_id) REFERENCES shifts(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_user_shifts_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE digital_signatures (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  signature_type ENUM('biometric', 'touchscreen') NOT NULL DEFAULT 'touchscreen',
  signature_data LONGTEXT NOT NULL,
  signature_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_digital_signatures_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE documents (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  document_type ENUM('contract', 'medical_certificate', 'id_scan', 'other') NOT NULL DEFAULT 'other',
  file_name VARCHAR(255) NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  status ENUM('valid', 'expired', 'pending') NOT NULL DEFAULT 'pending',
  upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_documents_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE attendances (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  user_shift_id INT NULL,
  digital_signature_id INT NULL,
  work_date DATE NOT NULL,
  check_in_time TIME NULL,
  check_out_time TIME NULL,
  status ENUM('present', 'absent', 'late', 'early_departure') NOT NULL DEFAULT 'present',
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_attendances_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_attendances_user_shift
    FOREIGN KEY (user_shift_id) REFERENCES user_shifts(id)
    ON DELETE SET NULL,
  CONSTRAINT fk_attendances_signature
    FOREIGN KEY (digital_signature_id) REFERENCES digital_signatures(id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  recipient_id INT NULL,
  type ENUM('shift_coverage', 'leave', 'permission', 'document_signature', 'notification') NOT NULL,
  title VARCHAR(255) NULL,
  message TEXT NOT NULL,
  status ENUM('pending', 'approved', 'rejected', 'cancelled', 'read', 'unread') NOT NULL DEFAULT 'pending',
  shift_id INT NULL,
  document_id INT NULL,
  url_link VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_requests_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_requests_recipient
    FOREIGN KEY (recipient_id) REFERENCES users(id)
    ON DELETE SET NULL,
  CONSTRAINT fk_requests_shift
    FOREIGN KEY (shift_id) REFERENCES shifts(id)
    ON DELETE SET NULL,
  CONSTRAINT fk_requests_document
    FOREIGN KEY (document_id) REFERENCES documents(id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

