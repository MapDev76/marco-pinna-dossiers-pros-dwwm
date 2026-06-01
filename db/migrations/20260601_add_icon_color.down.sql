-- Rollback migration: drop icon and color columns from departments and shifts
-- Note: dropping columns will permanently remove stored icons/colors.

ALTER TABLE shifts DROP COLUMN IF EXISTS color;
ALTER TABLE shifts DROP COLUMN IF EXISTS icon;

ALTER TABLE departments DROP COLUMN IF EXISTS color;
ALTER TABLE departments DROP COLUMN IF EXISTS icon;

-- End rollback
