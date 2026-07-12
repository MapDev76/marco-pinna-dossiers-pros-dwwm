-- Roll back indexes added for query performance.
ALTER TABLE user_shifts
  DROP INDEX idx_user_shifts_work_date,
  DROP INDEX idx_user_shifts_user_work_date;

ALTER TABLE attendances
  DROP INDEX idx_attendances_work_date,
  DROP INDEX idx_attendances_user_work_date;
