USE arcadia_smart_city;

-- Add user association to conference bookings so we can track who booked
ALTER TABLE conf_bookings
  ADD COLUMN user_id INT NULL AFTER id,
  ADD INDEX idx_conf_user (user_id);

-- Add foreign key if users table exists
ALTER TABLE conf_bookings
  ADD CONSTRAINT fk_conf_bookings_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;