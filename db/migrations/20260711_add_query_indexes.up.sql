-- Add indexes for frequently queried date/user filters.
ALTER TABLE user_shifts
  ADD INDEX idx_user_shifts_work_date (work_date),
  ADD INDEX idx_user_shifts_user_work_date (user_id, work_date);

ALTER TABLE attendances
  ADD INDEX idx_attendances_work_date (work_date),
  ADD INDEX idx_attendances_user_work_date (user_id, work_date);
