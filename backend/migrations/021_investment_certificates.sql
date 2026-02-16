USE arcadia_smart_city;

-- Investment Certificates Table
CREATE TABLE IF NOT EXISTS investment_certificates (
  id INT AUTO_INCREMENT PRIMARY KEY,
  account_id INT NOT NULL,
  user_id INT NOT NULL,
  certificate_no VARCHAR(50) UNIQUE NOT NULL,
  type ENUM('annual_lump', 'annual_monthly') NOT NULL,
  principal_amount DECIMAL(14,2) NOT NULL,
  interest_rate DECIMAL(5,2) NOT NULL, -- 27% or 24%
  monthly_interest_rate DECIMAL(5,2) DEFAULT NULL, -- 2% for monthly type
  start_date DATE NOT NULL,
  maturity_date DATE NOT NULL,
  status ENUM('active', 'matured', 'cancelled', 'completed') NOT NULL DEFAULT 'active',
  cancelled_at DATE DEFAULT NULL,
  cancellation_penalty DECIMAL(14,2) DEFAULT 0.00,
  total_interest_paid DECIMAL(14,2) DEFAULT 0.00,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (account_id) REFERENCES bank_accounts(id),
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Monthly Interest Payments Table
CREATE TABLE IF NOT EXISTS certificate_monthly_payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  certificate_id INT NOT NULL,
  payment_month DATE NOT NULL, -- First day of the month
  amount DECIMAL(14,2) NOT NULL,
  transaction_id BIGINT DEFAULT NULL,
  paid_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (certificate_id) REFERENCES investment_certificates(id),
  FOREIGN KEY (transaction_id) REFERENCES bank_transactions(id),
  UNIQUE KEY uk_cert_month (certificate_id, payment_month)
);

-- Indexes for performance
ALTER TABLE investment_certificates ADD INDEX idx_user_status (user_id, status);
ALTER TABLE investment_certificates ADD INDEX idx_maturity_date (maturity_date, status);
ALTER TABLE certificate_monthly_payments ADD INDEX idx_certificate (certificate_id);

