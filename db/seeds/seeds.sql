-- Seed data for StaffEase Pro.
-- Load after schema.sql.

INSERT INTO companies (name, type, address, city, zip_code, phone, email, signature_ip)
VALUES ('Test Company', 'hotel', 'Example St 123', 'Rome', '00100', '0612345678', 'info@testcompany.com', NULL);

INSERT INTO departments (company_id, name, description)
VALUES (1, 'Reception', 'Reception department');

INSERT INTO users (department_id, first_name, last_name, email, phone, password, role, status)
VALUES
  (1, 'Marco', 'Pinna', 'admin@testcompany.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin', 'active'),
  (1, 'Giovanni', 'Rossi', 'admin2@testcompany.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active'),
  (1, 'Luca', 'Bianchi', 'employee@testcompany.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee', 'active');

INSERT INTO shifts (department_id, name, description, start_time, end_time)
VALUES
  (1, 'Morning', 'Morning shift', '08:00:00', '14:00:00'),
  (1, 'Afternoon', 'Afternoon shift', '14:00:00', '20:00:00'),
  (1, 'Night', 'Night shift', '20:00:00', '06:00:00');

INSERT INTO user_shifts (shift_id, user_id, work_date, status)
VALUES
  (1, 3, CURDATE(), 'assigned'),
  (2, 3, DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'assigned');
