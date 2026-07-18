CREATE TABLE IF NOT EXISTS user_month_hours_plans (
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
