-- Schéma principal de la base de données StaffEase Pro.
-- Ce fichier crée la base, les tables et quelques données de démonstration.
CREATE DATABASE IF NOT EXISTS staff_ease_pro
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE staff_ease_pro;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS requests;
DROP TABLE IF EXISTS attendances;
DROP TABLE IF EXISTS user_shifts;
DROP TABLE IF EXISTS shifts;
DROP TABLE IF EXISTS documents;
DROP TABLE IF EXISTS digital_signatures;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS departments;
DROP TABLE IF EXISTS companies;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE companies (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  type ENUM('hotel', 'hospital', 'clinic', 'elderly_center', 'restaurant', 'other') NOT NULL DEFAULT 'other',
  address TEXT,
  city VARCHAR(80),
  province VARCHAR(20),
  zip_code VARCHAR(10),
  phone VARCHAR(30),
  email VARCHAR(120),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE departments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  company_id INT NOT NULL,
  name VARCHAR(120) NOT NULL,
  description TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_departments_company
    FOREIGN KEY (company_id) REFERENCES companies(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE shifts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  department_id INT NOT NULL,
  name VARCHAR(120) NOT NULL,
  description TEXT,
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_shifts_department
    FOREIGN KEY (department_id) REFERENCES departments(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_shifts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  shift_id INT NOT NULL,
  user_id INT NOT NULL,
  work_date DATE NOT NULL,
  status ENUM('assigned', 'completed', 'cancelled', 'in_progress') NOT NULL DEFAULT 'assigned',
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

INSERT INTO companies (name, type, address, city, province, zip_code, phone, email)
VALUES ('Azienda Test', 'hotel', 'Via Esempio 123', 'Roma', 'RM', '00100', '0612345678', 'info@aziendatest.it');

INSERT INTO departments (company_id, name, description)
VALUES (1, 'Reception', 'Dipartimento reception');

INSERT INTO users (department_id, first_name, last_name, email, phone, password, role, status)
VALUES
  (1, 'Marco', 'Pinna', 'admin@aziendatest.it', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin', 'active'),
  (1, 'Giovanni', 'Rossi', 'admin2@aziendatest.it', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active'),
  (1, 'Luca', 'Bianchi', 'employee@aziendatest.it', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee', 'active');

INSERT INTO shifts (department_id, name, description, start_time, end_time)
VALUES
  (1, 'Mattina', 'Turno mattutino', '08:00:00', '14:00:00'),
  (1, 'Pomeriggio', 'Turno pomeridiano', '14:00:00', '20:00:00'),
  (1, 'Notte', 'Turno notturno', '20:00:00', '06:00:00');

INSERT INTO user_shifts (shift_id, user_id, work_date, status)
VALUES
  (1, 3, CURDATE(), 'assigned'),
  (2, 3, DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'assigned');
