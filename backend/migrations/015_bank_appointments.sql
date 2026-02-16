USE arcadia_smart_city;

-- Bank appointments table: users can book appointments at the bank
CREATE TABLE IF NOT EXISTS bank_appointments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  account_id INT DEFAULT NULL,
  starts_at DATETIME NOT NULL,
  ends_at DATETIME DEFAULT NULL,
  type VARCHAR(50) DEFAULT 'general', -- e.g., account_opening, inquiry, loan
  status ENUM('requested','confirmed','completed','cancelled') DEFAULT 'requested',
  notes TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (account_id) REFERENCES bank_accounts(id)
);
