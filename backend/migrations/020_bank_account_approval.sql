USE arcadia_smart_city;

-- Add status and approval fields to bank_accounts
ALTER TABLE bank_accounts 
ADD COLUMN status ENUM('pending','approved','frozen','rejected') NOT NULL DEFAULT 'pending' AFTER type,
ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER balance,
ADD COLUMN approved_at TIMESTAMP NULL DEFAULT NULL AFTER created_at,
ADD COLUMN approved_by INT NULL DEFAULT NULL AFTER approved_at,
ADD COLUMN rejected_at TIMESTAMP NULL DEFAULT NULL AFTER approved_by,
ADD COLUMN rejected_by INT NULL DEFAULT NULL AFTER rejected_at,
ADD COLUMN frozen_at TIMESTAMP NULL DEFAULT NULL AFTER rejected_by,
ADD COLUMN frozen_by INT NULL DEFAULT NULL AFTER frozen_at,
ADD COLUMN notes TEXT NULL DEFAULT NULL AFTER frozen_by,
ADD FOREIGN KEY (approved_by) REFERENCES users(id),
ADD FOREIGN KEY (rejected_by) REFERENCES users(id),
ADD FOREIGN KEY (frozen_by) REFERENCES users(id);

-- Update existing accounts to approved status
UPDATE bank_accounts SET status = 'approved' WHERE status = 'pending' AND created_at < NOW();

