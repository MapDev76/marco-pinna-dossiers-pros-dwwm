-- Migration: add icon and color columns to departments and shifts
ALTER TABLE departments ADD COLUMN IF NOT EXISTS icon VARCHAR(32) NULL AFTER name;
ALTER TABLE departments ADD COLUMN IF NOT EXISTS color VARCHAR(16) NULL AFTER icon;

ALTER TABLE shifts ADD COLUMN IF NOT EXISTS icon VARCHAR(32) NULL AFTER name;
ALTER TABLE shifts ADD COLUMN IF NOT EXISTS color VARCHAR(16) NULL AFTER icon;

-- End migration
